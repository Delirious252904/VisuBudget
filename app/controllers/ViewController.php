<?php
// app/controllers/ViewController.php
namespace controllers;

use models\Account;
use models\Transaction;
use models\RecurringRule;
use models\User;
use models\SavingsGoal;
use DateTime;

class ViewController {

    protected function getUserId() {
        return \Flight::get('user_data')['user_id'] ?? null;
    }

    /**
     * -- FIXED & SIMPLIFIED --
     * Renders a view with its header and footer. This method is now only responsible
     * for the rendering process and does not fetch any data itself.
     */
    public function render($viewName, $data = []) {
        // The calling method (e.g., dashboard) is now responsible for providing the complete $user object.
        // We just ensure flash messages are available.
        $data['flash_messages'] = \Flight::getFlashes();
        
        \Flight::render('layout/header', $data);
        \Flight::render($viewName, $data);
        \Flight::render('layout/footer', $data);
    }
    
    public function renderPublic($viewName, $data = []) {
        $data['flash_messages'] = \Flight::getFlashes();
        \Flight::render('layout/public_header', []);
        \Flight::render($viewName, $data);
        \Flight::render('layout/public_footer', []);
    }

    public function dashboard() {
        $user_id = $this->getUserId();
        if (!$user_id) { \Flight::redirect('/login'); return; }

        $accountModel = new Account();
        $transactionModel = new Transaction();
        $ruleModel = new RecurringRule();
        $userModel = new User();
        $goalModel = new SavingsGoal();

        // Fetch the user data ONCE using the reliable user ID. This is the single source of truth.
        $user = $userModel->findById($user_id);

        if (!$user) {
            \Flight::redirect('/logout');
            return;
        }

       if ($user['has_completed_setup'] == 0) {
            \Flight::redirect('/setup/step1');
            return;
        }

        // --- Upcoming Events Logic ---
        $upcomingEvents = [];
        $look_ahead_date = new DateTime('+3 months');

        $rules = $ruleModel->findAllActiveByUserId($user_id);
        foreach ($rules as $rule) {
            $nextDate = RecurringRule::calculateNextDueDate($rule);
            if ($nextDate && $nextDate <= $look_ahead_date) {
                $unique_key = $nextDate->format('Y-m-d') . '.rule.' . $rule['rule_id'];
                $upcomingEvents[$unique_key] = [
                    'transaction_id' => 'rule_' . $rule['rule_id'],
                    'transaction_date' => $nextDate->format('Y-m-d'),
                    'description' => $rule['description'],
                    'amount' => $rule['amount'],
                    'type' => $rule['type']
                ];
            }
        }
        
        $oneOffTransactions = $transactionModel->findUpcomingByUserId($user_id, 10);
        foreach ($oneOffTransactions as $tx) {
            $unique_key = $tx['transaction_date'] . '.tx.' . $tx['transaction_id'];
            $upcomingEvents[$unique_key] = $tx;
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
            $nextIncomeDate = new DateTime($nextIncomeEvent['next_date']);
            $nextIncomeAmount = (float)$nextIncomeEvent['amount'];
            $today = new DateTime('today');
            $totalExpensesUntilNextIncome = $transactionModel->getExpensesTotalBetweenDates($user_id, $today->format('Y-m-d'), $nextIncomeDate->format('Y-m-d'));
            
            $rawSafeToSpend = $totalBalance - $totalExpensesUntilNextIncome;
            $finalSafeToSpend = $rawSafeToSpend;
            
            // This 'if' condition now has the correct $user object and will work as expected.
            $savingsPercentage = (float)($user['savings_percentage'] ?? 0);
            if ($savingsPercentage > 0 && $rawSafeToSpend > 0) {
                $amountToSave = ($rawSafeToSpend / 100) * $savingsPercentage;
                $finalSafeToSpend -= $amountToSave;
                $savingsMessage = sprintf("We're setting aside <strong>£%01.2f</strong> (%d%% of £%01.2f) for your savings goal!", number_format($amountToSave, 2), $savingsPercentage, number_format($rawSafeToSpend, 2));
            }
            
            $daysUntil = $today->diff($nextIncomeDate)->days;
            $friendlyTimeUntil = $this->makeFriendlyDateDiff($daysUntil);
            
            if ($finalSafeToSpend < 0) {
                $safeToSpendNegative = abs($finalSafeToSpend);
                $safeToSpendMessage = sprintf("<p class='text-3xl font-bold mt-2'>You need</p> <p class='text-5xl font-bold mt-2 text-red-500'>£%01.2f</p>", number_format($safeToSpendNegative, 2));
                $nextIncomeMessage = sprintf("to cover expenses for the next %s.", $friendlyTimeUntil);
            } else {
                $safeToSpendMessage = sprintf("<p class='text-3xl font-bold mt-2'>You can spend</p> <p class='text-5xl font-bold mt-2 text-green-500'>£%01.2f</p>", number_format($finalSafeToSpend, 2));
                $nextIncomeMessage = sprintf("until your next income of £%s on %s (%s).", number_format($nextIncomeAmount, 2), $nextIncomeDate->format('F jS'), $friendlyTimeUntil);
                if ($daysUntil > 1) {
                    $dailyAllowance = $finalSafeToSpend / $daysUntil;
                    $dailyAllowanceMessage = sprintf("That's about £%01.2f per day.", number_format($dailyAllowance, 2));
                }
            }
        } else {
            $safeToSpendMessage = sprintf("<p class='text-3xl font-bold mt-2'>Your current balance is</p> <p class='text-5xl font-bold mt-2 text-white'>£%s</p>", number_format($totalBalance, 2));
        }

        $viewData = [
            'user' => $user, // Pass the complete user object to the view
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
        $data = ['accounts' => (new Account())->findAllByUserId($this->getUserId()), 'user' => (new User())->findById($this->getUserId())];
        $this->render('add_transaction/index', $data);
    }
    
    public function addAccountForm() { 
        $this->render('add_account/index', ['user' => (new User())->findById($this->getUserId())]); 
    }

    public function showRecurringRules() {
        $user_id = $this->getUserId();
        $this->render('recurring/index', [
            'user' => (new User())->findById($user_id),
            'rules' => (new RecurringRule())->findAllByUserIdWithPagination($user_id, 0, 100),
            'currentPage' => 1,
            'totalPages' => 1
        ]);
    }

    public function showLandingPage() {
        $this->renderPublic('home/index', [
            'title' => 'VisuBudget | Your Future-Proof Wallet',
            'description' => 'A neurodivergent-friendly budgeting app that shows you what\'s truly safe to spend.'
        ]);
    }
}
