<?php
// app/controllers/ContactController.php
// This file handles the contact form for requesting closed beta access.
namespace controllers;

use core\Mailer;

class ContactController extends ViewController {
    /**
     * Handles the beta access request form submission.
     */
    public function handleBetaRequest() {
        $name = $_POST['name'] ?? 'No name provided';
        $email = $_POST['email'] ?? 'No email provided';
        $reason = $_POST['reason'] ?? 'No reason provided';
        
        // Basic validation
        if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // In a real app, you'd show an error. For now, we just stop.
            \Flight::redirect('/#request-access');
            return;
        }

        $mailer = new Mailer();
        $success = $mailer->sendBetaRequestEmail($name, $email, $reason);
        
        // After submission, show a generic thank you message
        $this->render('auth/message', [
            'title' => 'Request Sent!',
            'message' => 'Thank you for your interest in VisuBudget. We have received your request and will be in touch soon if you are selected for the closed beta.',
            'link_href' => '/',
            'link_text' => 'Back to Home'
        ]);
    }
}