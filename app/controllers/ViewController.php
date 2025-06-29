<?php
// app/controllers/ViewController.php
namespace controllers;

use models\Account;
use models\Transaction;
use models\RecurringRule;
use models\User;
use models\SavingsGoal;

class ViewController {

    /**
     * Helper method to get the current user's ID.
     * @return int|null
     */
    protected function getUserId() {
        $userData = \Flight::get('user_data');
        return $userData['user_id'] ?? null;
    }

    /**
     * Renders a view file.
     */
    public function render($viewName, $data = []) {
        // Add user data to every view so we can use it in the header, etc.
         // Get basic user data (id, email) from the JWT
        $jwt_user_data = \Flight::get('user_data');
        
        if ($jwt_user_data) {
            // Use the email to fetch the full user profile from the database
            $userModel = new User();
            $full_user_data = $userModel->findByEmail($jwt_user_data['email']);
            // Add the complete user object to the data passed to the view
            $data['user'] = $full_user_data;
        } else {
            $data['user'] = null;
        }
        
        extract($data);
        $viewPath = \Flight::get('flight.views.path');

        
        extract($data);
        $viewPath = \Flight::get('flight.views.path');
        $finalViewFile = null;
        $indexPath = $viewPath . '/' . $viewName . '/index.php';
        $filePath = $viewPath . '/' . $viewName . '.php';

        if (file_exists($indexPath)) {
            $finalViewFile = $indexPath;
        } elseif (file_exists($filePath)) {
            $finalViewFile = $filePath;
        }

        if ($finalViewFile) {
            require $viewPath . '/layout/header.php';
            require $finalViewFile;
            require $viewPath . '/layout/footer.php';
        } else {
            echo "Error: View '" . htmlspecialchars($viewName) . "' not found.";
        }
    }

    public function renderPublic($viewName, $data = []) {
        // This is for public views that do not require the standard header/footer.
        $jwt_user_data['user'] = null;

        extract($data);
        $viewPath = \Flight::get('flight.views.path');
        $finalViewFile = null;
        $indexPath = $viewPath . '/' . $viewName . '/index.php';
        $filePath = $viewPath . '/' . $viewName . '.php';

        if (file_exists($indexPath)) {
            $finalViewFile = $indexPath;
        } elseif (file_exists($filePath)) {
            $finalViewFile = $filePath;
        }

        if ($finalViewFile) {
            require $viewPath . '/layout/public_header.php';
            require $finalViewFile;
            require $viewPath . '/layout/public_footer.php';
        } else {
            echo "Error: Public view '" . htmlspecialchars($viewName) . "' not found.";
        }
    }

