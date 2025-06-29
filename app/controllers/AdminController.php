<?php
// --- Create new file: app/controllers/AdminController.php ---
namespace controllers;

use models\User;

class AdminController extends ViewController {

    /**
     * Renders a view file using the dedicated ADMIN layout.
     */
    public function renderAdmin($viewName, $data = []) {
        // We can reuse the main render logic but point to a different layout file
        extract($data);
        $viewPath = \Flight::get('flight.views.path');
        // ... (logic to find the view file)
        if ($finalViewFile) {
            require $viewPath . '/layout/admin_header.php';
            require $finalViewFile;
            require $viewPath . '/layout/admin_footer.php';
        } else { /* ... */ }
    }

    /**
     * Shows the main admin dashboard.
     */
    public function dashboard() {
        // We can pass some stats to the dashboard later
        $this->renderAdmin('admin/dashboard');
    }

    /**
     * Shows the form to send an email to all users.
     */
    public function showEmailForm() {
        $this->renderAdmin('admin/send_email');
    }
    
    // We will add the logic to handle the form submissions next.
}