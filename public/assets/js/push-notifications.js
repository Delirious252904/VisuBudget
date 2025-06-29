// public/assets/js/push-notifications.js

/**
 * This function sets up the logic for the "Enable Notifications" button.
 */
function setupPushNotifications() {
    const enableButton = document.getElementById('enable-notifications-button');
    const statusText = document.getElementById('notification-status');
    // We get the VAPID public key from a data attribute on the button itself.
    const vapidPublicKey = enableButton.dataset.vapidPublicKey;

    if (!enableButton || !statusText || !vapidPublicKey) {
        return; // Stop if any required element is missing.
    }

    // A helper function to convert the VAPID key for the browser.
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
    
    // This is the main function that runs when the button is clicked.
    async function subscribeUser() {
        // 1. Ask the user for permission.
        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                statusText.textContent = 'Permission was denied.';
                throw new Error('Permission not granted for Notification');
            }

            // 2. If permission is granted, subscribe the browser to the push service.
            const serviceWorkerRegistration = await navigator.serviceWorker.ready;
            const subscription = await serviceWorkerRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
            });

            // 3. Send the new subscription object to our backend server to be saved.
            await fetch('/notifications/subscribe', {
                method: 'POST',
                body: JSON.stringify(subscription),
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            // 4. Update the UI to show that it worked.
            statusText.textContent = 'You are subscribed to daily reminders!';
            enableButton.disabled = true;
            enableButton.textContent = 'Subscribed';

        } catch (error) {
            console.error('Failed to subscribe the user: ', error);
            statusText.textContent = 'Failed to subscribe. Please try again.';
        }
    }

    // Attach the main function to the button's click event.
    enableButton.addEventListener('click', subscribeUser);

    // Also check the current status when the page loads.
    navigator.serviceWorker.ready.then(reg => {
        reg.pushManager.getSubscription().then(sub => {
            if(sub) {
                statusText.textContent = 'You are already subscribed to daily reminders.';
                enableButton.disabled = true;
                enableButton.textContent = 'Subscribed';
            } else {
                 statusText.textContent = 'Enable daily reminders for upcoming bills.';
            }
        });
    });
}

// Run our setup function when the page is ready.
document.addEventListener('DOMContentLoaded', setupPushNotifications);
