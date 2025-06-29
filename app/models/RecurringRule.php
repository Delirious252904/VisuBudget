<?php
// app/models/RecurringRule.php
namespace models;

class RecurringRule {

    protected $db;

    public function __construct() {
        $this->db = \Flight::db();
    }

    /**
     * Creates a new recurring rule and saves it to the database.
     *
     * @param int $user_id The ID of the user.
     * @param array $data The data from the transaction form.
     * @return int|false The ID of the new rule on success, false on failure.
     */
     public function create($user_id, $data) {
        $sql = "INSERT INTO recurring_rules (user_id, description, amount, type, from_account_id, to_account_id, start_date, frequency, `interval_value`, day_of_week, end_date, occurrences) 
                VALUES (:user_id, :description, :amount, :type, :from_account_id, :to_account_id, :start_date, :frequency, :interval_value, :day_of_week, :end_date, :occurrences)";
        
        $stmt = $this->db->prepare($sql);

        // We ensure the date is in the correct 'Y-m-d' format before saving.
        // This removes any ambiguity between UK (d/m/Y) and US (m/d/Y) formats.
        try {
            $startDate = (new \DateTime($data['transaction_date']))->format('Y-m-d');
        } catch (\Exception $e) {
            // Handle cases where the date is completely invalid
            error_log("Invalid start date format for recurring rule: " . $data['transaction_date']);
            return false;
        }

        // --- Prepare other data for insertion (logic is the same as before) ---
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
        $day_of_week = ($data['frequency'] === 'weekly' && !empty($data['day_of_week'])) ? $data['day_of_week'] : null;
        $end_date = !empty($data['end_date']) ? (new \DateTime($data['end_date']))->format('Y-m-d') : null;
        $occurrences = !empty($data['occurrences']) ? $data['occurrences'] : null;
        $interval_value = !empty($data['interval_value']) ? $data['interval_value'] : 1;

        // --- Bind all the parameters ---
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':type', $data['type']);
        $stmt->bindParam(':from_account_id', $from_account_id);
        $stmt->bindParam(':to_account_id', $to_account_id);
        $stmt->bindParam(':start_date', $startDate); // Bind the sanitized date
        $stmt->bindParam(':frequency', $data['frequency']);
        $stmt->bindParam(':interval_value', $interval_value);
        $stmt->bindParam(':day_of_week', $day_of_week);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->bindParam(':occurrences', $occurrences);

