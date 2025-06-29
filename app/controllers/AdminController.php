<?php
namespace controllers;

class AdminController {

    /**
     * Renders a view file using the dedicated ADMIN layout.
     * This is a corrected, self-contained render method.
     */
    public function renderAdminView($viewName, $data = []) {
        $viewPath = \Flight::get('flight.views.path');
        
        // Make variables from the data array available to the view
        extract($data);
        
        // Capture flash messages to pass to the view
        $flash = \Flight::get('flash_messages') ?? [];
        \Flight::clear('flash_messages');
        extract($flash);

        // Include header, the specific view content, and footer
        require_once($viewPath . '/layout/admin_header.php');
        require_once($viewPath . '/' . $viewName . '.php');
        require_once($viewPath . '/layout/admin_footer.php');
    }

    /**
     * Shows the main admin dashboard.
     */
    public function dashboard() {
        $userModel = new \models\User();
        $stats = [
            'total_users' => $userModel->countAll(),
            // Note: You may need to create this method in your User model
            // 'new_users_24h' => $userModel->countNewSince(date('Y-m-d H:i:s', strtotime('-24 hours')))
        ];
        $this->renderAdminView('admin/dashboard', ['stats' => $stats]);
    }

    /**
     * Shows the form to send an email to all users.
     */
    public function showEmailForm() {
        $this->renderAdminView('admin/send_email');
    }
    
    /**
     * Handles the submission of the "Send Email" form.
     */
    public function handleSendEmail() {
        $subject = $_POST['subject'] ?? '';
        $message = $_POST['message'] ?? '';

        if (empty($subject) || empty($message)) {
            \Flight::flash('danger', 'Subject and message cannot be empty.');
            \Flight::redirect('/admin/email');
            exit();
        }

        $userModel = new \models\User();
        $allUsers = $userModel->findAll(); 
        
        $mailer = new \core\Mailer();
        $successCount = 0;
        $failureCount = 0;

        foreach($allUsers as $user) {
            try {
                // The message body can contain HTML
                $mailer->send($user['email'], $user['first_name'], $subject, nl2br($message));
                $successCount++;
            } catch (\Exception $e) {
                error_log("Failed to send admin email to {$user['email']}: " . $e->getMessage());
                $failureCount++;
            }
        }
        
        \Flight::flash('success', "Email sent to {$successCount} users. {$failureCount} failures.");
        \Flight::redirect('/admin/dashboard');
    }
}
