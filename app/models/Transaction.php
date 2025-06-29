<?php
// app/models/Transaction.php
namespace models;

use models\RecurringRule;

class Transaction {
    protected $db;

    public function __construct() {
        $this->db = \Flight::db();
    }

    /**
     * Finds the true next income date by checking both generated transactions and recurring rules.
     *
     * @param int $user_id The ID of the user.
     * @return string|null The date of the next income in 'Y-m-d' format, or null.
     */
    public function findNextIncomeEvent($user_id) {
        $potentialEvents = [];

        // 1. Find the nearest future income from already-generated transactions.
        $sql = "SELECT transaction_date, amount FROM transactions WHERE user_id = :user_id AND type = 'income' AND transaction_date > CURDATE() ORDER BY transaction_date ASC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, \PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        if ($result) {
            $potentialEvents[] = ['date' => new \DateTime($result['transaction_date']), 'amount' => $result['amount']];
        }

        // 2. Find the next income date from all recurring income rules.
        $ruleModel = new RecurringRule();
        $rules = $ruleModel->findAllByUserId($user_id);
        foreach ($rules as $rule) {
            if ($rule['type'] === 'income') {
                $nextDueDate = RecurringRule::calculateNextDueDate($rule);
                if ($nextDueDate) {
                    $potentialEvents[] = ['date' => $nextDueDate, 'amount' => $rule['amount']];
                }
            }
        }

        // 3. If we didn't find any events, return null.
        if (empty($potentialEvents)) {
            return null;
        }

        // 4. Sort all the potential events by date and return the earliest one.
        usort($potentialEvents, function($a, $b) {
            return $a['date'] <=> $b['date'];
        });
        
