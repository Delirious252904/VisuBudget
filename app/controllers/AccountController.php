<?php
// app/controllers/AccountController.php
namespace controllers;

use models\Account;
use models\Transaction;
use models\RecurringRule;

// It now extends ViewController so it can render pages and get the user ID
class AccountController extends ViewController {

    /**
     * Shows a list of all accounts for the user.
     */
    public function showList() {
        $accountModel = new Account();
        $ruleModel = new RecurringRule(); // Instantiate the model
        
        // Get all accounts with their current balances
        $accounts = $accountModel->findAllByUserIdWithCurrentBalances($user_id);
        
        // --- NEW: Loop through accounts to get their recurring totals ---
        foreach ($accounts as $key => $account) {
            $totals = $ruleModel->getMonthlyTotalsForAccount($account['account_id']);
            $accounts[$key]['recurring_income'] = $totals['income'];
            $accounts[$key]['recurring_expenses'] = $totals['expenses'];
        }

        \Flight::render('accounts/index', [
            'accounts' => $accounts
        ]);
    }

    /**
     * Shows the form to edit a specific account.
     */
    public function showEditForm($account_id) {
        $user_id = $this->getUserId();
        $accountModel = new Account();
        $account = $accountModel->findById($account_id, $user_id);

        if (!$account) {
            $this->render('auth/message', ['title' => 'Error', 'message' => 'Account not found.']);
            return;
        }
        $this->render('accounts/edit', ['account' => $account]);
    }

    /**
     * Processes the submission of the account update form.
     */
    public function update($account_id) {
        $user_id = $this->getUserId();
        $data = \Flight::request()->data->getData();
        $accountModel = new Account();

        // Security check: ensure user owns this account before trying to update it.
        if (!$accountModel->findById($account_id, $user_id)) {
            \Flight::redirect('/accounts');
            return;
        }
        
        $accountModel->update($account_id, $data);
        \Flight::redirect('/accounts');
    }

    /**
     * Deletes an account AND all of its associated transactions safely.
     */
    public function delete($account_id) {
        $user_id = $this->getUserId();
        $db = \Flight::db();

        try {
            // Start a protective "bubble" around our database operations
            $db->beginTransaction();

            // 1. Delete all transactions associated with this account first.
            $transactionModel = new Transaction();
            $transactionModel->deleteByAccountId($account_id, $user_id);
            
            // 2. Then, delete the account itself.
            $accountModel = new Account();
            $accountModel->delete($account_id, $user_id);

            // 3. If both steps succeeded, make the changes permanent.
            $db->commit();

        } catch (\Exception $e) {
            // If anything went wrong, undo everything to protect data integrity.
            $db->rollBack();
            error_log("Failed to delete account and its transactions: " . $e->getMessage());
        }
    }

    /**
     * Shows the form to reset an account's balance.
     */
    public function showResetForm($account_id) {
        $user_id = $this->getUserId();
        $accountModel = new Account();
        $account = $accountModel->findById($account_id, $user_id);

        if (!$account) {
            $this->render('auth/message', ['title' => 'Error', 'message' => 'Account not found.']);
            return;
        }
        $this->render('accounts/reset', ['account' => $account]);
    }

    /**
     * Processes the submission of the balance reset form.
     */
    public function handleResetBalance($account_id) {
        $user_id = $this->getUserId();
        $data = \Flight::request()->data->getData();
        $new_balance = $data['current_balance'];

        $accountModel = new Account();
        // Security check
        if (!$accountModel->findById($account_id, $user_id)) {
            \Flight::redirect('/accounts');
            return;
        }

        $accountModel->setBalance($account_id, $new_balance);
        \Flight::redirect('/accounts');
    }
    
    /**
     * The existing method for adding a new account.
     * This remains unchanged.
     */
    public function add() {
        $user_id = $this->getUserId(); 
        $request = \Flight::request();
        $data = $request->data->getData();

        if (empty($data['account_name']) || !isset($data['current_balance'])) {
            \Flight::redirect('/account/add');
            return;
        }

        $accountModel = new Account();
        $success = $accountModel->create($user_id, $data);

        if ($success) {
            \Flight::redirect('/');
        } else {
            echo "There was an error saving the account.";
        }
    }
}
