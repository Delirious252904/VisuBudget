<?php
// cron/upgrade_user.php
// A command-line tool to upgrade a user to the Premium tier.
// Usage: php upgrade_user.php user@example.com

// Force all errors to be displayed for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- 1. BOOTSTRAP and connect to DB ---
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
// ... (Your existing bootstrap logic to connect to the DB)
try {
    Flight::register('db', 'PDO', 
        array('mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']), 
        function($db) { $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); }
    );
    $db = Flight::db();
} catch (PDOException $e) {
    die("ERROR: Could not connect to the database. " . $e->getMessage() . "\n");
}

// --- 2. SCRIPT LOGIC ---

// Get the email from the command line argument
if ($argc < 2) {
    die("Usage: php upgrade_user.php <email_address>\n");
}
$email_to_upgrade = $argv[1];

if (!filter_var($email_to_upgrade, FILTER_VALIDATE_EMAIL)) {
    die("Error: Invalid email address provided.\n");
}

echo "Attempting to upgrade user: " . $email_to_upgrade . "\n";

try {
    // Find the user by their email address
    $user_stmt = $db->prepare("SELECT user_id, name FROM users WHERE email = ?");
    $user_stmt->execute([$email_to_upgrade]);
    $user = $user_stmt->fetch();

    if (!$user) {
        die("Error: User with that email address not found.\n");
    }

    $user_id = $user['user_id'];
    $user_name = $user['name'];

    // Calculate the expiration date (1 year from now)
    $expiration_date = (new DateTime('now + 1 year'))->format('Y-m-d H:i:s');

    // Update the user's record in the database
    $update_stmt = $db->prepare(
        "UPDATE users SET subscription_tier = 'premium', subscription_expires_at = ? WHERE user_id = ?"
    );
    $success = $update_stmt->execute([$expiration_date, $user_id]);
    
    if ($success && $update_stmt->rowCount() > 0) {
        echo "SUCCESS! User '" . ($user_name ?? $email_to_upgrade) . "' has been upgraded to Premium.\n";
        echo "Subscription expires on: " . $expiration_date . "\n";
    } else {
        echo "Could not update the user. They may already be premium or an error occurred.\n";
    }

} catch (\Exception $e) {
    echo "An error occurred: " . $e->getMessage() . "\n";
}
