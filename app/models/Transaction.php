<?php
// app/models/Transaction.php
namespace models;

use models\RecurringRule;
use Flight;
use PDO;
use DateTime;

class Transaction {
    protected $db;

    /**
     * The constructor now accepts an optional PDO database connection object.
     * This allows the model to be used both within the Flight framework and in standalone scripts.
     */
    public function __construct($db = null)
    {
        // If a DB connection is passed, use it. Otherwise, get it from the Flight registry.
        $this->db = $db ?: \Flight::db();
    }

    public function findNextIncomeEvent($user_id) {
        $potentialEvents = [];
        $today = new DateTime('today');

        // 1. Find nearest future income from generated transactions
        $stmt_trans = $this->db->prepare(
            "SELECT transaction_date, amount FROM transactions 
             WHERE user_id = :user_id AND type = 'income' AND transaction_date >= :today 
             ORDER BY transaction_date ASC LIMIT 1"
        );
        $stmt_trans->execute([':user_id' => $user_id, ':today' => $today->format('Y-m-d')]);
        $result = $stmt_trans->fetch();
        if ($result) {
            $potentialEvents[] = ['date' => new DateTime($result['transaction_date']), 'amount' => $result['amount']];
        }

        // 2. Find next income from recurring rules
        $ruleModel = new RecurringRule();
        $rules = $ruleModel->findAllByUserId($user_id);
        foreach ($rules as $rule) {
            if ($rule['type'] === 'income') {
                $nextDueDate = RecurringRule::calculateNextDueDate($rule);
                if ($nextDueDate && $nextDueDate >= $today) {
                     $potentialEvents[] = ['date' => $nextDueDate, 'amount' => $rule['amount']];
                }
            }
        }

        if (empty($potentialEvents)) return null;

        usort($potentialEvents, function($a, $b) { return $a['date'] <=> $b['date']; });
        
        return [
            'date' => $potentialEvents[0]['date']->format('Y-m-d'),
            'amount' => $potentialEvents[0]['amount']
        ];
    }
    
    /**
     * -- REWRITTEN AND FIXED --
     * Calculates the total upcoming expenses by combining generated transactions and projecting future recurring rules.
     */
    public function getExpensesTotalBetweenDates($user_id, $startDateStr, $endDateStr) {
        $totalExpenses = 0;
        $start = new DateTime($startDateStr);
        $end = new DateTime($endDateStr);

        // 1. Get sum of already generated future expenses and transfers in the date range
        $sql_generated = "SELECT SUM(amount) as total 
                          FROM transactions 
                          WHERE user_id = :user_id 
                            AND (type = 'expense' OR type = 'transfer')
                            AND transaction_date > :start_date 
                            AND transaction_date < :end_date";
    
        $stmt_generated = $this->db->prepare($sql_generated);
        $stmt_generated->execute([
            ':user_id' => $user_id,
            ':start_date' => $startDateStr,
            ':end_date' => $endDateStr
        ]);
        $totalExpenses += (float) $stmt_generated->fetchColumn();
    
        // 2. Project future expenses from recurring rules that have NOT been generated yet
        $ruleModel = new RecurringRule();
        $rules = $ruleModel->findAllByUserId($user_id);

        foreach ($rules as $rule) {
            if ($rule['type'] !== 'expense' && $rule['type'] !== 'transfer') {
                continue;
            }

            // Start checking from the rule's start date
            $nextDate = new DateTime($rule['start_date']);

            // Loop through all possible future occurrences of the rule
            while ($nextDate <= $end) {
                // Only consider dates that fall within our calculation window (today -> next income)
                if ($nextDate > $start) {
                    // Check if a transaction for this rule on this specific date has ALREADY been created by the cron job.
                    $exists_stmt = $this->db->prepare("SELECT COUNT(*) FROM transactions WHERE rule_id = ? AND transaction_date = ?");
                    $exists_stmt->execute([$rule['rule_id'], $nextDate->format('Y-m-d')]);
                    
                    // If no transaction exists for this date, it's a projected expense we need to count.
                    if ($exists_stmt->fetchColumn() == 0) {
                        $totalExpenses += (float)$rule['amount'];
                    }
                }

                // Correctly calculate the next date for the next loop iteration
                $nextDate = RecurringRule::calculateNextDateForRule($nextDate, $rule);
                if ($nextDate === null) {
                    break; // Stop if rule has no more valid dates
                }
            }
        }
    
        return $totalExpenses;
    }

    /**
     * -- FIXED --
     * Fetches upcoming ONE-OFF transactions (not linked to a rule) for the dashboard.
     */
    public function findUpcomingByUserId($user_id, $limit = 5) {
        $stmt = $this->db->prepare("
            SELECT transaction_id, description, transaction_date, amount, type
            FROM transactions
            WHERE user_id = :user_id 
              AND transaction_date >= CURDATE()
              AND rule_id IS NULL
            ORDER BY transaction_date DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
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
     * -- FIXED --
     * Fetches all transactions for a user, paginated, with correct account names.
     */
    public function findAllByUserIdWithPagination($user_id, $offset, $limit)
    {
        $stmt = $this->db->prepare("
            SELECT t.*, a_from.account_name as from_account_name, a_to.account_name as to_account_name
            FROM transactions t
            LEFT JOIN accounts a_from ON t.from_account_id = a_from.account_id
            LEFT JOIN accounts a_to ON t.to_account_id = a_to.account_id
            WHERE t.user_id = :user_id AND t.transaction_date >= CURDATE()  -- Exclude previous transactions
            ORDER BY t.transaction_date ASC, t.transaction_id DESC, t.type DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Gets the sum of future income and expenses for a specific account.
     * This is used for the Accounts list page.
     */
    public function getFutureTotalsForAccount($account_id) {
        $stmt = $this->db->prepare("
            SELECT 
                SUM(CASE WHEN type = 'income' AND to_account_id = :account_id THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' AND from_account_id = :account_id THEN amount ELSE 0 END) as expense
            FROM transactions
            WHERE (from_account_id = :account_id OR to_account_id = :account_id) AND transaction_date > CURDATE()
        ");
        $stmt->execute([':account_id' => $account_id]);
        $totals = $stmt->fetch();
        return [
            'income' => $totals['income'] ?? 0.00,
            'expense' => $totals['expense'] ?? 0.00
        ];
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
}
