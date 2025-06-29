<?php
// app/core/AuthMiddleware.php
namespace core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware {
    /**
     * This function is our "gatekeeper" for protected routes.
     */
    public static function check() {
        // Check if the auth cookie exists
        if (isset($_COOKIE['auth_token'])) {
            $jwt = $_COOKIE['auth_token'];
            try {
                // Decode the token. If it's invalid, it will throw an exception.
                $decoded = JWT::decode($jwt, new Key($_ENV['JWT_SECRET_KEY'], 'HS256'));
                
                // The token is valid. Let's make the user data available to the rest of the app for this request.
                \Flight::set('user_data', (array) $decoded->data);
                
                // Allow the request to proceed
                return true;

            } catch (\Exception $e) {
                // Token is invalid (expired, wrong signature, etc.)
                // Clear the bad cookie and redirect to login.
                setcookie('auth_token', '', time() - 3600, '/');
                \Flight::redirect('/login');
                exit();
            }
        } else {
            // No auth token cookie was found. Redirect to login.
            \Flight::redirect('/login');
            exit();
        }
    }
}
