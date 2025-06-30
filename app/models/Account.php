<?php
// app/models/Account.php
namespace models;

class Account {
    protected $db;

    public function __construct() {
        $this->db = \Flight::db();
    }
    
    /**
     * Creates a new account, matching the database schema.
     */
    public function create($user_id, $name, $balance, $type) {
        $stmt = $this->db->prepare(
            "INSERT INTO accounts (user_id, account_name, current_balance, account_type) VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$user_id, $name, $balance, $type]);
    }
    
    public function findAllByUserId($user_id) {
        $stmt = $this->db->prepare("SELECT *, account_name as name, account_type as type, current_balance as balance FROM accounts WHERE user_id = ? ORDER BY account_name ASC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    public function findById($account_id) {
        $stmt = $this->db->prepare("SELECT *, account_name as name, account_type as type, current_balance as balance FROM accounts WHERE account_id = ?");
        $stmt->execute([$account_id]);
        return $stmt->fetch();
    }

    /**
     * Updates an existing account, matching the database schema.
     */
    public function update($account_id, $name, $balance, $type) {
        $stmt = $this->db->prepare(
            "UPDATE accounts SET account_name = ?, current_balance = ?, account_type = ? WHERE account_id = ?"
        );
        return $stmt->execute([$name, $balance, $type, $account_id]);
    }

    public function delete($account_id) {
        (new Transaction())->deleteByAccountId($account_id, \Flight::get('user_data')['user_id']);
        $stmt = $this->db->prepare("DELETE FROM accounts WHERE account_id = ?");
        return $stmt->execute([$account_id]);
    }
    
    /**
     * -- FIXED & SIMPLIFIED --
     * Calculates the total current balance by simply summing the current balances of all accounts for a user.
     * This is the correct starting point for the "Safe to Spend" calculation.
     */
    public function getCurrentTotalBalanceByUserId($user_id) {
        $stmt = $this->db->prepare("SELECT SUM(current_balance) as total FROM accounts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return (float)($result['total'] ?? 0.00);
    }

    /**
     * -- FIXED & SIMPLIFIED --
     * Finds all accounts and uses their stored current balance.
     * The `live_balance` alias is used to maintain consistency with the view.
     */
    public function findAllByUserIdWithCurrentBalances($user_id) {
        $stmt = $this->db->prepare("
            SELECT 
                account_id,
                user_id,
                account_name,
                account_type,
                current_balance,
                current_balance as live_balance  -- Use the stored balance directly
            FROM 
                accounts
            WHERE 
                user_id = :user_id
            ORDER BY 
                account_name ASC
        ");
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll();
    }

    public function handleResetBalance($account_id) {
        $stmt1 = $this->db->prepare("UPDATE accounts SET current_balance = 0 WHERE account_id = ?");
        $stmt1->execute([$account_id]);
    
        $stmt2 = $this->db->prepare("DELETE FROM transactions WHERE from_account_id = ? OR to_account_id = ?");
        $stmt2->execute([$account_id, $account_id]);
    
        return $stmt1->rowCount() > 0 || $stmt2->rowCount() > 0;
    }

    /**
     * Adjusts the balance of a specific account by a given amount.
     * Can be positive (for adding money) or negative (for subtracting).
     *
     * @param int $account_id The ID of the account to adjust.
     * @param float $amount The amount to adjust by (can be negative).
     * @return bool True on success, false on failure.
     */
    public function adjustBalance($account_id, $amount) {
        if ($account_id === null) {
            return true; // Nothing to adjust
        }
        $sql = "UPDATE accounts SET current_balance = current_balance + ? WHERE account_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$amount, $account_id]);
    }
}
