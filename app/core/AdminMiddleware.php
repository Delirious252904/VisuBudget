<?php
// app/core/AdminMiddleware.php
namespace core;

use models\User;

class AdminMiddleware {
    /**
     * This function checks if the current user is an administrator.
     * If not, it redirects them away.
     */
    public static function check() {
        // Our main AuthMiddleware has already run and confirmed the user is logged in.
        $jwt_user_data = \Flight::get('user_data');

        if (!$jwt_user_data) {
            \Flight::redirect('/login');
            exit();
        }

        $userModel = new User();
        $user = $userModel->findByEmail($jwt_user_data['email']);

        // The crucial check: does the user have the 'admin' role?
        if ($user && $user['role'] === 'admin') {
            return true; // They are an admin, let them proceed.
        }

        // If not an admin, send them back to the normal user dashboard.
        \Flight::redirect('/dashboard');
        exit();
    }
}
