<?php
// app/controllers/ViewController.php
namespace controllers;

use models\Account;
use models\Transaction;
use models\RecurringRule;
use models\User;
use models\SavingsGoal;

class ViewController {

    protected function getUserId() {
        return \Flight::get('user_data')['user_id'] ?? null;
    }

    /**
     * Renders a view for an authenticated user, including the standard header and footer.
     * -- FIX: Added the missing header and footer rendering calls. --
     */
    public function render($viewName, $data = []) {
        $userModel = new User();
        $user_id = $this->getUserId();
        if ($user_id) {
            $data['user'] = $userModel->findById($user_id);
        }
        $data['flash_messages'] = \Flight::getFlashes();
        
        \Flight::render('layout/header', $data); // Render the main application header
        \Flight::render($viewName, $data);      // Render the specific view's content
        \Flight::render('layout/footer', $data); // Render the main application footer
    }
    
    /**
     * Renders a view for a public (not logged-in) user.
     */
    public function renderPublic($viewName, $data = []) {
        $data['flash_messages'] = \Flight::getFlashes();
        \Flight::render('layout/public_header', []);
        \Flight::render($viewName, $data);
        \Flight::render('layout/public_footer', []);
    }

    /**
     * Displays the main dashboard page.
     */
    public function dashboard() {
        $user_id = $this->getUserId();
        if (!$user_id) { \Flight::redirect('/login'); return; }

        $accountModel = new Account();
        $transactionModel = new Transaction();
        $ruleModel = new RecurringRule();
        $userModel = new User();
        $goalModel = new SavingsGoal();

        $user = $userModel->findById($user_id);
        if (isset($user['has_completed_setup']) && !$user['has_completed_setup']) {
            \Flight::redirect('/setup/step1');
            return;
        }

        // --- Upcoming Events Logic ---
        $upcomingEvents = [];
        $realTransactions = $transactionModel->findUpcomingByUserId($user_id);
        foreach ($realTransactions as $tx) {
            $upcomingEvents[$tx['transaction_date'] . '.' . $tx['transaction_id']] = $tx;
        }

        $rules = $ruleModel->findAllByUserId($user_id);
        foreach ($rules as $rule) {
            $nextDueDate = RecurringRule::calculateNextDueDate($rule);
            if ($nextDueDate) {
                $event = [
                    'transaction_id' => 'rule_' . $rule['rule_id'],
                    'transaction_date' => $nextDueDate->format('Y-m-d'),
                    'description' => $rule['description'],
                    'amount' => $rule['amount'],
                    'type' => $rule['type']
                ];
                $upcomingEvents[$event['transaction_date'] . '.' . $event['transaction_id']] = $event;
            }
        }
        uasort($upcomingEvents, function($a, $b) { return strtotime($a['transaction_date']) <=> strtotime($b['transaction_date']); });
        $upcomingTransactionsForDisplay = array_slice($upcomingEvents, 0, 5);
        
        // --- Safe to Spend Logic ---
        $totalBalance = $accountModel->getCurrentTotalBalanceByUserId($user_id);
        $nextIncomeEvent = $transactionModel->findNextIncomeEvent($user_id);
        
        $safeToSpendMessage = "";
        $nextIncomeMessage = "No upcoming income is scheduled.";
        $dailyAllowanceMessage = null;
        $savingsMessage = null;
        $finalSafeToSpend = $totalBalance;

        if ($nextIncomeEvent) {
            $nextIncomeDate = new \DateTime($nextIncomeEvent['date']);
            $nextIncomeAmount = (float)$nextIncomeEvent['amount'];
            $today = new \DateTime('today');
            $totalExpensesUntilNextIncome = $transactionModel->getExpensesTotalBetweenDates($user_id, $today->format('Y-m-d'), $nextIncomeDate->format('Y-m-d'));
            $rawSafeToSpend = $totalBalance - $totalExpensesUntilNextIncome;
            $finalSafeToSpend = $rawSafeToSpend;
            
            $savingsPercentage = (float)($user['savings_percentage'] ?? 0);
            if ($savingsPercentage > 0 && $rawSafeToSpend > 0) {
                $amountToSave = ($rawSafeToSpend / 100) * $savingsPercentage;
                $finalSafeToSpend -= $amountToSave;
                $savingsMessage = sprintf("We're setting aside <strong>£%s</strong> (%.1f%%) for savings.", number_format($amountToSave, 2), $savingsPercentage);
            }
            
            $daysUntil = $today->diff($nextIncomeDate)->days;
            $friendlyTimeUntil = $this->makeFriendlyDateDiff($daysUntil);
            
            if ($finalSafeToSpend < 0) {
                $safeToSpendNegative = abs($finalSafeToSpend);
                $safeToSpendMessage = sprintf("<p class='text-3xl font-bold mt-2'>You need</p> <p class='text-5xl font-bold mt-2 text-red-500'>£%s</p>", number_format($safeToSpendNegative, 2));
                $nextIncomeMessage = sprintf("to cover expenses for the next %s.", $friendlyTimeUntil);
            } else {
                $safeToSpendMessage = sprintf("<p class='text-3xl font-bold mt-2'>You can spend</p> <p class='text-5xl font-bold mt-2 text-green-500'>£%s</p>", number_format($finalSafeToSpend, 2));
                $nextIncomeMessage = sprintf("until your next income of £%s on %s (%s).", number_format($nextIncomeAmount, 2), $nextIncomeDate->format('F jS'), $friendlyTimeUntil);
                if ($daysUntil > 0) {
                    $dailyAllowance = $finalSafeToSpend / $daysUntil;
                    $dailyAllowanceMessage = sprintf("That's about £%s per day.", number_format($dailyAllowance, 2));
                }
            }
        } else {
            $safeToSpendMessage = sprintf("<p class='text-3xl font-bold mt-2'>Your current balance is</p> <p class='text-5xl font-bold mt-2 text-white'>£%s</p>", number_format($totalBalance, 2));
        }

        // --- Final data for the view ---
        $viewData = [
            'safeToSpendMessage' => $safeToSpendMessage,
            'nextIncomeMessage' => $nextIncomeMessage,
            'dailyAllowanceMessage' => $dailyAllowanceMessage,
            'savingsMessage' => $savingsMessage,
            'accounts' => $accountModel->findAllByUserIdWithCurrentBalances($user_id),
            'upcomingTransactions' => $upcomingTransactionsForDisplay,
            'expenseChartData' => $transactionModel->getDailyExpensesForChart($user_id),
            'topSavingsGoals' => ($user['subscription_tier'] === 'premium') ? $goalModel->findTopGoalsByUserId($user_id, 3) : [],
            'safeToSpend' => $finalSafeToSpend
        ];
        
        $this->render('dashboard/index', $viewData);
    }

    private function makeFriendlyDateDiff($days) {
        if ($days < 1) return "today";
        if ($days == 1) return "tomorrow";
        return "in {$days} days";
    }

    public function addTransactionForm() {
        $this->render('add_transaction/index', ['accounts' => (new Account())->findAllByUserId($this->getUserId())]);
    }
    
    public function addAccountForm() { 
        $this->render('add_account/index'); 
    }

    public function showRecurringRules() {
        $user_id = $this->getUserId();
        $ruleModel = new RecurringRule();
        $items_per_page = 25;
        $total_items = $ruleModel->countAllByUserId($user_id);
        $total_pages = ceil($total_items / $items_per_page);
        $current_page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
        $offset = ($current_page - 1) * $items_per_page;
        $this->render('recurring/index', [
            'rules' => $ruleModel->findAllByUserIdWithPagination($user_id, $offset, $items_per_page),
            'currentPage' => $current_page,
            'totalPages' => $total_pages
        ]);
    }

    public function showLandingPage() {
        $this->renderPublic('home/index', [
            'title' => 'VisuBudget | Your Future-Proof Wallet',
            'description' => 'A neurodivergent-friendly budgeting app that shows you what\'s truly safe to spend.'
        ]);
    }
}
