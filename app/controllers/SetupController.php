<?php
// app/controllers/SetupController.php
namespace controllers;

use models\Account;
use models\Transaction;
use models\RecurringRule;
use models\User;

class SetupController extends ViewController {

    /**
     * Shows the first step of the setup wizard (Add Account).
     */
    public function step1_show() {
        $this->render('setup/step1');
    }

    /**
     * Processes the first step (Adding an account).
     */
    public function step1_process() {
        $user_id = $this->getUserId();
        $data = \Flight::request()->data->getData();
        
        $accountModel = new Account();
        $accountModel->create($user_id, $data);
        
        // After creating the account, send them to the next step.
        \Flight::redirect('/setup/step2');
    }

    /**
     * Shows the second step of the setup wizard (Add Recurring Income).
     */
    public function step2_show() {
        $user_id = $this->getUserId();
        $accountModel = new Account();
        // We need the account list for the dropdown.
        $accounts = $accountModel->findAllByUserId($user_id);
        
        $this->render('setup/step2', ['accounts' => $accounts]);
    }

    /**
     * Processes the second step and completes the setup.
     */
    public function step2_process() {
        $user_id = $this->getUserId();
        $data = \Flight::request()->data->getData();

        $db = \Flight::db();
        
        try {
            $db->beginTransaction();

            // 1. Create the recurring rule.
            $ruleModel = new RecurringRule();
            $newRuleId = $ruleModel->create($user_id, $data);
            if (!$newRuleId) { throw new \Exception("Failed to create rule."); }
            $data['rule_id'] = $newRuleId;

            // 2. Create the first transaction instance.
            $txModel = new Transaction();
            $txModel->create($user_id, $data);
            
            // 3. Update the user's account balance.
            $accountModel = new Account();
            $accountModel->adjustBalance($data['from_account_id'], (float)$data['amount']);
            
            // 4. Mark the user's setup as complete!
            $userModel = new User();
            $userModel->markSetupAsComplete($user_id);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Setup Step 2 failed: " . $e->getMessage());
            // Redirect to an error page or back to the step
            \Flight::redirect('/setup/step2?error=1');
            return;
        }
        
        // All done! Send them to their new dashboard.
        \Flight::redirect('/dashboard');
    }
}
