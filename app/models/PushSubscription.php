<?php
// app/models/PushSubscription.php
namespace models;

class PushSubscription {
    protected $db;

    public function __construct() {
        $this->db = \Flight::db();
    }

    /**
     * Saves a new push subscription to the database.
     * It checks for an existing endpoint to prevent duplicates.
     *
     * @param int $user_id The ID of the user.
     * @param object $subscription The subscription object from the browser.
     * @return bool True on success, false on failure.
     */
    public function saveSubscription($user_id, $subscription) {
        // The subscription object contains the endpoint and the keys
        $endpoint = $subscription->endpoint;
        $p256dh = $subscription->keys->p256dh;
        $auth = $subscription->keys->auth;

        // First, check if this endpoint already exists for this user to avoid duplicates
        $stmt = $this->db->prepare("SELECT id FROM push_subscriptions WHERE endpoint = ? AND user_id = ?");
        $stmt->execute([$endpoint, $user_id]);
        if ($stmt->fetch()) {
            // Already exists, do nothing.
            return true;
        }

        // It's a new subscription, so save it.
        $sql = "INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth) VALUES (:user_id, :endpoint, :p256dh, :auth)";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':endpoint', $endpoint);
        $stmt->bindParam(':p256dh', $p256dh);
        $stmt->bindParam(':auth', $auth);

        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Failed to save push subscription: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes all push subscriptions for a specific user.
     *
     * @param int $user_id The ID of the user.
     * @return array An array of push subscription objects.
     */
    public function deleteAllForUser($user_id) {
        $stmt = $this->db->prepare("DELETE FROM push_subscriptions WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }
}
