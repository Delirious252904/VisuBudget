<?php
namespace models;

use Flight;
use PDO;
use DateTime;

class RecurringRule {
    protected $db;

    public function __construct($db = null) {
        $this->db = $db ?: Flight::db();
    }

    /**
     * FIX: Uses bindValue to ensure LIMIT and OFFSET are treated as integers.
     */
    public function findAllByUserIdWithPagination($user_id, $offset, $limit) {
        $stmt = $this->db->prepare(
            "SELECT * FROM recurring_rules 
             WHERE user_id = :user_id 
             ORDER BY start_date DESC 
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findAllByUserId($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM recurring_rules WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    /**
     * FIX: Added the missing countByUserId method needed for pagination.
     */
    public function countByUserId($user_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM recurring_rules WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * FIX: Renamed and secured to find a rule by its ID and the logged-in user's ID.
     */
    public function findByIdAndUserId($rule_id, $user_id) {
        $stmt = $this->db->prepare("SELECT * FROM recurring_rules WHERE rule_id = ? AND user_id = ?");
        $stmt->execute([$rule_id, $user_id]);
        return $stmt->fetch();
    }

    /**
     * FIX: Updated to accept a data array, matching what the controller sends.
     */
    public function update($rule_id, $data) {
        $stmt = $this->db->prepare(
            "UPDATE recurring_rules SET 
            description = :description, amount = :amount, type = :type, from_account_id = :from_account_id, to_account_id = :to_account_id, 
            frequency = :frequency, interval_unit = :interval_unit, interval_value = :interval_value, start_date = :start_date, 
            end_date = :end_date, occurrences = :occurrences, is_active = :is_active
            WHERE rule_id = :rule_id"
        );
        
        // Use ?? to provide default values for optional fields
        return $stmt->execute([
            ':description' => $data['description'],
            ':amount' => $data['amount'],
            ':type' => $data['type'],
            ':from_account_id' => $data['from_account_id'] ?? null,
            ':to_account_id' => $data['to_account_id'] ?? null,
            ':frequency' => $data['frequency'],
            ':interval_unit' => $data['interval_unit'] ?? 'days',
            ':interval_value' => $data['interval_value'] ?? 1,
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'] ?? null,
            ':occurrences' => $data['occurrences'] ?? null,
            ':is_active' => $data['is_active'] ?? 1,
            ':rule_id' => $rule_id
        ]);
    }

    /**
     * FIX: Updated to be secure by checking the user_id.
     */
    public function delete($rule_id, $user_id) {
        $stmt = $this->db->prepare("DELETE FROM recurring_rules WHERE rule_id = ? AND user_id = ?");
        return $stmt->execute([$rule_id, $user_id]);
    }
    
    // This method is used by the cron script
    public function findAllActive() {
        $stmt = $this->db->prepare("SELECT * FROM recurring_rules WHERE is_active = 1");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // This method is used by the cron script
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
                break;
            case 'yearly':
                $next_date->modify("+{$interval} year");
                break;
            default:
                return null;
        }
        return $next_date;
    }

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
     * FIX: Restored the missing method.
     * Calculates the total monthly recurring income and expenses for a specific account.
     */
    public function getMonthlyTotalsForAccount($account_id) {
        $totals = ['income' => 0, 'expenses' => 0];
        
        // Calculate total recurring income for the account
        $stmt_income = $this->db->prepare("SELECT SUM(amount) as total FROM recurring_rules WHERE to_account_id = ? AND type = 'income' AND is_active = 1");
        $stmt_income->execute([$account_id]);
        if ($row = $stmt_income->fetch()) { $totals['income'] = (float) $row['total']; }

        // Calculate total recurring expenses for the account
        $stmt_expenses = $this->db->prepare("SELECT SUM(amount) as total FROM recurring_rules WHERE from_account_id = ? AND type = 'expense' AND is_active = 1");
        $stmt_expenses->execute([$account_id]);
        if ($row = $stmt_expenses->fetch()) { $totals['expenses'] = (float) $row['total']; }

        return $totals;
    }
}
