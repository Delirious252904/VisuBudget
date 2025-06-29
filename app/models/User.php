<?php
// app/models/User.php
namespace models;

use PDO;

class User {
    protected $db;

    public function __construct() {
        $this->db = \Flight::db();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT user_id, name, email, role, subscription_tier, created_at FROM users WHERE user_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function findAll() {
        $stmt = $this->db->query("SELECT user_id, name, email, role FROM users");
        return $stmt->fetchAll();
    }

    public function countAll() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM users");
        return $stmt->fetchColumn();
    }

    /**
     * Creates a new user in the database.
     * CORRECTED: Now saves to 'name' and 'password_hash' to match the database schema.
     */
    public function create($name, $email, $password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $verification_token = bin2hex(random_bytes(32));
        $token_expires_at = (new \DateTime('now + 1 hour'))->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            "INSERT INTO users (name, email, password_hash, email_verification_token, token_expires_at, role) VALUES (?, ?, ?, ?, ?, 'user')"
        );
        
        $stmt->execute([$name, $email, $hashed_password, $verification_token, $token_expires_at]);
        
        // Return the token for the verification email, not the user ID.
        return $verification_token;
    }

    /**
     * Finds a user by their verification token.
     */
    public function findByVerificationToken($token) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email_verification_token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /**
     * Activates a user's account in the database.
     */
    public function activateAccount($user_id) {
        $sql = "UPDATE users SET is_verified = 1, status = 'active', email_verification_token = NULL, token_expires_at = NULL WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->rowCount() > 0;
    }

     /**
     * Updates an existing user's verification token and expiration date.
     */
    public function updateVerificationToken($user_id) {
        $token = bin2hex(random_bytes(32));
        $expires_at = (new \DateTime('now + 1 hour'))->format('Y-m-d H:i:s');

        $sql = "UPDATE users SET email_verification_token = :token, token_expires_at = :expires_at WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires_at', $expires_at);
        $stmt->bindParam(':user_id', $user_id);

        if ($stmt->execute()) {
            return $token;
        }
        return false;
    }

     /**
     * Verifies a user's login credentials.
     */
    public function verifyCredentials($email, $password) {
        $user = $this->findByEmail($email);
        
        if ($user && isset($user['password_hash']) && password_verify($password, $user['password_hash'])) {
            return $user; 
        }
        
        return false;
    }

    /**
     * Generates and saves a password reset token for a user.
     */
    public function generatePasswordResetToken($user_id) {
        $token = bin2hex(random_bytes(32));
        $expires_at = (new \DateTime('now + 1 hour'))->format('Y-m-d H:i:s');

        $sql = "UPDATE users SET password_reset_token = :token, password_reset_expires_at = :expires_at WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires_at', $expires_at);
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute() ? $token : false;
    }

    /**
     * Finds a user by their password reset token.
     */
    public function findByPasswordResetToken($token) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE password_reset_token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /**
     * Updates a user's password and clears the reset token.
     */
    public function updatePassword($user_id, $new_password) {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password_hash = :password_hash, password_reset_token = NULL, password_reset_expires_at = NULL WHERE user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }

    /**
     * Updates a user's profile information.
     * CORRECTED: Now updates 'name' column.
     */
    public function updateProfile($user_id, $data) {
        $sql = "UPDATE users SET name = :name, savings_percentage = :savings_percentage WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        
        $savings_percentage = isset($data['savings_percentage']) ? floatval($data['savings_percentage']) : 0.00;
        $name = $data['name'] ?? '';

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':savings_percentage', $savings_percentage);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }

     /**
     * Marks a user's initial setup process as complete.
     */
    public function markSetupAsComplete($user_id) {
        $sql = "UPDATE users SET has_completed_setup = 1 WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$user_id]);
    }

    /**
     * Deletes a user from the database by their ID.
     */
    public function deleteById($user_id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }
}
