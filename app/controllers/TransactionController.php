<?php
// app/controllers/TransactionController.php
namespace controllers;

use models\Account;
use models\Transaction;
use models\RecurringRule;

class TransactionController extends ViewController {

    /**
     * Shows the unified form for adding single OR recurring transactions.
     */
    public function showAddForm() {
        $user_id = $this->getUserId();
        $accountModel = new Account();
        $this->render('transactions/add', [
            'accounts' => $accountModel->findAllByUserId($user_id),
            'user' => (new \models\User())->findById($user_id)
        ]);
    }

    /**
     * -- FIXED & REWRITTEN --
     * Processes the submission from the unified transaction form.
     * It now correctly handles both single and recurring transactions.
     */
    public function create() {
        $user_id = $this->getUserId();
        if (!$user_id) { \Flight::redirect('/login'); return; }

        $data = \Flight::request()->data;

        // Prepare a clean data array, starting with the essential user_id
        $clean_data = [
            'user_id' => $user_id,
            'description' => $data->description,
            'amount' => $data->amount,
            'type' => $data->type,
            'transaction_date' => $data->start_date, // Use start_date for both
            'start_date' => $data->start_date,
            // Correctly determine account IDs based on type, ensuring NULL if not applicable
            'from_account_id' => ($data->type === 'income') ? null : ($data->from_account_id ?: null),
            'to_account_id' => ($data->type === 'expense') ? null : ($data->to_account_id ?: null)
        ];

        // --- If "Is Recurring" is checked, create a Recurring Rule ---
        if (isset($data->is_recurring) && $data->is_recurring == 'on') {
            $ruleModel = new RecurringRule();
            
            // Add recurring-specific fields to the clean data array
            $clean_data['frequency'] = $data->frequency;
            $clean_data['interval_value'] = $data->interval_value ?? 1;
            $clean_data['day_of_week'] = !empty($data->day_of_week) ? $data->day_of_week : null;
            $clean_data['day_of_month'] = !empty($data->day_of_month) ? $data->day_of_month : null;
            $clean_data['occurrences'] = !empty($data->occurrences) ? $data->occurrences : null;
            $clean_data['end_date'] = !empty($data->end_date) ? $data->end_date : null;

            $success = $ruleModel->create($clean_data);
            \Flight::flash($success ? 'message' : 'error', $success ? 'Recurring transaction scheduled!' : 'Failed to schedule recurring transaction.');
            \Flight::redirect('/recurring');
        } 
        // --- Otherwise, create a single Transaction ---
        else {
            $transactionModel = new Transaction();
            // FIX: Use getData() to correctly get form data as an array
            $success = $transactionModel->create($user_id, $data->getData());
            
            if ($success) {
                $accountModel = new Account();
                $amount = (float)$data->amount;
                
                if ($data->type === 'income') {
                    $accountModel->adjustBalance($data->to_account_id, $amount);
                } elseif ($data->type === 'expense') {
                    $accountModel->adjustBalance($data->from_account_id, -$amount);
                } elseif ($data->type === 'transfer') {
                     $accountModel->adjustBalance($data->from_account_id, -$amount);
                     $accountModel->adjustBalance($data->to_account_id, $amount);
                }
                \Flight::flash('message', 'Transaction added successfully!');
            } else {
                \Flight::flash('error', 'Failed to add transaction.');
            }
            \Flight::redirect('/transactions');
        }
    }

    /**
     * Shows the form to edit a single transaction.
     */
    public function showEditForm($id) {
        $transaction = (new Transaction())->findById($id, $this->getUserId());
        if (!$transaction) {
            \Flight::flash('error', 'Transaction not found.');
            \Flight::redirect('/transactions');
            return;
        }
        $this->render('transactions/edit', ['transaction' => $transaction]);
    }

    /**
     * Processes the update of a single transaction.
     */
    public function update($id) {
        $transactionModel = new Transaction();
        // You would add security checks here to ensure the user owns the transaction
        $transactionModel->update($id, \Flight::request()->data->all());
        \Flight::flash('message', 'Transaction updated.');
        \Flight::redirect('/transactions');
    }

    /**
     * -- FIXED --
     * Deletes a single transaction AND correctly adjusts the associated account balance.
     */
    public function delete($id) {
        $user_id = $this->getUserId();
        $transactionModel = new Transaction();
        $accountModel = new Account();

        $transaction = $transactionModel->findById($id, $user_id);

        if ($transaction) {
            $db = \Flight::db();
            try {
                $db->beginTransaction();
                
                $amount = (float)$transaction['amount'];
                if ($transaction['type'] === 'income') {
                    $accountModel->adjustBalance($transaction['to_account_id'], -$amount);
                } else { 
                    $accountModel->adjustBalance($transaction['from_account_id'], $amount);
                    if ($transaction['type'] === 'transfer') {
                        $accountModel->adjustBalance($transaction['to_account_id'], -$amount);
                    }
                }

                $transactionModel->delete($id);
                $db->commit();
                \Flight::flash('message', 'Transaction deleted and account balance updated.');

            } catch (\Exception $e) {
                $db->rollBack();
                error_log("Delete transaction failed: " . $e->getMessage());
                \Flight::flash('error', 'Could not delete transaction.');
            }
        } else {
            \Flight::flash('error', 'Transaction could not be found.');
        }

        \Flight::redirect('/transactions');
    }

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
}
