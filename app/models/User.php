<?php
// app/models/User.php
namespace models;

class User {
    protected $db;

    public function __construct() {
        $this->db = \Flight::db();
    }

    /**
     * Finds a user by their email address.
     * @param string $email The email to search for.
     * @return mixed The user data if found, otherwise false.
     */
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * Finds a user by their verification token.
     * @param string $token The token to search for.
     * @return mixed The user data if found, otherwise false.
     */
    public function findByVerificationToken($token) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email_verification_token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /**
     * Creates a new, unverified user in the database.
     * @param string $email The user's email.
     * @param string $password The user's password (unhashed).
     * @return string|false The verification token on success, false on failure.
     */
    public function create($email, $password) {
        if ($this->findByEmail($email)) {
            // User already exists
            return false;
        }

        // Hash the password for security
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Generate a secure random token
        $token = bin2hex(random_bytes(32));
        
        // Set token expiration for 1 hour from now
        $expires_at = (new \DateTime('now + 1 hour'))->format('Y-m-d H:i:s');

        $sql = "INSERT INTO users (email, password_hash, email_verification_token, token_expires_at, is_verified, status)
                VALUES (:email, :password_hash, :token, :expires_at, 0, 'inactive')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires_at', $expires_at);

        if ($stmt->execute()) {
            return $token; // Return the token so the controller can email it
        }
        return false;
    }

    /**
     * Activates a user's account in the database.
     * **UPDATED** to be more robust.
     *
     * @param int $user_id The ID of the user to activate.
     * @return bool True if the row was successfully updated, false otherwise.
     */
    public function activateAccount($user_id) {
        $sql = "UPDATE users SET is_verified = 1, status = 'active', email_verification_token = NULL, token_expires_at = NULL WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        
        // Execute the statement
        $stmt->execute([$user_id]);
        
        // We now check rowCount(). This confirms that a row was actually affected by the UPDATE.
        // It returns true only if the update was successful AND it found the user to update.
        return $stmt->rowCount() > 0;
    }

     /**
     * Updates an existing user's verification token and expiration date.
     *
     * @param int $user_id The ID of the user.
     * @return string|false The new token on success, false on failure.
     */
    public function updateVerificationToken($user_id) {
        // Generate a new secure token and expiration date
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
     * **UPDATED** to return the full user object even on verification failure.
     *
     * @param string $email
     * @param string $password
     * @return mixed User data if email/password match, otherwise false.
     */
    public function verifyCredentials($email, $password) {
        $user = $this->findByEmail($email);
        
        // Check if user exists and password is correct, regardless of verification status.
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user; // Return user data, the controller will check the 'is_verified' flag.
        }
        
        return false; // Email not found or password incorrect.
    }

    /**
     * Generates and saves a password reset token for a user.
     *
     * @param int $user_id The ID of the user.
     * @return string|false The new token on success, false otherwise.
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
     *
     * @param string $token The reset token.
     * @return mixed User data if found, otherwise false.
     */
    public function findByPasswordResetToken($token) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE password_reset_token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /**
     * Updates a user's password and clears the reset token.
     *
     * @param int $user_id The ID of the user.
     * @param string $new_password The new password (unhashed).
     * @return bool True on success, false otherwise.
     */
    public function updatePassword($user_id, $new_password) {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        // Also clears any lingering password reset tokens for security.
        $sql = "UPDATE users SET password_hash = :password_hash, password_reset_token = NULL, password_reset_expires_at = NULL WHERE user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }

    /**
     * Updates a user's profile information, now including the savings percentage.
     * @param int $user_id The ID of the user to update.
     * @param array $data The new data from the form (e.g., ['name' => 'John', 'savings_percentage' => 10]).
     * @return bool True on success, false on failure.
     */
    public function updateProfile($user_id, $data) {
        $sql = "UPDATE users SET name = :name, savings_percentage = :savings_percentage WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        
        // Sanitize the percentage to make sure it's a valid number and handle if it's not set
        $savings_percentage = isset($data['savings_percentage']) ? floatval($data['savings_percentage']) : 0.00;
        $name = $data['name'] ?? ''; // Handle case where name might be empty

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':savings_percentage', $savings_percentage);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }

     /**
     * Marks a user's initial setup process as complete in the database.
     *
     * @param int $user_id The ID of the user to update.
     * @return bool True on success, false on failure.
     */
    public function markSetupAsComplete($user_id) {
        $sql = "UPDATE users SET has_completed_setup = 1 WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$user_id]);
    }

    /**
     * Deletes a user from the database by their ID.
     *
     * @param int $user_id The ID of the user to delete.
     * @return bool True on success, false on failure.
     */
    public function deleteById($user_id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }
}
