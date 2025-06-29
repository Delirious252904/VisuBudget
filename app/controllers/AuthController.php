<?php
// app/controllers/AuthController.php
namespace controllers;

use models\User;
use core\Mailer;
use Firebase\JWT\JWT;

class AuthController extends ViewController {

    /**
     * Shows the registration form, but only if the user has a valid access source.
     */
    public function showRegisterForm() {
        $source = \Flight::request()->query['from'] ?? null;
        $allowed_sources = ['twa', 'invite'];

        if (in_array($source, $allowed_sources)) {
            $this->renderPublic('register/index');
        } else {
            \Flight::flash('info', 'VisuBudget is currently in a closed beta. Please request access below.');
            \Flight::redirect('/#request-access');
        }
    }

    /**
     * Handles the registration form submission.
     * CORRECTED: Now passes the 'name' field to the User model.
     */
    public function register() {
        $request = \Flight::request();
        $name = $request->data['name']; // Use 'name' to match the form and DB
        $email = $request->data['email'];
        $password = $request->data['password'];

        if (empty($name) || empty($email) || empty($password) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->renderPublic('register/index', ['error' => 'All fields are required.']);
            return;
        }

        $userModel = new User();
        
        // Check if user already exists
        if ($userModel->findByEmail($email)) {
            $this->renderPublic('register/index', ['error' => 'This email address is already in use.']);
            return;
        }
        
        // Pass all required parameters to the corrected create method
        $token = $userModel->create($name, $email, $password);

        if (!$token) {
            $this->renderPublic('register/index', ['error' => 'Could not create account. Please try again.']);
            return;
        }

        $mailer = new Mailer();
        $mailSent = $mailer->sendVerificationEmail($email, $token);

        if ($mailSent) {
            $this->renderPublic('auth/message', [
                'title' => 'Registration Successful!',
                'message' => 'We\'ve sent a verification link to your email address. Please check your inbox to activate your account.',
                'link_href' => '/login',
                'link_text' => 'Go to Login'
            ]);
        } else {
            // Log this error for your own debugging
            error_log("Failed to send verification email to: " . $email);
            $this->renderPublic('auth/message', [
                'title' => 'Registration Successful!',
                'message' => 'Your account was created, but we couldn\'t send the verification email. Please contact support to activate your account.',
                'link_href' => '/login',
                'link_text' => 'Go to Login'
            ]);
        }
    }

    /**
     * Shows the login form view.
     */
    public function showLoginForm() {
        $this->renderPublic('login/index');
    }

    /**
     * Handles the login form submission.
     * This version is fully synchronized with the User model and DB schema.
     */
    public function login() {
        $request = \Flight::request();
        $email = $request->data['email'];
        $password = $request->data['password'];

        $userModel = new User();
        $user = $userModel->verifyCredentials($email, $password);

        if (!$user) {
            \Flight::flash('danger', 'Invalid login credentials.');
            $this->renderPublic('login/index', ['error' => 'Invalid login credentials.']);
            return;
        }

        if (!$user['is_verified']) {
            $this->renderPublic('login/index', [
                'error' => 'Your account is not verified. Please check your email.',
                'show_resend' => true,
                'email' => $email
            ]);
            return;
        }

        // --- JWT CREATION with correct payload ---
        $issuedAt = time();
        $expire = $issuedAt + (60*60*24*30); // 30 days

        $payload = [
            'iss' => $_ENV['APP_URL'],
            'aud' => $_ENV['APP_URL'],
            'iat' => $issuedAt,
            'exp' => $expire,
            'data' => [
                'user_id' => $user['user_id'], // Uses the correct 'user_id'
                'email' => $user['email'],
                'role' => $user['role']       // Includes the 'role'
            ]
        ];
        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET_KEY'], 'HS256');

        setcookie('auth_token', $jwt, [
            'expires' => $expire, 'path' => '/', 'domain' => '',
            'secure' => true, 'httponly' => true, 'samesite' => 'Lax'
        ]);
        
        // Redirect based on role after successful login
        if ($user['role'] === 'admin') {
            \Flight::redirect('/admin/dashboard');
        } else {
            \Flight::redirect('/dashboard');
        }
    }

