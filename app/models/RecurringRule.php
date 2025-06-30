<?php
// app/models/RecurringRule.php
namespace models;
use DateTime;

class RecurringRule {
    protected $db;

    public function __construct() {
        $this->db = \Flight::db();
    }

    public function getMonthlyTotalsForAccount($account_id) {
        $totals = [
            'income' => 0,
            'expenses' => 0,
        ];

        $stmt_income = $this->db->prepare(
            "SELECT SUM(amount) as total FROM recurring_rules WHERE to_account_id = ? AND type = 'income' AND (end_date IS NULL OR end_date >= CURDATE())"
        );
        $stmt_income->execute([$account_id]);
        $result_income = $stmt_income->fetch();
        if ($result_income && $result_income['total']) {
            $totals['income'] = (float) $result_income['total'];
        }

        $stmt_expenses = $this->db->prepare(
            "SELECT SUM(amount) as total FROM recurring_rules WHERE from_account_id = ? AND type = 'expense' AND (end_date IS NULL OR end_date >= CURDATE())"
        );
        $stmt_expenses->execute([$account_id]);
        $result_expenses = $stmt_expenses->fetch();
        if ($result_expenses && $result_expenses['total']) {
            $totals['expenses'] = (float) $result_expenses['total'];
        }
        
        $stmt_transfers = $this->db->prepare(
            "SELECT SUM(amount) as total FROM recurring_rules WHERE from_account_id = ? AND type = 'transfer' AND (end_date IS NULL OR end_date >= CURDATE())"
        );
        $stmt_transfers->execute([$account_id]);
        $result_transfers = $stmt_transfers->fetch();
        if ($result_transfers && $result_transfers['total']) {
            $totals['expenses'] += (float) $result_transfers['total'];
        }

        return $totals;
    }

    public function create($user_id, $description, $amount, $type, $start_date, $frequency, $interval_value, $day_of_week, $occurrences, $from_account_id, $to_account_id, $end_date) {
        $stmt = $this->db->prepare("INSERT INTO recurring_rules (user_id, description, amount, type, start_date, frequency, interval_value, day_of_week, occurrences, from_account_id, to_account_id, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$user_id, $description, $amount, $type, $start_date, $frequency, $interval_value, $day_of_week, $occurrences, $from_account_id, $to_account_id, $end_date]);
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

    public static function calculateNextDueDate(array $rule, $after_date = 'today') {
        try {
            $startDate = new DateTime($rule['start_date']);
            $after = new DateTime($after_date);
            
            if ($rule['end_date'] && new DateTime($rule['end_date']) < $after) {
                return null;
            }
            
            $nextDate = clone $startDate;
            
            while ($nextDate <= $after) {
                 $nextDate = self::calculateNextDateForRule($nextDate, $rule);
                 if ($nextDate === null) return null;
            }

            return $nextDate;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * -- FIX: Changed from private to public so other models can access it. --
     */
    public static function calculateNextDateForRule(DateTime $current, array $rule) {
        $next_date = clone $current;
        $interval_value = (int)$rule['interval_value'] > 0 ? (int)$rule['interval_value'] : 1;
        
        switch ($rule['frequency']) {
            case 'daily':
                return $next_date->modify("+$interval_value day");
            case 'weekly':
                return $next_date->modify("+$interval_value week");
            case 'monthly':
                return $next_date->modify("+$interval_value month");
            case 'yearly':
                return $next_date->modify("+$interval_value year");
        }
        return null;
    }
}
