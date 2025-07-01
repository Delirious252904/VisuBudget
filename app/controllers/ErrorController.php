<?php
namespace controllers;

use Flight;
use core\Mailer;
use models\User;

class ErrorController {
    /**
     * The constructor now accepts an optional PDO database connection object.
     * This allows the model to be used both within the Flight framework and in standalone scripts.
     */
    public function __construct($db = null)
    {
        // This model doesn't use the DB, but we add the constructor for consistency.
    }

    /**
     * Renders the custom error page.
     * This method is called by Flight's error handler.
     * @param \Throwable $error The exception or error object.
     */
    public function display_error(\Throwable $error) {
        // Attempt to get the logged-in user's details to pre-fill the form
        $user = null;
        if (isset($_SESSION['user_id'])) {
            // We need a DB connection to get user details
            $db = Flight::db();
            $userModel = new User($db);
            $user = $userModel->findById($_SESSION['user_id']);
        }

        // Prepare a data package for the view
        $error_data = [
            'message' => $error->getMessage(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => $error->getTraceAsString(),
            'user' => $user
        ];
        
        // --- FIX: Render the error view without the third 'false' parameter ---
        // This is the correct way to render a view without a layout in Flight.
        Flight::render('error/index', ['error_data' => $error_data]);
    }

    /**
     * Handles the form submission from the error page.
     * Sends the error details and user's description to the feedback email.
     */
    public function send_report() {
        $user_email = Flight::request()->data->user_email ?: 'Anonymous';
        $user_description = Flight::request()->data->user_description;
        $error_details = Flight::request()->data->error_details;

        $subject = "VisuBudget Error Report from " . $user_email;
        
        $body = "<h2>A user has submitted an error report.</h2>";
        $body .= "<h3>User Description:</h3>";
        $body .= "<blockquote>" . nl2br(htmlspecialchars($user_description)) . "</blockquote>";
        $body .= "<hr>";
        $body .= "<h3>Automated Error Details:</h3>";
        $body .= "<pre style='background-color:#f0f0f0; padding:10px; border-radius:5px;'>" . htmlspecialchars($error_details) . "</pre>";
        
        $mailer = new Mailer();
        $sent = $mailer->send('feedback@visubudget.co.uk', $subject, $body);

        if ($sent) {
            Flight::flash('message', 'Thank you! Your error report has been sent. We appreciate your help!');
        } else {
            Flight::flash('error', 'Sorry, we could not send your error report at this time.');
        }
        Flight::redirect('/');
    }
}
