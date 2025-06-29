<?php
// cron/seed_demo_account.php
// This script creates a demo user with pre-filled data for screenshots and testing.

// This is a debugging tool to make sure all errors are displayed.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting demo account seeder...\n";

// --- 1. BOOTSTRAP and connect to DB ---
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
spl_autoload_register(function ($class_name) {
    $base_dir = __DIR__ . '/../app/';
    $file = $base_dir . str_replace('\\', '/', $class_name) . '.php';
    if (file_exists($file)) { require $file; }
});

try {
    Flight::register('db', 'PDO', 
        array('mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']), 
        function($db) { $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); }
    );
    $db = Flight::db();
} catch (PDOException $e) {
    die("ERROR: Could not connect to the database. " . $e->getMessage() . "\n");
}


// --- 2. SEEDING LOGIC ---
$db->beginTransaction();
try {
    // -- Create the Demo User --
    $demo_email = 'demo@visubudget.com';
    $demo_password = 'password123';
    $password_hash = password_hash($demo_password, PASSWORD_DEFAULT);

    // Check if demo user already exists
    $user_stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
    $user_stmt->execute([$demo_email]);
    $demo_user_id = $user_stmt->fetchColumn();

    if ($demo_user_id) {
        echo "Demo user already exists. Clearing old demo data...\n";
        // Clean up old data for this user
        $db->exec("DELETE FROM transactions WHERE user_id = $demo_user_id");
        $db->exec("DELETE FROM recurring_rules WHERE user_id = $demo_user_id");
        $db->exec("DELETE FROM accounts WHERE user_id = $demo_user_id");
    } else {
        $sql = "INSERT INTO users (name, email, password_hash, is_verified, status, has_completed_setup, savings_percentage)
                VALUES ('Demo User', ?, ?, 1, 'active', 1, 10.0)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$demo_email, $password_hash]);
        $demo_user_id = $db->lastInsertId();
        echo "Created new demo user with ID: $demo_user_id\n";
    }

    // -- Create Accounts --
    $account1_sql = "INSERT INTO accounts (user_id, account_name, account_type, current_balance) VALUES (?, 'Current Account', 'Checking', '1450.75')";
    $db->prepare($account1_sql)->execute([$demo_user_id]);
    $account1_id = $db->lastInsertId();
    echo "Created 'Current Account' with ID: $account1_id\n";

    $account2_sql = "INSERT INTO accounts (user_id, account_name, account_type, current_balance) VALUES (?, 'Savings Pot', 'Savings', '8200.00')";
    $db->prepare($account2_sql)->execute([$demo_user_id]);
    $account2_id = $db->lastInsertId();
    echo "Created 'Savings Pot' with ID: $account2_id\n";

    // -- Create Recurring Rules --
    // Salary (Income)
    $rule1_sql = "INSERT INTO recurring_rules (user_id, description, amount, type, to_account_id, start_date, frequency, interval_value) VALUES (?, 'Salary', '2100.00', 'income', ?, '2025-06-25', 'monthly', 1)";
    $db->prepare($rule1_sql)->execute([$demo_user_id, $account1_id]);
    $rule1_id = $db->lastInsertId();
    echo "Created 'Salary' rule.\n";

    // Rent (Expense)
    $rule2_sql = "INSERT INTO recurring_rules (user_id, description, amount, type, from_account_id, start_date, frequency, interval_value) VALUES (?, 'Rent', '850.00', 'expense', ?, '2025-07-01', 'monthly', 1)";
    $db->prepare($rule2_sql)->execute([$demo_user_id, $account1_id]);
    echo "Created 'Rent' rule.\n";

    // Council Tax (Expense)
    $rule3_sql = "INSERT INTO recurring_rules (user_id, description, amount, type, from_account_id, start_date, frequency, interval_value) VALUES (?, 'Council Tax', '145.50', 'expense', ?, '2025-07-01', 'monthly', 1)";
    $db->prepare($rule3_sql)->execute([$demo_user_id, $account1_id]);
    echo "Created 'Council Tax' rule.\n";

    // -- Create some historical transactions for the chart --
    $tx1_sql = "INSERT INTO transactions (user_id, description, amount, type, from_account_id, transaction_date) VALUES (?, 'Groceries', '85.40', 'expense', ?, DATE_SUB(CURDATE(), INTERVAL 5 DAY))";
    $db->prepare($tx1_sql)->execute([$demo_user_id, $account1_id]);
    
    $tx2_sql = "INSERT INTO transactions (user_id, description, amount, type, from_account_id, transaction_date) VALUES (?, 'Petrol', '55.00', 'expense', ?, DATE_SUB(CURDATE(), INTERVAL 10 DAY))";
    $db->prepare($tx2_sql)->execute([$demo_user_id, $account1_id]);

    // -- Generate the first instance of the recurring salary --
    $tx_salary_sql = "INSERT INTO transactions (user_id, rule_id, description, amount, type, to_account_id, transaction_date) VALUES (?, ?, 'Salary', '2100.00', 'income', ?, '2025-06-25')";
    $db->prepare($tx_salary_sql)->execute([$demo_user_id, $rule1_id, $account1_id]);
    echo "Created historical transactions.\n";


    $db->commit();
    echo "SUCCESS: Demo account has been seeded successfully!\n";
    echo "Email: $demo_email\n";
    echo "Password: $demo_password\n";

} catch (\Exception $e) {
    $db->rollBack();
    echo "ERROR: Seeding failed. Operation was rolled back. " . $e->getMessage() . "\n";
}
