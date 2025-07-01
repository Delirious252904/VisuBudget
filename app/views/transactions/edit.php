<!-- app/views/transactions/edit.php -->
<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 max-w-lg mx-auto">
    <h2 class="text-2xl font-bold mb-6">Edit Transaction</h2>
    
    <!-- Update Form -->
    <form id="update-form" action="/transaction/update/<?php echo $transaction['transaction_id']; ?>" method="POST">
        
        <div class="mb-4">
            <label for="description" class="block mb-2 text-sm font-medium text-gray-300">Description</label>
            <input type="text" name="description" id="description" class="form-input" value="<?php echo htmlspecialchars($transaction['description']); ?>" required>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="amount" class="block mb-2 text-sm font-medium text-gray-300">Amount (£)</label>
                <input type="number" name="amount" id="amount" class="form-input" step="0.01" value="<?php echo number_format($transaction['amount'], 2, '.', ''); ?>" required>
            </div>
            <div>
                <label for="transaction_date" class="block mb-2 text-sm font-medium text-gray-300">Date</label>
                <input type="date" name="transaction_date" id="transaction_date" class="form-input" value="<?php echo htmlspecialchars($transaction['transaction_date']); ?>" required>
            </div>
        </div>

        <!-- FIX: Added Type and Account Selectors -->
        <div class="mb-4">
            <label for="type" class="block mb-2 text-sm font-medium text-gray-300">Transaction Type</label>
            <select name="type" id="type" class="form-input" required>
                <option value="expense" <?php echo ($transaction['type'] == 'expense') ? 'selected' : ''; ?>>Expense</option>
                <option value="income" <?php echo ($transaction['type'] == 'income') ? 'selected' : ''; ?>>Income</option>
                <option value="transfer" <?php echo ($transaction['type'] == 'transfer') ? 'selected' : ''; ?>>Transfer</option>
            </select>
        </div>

        <div id="from_account_container" class="mb-4">
            <label for="from_account_id" class="block mb-2 text-sm font-medium text-gray-300">From Account</label>
            <select name="from_account_id" id="from_account_id" class="form-input">
                <option value="">None</option>
                <?php foreach ($accounts as $account): ?>
                    <option value="<?php echo $account['account_id']; ?>" <?php echo ($transaction['from_account_id'] == $account['account_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($account['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="to_account_container" class="mb-4">
            <label for="to_account_id" class="block mb-2 text-sm font-medium text-gray-300">To Account</label>
            <select name="to_account_id" id="to_account_id" class="form-input">
                <option value="">None</option>
                 <?php foreach ($accounts as $account): ?>
                    <option value="<?php echo $account['account_id']; ?>" <?php echo ($transaction['to_account_id'] == $account['account_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($account['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex items-center justify-end space-x-4 mt-6">
            <a href="/transactions" class="text-gray-400 hover:text-white">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Transaction</button>
        </div>
    </form>
    
    <!-- Delete Button Form -->
    <div class="border-t border-gray-700 mt-6 pt-6 text-center">
        <form action="/transaction/delete/<?php echo $transaction['transaction_id']; ?>" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this transaction?');">
            <button type="submit" class="btn text-red-500 text-sm hover:underline">
                Delete this Transaction
            </button>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const fromContainer = document.getElementById('from_account_container');
    const toContainer = document.getElementById('to_account_container');

    function toggleAccountFields() {
        const type = typeSelect.value;
        fromContainer.style.display = (type === 'expense' || type === 'transfer') ? 'block' : 'none';
        toContainer.style.display = (type === 'income' || type === 'transfer') ? 'block' : 'none';
    }

    // Initial check on page load
    toggleAccountFields();

    // Add event listener for changes
    typeSelect.addEventListener('change', toggleAccountFields);
});
</script>
