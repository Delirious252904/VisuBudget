<?php
// app/controllers/TransactionController.php
namespace controllers;

use models\Transaction;
use models\RecurringRule;
use models\Account;

class TransactionController extends ViewController {

    /**
     * Shows a paginated list of all transactions.
     */
    public function showList() {
        $user_id = $this->getUserId();
        $transactionModel = new Transaction();
        
        // --- Pagination Logic ---
        $items_per_page = 25;
        $total_items = $transactionModel->countAllByUserId($user_id);
        $total_pages = ceil($total_items / $items_per_page);
        
        // 1. Get the current page from the URL (e.g., /transactions?page=2), default to 1 if not set.
        $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        
        // 2. Basic validation to keep the page number in a valid range.
        if ($current_page < 1) { $current_page = 1; }
        if ($current_page > $total_pages && $total_pages > 0) { $current_page = $total_pages; }
        
        // 3. Calculate the 'offset' for the database query. For page 1, offset is 0. For page 2, offset is 25.
        $offset = ($current_page - 1) * $items_per_page;

        // 4. Fetch only the transactions for the current page.
        $transactions = $transactionModel->findAllByUserIdWithPagination($user_id, $offset, $items_per_page);
        
        // 5. Pass all the data, including pagination info, to the view.
        $viewData = [
            'transactions' => $transactions,
            'currentPage' => $current_page,
            'totalPages' => $total_pages
        ];

        $this->render('transactions/index', $viewData);
    }

    /**
     * Shows the form to edit a single transaction.
     */
    public function showEditForm($transaction_id) {
        $user_id = $this->getUserId();
        $transactionModel = new Transaction();
        $transaction = $transactionModel->findById($transaction_id, $user_id);

        if (!$transaction) {
            $this->render('auth/message', ['title' => 'Error', 'message' => 'Transaction not found.']);
            return;
        }

        $this->render('transactions/edit', ['transaction' => $transaction]);
    }

    /**
     * Processes the update to a single transaction.
     */
    public function update($transaction_id) {
        $user_id = $this->getUserId();
        $data = \Flight::request()->data->getData();
        $db = \Flight::db();
        
        $transactionModel = new Transaction();
        $accountModel = new Account();

        try {
            $db->beginTransaction();

            $original_tx = $transactionModel->findById($transaction_id, $user_id);
            if (!$original_tx) {
                throw new \Exception("Original transaction not found or user does not have permission.");
            }

            // Revert the financial impact of the ORIGINAL transaction.
            $old_amount = (float)$original_tx['amount'];
            if ($original_tx['from_account_id']) { $accountModel->adjustBalance($original_tx['from_account_id'], $old_amount); }
            if ($original_tx['to_account_id']) { $accountModel->adjustBalance($original_tx['to_account_id'], -$old_amount); }
            
            // Update the transaction record itself.
            $transactionModel->update($transaction_id, $data);

            // Apply the financial impact of the NEW, updated transaction.
            $new_amount = (float)$data['amount'];
            if ($original_tx['from_account_id']) { $accountModel->adjustBalance($original_tx['from_account_id'], -$new_amount); }
            if ($original_tx['to_account_id']) { $accountModel->adjustBalance($original_tx['to_account_id'], $new_amount); }
            
            $db->commit();
            \Flight::redirect('/transactions');

        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Transaction update failed: " . $e->getMessage());
            $this->render('auth/message', ['title' => 'Error', 'message' => 'Could not update the transaction.']);
        }
    }

    /**
     * Deletes a single transaction.
     */
    public function delete($transaction_id) {
        $user_id = $this->getUserId();
        $db = \Flight::db();

        $transactionModel = new Transaction();
        $accountModel = new Account();

        try {
            // Start a protective "bubble" around our database operations
            $db->beginTransaction();

            // 1. Get the original transaction to know how to reverse its effect.
            $transaction = $transactionModel->findById($transaction_id, $user_id);
            if (!$transaction) {
                throw new \Exception("Transaction not found or permission denied.");
            }
            
            // 2. Revert the financial impact.
            $amount = (float)$transaction['amount'];
            if ($transaction['type'] === 'expense' && $transaction['from_account_id']) {
                $accountModel->adjustBalance($transaction['from_account_id'], $amount); // Add the amount back
            } elseif ($transaction['type'] === 'income' && $transaction['to_account_id']) {
                $accountModel->adjustBalance($transaction['to_account_id'], -$amount); // Subtract the amount
            } elseif ($transaction['type'] === 'transfer') {
                if($transaction['from_account_id']) $accountModel->adjustBalance($transaction['from_account_id'], $amount); // Add back to source
                if($transaction['to_account_id']) $accountModel->adjustBalance($transaction['to_account_id'], -$amount); // Subtract from dest
            }
            
            // 3. Now that the balance is corrected, delete the transaction record itself.
            $transactionModel->delete($transaction_id);
            
            // 4. If everything succeeded, make the changes permanent.
            $db->commit();
            \Flight::redirect('/transactions');

        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Transaction deletion failed: " . $e->getMessage());
            $this->render('auth/message', ['title' => 'Error', 'message' => 'Could not delete the transaction.']);
        }
    }

    /**
     * Handles creating a new transaction.
     */
    public function add() {
        $user_id = $this->getUserId();
        $request = \Flight::request();
        $data = $request->data->getData();

        if (empty($data['description']) || empty($data['amount'])) { \Flight::redirect('/transaction/add?error=missing_data'); return; }
        if ($data['type'] === 'transfer') {
            if (empty($data['to_account_id']) || empty($data['from_account_id'])) { \Flight::redirect('/transaction/add?error=missing_accounts'); return; }
            if ($data['from_account_id'] === $data['to_account_id']) { \Flight::redirect('/transaction/add?error=same_account'); return; }
        }
        try {
            $dateObject = new \DateTime($data['transaction_date']);
            $data['transaction_date'] = $dateObject->format('Y-m-d');
        } catch (\Exception $e) { echo "Error: Invalid date format."; return; }

        $db = \Flight::db();
        $success = false;

        try {
            $db->beginTransaction();
            $transactionModel = new Transaction();
            if (isset($data['is_recurring']) && $data['is_recurring'] == '1') {
                $ruleModel = new RecurringRule();
                $newRuleId = $ruleModel->create($user_id, $data);
                if (!$newRuleId) { throw new \Exception("Failed to create the recurring rule record."); }
                $data['rule_id'] = $newRuleId;
            }
            $transactionCreated = $transactionModel->create($user_id, $data);
            if (!$transactionCreated) { throw new \Exception("Failed to create the transaction record."); }
            
            if ($data['type'] === 'transfer') {
                $accountModel = new Account();
                $amount = (float)$data['amount'];
                $accountModel->adjustBalance($data['from_account_id'], -$amount);
                $accountModel->adjustBalance($data['to_account_id'], $amount);
            } elseif ($data['transaction_date'] == date('Y-m-d')) {
                $accountModel = new Account();
                $amount = (float)$data['amount'];
                if ($data['type'] === 'expense') { $accountModel->adjustBalance($data['from_account_id'], -$amount); } 
                elseif ($data['type'] === 'income') { $accountModel->adjustBalance($data['from_account_id'], $amount); }
            }
            
            $db->commit();
            $success = true;
        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Transaction creation failed: " . $e->getMessage());
        }
        if ($success) {
            \Flight::redirect('/');
        } else {
            echo "There was a critical error saving the transaction.";
        }
    }
}