    /**
     * Displays the main dashboard page.
     * **UPDATED** to be much smarter about what is "Upcoming".
     */
    public function dashboard() {
        $user_id = $this->getUserId();
        if (!$user_id) { \Flight::redirect('/login'); return; }

        $accountModel = new Account();
        $transactionModel = new Transaction();
        $ruleModel = new RecurringRule();
        $userModel = new User();
        $goalModel = new SavingsGoal(); 

        // --- Data Fetching ---
        $accounts = $accountModel->findAllByUserId($user_id);
        $totalBalance = $accountModel->getTotalBalanceByUserId($user_id);
        $nextIncomeEvent = $transactionModel->findNextIncomeEvent($user_id);
        $user = $userModel->findByEmail(\Flight::get('user_data')['email']);

        // Before we do anything else, check if the user has finished the setup.
        if (isset($user['has_completed_setup']) && !$user['has_completed_setup']) {
            // If they haven't, send them to the first step of the wizard.
            \Flight::redirect('/setup/step1');
            return; // Stop running the rest of the dashboard code.
        }
        
        // --- Core Logic with Corrected Savings Calculation ---
        $upcomingExpenses = 0;
        $nextIncomeMessage = "No upcoming income has been scheduled.";
        $dailyAllowanceMessage = null;
        $savingsMessage = null;

        // 1. Calculate the raw, initial "Safe to Spend" amount.
        $initialSafeToSpend = $totalBalance - $upcomingExpenses;

        if ($nextIncomeEvent) {
            $nextIncomeDate = $nextIncomeEvent['date'];
            $nextIncomeAmount = $nextIncomeEvent['amount'];
            $upcomingExpenses = $transactionModel->getExpensesTotalBetweenDates($user_id, date('Y-m-d'), $nextIncomeDate);
            $initialSafeToSpend = $totalBalance - $upcomingExpenses; // Recalculate with expenses

            $today = new \DateTime('today');
            $incomeDay = new \DateTime($nextIncomeDate);
            $daysUntil = $today->diff($incomeDay)->days;
            $friendlyTimeUntil = $this->makeFriendlyDateDiff($daysUntil);
            
            $nextIncomeMessage = sprintf(
                "Until your next income of £%s on %s (%s)",
                number_format($nextIncomeAmount, 2),
                $incomeDay->format('F jS'),
                $friendlyTimeUntil
            );

            // Daily Allowance is now calculated from the INITIAL surplus.
            if ($daysUntil > 0 && $initialSafeToSpend > 0) {
                $dailyAllowance = $initialSafeToSpend / $daysUntil;
                $dailyAllowanceMessage = sprintf("You can spend £%s per day on average.", number_format($dailyAllowance, 2));
            } elseif ($initialSafeToSpend <= 0) {
                 $dailyAllowanceMessage = "You have nothing to spend.";
            }
        }
        
        // 2. Now, we apply the savings logic to determine the final display amount.
        $finalSafeToSpend = $initialSafeToSpend;
        $savingsPercentage = $user['savings_percentage'] ?? 0;
        
        if ($savingsPercentage > 0 && $initialSafeToSpend > 0) {
            $amountToSave = ($initialSafeToSpend / 100) * $savingsPercentage;
            $finalSafeToSpend = $initialSafeToSpend - $amountToSave;

            $savingsMessage = sprintf(
                "We're automatically setting aside <strong>£%s</strong> (%.1f%% of £%s) for your savings goal!",
                number_format($amountToSave, 2),
                $savingsPercentage,
                number_format($initialSafeToSpend, 2)
            );
        }

        // 3. Create the final "Safe to Spend" display message.
        if($finalSafeToSpend < 0) {
            $userNeeds = $upcomingExpenses - $totalBalance;
            $safeToSpendMessage = sprintf(
                "<p class='text-3xl font-bold mt-2' >You need</p> <p class='text-5xl font-bold mt-2 text-red-500'>£%s</p>",
                number_format($userNeeds, 2)
            );
        } else {
            $safeToSpendMessage = sprintf(
                "<p class='text-3xl font-bold mt-2'>You can spend</p> <p class='text-5xl font-bold mt-2 text-green-500'>£%s</p>",
                number_format($finalSafeToSpend, 2)
            );
        }

        // --- Fetch and Sort Savings Goals ---
        $topSavingsGoals = [];
        // Only fetch goals if the user is premium
        if (isset($user['subscription_tier']) && $user['subscription_tier'] === 'premium') {
            $allGoals = $goalModel->findAllByUserId($user_id);
            // Sort the goals by the current amount saved, in descending order
            usort($allGoals, function($a, $b) {
                return $b['current_amount'] <=> $a['current_amount'];
            });
            // Get just the top 3
            $topSavingsGoals = array_slice($allGoals, 0, 3);
        }

        // --- Upcoming Events Display Logic (remains the same) ---
        $upcomingEvents = [];
        $generatedTransactions = $transactionModel->findUpcomingByUserId($user_id, 10);
        foreach ($generatedTransactions as $tx) { $upcomingEvents[$tx['transaction_date'] . '_' . $tx['description']] = $tx; }
        $rules = $ruleModel->findAllByUserId($user_id);
        foreach ($rules as $rule) {
            $nextDueDate = RecurringRule::calculateNextDueDate($rule);
            if ($nextDueDate) {
                $event = ['transaction_date' => $nextDueDate->format('Y-m-d'), 'description' => $rule['description'], 'amount' => $rule['amount'], 'type' => $rule['type']];
                $upcomingEvents[$event['transaction_date'] . '_' . $event['description']] = $event;
            }
        }
        uasort($upcomingEvents, function($a, $b) { return strtotime($a['transaction_date']) - strtotime($b['transaction_date']); });
        $upcomingTransactionsForDisplay = array_slice($upcomingEvents, 0, 5);

        // Call the new method to get data specifically formatted for the chart.
        $expenseChartData = $transactionModel->getDailyExpensesForChart($user_id);
        
        // --- Pass all the data to the view ---
        $viewData = [
            'safeToSpendMessage' => $safeToSpendMessage,
            'safeToSpend' => $finalSafeToSpend,
            'savingsMessage' => $savingsMessage,
            'nextIncomeMessage' => $nextIncomeMessage,
            'dailyAllowanceMessage' => $dailyAllowanceMessage,
            'expenseChartData' => $expenseChartData,
            'accounts' => $accounts,
            'upcomingTransactions' => $upcomingTransactionsForDisplay,
            'topSavingsGoals' => $topSavingsGoals
        ];
        
        $this->render('dashboard/index', $viewData);
    }

