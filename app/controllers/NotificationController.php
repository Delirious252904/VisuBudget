<?php
// app/controllers/NotificationController.php
namespace controllers;

use models\PushSubscription;

class NotificationController extends ViewController {

    /**
     * Handles the request from the browser to save a push subscription.
     */
    public function subscribe() {
        $user_id = $this->getUserId();
        if (!$user_id) {
            // Must be logged in to subscribe
            \Flight::halt(401, 'Unauthorized');
            return;
        }

        // Get the subscription data sent from the browser's JavaScript
        $subscription_data_json = \Flight::request()->getBody();
        $subscription = json_decode($subscription_data_json);

        if (!$subscription || empty($subscription->endpoint)) {
            \Flight::halt(400, 'Bad Request: Invalid subscription data.');
            return;
        }

        $pushModel = new PushSubscription();
        $success = $pushModel->saveSubscription($user_id, $subscription);

        if ($success) {
            \Flight::json(['status' => 'success']);
        } else {
            \Flight::json(['status' => 'error', 'message' => 'Could not save subscription.'], 500);
        }
    }
}
