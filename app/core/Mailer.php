<?php
// app/core/Mailer.php
namespace core;

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    
    protected $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true); // Enable exceptions
        $this->configure();
    }

    /**
     * Configures PHPMailer with your SMTP server settings.
     */
    protected function configure() {
        // --- IMPORTANT: REPLACE WITH YOUR SMTP DETAILS ---
        $this->mailer->isSMTP();
        $this->mailer->Host       = 'smtp.livemail.co.uk'; // e.g., smtp.gmail.com
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = 'noreply@visubudget.co.uk'; // Your SMTP username
        $this->mailer->Password   = 'y^Ffg7LKcS8pD;@'; // Your SMTP password
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Or SMTPSecure = 'ssl'
        $this->mailer->Port       = 587; // Or 465 for SSL

        // Set the "From" address
        $this->mailer->setFrom('noreply@visubudget.co.uk', 'VisuBudget');
    }

    /**
     * Sends the verification email to a new user.
     *
     * @param string $recipientEmail The user's email address.
     * @param string $token The verification token.
     * @return bool True if the email was sent, false otherwise.
     */
    public function sendVerificationEmail($recipientEmail, $token) {
        try {
            // Recipient
            $this->mailer->addAddress($recipientEmail);

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Verify Your VisuBudget Account';

            // Create the verification link
            $verificationLink = "https://visubudget.local/verify?token=" . urlencode($token); // Adjust your domain if needed

            // Email Body
            $this->mailer->Body = "
                <div style='font-family: sans-serif; padding: 20px; color: #333;'>
                    <h2>Welcome to VisuBudget!</h2>
                    <p>Thanks for signing up. Please click the link below to verify your email address and activate your account:</p>
                    <p><a href='{$verificationLink}' style='padding: 10px 15px; background-color: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>Verify My Account</a></p>
                    <p>If you did not sign up for this account, you can safely ignore this email.</p>
                </div>
            ";
            
            $this->mailer->AltBody = "Welcome to VisuBudget! Please visit the following link to verify your account: " . $verificationLink;

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            // Log the error for debugging, but don't show it to the user.
            error_log("Mailer Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Sends the password reset email.
     *
     * @param string $recipientEmail The user's email address.
     * @param string $token The password reset token.
     * @return bool True if email was sent, false otherwise.
     */
    public function sendPasswordResetEmail($recipientEmail, $token) {
        try {
            $this->mailer->addAddress($recipientEmail);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Reset Your VisuBudget Password';

            // Construct the full reset link using the APP_URL from your .env file
            $resetLink = $_ENV['APP_URL'] . "/reset-password?token=" . urlencode($token);

            // The HTML body of the email
            $this->mailer->Body = "
                <div style='font-family: sans-serif; padding: 20px; color: #333;'>
                    <h2>Password Reset Request</h2>
                    <p>We received a request to reset your password. Click the link below to set a new one:</p>
                    <p><a href='{$resetLink}' style='padding: 10px 15px; background-color: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>Reset My Password</a></p>
                    <p>This link is valid for one hour. If you did not request a password reset, you can safely ignore this email.</p>
                </div>
            ";

            // A plain-text version for email clients that don't support HTML
            $this->mailer->AltBody = "To reset your VisuBudget password, visit the following link: " . $resetLink;

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            // Log the error for your own debugging, but don't expose it to the user.
            error_log("Mailer Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Sends the beta access request email to the admin.
     * @param string $name The applicant's name.
     * @param string $email The applicant's email.
     * @param string $reason The applicant's reason for interest.
     * @return bool True if the email was sent, false otherwise.
     */
    public function sendBetaRequestEmail($name, $email, $reason) {
        try {
            // The recipient is your special beta request email address.
            $this->mailer->addAddress('closedbeta@visubudget.co.uk');
            
            // Set the "Reply-To" header so you can easily reply to the applicant.
            $this->mailer->addReplyTo($email, $name);

            // Content
            $this->mailer->isHTML(false); // Plain text is better for internal emails
            $this->mailer->Subject = "New VisuBudget Beta Request from: " . $name;
            $this->mailer->Body = "You have received a new beta access request.\n\n" .
                                  "Name: " . $name . "\n" .
                                  "Email: " . $email . "\n\n" .
                                  "Reason for interest:\n" .
                                  "--------------------------\n" .
                                  $reason;

            $this->mailer->send();
            return true;
        } catch (\Exception $e) {
            error_log("Beta Request Mailer Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Sends the general contact form email to the admin.
     */
    public function sendContactFormEmail($name, $email, $message) {
        try {
            // The recipient is your main contact email
            $this->mailer->addAddress('admin@visubudget.co.uk'); // Replace with your actual contact email
            $this->mailer->addReplyTo($email, $name);

            $this->mailer->isHTML(false);
            $this->mailer->Subject = "VisuBudget Contact Form: " . $name;
            $this->mailer->Body = "New message from the website contact form:\n\n" .
                                  "Name: " . $name . "\n" .
                                  "Email: " . $email . "\n\n" .
                                  "Message:\n" .
                                  "--------------------------\n" .
                                  $message;

            return $this->mailer->send();
        } catch (\Exception $e) {
            error_log("Contact Form Mailer Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
}
