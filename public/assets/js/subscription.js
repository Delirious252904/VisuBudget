// public/assets/js/subscription.js

/**
 * This script handles the logic for the subscription upgrade button.
 */
document.addEventListener('DOMContentLoaded', function() {
    const upgradeButton = document.getElementById('upgrade-premium-button');

    // If the upgrade button doesn't exist on this page, stop.
    if (!upgradeButton) {
        return;
    }

    // Add a click listener to the button.
    upgradeButton.addEventListener('click', function() {
        
        // This is the "bridge". We check if a special 'Android' object exists.
        // This object will be injected by the Android TWA wrapper.
        if (typeof Android !== 'undefined' && Android.startPurchase) {
            
            // If it exists, we call the startPurchase method, passing the ID of the
            // subscription we want to buy. This ID must match what's set up in Google Play.
            console.log("Calling Android.startPurchase()...");
            Android.startPurchase('visubudget_premium_monthly'); // Example SKU

        } else {
            // If the 'Android' object doesn't exist, it means the user is on a regular web browser.
            // We can show a helpful message.
            console.log("Android bridge not found. This is a standard web browser.");
            alert("To upgrade, please install the VisuBudget app from the Google Play Store.");
        }
    });
});
