<?php
// app/views/recurring/edit.php
?>

<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 max-w-lg mx-auto">
    <h2 class="text-2xl font-bold mb-6">Edit Recurring Rule</h2>

    <!-- Update Form -->
    <form id="update-form" action="/recurring/update/<?php echo htmlspecialchars($rule['rule_id']); ?>" method="POST">
        
        <div class="mb-4">
            <label for="description" class="block mb-2 text-sm font-medium text-gray-300">Description</label>
            <input type="text" name="description" id="description" class="form-input" value="<?php echo htmlspecialchars($rule['description']); ?>" required>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="amount" class="block mb-2 text-sm font-medium text-gray-300">Amount (£)</label>
                <input type="number" name="amount" id="amount" class="form-input" step="0.01" value="<?php echo number_format($rule['amount'], 2, '.', ''); ?>" required>
            </div>
            <div>
                <label for="start_date" class="block mb-2 text-sm font-medium text-gray-300">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-input" value="<?php echo htmlspecialchars($rule['start_date']); ?>" required>
            </div>
        </div>

        <div class="mb-4">
            <label for="type" class="block mb-2 text-sm font-medium text-gray-300">Type</label>
            <select name="type" id="type" class="form-input" required>
                <option value="expense" <?php echo ($rule['type'] == 'expense') ? 'selected' : ''; ?>>Expense</option>
                <option value="income" <?php echo ($rule['type'] == 'income') ? 'selected' : ''; ?>>Income</option>
                <option value="transfer" <?php echo ($rule['type'] == 'transfer') ? 'selected' : ''; ?>>Transfer</option>
            </select>
        </div>

        <div id="from_account_container" class="mb-4">
            <label for="from_account_id" class="block mb-2 text-sm font-medium text-gray-300">From Account</label>
            <select name="from_account_id" id="from_account_id" class="form-input">
                <option value="">None</option>
                <?php foreach ($accounts as $account): ?>
                    <option value="<?php echo $account['account_id']; ?>" <?php echo ($rule['from_account_id'] == $account['account_id']) ? 'selected' : ''; ?>>
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
                    <option value="<?php echo $account['account_id']; ?>" <?php echo ($rule['to_account_id'] == $account['account_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($account['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label for="frequency" class="block mb-2 text-sm font-medium text-gray-300">Frequency</label>
            <select name="frequency" id="frequency" class="form-input" required>
                <option value="daily" <?php echo ($rule['frequency'] == 'daily') ? 'selected' : ''; ?>>Daily</option>
                <option value="weekly" <?php echo ($rule['frequency'] == 'weekly') ? 'selected' : ''; ?>>Weekly</option>
                <option value="monthly" <?php echo ($rule['frequency'] == 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                <option value="yearly" <?php echo ($rule['frequency'] == 'yearly') ? 'selected' : ''; ?>>Yearly</option>
            </select>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex items-center justify-end space-x-4 mt-6">
            <a href="/recurring" class="text-gray-400 hover:text-white">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Rule</button>
        </div>
    </form>
    
    <!-- Delete Button Form -->
    <div class="border-t border-gray-700 mt-6 pt-6 text-center">
        <form action="/recurring/delete/<?php echo $rule['rule_id']; ?>" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this recurring rule and all of its future transactions?');">
            <button type="submit" class="btn text-red-500 text-sm hover:underline">
                Delete this Recurring Rule
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