    /**
     * Handles the request to resend a verification email.
     */
    public function resendVerification() {
        $email = \Flight::request()->data['email'];
        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if ($user && !$user['is_verified']) {
            $newToken = $userModel->updateVerificationToken($user['user_id']);
            if ($newToken) {
                $mailer = new Mailer();
                $mailer->sendVerificationEmail($user['email'], $newToken);
            }
        }
        
        $this->renderPublic('auth/message', [
            'title' => 'Request Received',
            'message' => 'If an unverified account with that email address exists, we have sent a new verification link to it.',
            'link_href' => '/login',
            'link_text' => 'Back to Login'
        ]);
    }

    /**
     * Verifies a user's account from the email link.
     */
    public function verify() {
        $token = \Flight::request()->query['token'];
        if (empty($token)) {
             $this->renderPublic('auth/message', ['title' => 'Error', 'message' => 'No verification token was provided.']);
             return;
        }

        $userModel = new User();
        $user = $userModel->findByVerificationToken($token);

        if (!$user || new \DateTime() > new \DateTime($user['token_expires_at'])) {
            $this->renderPublic('auth/message', ['title' => 'Invalid Link', 'message' => 'This verification link is invalid or has expired.']);
            return;
        }

        $success = $userModel->activateAccount($user['user_id']);

        if ($success) {
            $this->renderPublic('login/index', ['success' => 'Your account has been verified! You can now log in.']);
        } else {
            $this->renderPublic('auth/message', [
                'title' => 'Activation Failed', 
                'message' => 'We could not activate your account at this time. Please try again later or contact support.'
            ]);
        }
    }
    
    public function showForgotPasswordForm() {
        $this->renderPublic('forgot-password/index');
    }

    public function forgot() {
        $email = \Flight::request()->data['email'];
        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if ($user) {
            $token = $userModel->generatePasswordResetToken($user['user_id']);
            if ($token) {
                $mailer = new Mailer();
                $mailer->sendPasswordResetEmail($user['email'], $token);
            }
        }

        $this->renderPublic('auth/message', [
            'title' => 'Check Your Email',
            'message' => 'If an account with that email address exists, we have sent a password reset link to it.',
            'link_href' => '/login',
            'link_text' => 'Back to Login'
        ]);
    }

    public function showResetPasswordForm() {
        $token = \Flight::request()->query['token'];
        $userModel = new User();
        $user = $userModel->findByPasswordResetToken($token);

        if (!$user || new \DateTime() > new \DateTime($user['password_reset_expires_at'])) {
            $this->renderPublic('auth/message', ['title' => 'Invalid Link', 'message' => 'This password reset link is invalid or has expired.']);
            return;
        }

        $this->renderPublic('reset-password/index', ['token' => $token]);
    }

    public function reset() {
        $token = \Flight::request()->data['token'];
        $password = \Flight::request()->data['password'];
        $password_confirm = \Flight::request()->data['password_confirm'];

        $userModel = new User();
        $user = $userModel->findByPasswordResetToken($token);

        if (!$user || new \DateTime() > new \DateTime($user['password_reset_expires_at'])) {
            $this->renderPublic('auth/message', ['title' => 'Invalid Link', 'message' => 'This password reset link is invalid or has expired.']);
            return;
        }
        
        if (empty($password) || $password !== $password_confirm) {
            $this->renderPublic('reset-password/index', ['token' => $token, 'error' => 'Passwords do not match.']);
            return;
        }

        $userModel->updatePassword($user['user_id'], $password);

        $this->renderPublic('login/index', ['success' => 'Your password has been updated successfully! You can now log in.']);
    }
    
    public function logout() {
        setcookie('auth_token', '', time() - 3600, '/');
        \Flight::redirect('/login');
    }
}
