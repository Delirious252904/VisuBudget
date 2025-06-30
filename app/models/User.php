<?php
// app/models/User.php
namespace models;
use Flight;

class User {
    protected $db;

    public function __construct($db = null)
    {
        // If a DB connection is passed, use it. Otherwise, get it from the Flight registry.
        $this->db = $db ?: \Flight::db();
    }

    /**
     * -- NEW, CORRECTED METHOD --
     * Finds a user by their unique user ID and returns all relevant data.
     * @param int $user_id The user's ID.
     * @return mixed The user data if found, otherwise false.
     */
    public function findById($user_id) {
        $stmt = $this->db->prepare(
            "SELECT 
                user_id, 
                name, 
                email, 
                role, 
                subscription_tier, 
                has_completed_setup, 
                savings_percentage,
                created_at
            FROM users WHERE user_id = ?"
        );
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }

    /**
     * Finds a user by their email address.
     */
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
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
     * Creates a new, unverified user in the database.
     */
    public function create($email, $password) {
        if ($this->findByEmail($email)) {
            return false;
        }
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));
        $expires_at = (new \DateTime('now + 1 hour'))->format('Y-m-d H:i:s');

        $sql = "INSERT INTO users (email, password_hash, email_verification_token, token_expires_at, is_verified, status)
                VALUES (:email, :password_hash, :token, :expires_at, 0, 'inactive')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires_at', $expires_at);

        return $stmt->execute() ? $token : false;
    }

    /**
     * Activates a user's account.
     */
    public function activateAccount($user_id) {
        $sql = "UPDATE users SET is_verified = 1, status = 'active', email_verification_token = NULL, token_expires_at = NULL WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->rowCount() > 0;
    }

     /**
     * Updates a user's verification token.
     */
    public function updateVerificationToken($user_id) {
        $token = bin2hex(random_bytes(32));
        $expires_at = (new \DateTime('now + 1 hour'))->format('Y-m-d H:i:s');
        $sql = "UPDATE users SET email_verification_token = :token, token_expires_at = :expires_at WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires_at', $expires_at);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute() ? $token : false;
    }

     /**
     * Verifies a user's login credentials.
     */
    public function verifyCredentials($email, $password) {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return false;
    }

    /**
     * Generates a password reset token.
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
     * Updates a user's password.
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
     * Marks a user's initial setup as complete.
     */
    public function markSetupAsComplete($user_id) {
        $sql = "UPDATE users SET has_completed_setup = 1 WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$user_id]);
    }

    /**
     * Deletes a user from the database.
     */
    public function deleteById($user_id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }
}
