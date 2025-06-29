<!-- -------------------------------------------------- -->
<!-- File: app/views/setup/step2.php                      -->
<!-- This is the second step: Adding the first income.  -->
<!-- -------------------------------------------------- -->
<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 max-w-lg mx-auto text-center">
    <h1 class="text-3xl font-bold text-blue-400 mb-2">Almost Done!</h1>
    <p class="text-lg text-gray-300 mb-6">This is the most important step.</p>
    
    <div class="text-left border-t border-gray-700 pt-6">
        <h2 class="text-xl font-bold mb-4">Step 2: Add Your Main Recurring Income</h2>
        <p class="text-gray-400 mb-6">Add your primary source of income, like your salary. This is crucial for calculating your 'Safe to Spend' amount. You can use a past date if you know when you were last paid.</p>
        
        <!-- This form reuses many fields from the main 'add transaction' form -->
        <form action="/setup/step2" method="POST">
            <input type="hidden" name="type" value="income">
            <input type="hidden" name="is_recurring" value="1">

            <div class="mb-4">
                <label for="description" class="block mb-2 text-sm font-medium">Income Description</label>
                <input type="text" name="description" id="description" class="form-input" placeholder="e.g., Salary, Wages" required>
            </div>
            <div class="mb-4">
                <label for="amount" class="block mb-2 text-sm font-medium">Amount (£)</label>
                <input type="number" name="amount" id="amount" class="form-input" step="0.01" placeholder="1800.00" required>
            </div>
            <div class="mb-4">
                <label for="from_account_id" class="block mb-2 text-sm font-medium">Which account does it go into?</label>
                <select name="from_account_id" id="from_account_id" class="form-input" required>
                    <?php foreach($accounts as $account): ?>
                        <option value="<?php echo $account['account_id']; ?>"><?php echo htmlspecialchars($account['account_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
             <div class="mb-4">
                <label for="transaction_date" class="block mb-2 text-sm font-medium">Date of First/Last Payment</label>
                <input type="date" name="transaction_date" id="transaction_date" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
             <div class="mb-6 p-4 border border-gray-700 rounded-lg space-y-4">
                <label class="block text-sm font-medium text-center">How often do you receive this?</label>
                <div class="flex items-center space-x-4">
                    <div class="flex-grow">
                        <select name="frequency" id="frequency" class="form-input">
                            <option value="monthly">Monthly</option>
                            <option value="weekly">Weekly</option>
                        </select>
                    </div>
                    <div>
                        <input type="number" name="interval_value" id="interval_value" class="form-input w-24 text-center" value="1" min="1">
                    </div>
                </div>
            </div>
            <button type="submit" class="w-full btn btn-primary">Finish Setup & Go to Dashboard</button>
        </form>
    </div>
</section>