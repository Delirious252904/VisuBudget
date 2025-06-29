<?php
// app/models/Account.php
namespace models;

use PDO;

class Account {
    protected $db;

    public function __construct() {
        $this->db = \Flight::db(); // Assuming Flight is used for dependency injection
    }

    public function create($user_id, $name, $balance, $type, $is_primary = 0) {
        $stmt = $this->db->prepare("INSERT INTO accounts (user_id, name, balance, type, is_primary) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$user_id, $name, $balance, $type, $is_primary]);
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

    public function update($account_id, $name, $balance, $type) {
        $stmt = $this->db->prepare("UPDATE accounts SET name = ?, balance = ?, type = ? WHERE account_id = ?");
        return $stmt->execute([$name, $balance, $type, $account_id]);
    }

    public function delete($account_id) {
        // Also delete associated transactions
        $stmt = $this->db->prepare("DELETE FROM transactions WHERE from_account_id = ? OR to_account_id = ?");
        $stmt->execute([$account_id, $account_id]);
        
        $stmt = $this->db->prepare("DELETE FROM accounts WHERE account_id = ?");
        return $stmt->execute([$account_id]);
    }

    /**
     * Calculates and returns the total current balance of all accounts for a specific user.
     * This method is corrected to only include transactions up to and including today's date.
     */
    public function getCurrentTotalBalanceByUserId($user_id) {
        $stmt = $this->db->prepare("
            SELECT 
                (SELECT IFNULL(SUM(balance), 0) FROM accounts WHERE user_id = :user_id1) +
                (SELECT IFNULL(SUM(CASE 
                                    WHEN type = 'income' THEN amount 
                                    WHEN type = 'expense' THEN -amount
                                    WHEN type = 'transfer' THEN 0 -- Transfers are neutral to total balance
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


    /**
     * Finds all accounts for a user and calculates their current balances by
     * summing up the initial balance and transactions up to and including today's date.
     */
    public function findAllByUserIdWithCurrentBalances($user_id) {
        $stmt = $this->db->prepare("
            SELECT 
                a.*,
                (a.balance 
                 + IFNULL((SELECT SUM(amount) FROM transactions WHERE to_account_id = a.account_id AND transaction_date <= CURDATE()), 0)
                 - IFNULL((SELECT SUM(amount) FROM transactions WHERE from_account_id = a.account_id AND transaction_date <= CURDATE()), 0)
                ) as current_balance
            FROM 
                accounts a
            WHERE 
                a.user_id = :user_id
            ORDER BY 
                a.name ASC
        ");
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll();
    }


    /**
     * DEPRECATED: Calculates and returns the total balance of all accounts for a specific user.
     * This method incorrectly includes future transactions. Use getCurrentTotalBalanceByUserId instead.
     */
    public function getTotalBalanceByUserId($user_id) {
        $stmt = $this->db->prepare("
            SELECT 
                (SELECT IFNULL(SUM(balance), 0) FROM accounts WHERE user_id = :user_id1) +
                (SELECT IFNULL(SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END), 0) 
                 FROM transactions WHERE user_id = :user_id2)
            AS total_balance
        ");
        $stmt->execute([':user_id1' => $user_id, ':user_id2' => $user_id]);
        $result = $stmt->fetch();
        return $result['total_balance'] ?? 0;
    }

    /**
     * DEPRECATED: Finds all accounts for a user and calculates their balances using all transactions.
     * This method incorrectly includes future transactions. Use findAllByUserIdWithCurrentBalances instead.
     */
    public function findAllByUserIdWithBalances($user_id) {
        $stmt = $this->db->prepare("
            SELECT 
                a.*,
                (a.balance 
                 + IFNULL((SELECT SUM(amount) FROM transactions WHERE to_account_id = a.account_id), 0)
                 - IFNULL((SELECT SUM(amount) FROM transactions WHERE from_account_id = a.account_id), 0)
                ) as current_balance
            FROM 
                accounts a
            WHERE 
                a.user_id = :user_id3
            ORDER BY 
                a.name ASC
        ");
        $stmt->execute([':user_id3' => $user_id]);
        return $stmt->fetchAll();
    }
}
