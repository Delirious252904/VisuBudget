<?php
// app/controllers/LegalController.php
namespace controllers;

// This controller extends ViewController to get access to the renderPublic() method
class LegalController extends ViewController {

    /**
     * Shows the Terms of Service page.
     */
    public function showTerms() {
        $this->renderPublic('legal/terms');
    }

    /**
     * Shows the Privacy Policy page.
     */
    public function showPrivacy() {
        $this->renderPublic('legal/privacy');
    }

    /**
     * Shows the Cookie Policy page.
     */
    public function showCookies() {
        $this->renderPublic('legal/cookies');
    }
}
