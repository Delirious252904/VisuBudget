// public/assets/js/main.js

/**
 * Main entry point. This runs when the page is loaded.
 * It checks which page we are on and calls the appropriate setup function(s).
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // Check if we are on the "Add Transaction" page.
    if (document.getElementById('is_recurring')) {
        setupAddTransactionForm();
    }
    
    // Check if we are on the "Edit Recurring" page.
    if (document.getElementById('edit-recurring-form')) {
        setupEditRecurringForm();
    }

    // Check if we are on a page that has delete forms that need a confirmation.
    if (document.querySelector('.js-delete-form')) {
        setupConfirmationModalForForms();
    }

    // Always check for a mobile menu button, on every page.
    if (document.getElementById('mobile-menu-button')) {
        setupMobileMenu();
    }
});


/**
 * Sets up all the interactive logic for the "Add Transaction" page.
 */
function setupAddTransactionForm() {
    const transactionTypeSelect = document.getElementById('type');
    const toAccountContainer = document.getElementById('to_account_container');
    const fromAccountLabel = document.getElementById('from_account_label');
    const toAccountSelect = document.getElementById('to_account_id');
    const isRecurringCheckbox = document.getElementById('is_recurring');
    const recurrenceOptionsDiv = document.getElementById('recurrence_options');
    const frequencySelect = document.getElementById('frequency');
    const dayOfWeekSelector = document.getElementById('day_of_week_selector');

    function handleTransactionTypeChange() {
        const selectedType = transactionTypeSelect.value;
        toAccountContainer.style.display = selectedType === 'transfer' ? 'block' : 'none';
        toAccountSelect.required = selectedType === 'transfer';
        fromAccountLabel.textContent = selectedType === 'income' ? 'To Account' : 'From Account';
    }

    function toggleRecurrenceOptions() {
        recurrenceOptionsDiv.style.display = isRecurringCheckbox.checked ? 'block' : 'none';
        toggleDayOfWeekSelector();
    }

    function toggleDayOfWeekSelector() {
        const shouldShow = isRecurringCheckbox.checked && frequencySelect.value === 'weekly';
        dayOfWeekSelector.style.display = shouldShow ? 'block' : 'none';
    }

    transactionTypeSelect.addEventListener('change', handleTransactionTypeChange);
    isRecurringCheckbox.addEventListener('change', toggleRecurrenceOptions);
    frequencySelect.addEventListener('change', toggleDayOfWeekSelector);

    handleTransactionTypeChange();
    toggleRecurrenceOptions();
}


/**
 * Sets up all the interactive logic for the "Edit Recurring Rule" page.
 */
function setupEditRecurringForm() {
    const transactionTypeSelect = document.getElementById('type');
    const toAccountContainer = document.getElementById('to_account_container');
    const fromAccountLabel = document.getElementById('from_account_label');
    const toAccountSelect = document.getElementById('to_account_id');
    const frequencySelect = document.getElementById('frequency');
    const dayOfWeekSelector = document.getElementById('day_of_week_selector');
    
    function handleTransactionTypeChange() {
        const selectedType = transactionTypeSelect.value;
        toAccountContainer.style.display = selectedType === 'transfer' ? 'block' : 'none';
        toAccountSelect.required = selectedType === 'transfer';
        fromAccountLabel.textContent = selectedType === 'income' ? 'To Account' : 'From Account';
    }

    function toggleDayOfWeekSelector() {
        dayOfWeekSelector.style.display = frequencySelect.value === 'weekly' ? 'block' : 'none';
    }

    transactionTypeSelect.addEventListener('change', handleTransactionTypeChange);
    frequencySelect.addEventListener('change', toggleDayOfWeekSelector);
    
    handleTransactionTypeChange();
    toggleDayOfWeekSelector();
}


/**
 * Sets up the logic for our custom confirmation modal to work with form submissions.
 */
function setupConfirmationModalForForms() {
    const modal = document.getElementById('confirmation-modal');
    const cancelButton = document.getElementById('modal-cancel-button');
    const confirmButton = document.getElementById('modal-confirm-button');
    const deleteForms = document.querySelectorAll('.js-delete-form');

    if (!modal || !cancelButton || !confirmButton || deleteForms.length === 0) {
        return;
    }

    let formToSubmit = null;

    deleteForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            // This is the crucial step: stop the form from submitting immediately.
            event.preventDefault();
            formToSubmit = this;
            modal.classList.remove('hidden');
        });
    });

    cancelButton.addEventListener('click', function() {
        modal.classList.add('hidden');
        formToSubmit = null;
    });

    confirmButton.addEventListener('click', function(event) {
        event.preventDefault(); 
        if (formToSubmit) {
            formToSubmit.submit();
        }
    });

    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.classList.add('hidden');
            formToSubmit = null;
        }
    });
}

function setupMobileMenu() {
    const menuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    const menuIcon = document.getElementById('mobile-menu-icon');

    // If any of these elements don't exist, stop to prevent errors.
    if (!menuButton || !mobileMenu || !menuIcon) {
        return;
    }

    menuButton.addEventListener('click', function() {
        // Toggle the 'hidden' class on the menu panel itself.
        mobileMenu.classList.toggle('hidden');

        // Toggle the icon between a hamburger and an 'X' for clear visual feedback.
        if (mobileMenu.classList.contains('hidden')) {
            // If menu is hidden, show the hamburger icon.
            menuIcon.classList.remove('fa-times'); // remove X
            menuIcon.classList.add('fa-bars');    // add hamburger
        } else {
            // If menu is visible, show the 'X' icon.
            menuIcon.classList.remove('fa-bars');    // remove hamburger
            menuIcon.classList.add('fa-times'); // add X
        }
    });
}

/**
 * Handles highlighting and scrolling to elements linked via a URL hash.
 * e.g., /transactions#transaction-123
 */
document.addEventListener('DOMContentLoaded', () => {
    // Check if the URL has a hash (e.g., #account-5)
    if (window.location.hash) {
        try {
            // Find the element with the ID that matches the hash
            const elementToHighlight = document.querySelector(window.location.hash);

            if (elementToHighlight) {
                // 1. Scroll the element into the middle of the view
                elementToHighlight.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });

                // 2. Add the highlight class to apply our CSS style
                elementToHighlight.classList.add('highlight-item');

                // 3. Remove the highlight class after 2.5 seconds so the effect is temporary
                setTimeout(() => {
                    elementToHighlight.classList.remove('highlight-item');
                }, 2500);
            }
        } catch (e) {
            // If the hash is not a valid element ID, do nothing.
            console.warn('Could not highlight element for hash:', window.location.hash);
        }
    }
});
