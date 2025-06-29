<?php
// app/models/Account.php
namespace models;

use models\User;

class Account {

    protected $db;

    public function __construct() {
        // Get the database connection from Flight
        $this->db = \Flight::db();
    }

    /**
     * Creates a new account for a specific user.
     * @param int $user_id The ID of the logged-in user.
     * @param array $data The data from the form.
     * @return bool
     */
    public function create($user_id, $data) {
        $sql = "INSERT INTO accounts (user_id, account_name, account_type, current_balance) 
                VALUES (:user_id, :account_name, :account_type, :current_balance)";
        
        $stmt = $this->db->prepare($sql);
        // Bind the real user ID
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':account_name', $data['account_name']);
        $stmt->bindParam(':account_type', $data['account_type']);
        $stmt->bindParam(':current_balance', $data['current_balance']);
        
        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Finds all accounts belonging to a specific user.
     *
     * @param int $user_id The ID of the user.
     * @return array An array of accounts.
     */
    public function findAllByUserId($user_id) {
        $stmt = $this->db->prepare("SELECT account_id, account_name, current_balance FROM accounts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    /**
     * Calculates the total balance across all of a user's accounts.
     *
     * @param int $user_id The ID of the user.
     * @return float The total balance.
     */
    public function getTotalBalanceByUserId($user_id) {
        $stmt = $this->db->prepare("SELECT SUM(current_balance) as total FROM accounts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0.00;
    }

    /**
     * Adjusts the balance of a specific account.
     *
     * @param int $account_id The ID of the account to update.
     * @param float $amount The amount to add (positive) or subtract (negative).
     * @return bool True on success, false on failure.
     */
    public function adjustBalance($account_id, $amount) {
        // This SQL query is atomic, ensuring the calculation is safe.
        $sql = "UPDATE accounts SET current_balance = current_balance + :amount WHERE account_id = :account_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':account_id', $account_id);

        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Balance adjust error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Finds a single account by its ID, ensuring the user owns it.
     */
    public function findById($account_id, $user_id) {
        $stmt = $this->db->prepare("SELECT * FROM accounts WHERE account_id = ? AND user_id = ?");
        $stmt->execute([$account_id, $user_id]);
        return $stmt->fetch();
    }

    /**
     * Updates an account's name and type.
     */
    public function update($account_id, $data) {
        $sql = "UPDATE accounts SET account_name = :account_name, account_type = :account_type WHERE account_id = :account_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':account_name', $data['account_name']);
        $stmt->bindParam(':account_type', $data['account_type']);
        $stmt->bindParam(':account_id', $account_id);
        return $stmt->execute();
    }

    /**
     * Deletes an account.
     * WARNING: This is a destructive action. We should add checks to prevent deletion if it has transactions.
     */
    public function delete($account_id, $user_id) {
        // For now, a simple delete. Later, we'd check for associated transactions first.
        $stmt = $this->db->prepare("DELETE FROM accounts WHERE account_id = ? AND user_id = ?");
        $stmt->execute([$account_id, $user_id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Force-sets an account's balance to a specific value.
     */
    public function setBalance($account_id, $new_balance) {
        $sql = "UPDATE accounts SET current_balance = :new_balance WHERE account_id = :account_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':new_balance', $new_balance);
        $stmt->bindParam(':account_id', $account_id);
        return $stmt->execute();
    }

    /**
     * Deletes all accounts for a specific user.
     * WARNING: This is a destructive action. We should add checks to prevent deletion if it has transactions.
     */
    public function deleteAllForUser($user_id) {
        $stmt = $this->db->prepare("DELETE FROM accounts WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }
}
