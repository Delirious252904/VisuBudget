<?php
// app/controllers/RecurringController.php
namespace controllers;

use Flight;
use models\RecurringRule;
use models\Account;
use models\Transaction;

class RecurringController extends ViewController {

    /**
     * Shows a list of all recurring rules for the logged-in user.
     * This method fetches all rules and their associated accounts, then renders the view.
     */
    public function index() {
        $user_id = $this->getUserId();
        $ruleModel = new RecurringRule();

        // Pagination settings
        $limit = 10; // Number of rules per page
        $page = (int) (Flight::request()->query->page ?? 1);
        $offset = ($page - 1) * $limit;

        // Fetch paginated rules and the total count for pagination links
        $rules = $ruleModel->findAllByUserIdWithPagination($user_id, $offset, $limit);
        $total_rules = $ruleModel->countByUserId($user_id);
        $total_pages = ceil($total_rules / $limit);

        // Pass all necessary data to the view
        $this->render('recurring/index', [
            'rules' => $rules,
            'currentPage' => $page,
            'totalPages' => $total_pages
        ]);
    }

    /**
     * Shows the form for editing a specific recurring rule.
     * @param int $rule_id The ID of the rule from the URL.
     */
    public function showEditForm($rule_id) {
        $user_id = $this->getUserId();
        $ruleModel = new RecurringRule();
        
        // Find the specific rule, ensuring it belongs to the logged-in user.
        $rule = $ruleModel->findByIdAndUserId($rule_id, $user_id);

        if (!$rule) {
            // If the rule doesn't exist or doesn't belong to the user, show an error.
            $this->render('auth/message', ['title' => 'Error', 'message' => 'Recurring rule not found.']);
            return;
        }

        // We also need to get all accounts to populate the dropdowns.
        $accountModel = new Account();
        $accounts = $accountModel->findAllByUserId($user_id);

        $this->render('recurring/edit', ['rule' => $rule, 'accounts' => $accounts]);
    }

    /**
     * Processes the submission of the 'edit recurring rule' form.
     * @param int $rule_id The ID of the rule from the URL.
     */
    public function update($rule_id) {
        $user_id = $this->getUserId();
        $ruleModel = new RecurringRule();
        
        // First, check if the user actually owns this rule.
        if (!$ruleModel->findByIdAndUserId($rule_id, $user_id)) {
            $this->render('auth/message', ['title' => 'Error', 'message' => 'You do not have permission to edit this rule.']);
            return;
        }

        $request = \Flight::request();
        $data = $request->data->getData();

        // Here you would add validation similar to the add transaction form.
        
        // Call the update method in the model.
        $success = $ruleModel->update($rule_id, $data);

        if ($success) {
            \Flight::redirect('/recurring');
        } else {
            echo "There was an error updating the rule.";
        }
    }

    /**
     * Deletes a recurring rule AND all of its future generated transactions..
     * @param int $rule_id The ID of the rule from the URL.
     */
    public function delete($rule_id) {
        $user_id = $this->getUserId();
        $db = \Flight::db();

        try {
            // Start a protective "bubble" around our database operations
            $db->beginTransaction();

            // 1. Delete all future transactions associated with this rule.
            $transactionModel = new Transaction();
            $transactionModel->deleteFutureByRuleId($rule_id, $user_id);
            
            // 2. Delete the rule itself.
            $ruleModel = new RecurringRule();
            $ruleModel->delete($rule_id, $user_id);
            
            // 3. If both steps succeeded, make the changes permanent.
            $db->commit();

        } catch (\Exception $e) {
            // If anything went wrong, undo all the changes.
            $db->rollBack();
            error_log("Failed to delete recurring rule and its transactions: " . $e->getMessage());
        }

        // Redirect back to the list of rules.
        \Flight::redirect('/recurring');
    }
}