<?php
// app/controllers/TransactionController.php
namespace controllers;

use Flight;
use models\Account;
use models\Transaction;
use models\RecurringRule;
use models\User;

class TransactionController extends ViewController {

    /**
     * Displays a paginated list of single transactions.
     */
    public function index() {
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

    /**
     * Displays a paginated list of recurring rules.
     */
    public function listRecurring() {
        $user_id = $this->getUserId();
        $ruleModel = new RecurringRule();
        $limit = 10;
        $page = (int) (Flight::request()->query->page ?? 1);
        $offset = ($page - 1) * $limit;
        $rules = $ruleModel->findAllByUserIdWithPagination($user_id, $offset, $limit);
        $total_rules = $ruleModel->countByUserId($user_id);
        $total_pages = ceil($total_rules / $limit);
        $this->render('recurring/index', [
            'rules' => $rules,
            'currentPage' => $page,
            'totalPages' => $total_pages
        ]);
    }
    
    /**
     * Shows the unified form for adding or editing a transaction/rule.
     * @param string|null $type 'transaction' or 'recurring'
     * @param int|null $id The ID of the item to edit.
     */
    public function showForm($type = null, $id = null) {
        $user_id = $this->getUserId();
        $data = null;

        if ($id) { // Editing existing item
            if ($type === 'transaction') {
                $model = new Transaction();
                $data = $model->findByIdAndUserId($id, $user_id);
                if ($data) {
                    $data['is_recurring'] = false;
                    $data['id'] = $data['transaction_id'];
                }
            } elseif ($type === 'recurring') {
                $model = new RecurringRule();
                $data = $model->findByIdAndUserId($id, $user_id);
                if ($data) {
                    $data['is_recurring'] = true;
                    $data['id'] = $data['rule_id'];
                }
            }
        }
        
        if ($id && !$data) {
            Flight::flash('error', 'Item not found.');
            Flight::redirect('/dashboard');
            return;
        }

        $accountModel = new Account();
        $accounts = $accountModel->findAllByUserId($user_id);
        
        $this->render('transactions/form', [
            'transaction' => $data,
            'accounts' => $accounts
        ]);
    }

    /**
     * Handles the unified save logic for all transaction types.
     */
    public function save() {
        $user_id = $this->getUserId();
        $data = Flight::request()->data->getData();
        $id = !empty($data['id']) ? $data['id'] : null;
        $is_recurring = isset($data['is_recurring']);
        $original_type = $data['original_type'];

        $db = Flight::db();
        $db->beginTransaction();
        try {
            if ($is_recurring) {
                $this->saveRecurringRule($user_id, $id, $data, $original_type);
            } else {
                $this->saveSingleTransaction($user_id, $id, $data, $original_type);
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Save transaction/rule failed: " . $e->getMessage());
            Flight::flash('error', 'There was an error saving your data.');
        }
        
        $redirect_url = $is_recurring ? '/recurring' : '/transactions';
        Flight::redirect($redirect_url);
    }

    /**
     * Deletes either a single transaction or a recurring rule.
     */
    public function delete() {
        $user_id = $this->getUserId();
        $data = Flight::request()->data;
        $id = $data['id'];
        $type = $data['type'];

        if ($type === 'single') {
            $this->deleteSingleTransaction($id, $user_id);
        } elseif ($type === 'recurring') {
            $this->deleteRecurringRule($id, $user_id);
        }

        $redirect_url = ($type === 'recurring') ? '/recurring' : '/transactions';
        Flight::redirect($redirect_url);
    }

    // --- Private Helper Methods ---

    private function saveSingleTransaction($user_id, $id, $data, $original_type) {
        $transactionModel = new Transaction(Flight::db());
        $accountModel = new Account(Flight::db());
        
        $data['user_id'] = $user_id;

        if ($id && $original_type === 'single') { // Updating a single transaction
            $original_tx = $transactionModel->findByIdAndUserId($id, $user_id);
            if (!$original_tx) throw new \Exception("Original transaction not found.");
            
            $this->revertTransactionBalance($original_tx, $accountModel);
            $transactionModel->update($id, $data);
            $this->applyTransactionBalance($data, $accountModel);
            Flight::flash('message', 'Transaction updated successfully!');

        } else { // Creating a new single transaction
            $transactionModel->create($data);
            $this->applyTransactionBalance($data, $accountModel);
            Flight::flash('message', 'Transaction added successfully!');
        }
    }

    private function saveRecurringRule($user_id, $id, $data, $original_type) {
        $ruleModel = new RecurringRule(Flight::db());
        $data['user_id'] = $user_id;

        if ($id && $original_type === 'single') { // Converting single tx to recurring
            $this->deleteSingleTransaction($id, $user_id); 
            $ruleModel->create($data);
            Flight::flash('message', 'Transaction converted to a new recurring rule!');
        } elseif ($id) { // Updating an existing recurring rule
            $ruleModel->update($id, $data);
            Flight::flash('message', 'Recurring rule updated successfully!');
        } else { // Creating a new recurring rule
            $ruleModel->create($data);
            Flight::flash('message', 'Recurring rule scheduled!');
        }
    }

    private function applyTransactionBalance($data, Account $accountModel) {
        $amount = (float)$data['amount'];
        if ($data['type'] === 'income' && !empty($data['to_account_id'])) {
            $accountModel->adjustBalance($data['to_account_id'], $amount);
        } elseif ($data['type'] === 'expense' && !empty($data['from_account_id'])) {
            $accountModel->adjustBalance($data['from_account_id'], -$amount);
        } elseif ($data['type'] === 'transfer' && !empty($data['from_account_id']) && !empty($data['to_account_id'])) {
            $accountModel->adjustBalance($data['from_account_id'], -$amount);
            $accountModel->adjustBalance($data['to_account_id'], $amount);
        }
    }

    private function revertTransactionBalance($tx_data, Account $accountModel) {
        $amount = (float)$tx_data['amount'];
        if ($tx_data['type'] === 'income' && $tx_data['to_account_id']) {
            $accountModel->adjustBalance($tx_data['to_account_id'], -$amount);
        } elseif ($tx_data['type'] === 'expense' && $tx_data['from_account_id']) {
            $accountModel->adjustBalance($tx_data['from_account_id'], $amount);
        } elseif ($tx_data['type'] === 'transfer') {
            if($tx_data['from_account_id']) $accountModel->adjustBalance($tx_data['from_account_id'], $amount);
            if($tx_data['to_account_id']) $accountModel->adjustBalance($tx_data['to_account_id'], -$amount);
        }
    }

    private function deleteSingleTransaction($id, $user_id) {
        $db = Flight::db();
        $db->beginTransaction();
        try {
            $transactionModel = new Transaction($db);
            $accountModel = new Account($db);
            $transaction = $transactionModel->findByIdAndUserId($id, $user_id);
            if ($transaction) {
                $this->revertTransactionBalance($transaction, $accountModel);
                $transactionModel->delete($id, $user_id);
            }
            $db->commit();
            Flight::flash('message', 'Transaction deleted.');
        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Delete single transaction failed: " . $e->getMessage());
            Flight::flash('error', 'Could not delete transaction.');
        }
    }

    private function deleteRecurringRule($id, $user_id) {
        $db = Flight::db();
        $db->beginTransaction();
        try {
            $transactionModel = new Transaction($db);
            $ruleModel = new RecurringRule($db);
            $transactionModel->deleteFutureByRuleId($id, $user_id);
            $ruleModel->delete($id, $user_id);
            $db->commit();
            Flight::flash('message', 'Recurring rule and its future transactions have been deleted.');
        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Delete recurring rule failed: " . $e->getMessage());
            Flight::flash('error', 'Could not delete recurring rule.');
        }
    }
}
