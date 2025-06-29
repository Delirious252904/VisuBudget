<?php
// app/controllers/AuthController.php
namespace controllers;

use models\User;
use core\Mailer;
use Firebase\JWT\JWT;

// This class now extends ViewController to gain access to the renderPublic() method.
class AuthController extends ViewController {

    /**
     * Shows the registration form, but only if the user has a valid access source.
     * This is the core of your controlled beta logic.
     */
    public function showRegisterForm() {
        // 1. Check for a special query parameter in the URL, like '/register?from=twa'.
        $source = \Flight::request()->query['from'] ?? null;

        // 2. Define which sources are allowed to see the registration page.
        $allowed_sources = ['twa', 'invite'];

        // 3. If the user's source is in our allowed list, show them the form.
        if (in_array($source, $allowed_sources)) {
            $this->renderPublic('register/index');
        } else {
            // 4. If they don't have a valid source (e.g., they just typed '/register' in the browser),
            // send them back to the main landing page.
            \Flight::redirect('/#request-access');
        }
    }

    /**
     * Handles the registration form submission.
     */
    public function register() {
        $request = \Flight::request();
        $email = $request->data['email'];
        $password = $request->data['password'];

        if (empty($email) || empty($password) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->renderPublic('register/index', ['error' => 'Invalid email or password.']);
            return;
        }

        $userModel = new User();
        $token = $userModel->create($email, $password);

        if (!$token) {
            $this->renderPublic('register/index', ['error' => 'This email address is already in use.']);
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
            $this->renderPublic('register/index', ['error' => 'Could not send verification email. Please try again later.']);
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
     * This version can now detect unverified accounts.
     */
    public function login() {
        $request = \Flight::request();
        $email = $request->data['email'];
        $password = $request->data['password'];

        $userModel = new User();
        $user = $userModel->verifyCredentials($email, $password);

        if (!$user) {
            // This means the email was not found or the password was incorrect.
            $this->renderPublic('login/index', ['error' => 'Invalid login credentials.']);
            return;
        }

        // The credentials were correct. Now we check if the account is verified.
        if (!$user['is_verified']) {
            $this->renderPublic('login/index', [
                'error' => 'Your account is not verified. Please check your email.',
                'show_resend' => true, // This flag tells the view to show the resend button
                'email' => $email // We pass the email back to the view
            ]);
            return;
        }

        // --- JWT CREATION on Successful Login ---
        $issuedAt = time();
        $expire = $issuedAt + JWT_EXPIRATION_TIME;

        $payload = [
            'iss' => $_ENV['APP_URL'],
            'aud' => $_ENV['APP_URL'],
            'iat' => $issuedAt,
            'exp' => $expire,
            'data' => [
                'user_id' => $user['user_id'],
                'email' => $user['email']
            ]
        ];
        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET_KEY'], 'HS256');

        setcookie('auth_token', $jwt, [
            'expires' => $expire, 'path' => '/', 'domain' => '',
            'secure' => true, 'httponly' => true, 'samesite' => 'Lax'
        ]);
        \Flight::redirect('/');
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
     * This is the final, non-debug version.
     */
    public function verify() {
        $token = \Flight::request()->query['token'];
        if (empty($token)) {
             $this->renderPublic('auth/message', ['title' => 'Error', 'message' => 'No verification token was provided.']);
             return;
        }

        $userModel = new \models\User();
        $user = $userModel->findByVerificationToken($token);

        // Check if the token is valid and not expired
        if (!$user || new \DateTime() > new \DateTime($user['token_expires_at'])) {
            $this->renderPublic('auth/message', ['title' => 'Invalid Link', 'message' => 'This verification link is invalid or has expired.']);
            return;
        }

        // Try to activate the account and check the result
        $success = $userModel->activateAccount($user['user_id']);

        if ($success) {
            // It worked! Show the login page with a success message.
            $this->renderPublic('login/index', ['success' => 'Your account has been verified! You can now log in.']);
        } else {
            // The database update failed for some reason.
            $this->renderPublic('auth/message', [
                'title' => 'Activation Failed', 
                'message' => 'We could not activate your account at this time. Please try again later or contact support.'
            ]);
        }
    }
    
    /**
     * Shows the 'forgot password' form.
     * Corresponds to: GET /forgot-password
     */
    public function showForgotPasswordForm() {
        $this->renderPublic('forgot-password/index');
    }

    /**
     * Handles the submission of the 'forgot password' form.
     * Renamed from handleForgotPassword to match your route.
     * Corresponds to: POST /forgot-password
     */
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

    /**
     * Shows the 'reset password' form if the token is valid.
     * Corresponds to: GET /reset-password
     */
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

    /**
     * Handles the submission of the new password.
     * Renamed from handleResetPassword to match your route.
     * Corresponds to: POST /reset-password
     */
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
    
    /**
     * Logs the user out by clearing the cookie.
     */
    public function logout() {
        setcookie('auth_token', '', time() - 3600, '/');
        \Flight::redirect('/login');
    }
}
