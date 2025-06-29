<?php
// app/controllers/SavingsGoalController.php
namespace controllers;

use models\SavingsGoal;
use models\Transaction;
use models\Account;

class SavingsGoalController extends ViewController {

    /**
     * Shows a list of all savings goals for the user.
     */
    public function showList() {
        $user_id = $this->getUserId();
        $goalModel = new SavingsGoal();
        $goals = $goalModel->findAllByUserId($user_id);
        
        $this->render('savings/index', ['goals' => $goals]);
    }

    /**
     * Shows the form to create a new savings goal.
     */
    public function showCreateForm() {
        $this->render('savings/create');
    }

    /**
     * Processes the submission of the 'create goal' form.
     */
    public function handleCreate() {
        $user_id = $this->getUserId();
        $data = \Flight::request()->data->getData();
        
        // Basic validation
        if (empty($data['goal_name']) || empty($data['target_amount'])) {
            \Flight::redirect('/savings/add?error=1');
            return;
        }

        $goalModel = new SavingsGoal();
        $goalModel->create($user_id, $data);
        
        \Flight::redirect('/savings');
    }

    /**
     * Shows the form to edit an existing savings goal.
     */
    public function showEditForm($goal_id) {
        $user_id = $this->getUserId();
        $goalModel = new SavingsGoal();
        $goal = $goalModel->findById($goal_id, $user_id);

        if (!$goal) {
            $this->render('auth/message', ['title' => 'Error', 'message' => 'Savings goal not found.']);
            return;
        }

        $this->render('savings/edit', ['goal' => $goal]);
    }

    /**
     * Processes the submission of the 'edit goal' form.
     */
    public function handleUpdate($goal_id) {
        $user_id = $this->getUserId();
        $data = \Flight::request()->data->getData();
        $goalModel = new SavingsGoal();

        // Security check to ensure user owns this goal
        if (!$goalModel->findById($goal_id, $user_id)) {
            \Flight::redirect('/savings');
            return;
        }

        $goalModel->update($goal_id, $data);
        \Flight::redirect('/savings');
    }

    /**
     * Deletes a savings goal.
     */
    public function handleDelete($goal_id) {
        $user_id = $this->getUserId();
        $goalModel = new SavingsGoal();
        $goalModel->delete($goal_id, $user_id);
        \Flight::redirect('/savings');
    }

    /**
     * Handles a user contributing from their surplus to a specific goal.
     */
    public function handleContribution($goal_id) {
        $user_id = $this->getUserId();
        $data = \Flight::request()->data->getData();
        $amount = (float)($data['amount'] ?? 0);
        $from_account_id = $data['from_account_id'] ?? null;

        if ($amount <= 0 || !$from_account_id) {
            \Flight::redirect('/dashboard?error=contribution_failed');
            return;
        }

        $db = \Flight::db();
        try {
            $db->beginTransaction();

            // 1. Get the goal name to use in the transaction description.
            $goalModel = new SavingsGoal();
            $goal = $goalModel->findById($goal_id, $user_id);
            if (!$goal) { throw new \Exception("Savings goal not found."); }
            
            // 2. Create a new expense transaction for the contribution.
            $transactionModel = new Transaction();
            $transactionData = [
                'type' => 'expense',
                'description' => 'Contribution to: ' . $goal['goal_name'],
                'amount' => $amount,
                'transaction_date' => date('Y-m-d'),
                'from_account_id' => $from_account_id
            ];
            $transactionModel->create($user_id, $transactionData);

            // 3. Add the amount to the savings goal's current total.
            $goalModel->addAmountToGoal($goal_id, $amount);
            
            // 4. Adjust the balance of the account the contribution came from.
            $accountModel = new Account();
            $accountModel->adjustBalance($from_account_id, -$amount);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Contribution failed: " . $e->getMessage());
            \Flight::redirect('/dashboard?error=contribution_failed');
            return;
        }

        \Flight::redirect('/dashboard');
    }
}
