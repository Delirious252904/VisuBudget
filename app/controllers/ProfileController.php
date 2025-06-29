<?php
// app/controllers/ProfileController.php
namespace controllers;

// We need to tell this controller where to find all the models it uses.
use models\User;
use models\Account;
use models\Transaction;
use models\RecurringRule;
use models\PushSubscription;
use models\SavingsGoal;


class ProfileController extends ViewController {

    /**
     * Shows the main user profile page.
     */
    public function showProfileForm() {
        $user_id = $this->getUserId();
        $userModel = new User();
        // We need to find the user's data again to get their name.
        // The findByEmail method is a good way to do this.
        $user_data = $userModel->findByEmail(\Flight::get('user_data')['email']);

        $this->render('profile/index', ['user' => $user_data]);
    }

    /**
     * Handles the form submission for updating the user's name.
     */
    public function updateProfile() {
        $user_id = $this->getUserId();
        $data = \Flight::request()->data->getData();
        $userModel = new User();

        $success = $userModel->updateProfile($user_id, $data);

        // We can add success/error messages later.
        \Flight::redirect('/profile');
    }

    /**
     * Handles the form submission for changing the user's password.
     */
    public function updatePassword() {
        $user_id = $this->getUserId();
        $data = \Flight::request()->data->getData();
        
        $current_password = $data['current_password'];
        $new_password = $data['new_password'];
        $confirm_password = $data['confirm_password'];
        
        // Basic validation
        if (empty($current_password) || empty($new_password) || $new_password !== $confirm_password) {
            // In a real app, we'd redirect with an error message.
            \Flight::redirect('/profile?error=password_mismatch');
            return;
        }

        $userModel = new User();
        $user_data = \Flight::get('user_data');

        // Verify the user's CURRENT password before allowing a change.
        $currentUser = $userModel->verifyCredentials($user_data['email'], $current_password);

        if (!$currentUser) {
            \Flight::redirect('/profile?error=current_password_incorrect');
            return;
        }

        // Current password is correct, so proceed with the update.
        $success = $userModel->updatePassword($user_id, $new_password);
        
        if ($success) {
            // In the future, we might log them out everywhere else for security.
            \Flight::redirect('/profile?success=password_updated');
        } else {
            \Flight::redirect('/profile?error=update_failed');
        }
    }

    /**
     * Resets all financial data for the logged-in user.
     */
    public function resetData() {
        $user_id = $this->getUserId();
        if (!$user_id) { \Flight::redirect('/login'); return; }

        $db = \Flight::db();

        try {
            $db->beginTransaction();
            // Delete all data in a specific order to respect foreign key constraints
            (new SavingsGoal())->deleteAllForUser($user_id);
            (new PushSubscription())->deleteAllForUser($user_id);
            (new Transaction())->deleteAllForUser($user_id);
            (new RecurringRule())->deleteAllForUser($user_id);
            (new Account())->deleteAllForUser($user_id);
            
            // Mark the user's setup as incomplete so they see the wizard again
            $userModel = new User();
            $userModel->markSetupAsComplete($user_id, false);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            error_log("User data reset failed for user_id {$user_id}: " . $e->getMessage());
            \Flight::redirect('/profile?error=reset_failed');
            return;
        }

        // After resetting, send them to the dashboard, which will trigger the setup wizard
        \Flight::redirect('/dashboard');
    }

    /**
     * Permanently deletes a user's account and all their data.
     */
    public function deleteAccount() {
        $user_id = $this->getUserId();
        if (!$user_id) { \Flight::redirect('/login'); return; }

        $db = \Flight::db();

        try {
            $db->beginTransaction();
            (new SavingsGoal())->deleteAllForUser($user_id);
            (new PushSubscription())->deleteAllForUser($user_id);
            (new Transaction())->deleteAllForUser($user_id);
            (new RecurringRule())->deleteAllForUser($user_id);
            (new Account())->deleteAllForUser($user_id);
            (new User())->deleteById($user_id);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Account deletion failed for user_id {$user_id}: " . $e->getMessage());
            \Flight::redirect('/profile?error=delete_failed');
            return;
        }

        setcookie('auth_token', '', time() - 3600, '/');
        \Flight::redirect('/');
    }
}
