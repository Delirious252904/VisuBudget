<!-- app/views/recurring/edit.php -->
<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 max-w-lg mx-auto">
    <h2 class="text-2xl font-bold mb-6 flex items-center">
        <i class="fas fa-edit mr-3 text-blue-400"></i>
        Edit Recurring Rule
    </h2>

    <!-- We add a unique ID to the form -->
    <form id="edit-recurring-form" action="/recurring/edit/<?php echo $rule['rule_id']; ?>" method="POST">
        
        <!-- Transaction Type -->
        <div class="mb-4">
            <label for="type" class="block mb-2 text-sm font-medium text-gray-300">Transaction Type</label>
            <select name="type" id="type" class="form-input" required>
                <option value="expense" <?php if ($rule['type'] === 'expense') echo 'selected'; ?>>Expense</option>
                <option value="income" <?php if ($rule['type'] === 'income') echo 'selected'; ?>>Income</option>
                <option value="transfer" <?php if ($rule['type'] === 'transfer') echo 'selected'; ?>>Transfer</option>
            </select>
        </div>

        <!-- From Account -->
        <div class="mb-4">
            <label for="from_account_id" id="from_account_label" class="block mb-2 text-sm font-medium text-gray-300">From Account</label>
            <select name="from_account_id" id="from_account_id" class="form-input" required>
                <?php foreach($accounts as $account): ?>
                    <option value="<?php echo $account['account_id']; ?>" <?php if ($rule['from_account_id'] == $account['account_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($account['account_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- To Account (for transfers) -->
        <div class="mb-4" id="to_account_container" style="display: none;">
            <label for="to_account_id" class="block mb-2 text-sm font-medium text-gray-300">To Account</label>
            <select name="to_account_id" id="to_account_id" class="form-input">
                <?php foreach($accounts as $account): ?>
                    <option value="<?php echo $account['account_id']; ?>" <?php if ($rule['to_account_id'] == $account['account_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($account['account_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Description -->
        <div class="mb-4">
            <label for="description" class="block mb-2 text-sm font-medium text-gray-300">Description</label>
            <input type="text" name="description" id="description" class="form-input" value="<?php echo htmlspecialchars($rule['description']); ?>" required>
        </div>

        <!-- Amount -->
        <div class="mb-4">
            <label for="amount" class="block mb-2 text-sm font-medium text-gray-300">Amount</label>
            <input type="number" name="amount" id="amount" class="form-input" step="0.01" value="<?php echo htmlspecialchars($rule['amount']); ?>" required>
        </div>

        <!-- Start Date -->
        <!-- NOTE: The 'transaction_date' name is used to match the controller logic -->
        <div class="mb-4">
            <label for="transaction_date" class="block mb-2 text-sm font-medium text-gray-300">Start Date</label>
            <input type="date" name="transaction_date" id="transaction_date" class="form-input" value="<?php echo htmlspecialchars($rule['start_date']); ?>" required>
        </div>
        
        <!-- Recurrence Options are always visible on the edit page -->
        <div class="mb-4 p-4 border border-gray-700 rounded-lg space-y-4">
            <!-- Frequency and Interval -->
            <div class="flex items-center space-x-4">
                <div class="flex-grow">
                    <label for="frequency" class="block mb-2 text-sm font-medium text-gray-300">Frequency</label>
                    <select name="frequency" id="frequency" class="form-input">
                        <option value="weekly" <?php if ($rule['frequency'] === 'weekly') echo 'selected'; ?>>Weekly</option>
                        <option value="monthly" <?php if ($rule['frequency'] === 'monthly') echo 'selected'; ?>>Monthly</option>
                        <option value="yearly" <?php if ($rule['frequency'] === 'yearly') echo 'selected'; ?>>Yearly</option>
                    </select>
                </div>
                <div>
                     <label for="interval_value" class="block mb-2 text-sm font-medium text-gray-300">Every</label>
                    <input type="number" name="interval_value" id="interval_value" class="form-input w-24 text-center" value="<?php echo htmlspecialchars($rule['interval_value']); ?>">
                </div>
            </div>

            <!-- Day of Week (for weekly) -->
            <div id="day_of_week_selector">
                <label for="day_of_week" class="block mb-2 text-sm font-medium text-gray-300">On a</label>
                <select name="day_of_week" id="day_of_week" class="form-input">
                    <option value="1" <?php if ($rule['day_of_week'] == 1) echo 'selected'; ?>>Sunday</option>
                    <option value="2" <?php if ($rule['day_of_week'] == 2) echo 'selected'; ?>>Monday</option>
                    <option value="3" <?php if ($rule['day_of_week'] == 3) echo 'selected'; ?>>Tuesday</option>
                    <option value="4" <?php if ($rule['day_of_week'] == 4) echo 'selected'; ?>>Wednesday</option>
                    <option value="5" <?php if ($rule['day_of_week'] == 5) echo 'selected'; ?>>Thursday</option>
                    <option value="6" <?php if ($rule['day_of_week'] == 6) echo 'selected'; ?>>Friday</option>
                    <option value="7" <?php if ($rule['day_of_week'] == 7) echo 'selected'; ?>>Saturday</option>
                </select>
            </div>
            
            <!-- End Condition -->
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-300">Ends</label>
                 <div class="flex items-center space-x-4">
                    <div class="flex-grow">
                        <label for="end_date" class="sr-only">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-input" value="<?php echo htmlspecialchars($rule['end_date']); ?>">
                    </div>
                    <span class="text-gray-400">OR</span>
                    <div>
                         <label for="occurrences" class="sr-only">Occurrences</label>
                         <input type="number" name="occurrences" id="occurrences" class="form-input w-24 text-center" placeholder="Times" value="<?php echo htmlspecialchars($rule['occurrences']); ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <button type="submit" class="w-full btn btn-primary">
                <i class="fas fa-save mr-2"></i> Update Rule
            </button>
        </div>
    </form>
</section>
