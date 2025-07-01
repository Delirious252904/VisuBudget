<?php
/**
 * VisuBudget - Front Controller
 */
session_start();
require_once __DIR__ . '/../private/app/controllers/ErrorController.php';

// --- 1. BOOTSTRAPPING & CONFIGURATION ---
require '../private/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable('../private');
$dotenv->load();

require '../private/app/config.php';

// Custom autoloader
spl_autoload_register(function ($class_name) {
    $base_dir = __DIR__ . '/../private/app/';
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

Flight::register('admin', 'controllers\AdminController');

// --- Flash Messaging System ---
// Creates a message that persists for the next page load.
Flight::map('flash', function($message, $type = 'success'){
    $_SESSION['flash_messages'][] = [
        'message' => $message,
        'type' => $type
    ];
});

// Used by the ViewController to retrieve and then clear messages.
Flight::map('getFlashes', function(){
    if (isset($_SESSION['flash_messages'])) {
        $flashes = $_SESSION['flash_messages'];
        unset($_SESSION['flash_messages']);
        return $flashes;
    }
    return [];
});

// Custom Error Handling
Flight::map('error', function(\Throwable $ex){
    // Log the full error to your server's error log for your own debugging
    error_log($ex->getMessage() . " in " . $ex->getFile() . " on line " . $ex->getLine());

    // Create an instance of our new controller and call the display method
    $errorController = new \controllers\ErrorController();
    $errorController->display_error($ex);
});

// --- 3. MIDDLEWARE (The Gatekeeper) ---
$public_routes = [
    '/', '/login', '/register', '/verify', '/resend-verification', 
    '/forgot-password', '/reset-password',
    '/terms', '/privacy', '/cookies', '/pricing',
    '/request-beta-access',
    '/about', '/contact', '/error'
];

$premium_routes = ['/savings', '/contribute'];
$admin_routes = ['/admin'];

Flight::before('start', function() use ($public_routes, $premium_routes, $admin_routes) {
    $current_path = parse_url(Flight::request()->url, PHP_URL_PATH) ?? '/';

    // Allow public routes to pass through without checks.
    // The logic to handle '?source=twa' is now in the '/' route itself.
    if ($current_path === '/' && !isset($_COOKIE['auth_token'])) {
        if (!isset(Flight::request()->query['source']) || Flight::request()->query['source'] !== 'twa') {
             return;
        }
    }
    if (in_array($current_path, $public_routes) && $current_path !== '/') {
        return;
    }
    
    // --- Protected routes below ---

    // First, check if the user is authenticated at all.
    \core\AuthMiddleware::check();
    
    // If authenticated, check for role-specific routes.
    foreach ($admin_routes as $admin_route) {
        if (str_starts_with($current_path, $admin_route)) {
            \core\AdminMiddleware::check();
            return; // Admin middleware passed, stop.
        }
    }

    foreach ($premium_routes as $premium_route) {
        if (str_starts_with($current_path, $premium_route)) {
            \core\SubscriptionMiddleware::check();
            return; // Premium middleware passed, stop.
        }
    }
});


// --- 4. ROUTING ---

// **CORRECTED ROOT ROUTE**
// This now properly handles the TWA start URL.
Flight::route('GET /', function() {
    $isTwa = isset(Flight::request()->query['source']) && Flight::request()->query['source'] === 'twa';

    // If the request is from the TWA OR if the user already has an auth cookie,
    // redirect to the dashboard. The middleware will then correctly handle
    // authentication, sending unauthenticated TWA users to the login page.
    if ($isTwa || isset($_COOKIE['auth_token'])) {
        Flight::redirect('/dashboard');
    } else {
        // For all other cases (e.g., direct browser visit), show the public landing page.
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
Flight::route('POST /request-beta-access', function() { (new controllers\ContactController())->handleBetaRequest(); });
// Error handling route
Flight::route('GET /error', function() { (new \controllers\ErrorController())->display_error(); });
Flight::route('POST /error/send-report', function(){ (new \controllers\ErrorController())->send_report(); });

// -- Protected Routes --
Flight::route('GET /dashboard', function() { (new controllers\ViewController())->dashboard(); });
Flight::route('GET /logout', function() { (new controllers\AuthController())->logout(); });
Flight::route('GET /profile', function() { (new controllers\ProfileController())->showProfileForm(); });
Flight::route('POST /profile', function() { (new controllers\ProfileController())->updateProfile(); });
Flight::route('POST /profile/reset-data', function() { (new controllers\ProfileController())->resetData(); });
Flight::route('POST /profile/delete-account', function() { (new controllers\ProfileController())->deleteAccount(); });

Flight::route('GET /accounts', function() { (new controllers\AccountController())->showList(); }); 
Flight::route('GET /account/add', function() { (new controllers\ViewController())->addAccountForm(); });
Flight::route('POST /account/add', function() { (new controllers\AccountController())->add(); });
Flight::route('GET /account/edit/@id', function($id) { (new controllers\AccountController())->showEditForm($id); });
Flight::route('POST /account/edit/@id', function($id) { (new controllers\AccountController())->update($id); });
Flight::route('POST /account/delete/@id', function($id) { (new controllers\AccountController())->delete($id); });
Flight::route('GET /account/reset/@id', function($id) { (new controllers\AccountController())->showResetForm($id); });
Flight::route('POST /account/reset/@id', function($id) { (new controllers\AccountController())->handleResetBalance($id); });

// All transaction and recurring rule creation now goes through a single form and controller.
Flight::route('GET /transactions', function() { (new controllers\TransactionController())->showList(); });
Flight::route('GET /transaction/add', function() { (new controllers\TransactionController())->showAddForm(); });
Flight::route('POST /transaction/add', function() { (new controllers\TransactionController())->create(); });
Flight::route('GET /transaction/edit/@id', function($id) { (new controllers\TransactionController())->showEditForm($id); });
Flight::route('POST /transaction/update/@id', function($id) { (new controllers\TransactionController())->update($id); });
Flight::route('POST /transaction/delete/@id', function($id) { (new controllers\TransactionController())->delete($id); });

// Routes for managing the list of recurring rules
Flight::route('GET /recurring', function() { (new controllers\RecurringController())->index(); });
Flight::route('GET /recurring/edit/@id', function($id) { (new controllers\RecurringController())->showEditForm($id); });
Flight::route('POST /recurring/update/@id',function($id) { (new controllers\RecurringController())->update($id); });
Flight::route('POST /recurring/delete/@id', function($id) { (new controllers\RecurringController())->delete($id); });

Flight::route('POST /notifications/subscribe', function() { (new controllers\NotificationController())->subscribe(); });

Flight::route('GET /setup/step1', function() { (new controllers\SetupController())->step1_show(); });
Flight::route('POST /setup/step1', function() { (new controllers\SetupController())->step1_process(); });
Flight::route('GET /setup/step2', function() { (new controllers\SetupController())->step2_show(); });
Flight::route('POST /setup/step2', function() { (new controllers\SetupController())->step2_process(); });

Flight::route('GET /savings', function() { (new controllers\SavingsGoalController())->showList(); });
Flight::route('GET /savings/add', function() { (new controllers\SavingsGoalController())->showCreateForm(); });
Flight::route('POST /savings/add', function() { (new controllers\SavingsGoalController())->handleCreate(); });
Flight::route('GET /savings/edit/@id', function($id) { (new controllers\SavingsGoalController())->showEditForm($id); });
Flight::route('POST /savings/edit/@id', function($id) { (new controllers\SavingsGoalController())->handleUpdate($id); });
Flight::route('POST /savings/delete/@id', function($id) { (new controllers\SavingsGoalController())->handleDelete($id); });
Flight::route('POST /contribute/@id', function($id) { (new controllers\SavingsGoalController())->handleContribution($id); });

// --- ADMIN ROUTES ---
Flight::route('GET /admin', function() { Flight::redirect('/admin/dashboard'); });
Flight::route('GET /admin/dashboard', function() { Flight::admin()->dashboard(); });
Flight::route('GET /admin/email', function() { Flight::admin()->showEmailForm(); });
Flight::route('POST /admin/email/send', function() { Flight::admin()->handleSendEmail(); });

// --- 5. LAUNCH ---
Flight::start();
