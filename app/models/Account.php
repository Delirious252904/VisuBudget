<?php
// app/models/Account.php
namespace models;

class Account {
    protected $db;

    /**
     * Constructor to fetch the database connection from the Flight framework.
     */
    public function __construct() {
        $this->db = \Flight::db();
    }
    
    /**
     * Creates a new account.
     * -- FIX: Column names now match the database schema --
     */
    public function create($user_id, $name, $balance, $type) {
        $stmt = $this->db->prepare(
            "INSERT INTO accounts (user_id, account_name, current_balance, account_type) VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$user_id, $name, $balance, $type]);
    }
    
    public function findAllByUserId($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM accounts WHERE user_id = ? ORDER BY account_name ASC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    public function findById($account_id) {
        $stmt = $this->db->prepare("SELECT * FROM accounts WHERE account_id = ?");
        $stmt->execute([$account_id]);
        return $stmt->fetch();
    }

    /**
     * Updates an existing account.
     * -- FIX: Column names now match the database schema --
     */
    public function update($account_id, $name, $balance, $type) {
        $stmt = $this->db->prepare(
            "UPDATE accounts SET account_name = ?, current_balance = ?, account_type = ? WHERE account_id = ?"
        );
        return $stmt->execute([$name, $balance, $type, $account_id]);
    }

    public function delete($account_id) {
        $stmt = $this->db->prepare("DELETE FROM transactions WHERE from_account_id = ? OR to_account_id = ?");
        $stmt->execute([$account_id, $account_id]);
        
        $stmt = $this->db->prepare("DELETE FROM accounts WHERE account_id = ?");
        return $stmt->execute([$account_id]);
    }

    /**
     * Finds all accounts for a user and calculates their real-time balances.
     * -- FIX: This query now uses the correct 'current_balance' column --
     */
    public function findAllByUserIdWithCurrentBalances($user_id) {
        $stmt = $this->db->prepare("
            SELECT 
                accounts.*,
                (accounts.current_balance 
                 + IFNULL((SELECT SUM(amount) FROM transactions WHERE to_account_id = accounts.account_id AND transaction_date <= CURDATE()), 0)
                 - IFNULL((SELECT SUM(amount) FROM transactions WHERE from_account_id = accounts.account_id AND transaction_date <= CURDATE()), 0)
                ) as live_balance
            FROM 
                accounts
            WHERE 
                accounts.user_id = :user_id
            ORDER BY 
                accounts.account_name ASC
        ");
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll();
    }

    /**
     * Calculates the total current balance of all accounts for a specific user.
     * -- FIX: This query now uses the correct 'current_balance' column --
     */
    public function getCurrentTotalBalanceByUserId($user_id) {
        $stmt = $this->db->prepare("
            SELECT 
                (SELECT IFNULL(SUM(current_balance), 0) FROM accounts WHERE user_id = :user_id1) +
                (SELECT IFNULL(SUM(CASE 
                                    WHEN type = 'income' THEN amount 
                                    WHEN type = 'expense' THEN -amount
                                    WHEN type = 'transfer' THEN 0 
                                    ELSE 0 
                                  END), 0) 
                 FROM transactions 
                 WHERE user_id = :user_id2 AND transaction_date <= CURDATE())
            AS total_balance
        ");
        $stmt->execute([':user_id1' => $user_id, ':user_id2' => $user_id]);
        $result = $stmt->fetch();
        return $result['total_balance'] ?? 0;
    }

    public function handleResetBalance($account_id) {
        // Set the account's starting balance to 0
        $stmt1 = $this->db->prepare("UPDATE accounts SET current_balance = 0 WHERE account_id = ?");
        $stmt1->execute([$account_id]);
    
        // Delete all past and future transactions for this account
        $stmt2 = $this->db->prepare("DELETE FROM transactions WHERE from_account_id = ? OR to_account_id = ?");
        $stmt2->execute([$account_id, $account_id]);
    
        return $stmt1->rowCount() > 0 || $stmt2->rowCount() > 0;
    }
}
