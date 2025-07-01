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
}