        try {
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (\PDOException $e) {
            error_log("RecurringRule Model Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Finds a single recurring rule by its ID, ensuring the user owns it.
     * @param int $rule_id The rule's ID.
     * @param int $user_id The user's ID.
     * @return mixed The rule data if found, otherwise false.
     */
    public function findById($rule_id, $user_id) {
        $stmt = $this->db->prepare("SELECT * FROM recurring_rules WHERE rule_id = ? AND user_id = ?");
        $stmt->execute([$rule_id, $user_id]);
        return $stmt->fetch();
    }
    
    /**
     * Updates an existing recurring rule in the database.
     * @param int $rule_id The ID of the rule to update.
     * @param array $data The new data from the form.
     * @return bool True on success, false otherwise.
     */
    public function update($rule_id, $data) {
        $sql = "UPDATE recurring_rules SET 
                    description = :description, 
                    amount = :amount, 
                    type = :type, 
                    from_account_id = :from_account_id, 
                    to_account_id = :to_account_id, 
                    start_date = :start_date, 
                    frequency = :frequency, 
                    `interval_value` = :interval_value, 
                    day_of_week = :day_of_week, 
                    end_date = :end_date, 
                    occurrences = :occurrences 
                WHERE rule_id = :rule_id";
        
        $stmt = $this->db->prepare($sql);

        // Sanitize all data just like in the create method
        $startDate = (new \DateTime($data['transaction_date']))->format('Y-m-d');
        $from_account_id = null;
        $to_account_id = null;
        if ($data['type'] === 'expense') { $from_account_id = $data['from_account_id'] ?: null; } 
        elseif ($data['type'] === 'income') { $to_account_id = $data['from_account_id'] ?: null; } 
        elseif ($data['type'] === 'transfer') { $from_account_id = $data['from_account_id'] ?: null; $to_account_id = $data['to_account_id'] ?: null; }
        $day_of_week = ($data['frequency'] === 'weekly' && !empty($data['day_of_week'])) ? $data['day_of_week'] : null;
        $end_date = !empty($data['end_date']) ? (new \DateTime($data['end_date']))->format('Y-m-d') : null;
        $occurrences = !empty($data['occurrences']) ? $data['occurrences'] : null;
        $interval_value = !empty($data['interval_value']) ? $data['interval_value'] : 1;

        // Bind all parameters
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':type', $data['type']);
        $stmt->bindParam(':from_account_id', $from_account_id);
        $stmt->bindParam(':to_account_id', $to_account_id);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':frequency', $data['frequency']);
        $stmt->bindParam(':interval_value', $interval_value);
        $stmt->bindParam(':day_of_week', $day_of_week);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->bindParam(':occurrences', $occurrences);
        $stmt->bindParam(':rule_id', $rule_id);
        
        return $stmt->execute();
    }

    /**
     * Deletes a recurring rule from the database.
     * Note: This only deletes the rule itself, not the transactions it has already generated.
     * @param int $rule_id The ID of the rule to delete.
     * @param int $user_id The user's ID to ensure they own the rule.
     * @return bool True if a row was deleted, false otherwise.
     */
    public function delete($rule_id, $user_id) {
        $stmt = $this->db->prepare("DELETE FROM recurring_rules WHERE rule_id = ? AND user_id = ?");
        $stmt->execute([$rule_id, $user_id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * A public, reusable function to calculate the next due date for a rule.
     *
     * @param array $rule The rule data from the database.
     * @return \DateTime|null The next due date object, or null.
     */
    public static function calculateNextDueDate(array $rule) {
        $startDate = new \DateTime($rule['start_date']);
        $today = new \DateTime('today');
        
        // If the rule starts in the future, its start date is the next due date.
        if ($startDate > $today) {
            return $startDate;
        }

        // The rule started in the past. We need to "walk" it forward.
        $nextDate = clone $startDate;
        $interval = (int)$rule['interval_value'];
        $lookahead = new \DateTime('+2 years'); // Safety break to prevent infinite loops

        while ($nextDate <= $today && $nextDate < $lookahead) {
            if ($rule['frequency'] === 'weekly') {
                $nextDate->modify("+$interval week");
            } elseif ($rule['frequency'] === 'monthly') {
                $nextDate->modify("+$interval month");
            } elseif ($rule['frequency'] === 'yearly') {
                $nextDate->modify("+$interval year");
            } else {
                return null;
            }
        }
        
        // Final checks to ensure the calculated date is valid.
        if ($nextDate > $lookahead || ($rule['end_date'] && $nextDate > new \DateTime($rule['end_date']))) {
            return null;
        }

        return $nextDate;
    }

    /**
     * Deletes all recurring rules for a specific user.
     * This is a destructive action, so it should be used with caution.
     */
    public function deleteAllForUser($user_id) {
        $stmt = $this->db->prepare("DELETE FROM recurring_rules WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }

    /**
     * Counts the total number of recurring rules for a user.
     */
    public function countAllByUserId($user_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM recurring_rules WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Finds all recurring rules for a user, with pagination.
     */
    public function findAllByUserIdWithPagination($user_id, $offset = 0, $limit = 25) {
        $stmt = $this->db->prepare("SELECT * FROM recurring_rules WHERE user_id = ? ORDER BY start_date ASC LIMIT ?, ?");
        $stmt->bindValue(1, $user_id, \PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, \PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Finds all recurring rules for a user
     */
    public function findAllByUserId($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM recurring_rules WHERE user_id = ? ORDER BY start_date ASC");
        $stmt->bindValue(1, $user_id, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * NEW: Calculates the sum of monthly recurring income and expenses for a given account.
     * This helps users see their regular cash flow at a glance.
     *
     * @param int $account_id The ID of the account.
     * @return array An associative array with 'income' and 'expenses' totals.
     */
    public function getMonthlyTotalsForAccount($account_id) {
        $totals = [
            'income' => 0,
            'expenses' => 0,
        ];

        // --- Calculate total recurring INCOME for this account ---
        // Sums rules where the 'to_account_id' matches this account.
        $stmt_income = $this->db->prepare(
            "SELECT SUM(amount) as total 
             FROM recurring_rules 
             WHERE to_account_id = ? 
             AND type = 'income' 
             AND (end_date IS NULL OR end_date >= CURDATE())"
        );
        $stmt_income->execute([$account_id]);
        $result_income = $stmt_income->fetch();
        if ($result_income && $result_income['total']) {
            $totals['income'] = (float) $result_income['total'];
        }

        // --- Calculate total recurring EXPENSES for this account ---
        // Sums rules where the 'from_account_id' matches this account.
        $stmt_expenses = $this->db->prepare(
            "SELECT SUM(amount) as total 
             FROM recurring_rules 
             WHERE from_account_id = ? 
             AND type = 'expense' 
             AND (end_date IS NULL OR end_date >= CURDATE())"
        );
        $stmt_expenses->execute([$account_id]);
        $result_expenses = $stmt_expenses->fetch();
        if ($result_expenses && $result_expenses['total']) {
            $totals['expenses'] = (float) $result_expenses['total'];
        }
        
        // --- Calculate total recurring TRANSFERS out of this account ---
        $stmt_transfers = $this->db->prepare(
            "SELECT SUM(amount) as total 
             FROM recurring_rules 
             WHERE from_account_id = ? 
             AND type = 'transfer' 
             AND (end_date IS NULL OR end_date >= CURDATE())"
        );
        $stmt_transfers->execute([$account_id]);
        $result_transfers = $stmt_transfers->fetch();
        if ($result_transfers && $result_transfers['total']) {
            $totals['expenses'] += (float) $result_transfers['total'];
        }


        return $totals;
    }
}
