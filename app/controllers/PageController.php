<?php
// --- Create new file: app/controllers/PageController.php ---
namespace controllers;

use core\Mailer;

class PageController extends ViewController {

    /**
     * Shows the About Us page.
     */
    public function showAboutPage() {
        $this->renderPublic('home/about');
    }

    /**
     * Shows the Contact Us page.
     */
    public function showContactPage() {
        $this->renderPublic('home/contact');
    }

    /**
     * Handles the contact form submission.
     */
    public function handleContactForm() {
        $name = $_POST['name'] ?? 'N/A';
        $email = $_POST['email'] ?? 'N/A';
        $message = $_POST['message'] ?? 'N/A';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Simple validation failed
            \Flight::redirect('/contact?error=1');
            return;
        }
        
        $mailer = new Mailer();
        $mailer->sendContactFormEmail($name, $email, $message);
        
        // Show a success message after sending
        $this->renderPublic('auth/message', [
            'title' => 'Message Sent!',
            'message' => "Thank you for getting in touch. We've received your message and will get back to you as soon as possible.",
            'link_href' => '/',
            'link_text' => 'Back to Home'
        ]);
    }
}