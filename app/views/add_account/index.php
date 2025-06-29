<!-- app/views/add_account/index.php -->
<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 max-w-lg mx-auto">
    <h2 class="text-2xl font-bold mb-6 flex items-center">
        <i class="fas fa-university mr-3 text-blue-400"></i>
        Add a New Account
    </h2>

    <form action="/account/add" method="POST">
        <!-- Account Name -->
        <div class="mb-4">
            <label for="account_name" class="block mb-2 text-sm font-medium text-gray-300">Account Name</label>
            <input type="text" name="account_name" id="account_name" class="form-input" placeholder="e.g., Main Checking, Savings Pot" required>
        </div>

        <!-- Account Type (Optional for now) -->
        <div class="mb-4">
            <label for="account_type" class="block mb-2 text-sm font-medium text-gray-300">Account Type</label>
            <select name="account_type" id="account_type" class="form-input">
                <option value="Checking">Checking / Current</option>
                <option value="Savings">Savings</option>
                <option value="Credit Card">Credit Card</option>
                <option value="Cash">Cash</option>
            </select>
        </div>

        <!-- Current Balance -->
        <div class="mb-6">
            <label for="current_balance" class="block mb-2 text-sm font-medium text-gray-300">Current Balance</label>
            <input type="number" name="current_balance" id="current_balance" class="form-input" step="0.01" placeholder="0.00" required>
        </div>

        <!-- Submit Button -->
        <div>
            <button type="submit" class="w-full btn btn-primary">
                <i class="fas fa-save mr-2"></i> Save Account
            </button>
        </div>
    </form>
</section>
