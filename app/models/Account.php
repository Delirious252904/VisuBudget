<?php
namespace models;

use PDO;

class Account
{
    protected $db;

    public function __construct()
    {
        $this->db = \Flight::db();
    }

    public function findAllByUserId($user_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM accounts WHERE user_id = ? ORDER BY name ASC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
    
    public function findById($id, $user_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM accounts WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        return $stmt->fetch();
    }

    public function create($user_id, $name, $balance, $type)
    {
        $stmt = $this->db->prepare("INSERT INTO accounts (user_id, name, balance, type) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$user_id, $name, $balance, $type]);
    }
    
    public function update($id, $name, $balance, $type)
    {
        $stmt = $this->db->prepare("UPDATE accounts SET name = ?, balance = ?, type = ? WHERE id = ?");
        return $stmt->execute([$name, $balance, $type, $id]);
    }

    public function delete($id, $user_id)
    {
        $stmt = $this->db->prepare("DELETE FROM accounts WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $user_id]);
    }

    public function adjustBalance($account_id, $amount)
    {
        if ($account_id === null || !is_numeric($amount) || $amount == 0) {
            return;
        }
        $sql = "UPDATE accounts SET balance = balance + ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$amount, $account_id]);
    }

    /**
     * Applies a transaction's financial impact based on its data array.
     */
    public function applyTransaction($transactionData)
    {
        $amount = (float)($transactionData['amount'] ?? 0);
        $type = $transactionData['type'] ?? '';
        $from_account = $transactionData['from_account_id'] ?? null;
        $to_account = $transactionData['to_account_id'] ?? null;
        $account_id = $transactionData['account_id'] ?? null;

        if ($type === 'income') {
            $this->adjustBalance($account_id, $amount);
        } elseif ($type === 'expense') {
            $this->adjustBalance($account_id, -$amount);
        } elseif ($type === 'transfer') {
            $this->adjustBalance($from_account, -$amount);
            $this->adjustBalance($to_account, $amount);
        }
    }

    /**
     * Reverts a transaction's financial impact based on its data array.
     */
    public function revertTransaction($transactionData)
    {
        $amount = (float)($transactionData['amount'] ?? 0);
        $type = $transactionData['type'] ?? '';
        $from_account = $transactionData['from_account_id'] ?? null;
        $to_account = $transactionData['to_account_id'] ?? null;
        $account_id = $transactionData['account_id'] ?? null;

        if ($type === 'income') {
            $this->adjustBalance($account_id, -$amount);
        } elseif ($type === 'expense') {
            $this->adjustBalance($account_id, $amount);
        } elseif ($type === 'transfer') {
            $this->adjustBalance($from_account, $amount);
            $this->adjustBalance($to_account, -$amount);
        }
    }

    public function getTotalBalanceByUserId($user_id)
    {
        $stmt = $this->db->prepare("SELECT SUM(current_balance) as total_balance FROM accounts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn() ?? 0;
    }
    
    // This is the method used by the updated ViewController for the dashboard
    public function findAllByUserIdWithBalances($user_id) {
        $stmt = $this->db->prepare(
            "SELECT account_id, account_name, current_balance 
             FROM accounts WHERE user_id = ?"
        );
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
}
