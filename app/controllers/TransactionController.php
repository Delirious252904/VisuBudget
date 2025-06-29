<?php
// app/controllers/TransactionController.php
namespace controllers;

use models\Transaction;
use models\RecurringRule;
use models\Account;

class TransactionController extends ViewController {

    /**
     * Handles creating a new transaction.
     * CORRECTED: Now properly handles future-dated transactions and all transaction types.
     */
    public function add() {
        $user_id = $this->getUserId();
        $request = \Flight::request();
        $data = $request->data->getData();

        // --- Form Validation ---
        if (empty($data['description']) || empty($data['amount']) || empty($data['type'])) { \Flight::redirect('/transaction/add?error=missing_data'); return; }
        if ($data['type'] === 'transfer') {
            if (empty($data['to_account_id']) || empty($data['from_account_id'])) { \Flight::redirect('/transaction/add?error=missing_accounts'); return; }
            if ($data['from_account_id'] === $data['to_account_id']) { \Flight::redirect('/transaction/add?error=same_account'); return; }
        } elseif (empty($data['account_id'])) { // Expense or Income
             \Flight::redirect('/transaction/add?error=missing_account'); return;
        }
        
        try {
            $transactionDate = new \DateTime($data['transaction_date']);
            $data['transaction_date'] = $transactionDate->format('Y-m-d');
        } catch (\Exception $e) { \Flight::redirect('/transaction/add?error=invalid_date'); return; }

        $db = \Flight::db();
        
        try {
            $db->beginTransaction();
            
            $transactionModel = new Transaction();
            $accountModel = new Account();

            // Create the transaction record first.
            $transaction_id = $transactionModel->create($user_id, $data);
            if (!$transaction_id) {
                throw new \Exception("Failed to create the transaction record.");
            }

            // --- REVISED BALANCE LOGIC ---
            // Only adjust account balances if the transaction date is today or in the past.
            $today = new \DateTime('today');
            if ($transactionDate <= $today) {
                $amount = (float)$data['amount'];
                if ($data['type'] === 'income') {
                    $accountModel->adjustBalance($data['account_id'], $amount);
                } elseif ($data['type'] === 'expense') {
                    $accountModel->adjustBalance($data['account_id'], -$amount);
                } elseif ($data['type'] === 'transfer') {
                    $accountModel->adjustBalance($data['from_account_id'], -$amount);
                    $accountModel->adjustBalance($data['to_account_id'], $amount);
                }
            }
            
            $db->commit();
            \Flight::flash('Transaction added successfully!', 'success');
            \Flight::redirect('/dashboard');

        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Transaction creation failed: " . $e->getMessage());
            \Flight::flash('There was a critical error saving the transaction.', 'danger');
            \Flight::redirect('/transaction/add');
        }
    }

    /**
     * Processes the update to a single transaction.
     * CORRECTED: Now properly reverts and applies balance changes based on dates.
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
                throw new \Exception("Original transaction not found or permission denied.");
            }

            $today = new \DateTime('today');
            $original_tx_date = new \DateTime($original_tx['transaction_date']);
            $new_tx_date = new \DateTime($data['transaction_date']);

            // --- REVISED BALANCE LOGIC ---
            // 1. Revert the financial impact of the ORIGINAL transaction, but only if it was already applied.
            if ($original_tx_date <= $today) {
                $accountModel->revertTransaction($original_tx);
            }
            
            // 2. Update the transaction record itself in the database.
            $transactionModel->update($transaction_id, $data);

            // 3. Apply the financial impact of the NEW transaction, but only if its date has passed.
            if ($new_tx_date <= $today) {
                $accountModel->applyTransaction($data);
            }
            
            $db->commit();
            \Flight::flash('Transaction updated successfully!', 'success');
            \Flight::redirect('/transactions');

        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Transaction update failed: " . $e->getMessage());
            $this->render('auth/message', ['title' => 'Error', 'message' => 'Could not update the transaction.']);
        }
    }

    /**
     * Deletes a single transaction.
     * CORRECTED: Only reverts balance if the transaction date has passed.
     */
    public function delete($transaction_id) {
        $user_id = $this->getUserId();
        $db = \Flight::db();

        $transactionModel = new Transaction();
        $accountModel = new Account();

        try {
            $db->beginTransaction();

            $transaction = $transactionModel->findById($transaction_id, $user_id);
            if (!$transaction) {
                throw new \Exception("Transaction not found or permission denied.");
            }
            
            // --- REVISED BALANCE LOGIC ---
            // Revert the financial impact, but only if it was already applied.
            $transactionDate = new \DateTime($transaction['transaction_date']);
            $today = new \DateTime('today');
            if ($transactionDate <= $today) {
                $accountModel->revertTransaction($transaction);
            }
            
            $transactionModel->delete($transaction_id, $user_id);
            
            $db->commit();
            \Flight::flash('Transaction deleted!', 'success');
            \Flight::redirect('/transactions');

        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Transaction deletion failed: " . $e->getMessage());
            \Flight::flash('Could not delete the transaction.', 'danger');
            \Flight::redirect('/transactions');
        }
    }

    // --- Other methods (showList, showEditForm) are mostly unchanged ---
    public function showList() {
        $user_id = $this->getUserId();
        $transactionModel = new Transaction();
        $items_per_page = 25;
        $total_items = $transactionModel->countAllByUserId($user_id);
        $total_pages = ceil($total_items / $items_per_page);
        $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($current_page < 1) { $current_page = 1; }
        if ($current_page > $total_pages && $total_pages > 0) { $current_page = $total_pages; }
        $offset = ($current_page - 1) * $items_per_page;
        $transactions = $transactionModel->findAllByUserIdWithPagination($user_id, $offset, $items_per_page);
        $this->render('transactions/index', [
            'transactions' => $transactions,
            'currentPage' => $current_page,
            'totalPages' => $total_pages
        ]);
    }

    public function showEditForm($transaction_id) {
        $user_id = $this->getUserId();
        $transaction = (new Transaction())->findById($transaction_id, $user_id);
        if (!$transaction) {
            \Flight::flash('Transaction not found.', 'danger');
            \Flight::redirect('/transactions');
            return;
        }
        $accounts = (new Account())->findAllByUserId($user_id);
        $this->render('transactions/edit', [
            'transaction' => $transaction,
            'accounts' => $accounts,
        ]);
    }
}