        // Return the winning event as a simple array.
        return [
            'date' => $potentialEvents[0]['date']->format('Y-m-d'),
            'amount' => $potentialEvents[0]['amount']
        ];
    }

    public function getExpensesTotalBetweenDates($user_id, $startDate, $endDate) {
        $sql = "SELECT SUM(amount) as total_expenses FROM transactions WHERE user_id = :user_id AND type = 'expense' AND transaction_date BETWEEN :start_date AND :end_date";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, \PDO::PARAM_INT);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        $result = $stmt->fetch();
        return (float)($result['total_expenses'] ?? 0.00);
    }

    public function findUpcomingByUserId($user_id, $limit = 5) {
        $sql = "SELECT transaction_date, description, amount, type FROM transactions WHERE user_id = :user_id AND transaction_date >= CURDATE() ORDER BY transaction_date ASC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, \PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Creates a new transaction.
     * The method signature now correctly accepts both the user's ID and the form data.
     *
     * @param int $user_id The ID of the logged-in user.
     * @param array $data The form data.
     * @return bool
     */
    public function create($user_id, $data) {
        $sql = "INSERT INTO transactions (user_id, rule_id, type, description, amount, transaction_date, from_account_id, to_account_id) 
                VALUES (:user_id, :rule_id, :type, :description, :amount, :transaction_date, :from_account_id, :to_account_id)";
        
        $stmt = $this->db->prepare($sql);
        $rule_id = isset($data['rule_id']) ? $data['rule_id'] : null;
        
        $from_account_id = null;
        $to_account_id = null;

        if ($data['type'] === 'expense') {
            $from_account_id = $data['from_account_id'] ?: null;
        } elseif ($data['type'] === 'income') {
            $to_account_id = $data['from_account_id'] ?: null;
        } elseif ($data['type'] === 'transfer') {
            $from_account_id = $data['from_account_id'] ?: null;
            $to_account_id = $data['to_account_id'] ?: null;
        }

        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':rule_id', $rule_id);
        $stmt->bindParam(':type', $data['type']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':transaction_date', $data['transaction_date']);
        $stmt->bindParam(':from_account_id', $from_account_id);
        $stmt->bindParam(':to_account_id', $to_account_id);
        
        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Transaction Model Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes all transactions linked to a specific recurring rule for a user.
     * We only delete future transactions (from today onwards) to preserve the historical record.
     *
     * @param int $rule_id The ID of the rule.
     * @param int $user_id The ID of the user to ensure ownership.
     * @return bool True on success, false otherwise.
     */
    public function deleteFutureByRuleId($rule_id, $user_id) {
        // We only delete transactions from today (CURDATE()) onwards.
        $sql = "DELETE FROM transactions WHERE rule_id = ? AND user_id = ? AND transaction_date >= CURDATE()";
        $stmt = $this->db->prepare($sql);
        
        try {
            // This doesn't need to check rowCount, just that the query ran successfully.
            return $stmt->execute([$rule_id, $user_id]);
        } catch (\PDOException $e) {
            error_log("Failed to delete transactions by rule ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches daily total expenses for the last 30 days for a user.
     * This data is specifically formatted for use with Chart.js.
     *
     * @param int $user_id The ID of the user.
     * @return array An array containing 'labels' and 'data' for the chart.
     */
    public function getDailyExpensesForChart($user_id) {
        $sql = "SELECT 
                    DATE(transaction_date) as day, 
                    SUM(amount) as total_expenses 
                FROM transactions 
                WHERE 
                    user_id = :user_id 
                    AND type = 'expense' 
                    AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                    AND transaction_date <= CURDATE()
                GROUP BY 
                    DATE(transaction_date) 
                ORDER BY 
                    day ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, \PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll();

        // Now, format the data for Chart.js
        $chartData = [
            'labels' => [],
            'data' => []
        ];

        // Create a template for all 30 days with 0 expenses
        // This ensures the chart always shows a full 30-day period
        $daysTemplate = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = new \DateTime("-$i days");
            $daysTemplate[$date->format('Y-m-d')] = 0;
        }

        // Fill in the template with real data from the database
        foreach ($results as $row) {
            if (isset($daysTemplate[$row['day']])) {
                $daysTemplate[$row['day']] = (float)$row['total_expenses'];
            }
        }

        // Separate the keys (labels) and values (data) for the chart
        foreach ($daysTemplate as $day => $total) {
            // Format the date for display on the chart (e.g., "Jun 26")
            $chartData['labels'][] = (new \DateTime($day))->format('M j');
            $chartData['data'][] = $total;
        }

        return $chartData;
    }

    /**
     * Finds a single transaction by its ID, ensuring the user owns it.
     */
    public function findById($transaction_id, $user_id) {
        $stmt = $this->db->prepare("SELECT * FROM transactions WHERE transaction_id = ? AND user_id = ?");
        $stmt->execute([$transaction_id, $user_id]);
        return $stmt->fetch();
    }
    
    /**
     * Finds all transactions for a user, with pagination.
     */
    public function findAllByUserIdWithPagination($user_id, $offset = 0, $limit = 25) {
        $stmt = $this->db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY transaction_date DESC, created_at DESC LIMIT ?, ?");
        $stmt->bindValue(1, $user_id, \PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, \PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Updates a single transaction's details.
     * Note: This does NOT update the balance, as that requires reversing the old one first.
     */
    public function update($transaction_id, $data) {
        $sql = "UPDATE transactions SET description = :description, amount = :amount, transaction_date = :transaction_date WHERE transaction_id = :transaction_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':transaction_date', $data['transaction_date']);
        $stmt->bindParam(':transaction_id', $transaction_id);
        return $stmt->execute();
    }

    /**
     * Deletes a single transaction by its ID.
     */
    public function delete($transaction_id) {
        $stmt = $this->db->prepare("DELETE FROM transactions WHERE transaction_id = ?");
        return $stmt->execute([$transaction_id]);
    }

    /**
     * Deletes all transactions associated with a specific account for a user.
     * This is crucial for cleaning up data when an account is deleted.
     *
     * @param int $account_id The ID of the account being deleted.
     * @param int $user_id The ID of the user to ensure ownership.
     * @return bool True on success, false otherwise.
     */
    public function deleteByAccountId($account_id, $user_id) {
        $sql = "DELETE FROM transactions WHERE user_id = :user_id AND (from_account_id = :account_id OR to_account_id = :account_id)";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':account_id', $account_id);

        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Failed to delete transactions by account ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes all transactions for a specific user.
     * This is a destructive action, so it should be used with caution.
     */
    public function deleteAllForUser($user_id) {
        $stmt = $this->db->prepare("DELETE FROM transactions WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }

    /**
     * Counts the total number of transactions for a specific user.
     *
     * @param int $user_id The ID of the user.
     * @return int The total number of transactions.
     */
    public function countAllByUserId($user_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    }
}
