<?php
// app/controllers/SubscriptionController.php
namespace controllers;

class SubscriptionController extends ViewController {

    /**
     * Shows the public pricing page.
     */
    public function showPricingPage() {
        // Since the pricing page has a standard header and footer, we can use render().
        $this->renderPublic('/pricing');
    }
}
