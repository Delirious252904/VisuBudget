<?php
// app/core/SubscriptionMiddleware.php
namespace core;

use models\User;

class SubscriptionMiddleware {
    /**
     * This function checks if the current user has an active premium subscription.
     * If not, it redirects them to the pricing page.
     */
    public static function check() {
        // Our AuthMiddleware has already run and set the user's data.
        $jwt_user_data = \Flight::get('user_data');

        if (!$jwt_user_data) {
            // This should not happen on a protected route, but as a safeguard:
            \Flight::redirect('/login');
            exit();
        }

        // We need to get the most up-to-date user info from the database.
        $userModel = new User();
        $user = $userModel->findByEmail($jwt_user_data['email']);

        // The main check: is the user's tier 'premium'?
        if ($user && $user['subscription_tier'] === 'premium') {
            // Optional: We could also check if subscription_expires_at is in the future.
            // For now, we'll trust the tier status.
            return true; // User is premium, allow the request to proceed.
        }

        // If the user is not premium, send them to the pricing page.
        \Flight::redirect('/pricing');
        exit();
    }
}
