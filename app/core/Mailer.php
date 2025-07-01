<?php
// app/core/Mailer.php
namespace core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    
    protected $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true); // Enable exceptions
        $this->configure();
    }

    /**
     * Configures PHPMailer with your SMTP server settings from the .env file.
     */
    protected function configure() {
        try {
            // --- FIX: Use environment variables for SMTP configuration ---
            $this->mailer->isSMTP();
            $this->mailer->Host       = $_ENV['SMTP_HOST'];
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = $_ENV['SMTP_USER'];
            $this->mailer->Password   = $_ENV['SMTP_PASS'];
            $this->mailer->SMTPSecure = $_ENV['SMTP_SECURE'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port       = $_ENV['SMTP_PORT'];

            // Set the "From" address
            $this->mailer->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);
        } catch (\Throwable $e) {
            // Log a more descriptive error if .env variables are missing
            error_log("Mailer Configuration Error: Please ensure all SMTP variables are set in your .env file. " . $e->getMessage());
        }
    }

    /**
     * A generic method to send any email. This is the new, centralized method.
     * @param string $to The recipient's email address.
     * @param string $subject The email subject.
     * @param string $body The HTML or plain text body.
     * @param bool $isHTML Whether the body is HTML.
     * @return bool True if sent, false otherwise.
     */
    public function send($to, $subject, $body, $isHTML = true) {
        try {
            $this->mailer->clearAllRecipients(); // Clear previous recipients and reply-to addresses
            $this->mailer->addAddress($to);
            $this->mailer->isHTML($isHTML);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $body;
            
            if ($isHTML) {
                // Create a plain text version for non-HTML email clients
                $this->mailer->AltBody = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $body));
            }

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Mailer Send Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    public function sendVerificationEmail($recipientEmail, $token) {
        $verificationLink = $_ENV['APP_URL'] . "/verify?token=" . urlencode($token);
        $subject = 'Verify Your VisuBudget Account';
        $body = "
            <div style='font-family: sans-serif; padding: 20px; color: #333;'>
                <h2>Welcome to VisuBudget!</h2>
                <p>Thanks for signing up. Please click the link below to verify your email address and activate your account:</p>
                <p><a href='{$verificationLink}' style='padding: 10px 15px; background-color: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>Verify My Account</a></p>
                <p>If you did not sign up for this account, you can safely ignore this email.</p>
            </div>
        ";
        return $this->send($recipientEmail, $subject, $body);
    }

    public function sendPasswordResetEmail($recipientEmail, $token) {
        $resetLink = $_ENV['APP_URL'] . "/reset-password?token=" . urlencode($token);
        $subject = 'Reset Your VisuBudget Password';
        $body = "
            <div style='font-family: sans-serif; padding: 20px; color: #333;'>
                <h2>Password Reset Request</h2>
                <p>We received a request to reset your password. Click the link below to set a new one:</p>
                <p><a href='{$resetLink}' style='padding: 10px 15px; background-color: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>Reset My Password</a></p>
                <p>This link is valid for one hour. If you did not request a password reset, you can safely ignore this email.</p>
            </div>
        ";
        return $this->send($recipientEmail, $subject, $body);
    }

    public function sendContactFormEmail($name, $email, $message) {
        $subject = "VisuBudget Contact Form: " . $name;
        $body = "New message from the website contact form:\n\n" .
                              "Name: " . $name . "\n" .
                              "Email: " . $email . "\n\n" .
                              "Message:\n" .
                              "--------------------------\n" .
                              $message;
        
        // Add a reply-to so you can directly reply to the user
        $this->mailer->addReplyTo($email, $name);
        
        return $this->send('admin@visubudget.co.uk', $subject, $body, false);
    }
}
