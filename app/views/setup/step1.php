<!-- -------------------------------------------------- -->
<!-- File: app/views/setup/step1.php                      -->
<!-- This is the first step: Adding the first account.  -->
<!-- -------------------------------------------------- -->
<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 max-w-lg mx-auto text-center">
    <h1 class="text-3xl font-bold text-blue-400 mb-2">Welcome to VisuBudget!</h1>
    <p class="text-lg text-gray-300 mb-6">Let's get you set up.</p>
    
    <div class="text-left border-t border-gray-700 pt-6">
        <h2 class="text-xl font-bold mb-4">Step 1: Add Your First Account</h2>
        <p class="text-gray-400 mb-6">Start by adding a primary account, like your main current or checking account. You can add more later.</p>
        
        <form action="/setup/step1" method="POST">
            <div class="mb-4">
                <label for="account_name" class="block mb-2 text-sm font-medium">Account Name</label>
                <input type="text" name="account_name" id="account_name" class="form-input" placeholder="e.g., Current Account" required>
            </div>
            <div class="mb-6">
                <label for="current_balance" class="block mb-2 text-sm font-medium">Current Balance (£)</label>
                <input type="number" name="current_balance" id="current_balance" class="form-input" step="0.01" placeholder="1234.56" required>
            </div>
            <button type="submit" class="w-full btn btn-primary">Continue to Step 2</button>
        </form>
    </div>
</section>