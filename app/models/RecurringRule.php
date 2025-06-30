<?php
// app/models/RecurringRule.php
namespace models;
use PDO;
use DateTime;

class RecurringRule {
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

    // --- FIX: The main calculation logic has been significantly improved ---
    
    /**
     * Calculates the next occurrence of a rule AFTER a given date.
     *
     * @param array $rule The recurring rule data.
     * @param string $after_date_str The date to calculate from (defaults to 'today').
     * @return DateTime|null The next due date, or null if none.
     */
    public static function calculateNextDueDate(array $rule, $after_date_str = 'today') {
        try {
            $current = new DateTime($rule['start_date']);
            $after = new DateTime($after_date_str);

            // Fast-forward until the date is on or after our start point
            while ($current < $after) {
                $current = self::calculateNextDateForRule($current, $rule);
                if ($current === null) return null;
            }

            // If the rule's start date is in the future, it's the next due date.
            if ($current > $after) {
                 return $rule['end_date'] && $current > new DateTime($rule['end_date']) ? null : $current;
            }

            // If we land exactly on the 'after' date, we need the *next* one
            $next = self::calculateNextDateForRule($current, $rule);
            return $rule['end_date'] && $next > new DateTime($rule['end_date']) ? null : $next;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Calculates the single next date from a given current date based on the rule's frequency.
     * This is now the core, reliable function for date progression.
     */
    public static function calculateNextDateForRule(DateTime $current, array $rule): ?DateTime {
        $next_date = clone $current;
        $interval = max(1, (int)($rule['interval_value'] ?? 1));

        switch ($rule['frequency']) {
            case 'daily':
                $next_date->modify("+{$interval} day");
                break;
            case 'weekly':
                $next_date->modify("+{$interval} week");
                break;
            case 'monthly':
                $next_date->modify("+{$interval} month");
                // If a specific day is set, adjust to that day of the new month.
                if (!empty($rule['day_of_month'])) {
                    $year = $next_date->format('Y');
                    $month = $next_date->format('m');
                    $day = $rule['day_of_month'];
                    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int)$month, (int)$year);
                    $actual_day = min($day, $daysInMonth);
                    $next_date->setDate((int)$year, (int)$month, $actual_day);
                }
                break;
            case 'yearly':
                $next_date->modify("+{$interval} year");
                break;
            default:
                return null;
        }
        return $next_date;
    }

    // --- All other methods remain the same ---
    
    public function getMonthlyTotalsForAccount($account_id) {
        // This method remains correct and is unchanged.
        $totals = ['income' => 0, 'expenses' => 0];
        $stmt_income = $this->db->prepare("SELECT SUM(amount) as total FROM recurring_rules WHERE to_account_id = ? AND type = 'income' AND (end_date IS NULL OR end_date >= CURDATE())");
        $stmt_income->execute([$account_id]);
        if ($row = $stmt_income->fetch()) { $totals['income'] = (float) $row['total']; }
        $stmt_expenses = $this->db->prepare("SELECT SUM(amount) as total FROM recurring_rules WHERE from_account_id = ? AND type IN ('expense', 'transfer') AND (end_date IS NULL OR end_date >= CURDATE())");
        $stmt_expenses->execute([$account_id]);
        if ($row = $stmt_expenses->fetch()) { $totals['expenses'] = (float) $row['total']; }
        return $totals;
    }

   /**
     * -- REWRITTEN & FIXED --
     * Creates a new recurring rule from a data array.
     */
    public function create($data) {
        $sql = "INSERT INTO recurring_rules 
                    (user_id, description, amount, type, start_date, frequency, interval_value, day_of_week, day_of_month, occurrences, from_account_id, to_account_id, end_date) 
                VALUES 
                    (:user_id, :description, :amount, :type, :start_date, :frequency, :interval_value, :day_of_week, :day_of_month, :occurrences, :from_account_id, :to_account_id, :end_date)";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':user_id' => $data['user_id'],
            ':description' => $data['description'],
            ':amount' => $data['amount'],
            ':type' => $data['type'],
            ':start_date' => $data['start_date'],
            ':frequency' => $data['frequency'],
            ':interval_value' => $data['interval_value'] ?? 1,
            ':day_of_week' => $data['day_of_week'] ?? null,
            ':day_of_month' => $data['day_of_month'] ?? null,
            ':occurrences' => $data['occurrences'] ?? null,
            ':from_account_id' => $data['from_account_id'] ?? null,
            ':to_account_id' => $data['to_account_id'] ?? null,
            ':end_date' => $data['end_date'] ?? null
        ]);
    }
    
    public function findAllByUserId($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM recurring_rules WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
    
    public function findById($rule_id) {
        $stmt = $this->db->prepare("SELECT * FROM recurring_rules WHERE rule_id = ?");
        $stmt->execute([$rule_id]);
        return $stmt->fetch();
    }

    public function update($rule_id, $description, $amount, $type, $start_date, $frequency, $interval_value, $day_of_week, $occurrences, $from_account_id, $to_account_id, $end_date) {
        $stmt = $this->db->prepare("UPDATE recurring_rules SET description = ?, amount = ?, type = ?, start_date = ?, frequency = ?, interval_value = ?, day_of_week = ?, occurrences = ?, from_account_id = ?, to_account_id = ?, end_date = ? WHERE rule_id = ?");
        return $stmt->execute([$description, $amount, $type, $start_date, $frequency, $interval_value, $day_of_week, $occurrences, $from_account_id, $to_account_id, $end_date, $rule_id]);
    }
    
    public function delete($rule_id) {
        $stmt = $this->db->prepare("DELETE FROM recurring_rules WHERE rule_id = ?");
        return $stmt->execute([$rule_id]);
    }
    
    public function countAllByUserId($user_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM recurring_rules WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }

    public function findAllByUserIdWithPagination($user_id, $offset, $limit) {
        $stmt = $this->db->prepare("SELECT * FROM recurring_rules WHERE user_id = ? ORDER BY start_date DESC LIMIT ?, ?");
        $stmt->execute([$user_id, $offset, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Finds all active recurring rules in the system.
     */
    public function findAllActive() {
        $stmt = $this->db->prepare("SELECT * FROM recurring_rules WHERE is_active = 1");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
