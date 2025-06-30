<?php

/**
 * VisuBudget Recurring Transaction Generator
 *
 * This cron script is designed to be run once per day.
 * It finds all active recurring transaction rules and generates any
 * upcoming transactions that are due within the next 3 months.
 *
 * @author Gemini
 * @version 3.2.0
 */

// Bootstrap the application environment
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/models/RecurringRule.php';
require_once __DIR__ . '/../app/models/Transaction.php';

// Load environment variables from the .env file in the parent directory
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    $log_message = "CRON ERROR: Could not find the .env file. " . $e->getMessage() . "\n";
    error_log($log_message, 3, __DIR__ . '/cron.log');
    echo $log_message;
    exit(1);
}

echo "Starting recurring transaction generation...\n";

// --- 1. Database Connection & Setup ---
try {
    $db_host = $_ENV['DB_HOST'];
    $db_name = $_ENV['DB_NAME'];
    $db_user = $_ENV['DB_USER'];
    $db_pass = $_ENV['DB_PASS'];

    $db = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $log_message = "CRON ERROR: Could not connect to the database. " . $e->getMessage() . "\n";
    error_log($log_message, 3, __DIR__ . '/cron.log');
    echo $log_message;
    exit(1);
} catch (\Throwable $th) {
    $log_message = "CRON ERROR: A required database environment variable is missing in your .env file (DB_HOST, DB_NAME, DB_USER, DB_PASS). " . $th->getMessage() . "\n";
    error_log($log_message, 3, __DIR__ . '/cron.log');
    echo $log_message;
    exit(1);
}


// --- 2. Core Generation Logic ---
$ruleModel = new \models\RecurringRule($db);
$transactionModel = new \models\Transaction($db);

$active_rules = $ruleModel->findAllActive();
echo "Found " . count($active_rules) . " active rules to process.\n";

$transactions_created = 0;
$look_ahead_date = new DateTime('now + 3 months');

foreach ($active_rules as $rule) {
    echo "Processing rule ID: {$rule['rule_id']} '{$rule['description']}'\n";

    // --- FIX: Determine the first date we need to check ---
    $last_date_str = $transactionModel->findLatestDateByRuleId($rule['rule_id']);
    $next_date_to_check = null;

    if ($last_date_str) {
        $last_date = new DateTime($last_date_str);
        $next_date_to_check = \models\RecurringRule::calculateNextDateForRule($last_date, $rule);
        echo "  - Last transaction on {$last_date->format('Y-m-d')}. Next date to check is " . ($next_date_to_check ? $next_date_to_check->format('Y-m-d') : 'N/A') . ".\n";
    } else {
        $next_date_to_check = new DateTime($rule['start_date']);
        echo "  - No previous transactions. First date to check is {$next_date_to_check->format('Y-m-d')}.\n";
    }

    // --- FIX: Unified loop to generate all necessary transactions ---
    while ($next_date_to_check !== null && $next_date_to_check <= $look_ahead_date) {
        // Stop if the rule has a specific end date and we've passed it.
        if ($rule['end_date'] && $next_date_to_check > new DateTime($rule['end_date'])) {
            echo "  - Reached rule end date.\n";
            break;
        }

        // Stop if the rule has a maximum number of occurrences and we've reached it.
        if ($rule['occurrences'] > 0) {
            $occurrence_count = $transactionModel->countByRuleId($rule['rule_id']);
            if ($occurrence_count >= $rule['occurrences']) {
                echo "  - Reached max occurrences ({$rule['occurrences']}).\n";
                break;
            }
        }
        
        // Check for existence before creating to prevent duplicates.
        if (!$transactionModel->existsByRuleIdAndDate($rule['rule_id'], $next_date_to_check->format('Y-m-d'))) {
            $transactionModel->createFromRule($rule, $next_date_to_check->format('Y-m-d'));
            $transactions_created++;
            echo "  -> Generated transaction for " . $next_date_to_check->format('Y-m-d') . "\n";
        }

        // Calculate the *next* potential date for the next loop iteration.
        $next_date_to_check = \models\RecurringRule::calculateNextDateForRule($next_date_to_check, $rule);
    }
    echo "  - Finished processing rule ID: {$rule['rule_id']}.\n";
}

$summary_message = "Finished. Created $transactions_created new transactions.\n";
error_log($summary_message, 3, __DIR__ . '/cron.log');
echo $summary_message;
