<?php
// app/models/SavingsGoal.php
namespace models;

class SavingsGoal {
    protected $db;

    public function __construct() {
        $this->db = \Flight::db();
    }

    /**
     * Finds all savings goals for a specific user.
     */
    public function findAllByUserId($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM savings_goals WHERE user_id = ? ORDER BY created_at ASC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    /**
     * Finds a single savings goal by its ID, ensuring user ownership.
     */
    public function findById($goal_id, $user_id) {
        $stmt = $this->db->prepare("SELECT * FROM savings_goals WHERE goal_id = ? AND user_id = ?");
        $stmt->execute([$goal_id, $user_id]);
        return $stmt->fetch();
    }

    /**
     * Creates a new savings goal.
     */
    public function create($user_id, $data) {
        $sql = "INSERT INTO savings_goals (user_id, goal_name, target_amount) VALUES (:user_id, :goal_name, :target_amount)";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':goal_name', $data['goal_name']);
        $stmt->bindParam(':target_amount', $data['target_amount']);
        
        return $stmt->execute();
    }

    /**
     * Updates an existing savings goal.
     */
    public function update($goal_id, $data) {
        $sql = "UPDATE savings_goals SET goal_name = :goal_name, target_amount = :target_amount WHERE goal_id = :goal_id";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':goal_name', $data['goal_name']);
        $stmt->bindParam(':target_amount', $data['target_amount']);
        $stmt->bindParam(':goal_id', $goal_id);

        return $stmt->execute();
    }
    
    /**
     * Deletes a savings goal.
     */
    public function delete($goal_id, $user_id) {
        $stmt = $this->db->prepare("DELETE FROM savings_goals WHERE goal_id = ? AND user_id = ?");
        $stmt->execute([$goal_id, $user_id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Deletes all savings goals for a specific user.
     * WARNING: This is a destructive action. We should add checks to prevent deletion
     */
    public function deleteAllForUser($user_id) {
        $stmt = $this->db->prepare("DELETE FROM savings_goals WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }
    
    /**
     * Adds a specific amount to a goal's current balance.
     */
    public function addAmountToGoal($goal_id, $amount) {
        $sql = "UPDATE savings_goals SET current_amount = current_amount + :amount WHERE goal_id = :goal_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':goal_id', $goal_id);
        return $stmt->execute();
    }

    /**
     * Resets a goal's current amount to zero.
     */
    public function resetGoal($goal_id) {
        $sql = "UPDATE savings_goals SET current_amount = 0 WHERE goal_id = :goal_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':goal_id', $goal_id);
        return $stmt->execute();
    }
}