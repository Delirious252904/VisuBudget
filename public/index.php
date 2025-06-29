<?php
/**
 * VisuBudget - Front Controller
 */
session_start();

// --- 1. BOOTSTRAPPING & CONFIGURATION ---
require '../private/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable('../private');
$dotenv->load();

require '../private/app/config.php';

spl_autoload_register(function ($class_name) {
    $base_dir = '../private/app/';
    $file = $base_dir . str_replace('\\', '/', $class_name) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// --- 2. FLIGHT SETUP ---
Flight::set('flight.views.path', '../private/app/views');
Flight::register('db', 'PDO', 
    array(
        'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'], 
        $_ENV['DB_USER'], 
        $_ENV['DB_PASS']
    ), 
    function($db) {
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
);

// --- 3. MIDDLEWARE (The Gatekeeper) ---

// Define which routes do NOT require a login.
$public_routes = [
    '/', '/login', '/register', '/verify', '/resend-verification', 
    '/forgot-password', '/reset-password',
    '/terms', '/privacy', '/cookies', '/pricing',
    '/request-beta-access',
    '/about', '/contact' // New public pages
];

// Define which routes require a PREMIUM subscription.
$premium_routes = [
    '/savings',
    '/contribute' // We check if the path starts with this, so it includes /savings, /savings/add, etc.
];

// This 'before' filter runs before every single request.
Flight::before('start', function() use ($public_routes, $premium_routes) {
    $full_url = Flight::request()->url;
    $current_path = parse_url($full_url, PHP_URL_PATH);
    if ($current_path === null || $current_path === '') { $current_path = '/'; }

    // First, check if the route is public.
    if (in_array($current_path, $public_routes)) {
        return; // It's public, let it pass.
    }

    // If it's not public, it must be a protected route.
    // First, run the standard authentication check to make sure the user is logged in.
    \core\AuthMiddleware::check();
    
    // **THE NEW LOGIC IS HERE**
    // Now, check if the route is a premium-only route.
    foreach ($premium_routes as $premium_route) {
        if (str_starts_with($current_path, $premium_route)) {
            // It's a premium route, so run our new subscription check.
            \core\SubscriptionMiddleware::check();
            return; // We can stop checking once we find a match.
        }
    }
});


// --- 4. ROUTING ---
// All routes are now defined normally, without a group. The middleware handles protection.

// This route now decides what to show when someone visits your main domain.
Flight::route('GET /', function() {
    // 1. We get the User Agent string from the request headers.
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // 2. We check if the ?source=twa parameter is present.
    $isTwaSource = isset(\Flight::request()->query['source']) && \Flight::request()->query['source'] === 'twa';

    // 3. We check if the User Agent string contains 'Android' and '; wv)' which is typical for a Trusted Web Activity.
    $isAndroidWebView = str_contains($userAgent, 'Android') && str_contains($userAgent, '; wv)');

    // 4. A user gets access to the app if they are already logged in OR if BOTH the TWA source and the User Agent check pass.
    if (isset($_COOKIE['auth_token']) || ($isTwaSource && $isAndroidWebView)) {
    $isTwa = isset(\Flight::request()->query['source']) && \Flight::request()->query['source'] === 'twa';
        // If they are, don't show the dashboard directly.
        // Instead, redirect to the protected '/dashboard' route.
        // This forces the middleware to run and set the user data correctly.
        Flight::redirect('/dashboard');
    } else {
        // Otherwise, show the public landing page.
        (new controllers\ViewController())->showLandingPage();
    }
});

// -- Public Routes --
Flight::route('GET /about', function() { (new controllers\PageController())->showAboutPage(); });
Flight::route('GET /contact', function() { (new controllers\PageController())->showContactPage(); });
Flight::route('POST /contact', function() { (new controllers\PageController())->handleContactForm(); });
Flight::route('GET /login', function() { (new controllers\AuthController())->showLoginForm(); });
Flight::route('POST /login', function() { (new controllers\AuthController())->login(); });
Flight::route('GET /register', function() { (new controllers\AuthController())->showRegisterForm(); });
Flight::route('POST /register', function() { (new controllers\AuthController())->register(); });
Flight::route('GET /verify', function() { (new controllers\AuthController())->verify(); });
Flight::route('POST /resend-verification', function() { (new controllers\AuthController())->resendVerification(); });
Flight::route('GET /forgot-password', function() { (new controllers\AuthController())->showForgotPasswordForm(); });
Flight::route('POST /forgot-password', function() { (new controllers\AuthController())->forgot(); });
Flight::route('GET /reset-password', function() { (new controllers\AuthController())->showResetPasswordForm(); });
Flight::route('POST /reset-password', function() { (new controllers\AuthController())->reset(); });
Flight::route('GET /terms', function() { (new controllers\LegalController())->showTerms(); });
Flight::route('GET /privacy', function() { (new controllers\LegalController())->showPrivacy(); });
Flight::route('GET /cookies', function() { (new controllers\LegalController())->showCookies(); });
Flight::route('GET /pricing', function() { (new controllers\SubscriptionController())->showPricingPage(); });
// route for the beta request form
Flight::route('POST /request-beta-access', function() { (new controllers\ContactController())->handleBetaRequest(); });

// -- Protected Routes --
// We add a specific '/dashboard' route in case it's needed for direct linking inside the app.
Flight::route('GET /dashboard', function() { (new controllers\ViewController())->dashboard(); });
Flight::route('GET /logout', function() { (new controllers\AuthController())->logout(); });

// The User's Profile
Flight::route('GET /profile', function() { (new controllers\ProfileController())->showProfileForm(); });
Flight::route('POST /profile', function() { (new controllers\ProfileController())->updateProfile(); });
Flight::route('GET /profile/change-password', function() { (new controllers\ProfileController())->showChangePasswordForm(); });
Flight::route('POST /profile/change-password', function() { (new controllers\ProfileController())->changePassword(); });

// The main list of all accounts
Flight::route('GET /accounts', function() { (new controllers\AccountController())->showList(); }); 
// The form to add a new account
Flight::route('GET /account/add', function() { (new controllers\ViewController())->addAccountForm(); });
Flight::route('POST /account/add', function() { (new controllers\AccountController())->add(); });
// Routes for editing an account
Flight::route('GET /account/edit/@id', function($id) { (new controllers\AccountController())->showEditForm($id); });
Flight::route('POST /account/edit/@id', function($id) { (new controllers\AccountController())->update($id); });
// Route for deleting an account
Flight::route('POST /account/delete/@id', function($id) { (new controllers\AccountController())->delete($id); });
// Routes for resetting a balance
Flight::route('GET /account/reset/@id', function($id) { (new controllers\AccountController())->showResetForm($id); });
Flight::route('POST /account/reset/@id', function($id) { (new controllers\AccountController())->handleResetBalance($id); });

// -- Transaction Routes --
Flight::route('GET /transactions', function() { (new controllers\TransactionController())->showList(); });
Flight::route('GET /transaction/add', function() { (new controllers\ViewController())->addTransactionForm(); });
Flight::route('POST /transaction/add', function() { (new controllers\TransactionController())->add(); });
Flight::route('GET /transaction/edit/@id', function($id) { (new controllers\TransactionController())->showEditForm($id); });
Flight::route('POST /transaction/edit/@id', function($id) { (new controllers\TransactionController())->update($id); });
Flight::route('POST /transaction/delete/@id', function($id) { (new controllers\TransactionController())->delete($id); });

Flight::route('GET /recurring', function() { (new controllers\ViewController())->showRecurringRules(); });
Flight::route('GET /recurring/edit/@rule_id', function($rule_id) { (new controllers\RecurringController())->showEditForm($rule_id); });
Flight::route('POST /recurring/edit/@rule_id', function($rule_id) { (new controllers\RecurringController())->update($rule_id); });
Flight::route('POST /recurring/delete/@rule_id', function($rule_id) { (new controllers\RecurringController())->delete($rule_id); });

// This is the endpoint the browser sends the subscription data to.
Flight::route('POST /notifications/subscribe', function() { (new controllers\NotificationController())->subscribe(); });

// -- Setup Wizard Routes --
Flight::route('GET /setup/step1', function() { (new controllers\SetupController())->step1_show(); });
Flight::route('POST /setup/step1', function() { (new controllers\SetupController())->step1_process(); });
Flight::route('GET /setup/step2', function() { (new controllers\SetupController())->step2_show(); });
Flight::route('POST /setup/step2', function() { (new controllers\SetupController())->step2_process(); });

// **DESTRUCTIVE ROUTES**
Flight::route('POST /profile/reset-data', function() { (new controllers\ProfileController())->resetData(); });
Flight::route('POST /profile/delete-account', function() { (new controllers\ProfileController())->deleteAccount(); });

// Savings Goals Routes
Flight::route('GET /savings', function() { (new controllers\SavingsGoalController())->showList(); });
Flight::route('GET /savings/add', function() { (new controllers\SavingsGoalController())->showCreateForm(); });
Flight::route('POST /savings/add', function() { (new controllers\SavingsGoalController())->handleCreate(); });
Flight::route('GET /savings/edit/@id', function($id) { (new controllers\SavingsGoalController())->showEditForm($id); });
Flight::route('POST /savings/edit/@id', function($id) { (new controllers\SavingsGoalController())->handleUpdate($id); });
Flight::route('POST /savings/delete/@id', function($id) { (new controllers\SavingsGoalController())->handleDelete($id); });
// Savings Goals Contribution Route
Flight::route('POST /contribute/@id', function($id) { (new controllers\SavingsGoalController())->handleContribution($id); });

// --- 5. LAUNCH ---
Flight::start();
