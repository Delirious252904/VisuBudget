<?php
// cron/send_daily_reminders.php
// This script runs once per day to send out push notification reminders.

echo "Starting daily reminder job for " . date('Y-m-d') . "...\n";

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


// --- 2. LOGIC using the minishlink/web-push library v9 ---
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\Message;

// Get all users who have at least one push subscription.
$users_stmt = $db->query("SELECT DISTINCT user_id FROM push_subscriptions");
$users = $users_stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($users)) {
    echo "No users are subscribed to notifications. Exiting.\n";
    exit;
}

echo "Found " . count($users) . " user(s) with subscriptions.\n";

$notifications_sent = 0;

// Set up the WebPush object with our VAPID keys from the .env file
$auth = [
    'VAPID' => [
        'subject' => $_ENV['VAPID_SUBJECT'],
        'publicKey' => $_ENV['VAPID_PUBLIC_KEY'],
        'privateKey' => $_ENV['VAPID_PRIVATE_KEY'],
    ],
];
$webPush = new WebPush($auth);

foreach ($users as $user_id) {
    $tx_stmt = $db->prepare("SELECT description, type FROM transactions WHERE user_id = ? AND transaction_date = CURDATE()");
    $tx_stmt->execute([$user_id]);
    $todays_transactions = $tx_stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($todays_transactions)) { continue; }

    // ... (message construction logic is the same) ...
    $income_items = [];
    $expense_items = [];
    foreach ($todays_transactions as $tx) {
        if ($tx['type'] === 'income') {
            $income_items[] = "'" . $tx['description'] . "'";
        } else {
            $expense_items[] = "'" . $tx['description'] . "'";
        }
    }
    $body_parts = [];
    if (!empty($income_items)) { $body_parts[] = implode(' & ', $income_items) . " is coming in"; }
    if (!empty($expense_items)) { $body_parts[] = implode(' & ', $expense_items) . " is due"; }
    $notification_body = implode(", and ", $body_parts) . " today.";
    
    // **THE FIX IS HERE**
    // Create the notification payload as a structured JSON object.
    $payload = json_encode([
        'title' => 'VisuBudget Daily Reminder',
        'body' => $notification_body,
        'icon' => '/assets/images/logo.svg',
    ]);
    
    $sub_stmt = $db->prepare("SELECT * FROM push_subscriptions WHERE user_id = ?");
    $sub_stmt->execute([$user_id]);
    $subscriptions = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($subscriptions as $sub) {
        $subscription_object = Subscription::create([
            'endpoint' => $sub['endpoint'],
            'keys' => [
                'p256dh' => $sub['p256dh'],
                'auth' => $sub['auth'],
            ],
        ]);
        $webPush->queueNotification($subscription_object, $payload);
    }
    
    echo "  -> Queued reminders for user ID $user_id: $notification_body\n";
    echo $payload;
}

// Flush all queued notifications.
foreach ($webPush->flush() as $report) {
    $endpoint = $report->getRequest()->getUri()->__toString();
    if (!$report->isSuccess() && $report->isSubscriptionExpired()) {
        echo "  [!] Subscription has expired or is invalid. Deleting from DB: $endpoint\n";
        $delete_stmt = $db->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?");
        $delete_stmt->execute([$endpoint]);
    } elseif ($report->isSuccess()) {
        $notifications_sent++;
    }
}

echo "Finished. Sent $notifications_sent notifications.\n";
