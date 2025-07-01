<?php
namespace models;

use Flight;
use PDO;
use DateTime;

class Transaction
{
    protected $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: Flight::db();
    }

    public function findByIdAndUserId($transaction_id, $user_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM transactions WHERE transaction_id = ? AND user_id = ?");
        $stmt->execute([$transaction_id, $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO transactions (user_id, description, amount, type, from_account_id, to_account_id, transaction_date) 
            VALUES (:user_id, :description, :amount, :type, :from_account_id, :to_account_id, :transaction_date)"
        );
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':description' => $data['description'],
            ':amount' => $data['amount'],
            ':type' => $data['type'],
            ':from_account_id' => $data['from_account_id'] ?: null,
            ':to_account_id' => $data['to_account_id'] ?: null,
            ':transaction_date' => $data['transaction_date']
        ]);
        return $this->db->lastInsertId();
    }

    public function update($transaction_id, $data)
    {
        $stmt = $this->db->prepare(
            "UPDATE transactions SET 
            description = :description, amount = :amount, type = :type, 
            from_account_id = :from_account_id, to_account_id = :to_account_id, transaction_date = :transaction_date
            WHERE transaction_id = :transaction_id"
        );
        return $stmt->execute([
            ':description' => $data['description'],
            ':amount' => $data['amount'],
            ':type' => $data['type'],
            ':from_account_id' => $data['from_account_id'] ?: null,
            ':to_account_id' => $data['to_account_id'] ?: null,
            ':transaction_date' => $data['transaction_date'],
            ':transaction_id' => $transaction_id
        ]);
    }

    public function delete($transaction_id, $user_id)
    {
        $stmt = $this->db->prepare("DELETE FROM transactions WHERE transaction_id = ? AND user_id = ?");
        return $stmt->execute([$transaction_id, $user_id]);
    }
    
    public function findLatestDateByRuleId($rule_id) {
        $stmt = $this->db->prepare("SELECT MAX(transaction_date) FROM transactions WHERE rule_id = ?");
        $stmt->execute([$rule_id]);
        return $stmt->fetchColumn();
    }

    public function countByRuleId($rule_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM transactions WHERE rule_id = ?");
        $stmt->execute([$rule_id]);
        return (int)$stmt->fetchColumn();
    }

    public function existsByRuleIdAndDate($rule_id, $date) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM transactions WHERE rule_id = ? AND transaction_date = ?");
        $stmt->execute([$rule_id, $date]);
        return $stmt->fetchColumn() > 0;
    }

    public function createFromRule($rule, $date) {
        $stmt = $this->db->prepare(
            "INSERT INTO transactions (user_id, rule_id, description, amount, type, from_account_id, to_account_id, transaction_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $rule['user_id'], $rule['rule_id'], $rule['description'], $rule['amount'], $rule['type'],
            $rule['from_account_id'], $rule['to_account_id'], $date
        ]);
        return $this->db->lastInsertId();
    }
    
    public function deleteFutureByRuleId($rule_id, $user_id) {
        $stmt = $this->db->prepare("DELETE FROM transactions WHERE rule_id = ? AND user_id = ? AND transaction_date > CURDATE()");
        $stmt->execute([$rule_id, $user_id]);
    }
    
    public function deleteAllByAccountId($account_id, $user_id) {
        $stmt = $this->db->prepare("DELETE FROM transactions WHERE user_id = ? AND (from_account_id = ? OR to_account_id = ?)");
        $stmt->execute([$user_id, $account_id, $account_id]);
    }

    public function countAllByUserId($user_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }

    public function findAllByUserIdWithPagination($user_id, $offset, $limit) {
        $stmt = $this->db->prepare(
            "SELECT * FROM transactions 
             WHERE user_id = :user_id 
             ORDER BY transaction_date DESC, transaction_id DESC 
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findUpcomingByUserId($user_id, $limit = 5) {
        $stmt = $this->db->prepare(
            "SELECT * FROM transactions 
             WHERE user_id = :user_id AND transaction_date >= CURDATE()
             ORDER BY transaction_date ASC, description ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * FIX: Added the missing method to find the next upcoming income event.
     */
    public function findNextIncomeEvent($user_id) {
        $ruleModel = new \models\RecurringRule($this->db);
        $rules = $ruleModel->findAllActiveByUserId($user_id, 'income');
        
        $next_event = null;

        foreach ($rules as $rule) {
            $next_due = \models\RecurringRule::calculateNextDueDate($rule);
            if ($next_due && (!$next_event || $next_due < $next_event['next_date'])) {
                $next_event = [
                    'description' => $rule['description'],
                    'next_date' => $next_due,
                    'amount' => $rule['amount']
                ];
            }
        }
        
        if ($next_event) {
            $next_event['next_date'] = $next_event['next_date']->format('Y-m-d');
        }
        
        return $next_event;
    }

    public function getExpensesTotalBetweenDates($user_id, $start_date, $end_date) {
        $stmt = $this->db->prepare(
            "SELECT SUM(amount) FROM transactions 
             WHERE user_id = ? AND type = 'expense' AND transaction_date BETWEEN ? AND ?"
        );
        $stmt->execute([$user_id, $start_date, $end_date]);
        return (float)$stmt->fetchColumn();
    }

    /**
     * FIX: Added a proactive method to find the next upcoming expense event.
     */
    public function findNextExpenseEvent($user_id) {
        $ruleModel = new \models\RecurringRule($this->db);
        $rules = $ruleModel->findAllActiveByUserId($user_id, 'expense');
        
        $next_event = null;

        foreach ($rules as $rule) {
            $next_due = \models\RecurringRule::calculateNextDueDate($rule);
            if ($next_due && (!$next_event || $due_date < $next_event['next_date'])) {
                $next_event = [
                    'description' => $rule['description'],
                    'next_date' => $next_due,
                    'amount' => $rule['amount']
                ];
            }
        }

        if ($next_event) {
            $next_event['next_date'] = $next_event['next_date']->format('Y-m-d');
        }

        return $next_event;
    }

    public function getDailyExpensesForChart($user_id) {
        $stmt = $this->db->prepare(
            "SELECT transaction_date, SUM(amount) as total 
             FROM transactions 
             WHERE user_id = ? AND type = 'expense' AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY transaction_date 
             ORDER BY transaction_date ASC"
        );
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    public function getMonthlyTotal($user_id, $type) {
        $stmt = $this->db->prepare(
            "SELECT SUM(amount) as total 
             FROM transactions 
             WHERE user_id = ? AND type = ? AND MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())"
        );
        $stmt->execute([$user_id, $type]);
        return (float) $stmt->fetchColumn();
    }
}