    /**
     * Creates a friendly, human-readable time difference string.
     * @param int $days The number of days.
     * @return string The friendly string.
     */
    private function makeFriendlyDateDiff($days) {
        if ($days < 1) return "today";
        if ($days == 1) return "tomorrow";
        if ($days < 7) return "in $days days";
        if ($days < 14) return "in about a week";
        
        $weeks = floor($days / 7);
        $remainingDays = $days % 7;
        
        if ($remainingDays == 0) {
            return "in $weeks weeks";
        }
        
        return "in $weeks weeks and $remainingDays days";
    }
    
   

    /**
     * Displays the form to add a new transaction.
     */
    public function addTransactionForm() {
        $user_id = $this->getUserId(); // Get real user ID
        $accountModel = new Account();
        $accounts = $accountModel->findAllByUserId($user_id);
        $this->render('add_transaction/index', ['accounts' => $accounts]);
    }

    /**
     * Displays the form to add a new account.
     */
    public function addAccountForm() {
        $this->render('add_account/index');
    }

    /**
     * Displays the page for managing all recurring transaction rules.
     */
    public function showRecurringRules() {
        $user_id = $this->getUserId();
        if (!$user_id) {
            \Flight::redirect('/login');
            return;
        }
        
        $ruleModel = new RecurringRule();
        
        // --- Pagination Logic ---
        $items_per_page = 25;
        $total_items = $ruleModel->countAllByUserId($user_id);
        $total_pages = ceil($total_items / $items_per_page);
        
        // Get the current page from the URL, defaulting to page 1.
        $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($current_page < 1) { $current_page = 1; }
        if ($current_page > $total_pages && $total_pages > 0) { $current_page = $total_pages; }
        
        // Calculate the database offset.
        $offset = ($current_page - 1) * $items_per_page;

        // Fetch only the rules for the current page using the new model method.
        $rules = $ruleModel->findAllByUserIdWithPagination($user_id, $offset, $items_per_page);

        // Pass all the necessary data to the view.
        $viewData = [
            'rules' => $rules,
            'currentPage' => $current_page,
            'totalPages' => $total_pages
        ];

        $this->render('recurring/index', $viewData);
    }

    /**
     * Displays the public-facing landing page.
     * Note: This method does NOT call the standard render() because the landing page
     * has a unique layout without the standard header/footer navigation.
     */
    public function showLandingPage() {
        // We directly require the view file itself.
        $this->renderPublic('home', [
            'title' => 'VisuBudget | Your Future-Proof Wallet',
            'description' => 'Tired of financial stress? VisuBudget is a neurodivergent-friendly budgeting app that shows you what\'s truly safe to spend, helping you plan, save, and reduce anxiety.'
        ]);
    }
}