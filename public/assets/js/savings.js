// public/assets/js/savings.js

/**
 * This function sets up the logic for the "Save to Goal" buttons and modal
 * on the main dashboard.
 */
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('contribution-modal');
    // If the modal doesn't exist on this page, there's nothing to do.
    if (!modal) {
        console.warn('Contribution modal not found on this page.');
        return;
    }

    // Get all the elements we need from the modal
    const form = document.getElementById('contribution-form');
    const title = document.getElementById('modal-title');
    const cancelButton = document.getElementById('modal-cancel-button');
    const amountInput = document.getElementById('amount');

    // Get all the "+ Save" buttons
    const saveButtons = document.querySelectorAll('.js-save-button');

    // Add a click listener to every "+ Save" button
    saveButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Get the specific goal details from the button's data attributes
            const goalId = this.dataset.goalId;
            const goalName = this.dataset.goalName;

            // Update the form's 'action' attribute to point to the correct URL for this goal
            form.action = `/contribute/${goalId}`;

            // Update the modal's title to be specific to this goal
            title.textContent = `Save towards "${goalName}"`;
            
            // Show the modal by removing the 'hidden' class
            modal.classList.remove('hidden');
            
            // Automatically focus the amount input field for a better user experience
            amountInput.focus();
        });
    });

    // Add listeners to close the modal
    cancelButton.addEventListener('click', () => {
        modal.classList.add('hidden');
    });

    // Also close the modal if the user clicks on the dark background overlay
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.classList.add('hidden');
        }
    });
});
