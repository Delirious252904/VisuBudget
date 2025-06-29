<!-- app/views/add_transaction/index.php -->
<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 max-w-lg mx-auto">
    <h2 class="text-2xl font-bold mb-6 flex items-center">
        <i class="fas fa-plus-circle mr-3 text-blue-400"></i>
        Add a New Transaction
    </h2>

    <form action="/transaction/add" method="POST">
        <!-- Transaction Type -->
        <div class="mb-4">
            <label for="type" class="block mb-2 text-sm font-medium text-gray-300">Transaction Type</label>
            <select name="type" id="type" class="form-input" required>
                <option value="expense">Expense</option>
                <option value="income">Income</option>
                <option value="transfer">Transfer</option>
            </select>
        </div>


       <!-- From Account (Label changes based on type) -->
        <div class="mb-4">
            <label for="from_account_id" id="from_account_label" class="block mb-2 text-sm font-medium text-gray-300">From Account</label>
            <select name="from_account_id" id="from_account_id" class="form-input" required>
                <option value="">-- Select Account --</option>
                <?php foreach($accounts as $account): ?>
                    <option value="<?php echo $account['account_id']; ?>"><?php echo htmlspecialchars($account['account_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- To Account (hidden by default) -->
        <div class="mb-4" id="to_account_container" style="display: none;">
            <label for="to_account_id" class="block mb-2 text-sm font-medium text-gray-300">To Account</label>
            <select name="to_account_id" id="to_account_id" class="form-input">
                <option value="">-- Select Account --</option>
                 <?php foreach($accounts as $account): ?>
                    <option value="<?php echo $account['account_id']; ?>"><?php echo htmlspecialchars($account['account_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Description -->
        <div class="mb-4">
            <label for="description" class="block mb-2 text-sm font-medium text-gray-300">Description</label>
            <input type="text" name="description" id="description" class="form-input" placeholder="e.g., Groceries, Salary" required>
        </div>

        <!-- Amount -->
        <div class="mb-4">
            <label for="amount" class="block mb-2 text-sm font-medium text-gray-300">Amount</label>
            <input type="number" name="amount" id="amount" class="form-input" step="0.01" placeholder="0.00" required>
        </div>

        <!-- Date (This becomes the "Start Date" for recurring) -->
        <div class="mb-4">
            <label for="transaction_date" class="block mb-2 text-sm font-medium text-gray-300">Date of First Occurrence</label>
            <input type="date" name="transaction_date" id="transaction_date" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <!-- Recurrence Toggle -->
        <div class="mb-4 p-4 border border-gray-700 rounded-lg">
            <label for="is_recurring" class="flex items-center cursor-pointer">
                <input type="checkbox" name="is_recurring" id="is_recurring" value="1" class="form-checkbox h-5 w-5 text-blue-500 bg-gray-700 border-gray-600 rounded focus:ring-blue-600">
                <span class="ml-3 text-lg font-medium text-gray-200">Make this a recurring transaction</span>
            </label>
        
            <!-- Hidden Recurrence Options -->
            <div id="recurrence_options" class="hidden mt-4 pt-4 border-t border-gray-700 space-y-4">
                <!-- Frequency and Interval -->
                <div class="flex items-center space-x-4">
                    <div class="flex-grow">
                        <label for="frequency" class="block mb-2 text-sm font-medium text-gray-300">Frequency</label>
                        <select name="frequency" id="frequency" class="form-input">
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                    <div>
                         <label for="interval_value" class="block mb-2 text-sm font-medium text-gray-300">Every</label>
                        <input type="number" name="interval_value" id="interval_value" class="form-input w-24 text-center" value="1">
                    </div>
                </div>

                <!-- Day of Week (for weekly) -->
                <div id="day_of_week_selector" class="hidden">
                    <label for="day_of_week" class="block mb-2 text-sm font-medium text-gray-300">On a</label>
                    <select name="day_of_week" id="day_of_week" class="form-input">
                        <option value="1">Sunday</option>
                        <option value="2">Monday</option>
                        <option value="3">Tuesday</option>
                        <option value="4">Wednesday</option>
                        <option value="5">Thursday</option>
                        <option value="6">Friday</option>
                        <option value="7">Saturday</option>
                    </select>
                </div>
                
                <!-- End Condition -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-300">Ends</label>
                     <div class="flex items-center space-x-4">
                        <div class="flex-grow">
                            <label for="end_date" class="sr-only">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-input">
                        </div>
                        <span class="text-gray-400">OR</span>
                        <div>
                             <label for="occurrences" class="sr-only">Occurrences</label>
                             <input type="number" name="occurrences" id="occurrences" class="form-input w-24 text-center" placeholder="Times">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div>
            <button type="submit" class="w-full btn btn-primary">
                <i class="fas fa-save mr-2"></i> Save Transaction
            </button>
        </div>
    </form>
</section>
