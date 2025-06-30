<?php
// cron/generate_recurring.php
// This script is designed to be run automatically by a cron job once per day.

// Autoloader and Configuration
require_once __DIR__ . '/../private/vendor/autoload.php';

// --- FIX: We now include the necessary models directly ---
// This ensures we use the correct, centralized logic.
spl_autoload_register(function ($class_name) {
    $base_dir = __DIR__ . '/../private/app/';
    $file = $base_dir . str_replace('\\', '/', $class_name) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

echo "Starting recurring transaction generation...\n";

// --- 1. BOOTSTRAP a minimal environment ---
$db_host = 'mysql-200-139.mysql.prositehosting.net';
$db_name = 'visubudget';
$db_user = 'visubudget';
$db_pass = 'vbuser7623';

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Pass the database connection to the Flight registry so models can use it.
    Flight::register('db', function() use ($db){
        return $db;
    });

} catch (PDOException $e) {
    error_log("ERROR: Could not connect to the database. " . $e->getMessage());
    exit(1);
}

// --- 2. LOGIC ---
$ruleModel = new \models\RecurringRule();
$active_rules = $ruleModel->findAllActive(); // A new, cleaner method in the model

echo "Found " . count($active_rules) . " active rules to process.\n";

$transactions_created = 0;
$today = new DateTime('today');

foreach ($active_rules as $rule) {
    $look_ahead_date = new DateTime('now + 3 months');
    
    $last_gen_stmt = $db->prepare("SELECT MAX(transaction_date) as last_date FROM transactions WHERE rule_id = ?");
    $last_gen_stmt->execute([$rule['rule_id']]);
    $last_date_str = $last_gen_stmt->fetchColumn();
    
    $next_date = $last_date_str ? new DateTime($last_date_str) : new DateTime($rule['start_date']);
    
    if($last_date_str === null && $next_date < $today) {
        // If no transactions exist and start date is in the past, start generating from today
    } else {
        // Otherwise, start from the last generated date
        $next_date = new DateTime($last_date_str);
    }


    while ($next_date <= $look_ahead_date) {
        
        // --- FIX: Use the robust, centralized method from the model ---
        $next_date = \models\RecurringRule::calculateNextDateForRule($next_date, $rule);

        if ($next_date === null || $next_date > $look_ahead_date) {
            break;
        }

        // --- CHECK END CONDITIONS ---
        if ($rule['end_date'] && $next_date > new DateTime($rule['end_date'])) {
            break; 
        }
        
        $occurrences_count_stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE rule_id = ?");
        $occurrences_count_stmt->execute([$rule['rule_id']]);
        if ($rule['occurrences'] > 0 && (int)$occurrences_count_stmt->fetchColumn() >= $rule['occurrences']) {
            break;
        }
        
        $exists_stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE rule_id = ? AND transaction_date = ?");
        $exists_stmt->execute([$rule['rule_id'], $next_date->format('Y-m-d')]);

        if ($exists_stmt->fetchColumn() == 0) {
             // --- FIX: Removed the non-existent 'is_recurring' column ---
             $insert_stmt = $db->prepare(
                "INSERT INTO transactions (user_id, rule_id, description, amount, type, from_account_id, to_account_id, transaction_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $insert_stmt->execute([
                $rule['user_id'], $rule['rule_id'], $rule['description'], $rule['amount'], $rule['type'],
                $rule['from_account_id'], $rule['to_account_id'], $next_date->format('Y-m-d')
            ]);
            $transactions_created++;
            echo "  -> Generated transaction for '{$rule['description']}' on " . $next_date->format('Y-m-d') . "\n";
        }
    }
}

echo "Finished. Created $transactions_created new transactions.\n";
// The local calculate_next_date_from_rule function has been removed.
