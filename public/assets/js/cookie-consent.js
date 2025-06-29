// public/assets/js/cookie-consent.js

/**
 * This script handles the logic for the cookie consent banner.
 * It runs when the page is fully loaded.
 */
document.addEventListener('DOMContentLoaded', function() {
    const banner = document.getElementById('cookie-consent-banner');
    const acceptButton = document.getElementById('accept-cookies-button');

    // If the banner or button doesn't exist on the page, stop to prevent errors.
    if (!banner || !acceptButton) {
        return;
    }

    // A helper function to easily get a cookie's value by its name.
    const getCookie = (name) => {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }

    // Check if our specific consent cookie has been set.
    // If it hasn't, we show the banner.
    if (!getCookie('visubudget_cookie_consent')) {
        banner.classList.remove('hidden');
    }

    // Add a click listener to the "Got it!" button.
    acceptButton.addEventListener('click', function() {
        // When clicked, set a cookie named 'visubudget_cookie_consent' to 'true'.
        // It's set to expire in 365 days.
        const d = new Date();
        d.setTime(d.getTime() + (365 * 24 * 60 * 60 * 1000));
        let expires = "expires=" + d.toUTCString();
        document.cookie = "visubudget_cookie_consent=true;" + expires + ";path=/;SameSite=Lax";

        // Hide the banner with a smooth transition.
        banner.style.display = 'none';
    });
});
