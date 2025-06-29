<?php
// app/models/SavingsGoal.php
namespace models;

use PDO;

class SavingsGoal {
    protected $db;

    public function __construct() {
        $this->db = \Flight::db();
    }

    /**
     * Finds all savings goals for a specific user.
     */
    public function findAllByUserId($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM savings_goals WHERE user_id = ? ORDER BY target_date ASC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    /**
     * Finds a single savings goal by its ID and user ID.
     */
    public function findById($goal_id, $user_id) {
        $stmt = $this->db->prepare("SELECT * FROM savings_goals WHERE goal_id = ? AND user_id = ?");
        $stmt->execute([$goal_id, $user_id]);
        return $stmt->fetch();
    }
    
    /**
     * NEW: Finds the top savings goals for a user, sorted by completion percentage.
     * This method is called by the ViewController for the dashboard.
     */
    public function findTopGoalsByUserId($user_id, $limit = 3) {
        $stmt = $this->db->prepare("
            SELECT 
                goal_id,
                goal_name,
                current_amount,
                target_amount,
                (current_amount / target_amount) * 100 AS completion_percentage
            FROM savings_goals 
            WHERE user_id = :user_id AND target_amount > 0
            ORDER BY completion_percentage DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Creates a new savings goal.
     */
    public function create($user_id, $data) {
        $sql = "INSERT INTO savings_goals (user_id, goal_name, target_amount, target_date) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $user_id,
            $data['goal_name'],
            $data['target_amount'],
            $data['target_date']
        ]);
    }

    /**
     * Updates an existing savings goal.
     */
    public function update($goal_id, $data) {
        $sql = "UPDATE savings_goals SET goal_name = ?, target_amount = ?, target_date = ? WHERE goal_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['goal_name'],
            $data['target_amount'],
            $data['target_date'],
            $goal_id
        ]);
    }

    /**
     * Adds a contribution to a savings goal's current amount.
     */
    public function addContribution($goal_id, $amount) {
        $sql = "UPDATE savings_goals SET current_amount = current_amount + ? WHERE goal_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$amount, $goal_id]);
    }

    /**
     * Deletes a savings goal.
     */
    public function delete($goal_id, $user_id) {
        $stmt = $this->db->prepare("DELETE FROM savings_goals WHERE goal_id = ? AND user_id = ?");
        return $stmt->execute([$goal_id, $user_id]);
    }

    /**
     * Gets the sum of all current savings for a user.
     */
    public function getTotalSavingsByUserId($user_id) {
        $stmt = $this->db->prepare("SELECT SUM(current_amount) FROM savings_goals WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn() ?? 0;
    }
}
