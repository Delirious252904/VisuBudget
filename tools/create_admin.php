<?php
// create_admin.php
// A one-time script to create the initial admin user.

require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $db = new PDO(
        'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Connection Failed: " . $e->getMessage());
}

$admin_email = 'admin@visubudget.co.uk';
$admin_password = 'fleepFarp630';
$password_hash = password_hash($admin_password, PASSWORD_DEFAULT);

// Check if admin already exists
$stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->execute([$admin_email]);
if ($stmt->fetch()) {
    die("Admin user already exists.\n");
}

// Create the admin user with the 'admin' role
$sql = "INSERT INTO users (role, name, email, password_hash, is_verified, status, has_completed_setup)
        VALUES ('admin', 'VisuBudget Admin', ?, ?, 1, 'active', 1)";
$stmt = $db->prepare($sql);
$stmt->execute([$admin_email, $password_hash]);

echo "SUCCESS: Admin user created!\n";
echo "Email: $admin_email\n";
echo "Password: $admin_password\n";
