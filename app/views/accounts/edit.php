<?php
// app/views/accounts/edit.php
?>

<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 max-w-lg mx-auto">
    <h2 class="text-2xl font-bold mb-6">Edit Account</h2>

    <!-- Edit Account Form -->
    <form action="/accounts/update/<?php echo htmlspecialchars($account['account_id']); ?>" method="post">
        <div class="mb-4">
            <label for="name" class="block mb-2 text-sm font-medium text-gray-300">Account Name</label>
            <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($account['name']); ?>" required>
        </div>
        <div class="mb-4">
            <label for="balance" class="block mb-2 text-sm font-medium text-gray-300">Current Balance (£)</label>
            <input type="number" step="0.01" id="balance" name="balance" class="form-input" value="<?php echo number_format($account['balance'], 2, '.', ''); ?>" required>
        </div>
        <div class="mb-6">
            <label for="type" class="block mb-2 text-sm font-medium text-gray-300">Account Type</label>
            <select id="type" name="type" class="form-input">
                <option value="checking" <?php echo ($account['type'] == 'checking') ? 'selected' : ''; ?>>Checking</option>
                <option value="savings" <?php echo ($account['type'] == 'savings') ? 'selected' : ''; ?>>Savings</option>
                <option value="credit" <?php echo ($account['type'] == 'credit') ? 'selected' : ''; ?>>Credit</option>
            </select>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex items-center justify-end space-x-4">
            <a href="/accounts" class="text-gray-400 hover:text-white">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Account</button>
        </div>
    </form>

    <!-- Danger Zone -->
    <div class="border-t border-gray-700 mt-6 pt-6 text-left">
        <h3 class="text-xl font-bold mb-4 text-red-500">Danger Zone</h3>
        <div class="space-y-6">
            <!-- Reset Balance Section -->
            <div>
                <p class="text-sm text-gray-400 mb-2">
                    <strong>Reset Account Balance:</strong> This will set the account balance to £0.00 and delete all associated transactions. This action cannot be undone.
                </p>
                <form action="/accounts/reset/<?php echo htmlspecialchars($account['account_id']); ?>" method="post" onsubmit="return confirm('Are you sure you want to reset this account\'s balance to £0.00? This will also delete all associated transactions and cannot be undone.');">
                    <button type="submit" class="btn text-yellow-500 text-sm hover:underline">
                        Reset Account Balance
                    </button>
                </form>
            </div>
            <!-- Delete Account Section -->
            <div>
                <p class="text-sm text-gray-400 mb-2">
                    <strong>Delete Account:</strong> This will permanently delete this account and all of its transactions. This action cannot be undone.
                </p>
                <form action="/accounts/delete/<?php echo htmlspecialchars($account['account_id']); ?>" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this account? This cannot be undone.');">
                    <button type="submit" class="btn text-red-500 text-sm hover:underline">
                        Delete this Account
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
