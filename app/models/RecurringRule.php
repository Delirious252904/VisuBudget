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
     * FIX: New method to get all active rules for a specific user, optionally filtered by type.
     * This is required by the dashboard logic.
     */
    public function findAllActiveByUserId($user_id, $type = null) {
        $sql = "SELECT * FROM recurring_rules WHERE user_id = ? AND is_active = 1";
        $params = [$user_id];

        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function findAllByUserIdWithPagination($user_id, $offset, $limit) {
        $stmt = $this->db->prepare(
            "SELECT * FROM recurring_rules 
             WHERE user_id = :user_id 
             ORDER BY description ASC 
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByUserId($user_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM recurring_rules WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    }

    public function findByIdAndUserId($rule_id, $user_id) {
        $stmt = $this->db->prepare("SELECT * FROM recurring_rules WHERE rule_id = ? AND user_id = ?");
        $stmt->execute([$rule_id, $user_id]);
        return $stmt->fetch();
    }

    public function update($rule_id, $data) {
        $stmt = $this->db->prepare(
            "UPDATE recurring_rules SET 
            description = :description, amount = :amount, type = :type, from_account_id = :from_account_id, to_account_id = :to_account_id, 
            frequency = :frequency, interval_value = :interval_value, start_date = :start_date, 
            end_date = :end_date, occurrences = :occurrences, is_active = :is_active
            WHERE rule_id = :rule_id"
        );
        
        return $stmt->execute([
            ':description' => $data['description'],
            ':amount' => $data['amount'],
            ':type' => $data['type'],
            ':from_account_id' => $data['from_account_id'] ?: null,
            ':to_account_id' => $data['to_account_id'] ?: null,
            ':frequency' => $data['frequency'],
            ':interval_value' => $data['interval_value'] ?? 1,
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'] ?: null,
            ':occurrences' => $data['occurrences'] ?: null,
            ':is_active' => $data['is_active'] ?? 1,
            ':rule_id' => $rule_id
        ]);
    }

    public function delete($rule_id, $user_id) {
        $stmt = $this->db->prepare("DELETE FROM recurring_rules WHERE rule_id = ? AND user_id = ?");
        return $stmt->execute([$rule_id, $user_id]);
    }
    
    public function findAllActive() {
        $stmt = $this->db->prepare("SELECT * FROM recurring_rules WHERE is_active = 1");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function calculateNextDueDate(array $rule, $after_date_str = 'today') {
        try {
            $current = new DateTime($rule['start_date']);
            $after = new DateTime($after_date_str);

            while ($current < $after) {
                $current = self::calculateNextDateForRule($current, $rule);
                if ($current === null) return null;
            }
            
            return $rule['end_date'] && $current > new DateTime($rule['end_date']) ? null : $current;

        } catch (\Exception $e) {
            return null;
        }
    }

    public static function calculateNextDateForRule(DateTime $current, array $rule): ?DateTime {
        $next_date = clone $current;
        $interval = max(1, (int)($rule['interval_value'] ?? 1));

        switch ($rule['frequency']) {
            case 'daily': $next_date->modify("+{$interval} day"); break;
            case 'weekly': $next_date->modify("+{$interval} week"); break;
            case 'monthly': $next_date->modify("+{$interval} month"); break;
            case 'yearly': $next_date->modify("+{$interval} year"); break;
            default: return null;
        }
        return $next_date;
    }
}
