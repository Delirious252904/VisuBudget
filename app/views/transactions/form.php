<?php
// app/views/transactions/form.php
// This is the unified form for creating and editing all transactions.

// Determine the mode (add vs edit) and set the title
$is_editing = isset($transaction) && !empty($transaction['id']);
$page_title = $is_editing ? 'Edit Transaction' : 'Add New Transaction';

// Set default values for new entries
$transaction = $transaction ?? [
    'id' => null,
    'description' => '',
    'amount' => '',
    'type' => 'expense',
    'transaction_date' => date('Y-m-d'),
    'from_account_id' => null,
    'to_account_id' => null,
    'is_recurring' => false,
    'frequency' => 'monthly',
    'start_date' => date('Y-m-d'),
    'end_date' => null,
    'occurrences' => null
];

// Determine if the form is for a recurring rule
$is_recurring = (bool)($transaction['is_recurring'] ?? false);

?>
<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6"><?php echo $page_title; ?></h2>

    <form action="/transaction/save" method="POST">
        <!-- Hidden fields to manage state -->
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($transaction['id'] ?? ''); ?>">
        <input type="hidden" name="original_type" value="<?php echo $is_recurring ? 'recurring' : 'single'; ?>">

        <!-- Core Details -->
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
                <label for="type" class="block mb-2 text-sm font-medium text-gray-300">Type</label>
                <select name="type" id="type" class="form-input" required>
                    <option value="expense" <?php echo ($transaction['type'] == 'expense') ? 'selected' : ''; ?>>Expense</option>
                    <option value="income" <?php echo ($transaction['type'] == 'income') ? 'selected' : ''; ?>>Income</option>
                    <option value="transfer" <?php echo ($transaction['type'] == 'transfer') ? 'selected' : ''; ?>>Transfer</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div id="from_account_container">
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
            <div id="to_account_container">
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
        </div>

        <!-- Recurring Toggle -->
        <div class="border-t border-b border-gray-700 my-6 py-4">
            <label for="is_recurring" class="flex items-center cursor-pointer">
                <input type="checkbox" name="is_recurring" id="is_recurring" class="form-checkbox" <?php echo $is_recurring ? 'checked' : ''; ?>>
                <span class="ml-3 text-sm font-medium text-gray-300">Make this a Recurring Transaction</span>
            </label>
        </div>

        <!-- Recurring-Specific Fields (hidden by default) -->
        <div id="recurring_fields" class="<?php echo $is_recurring ? '' : 'hidden'; ?>">
             <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="frequency" class="block mb-2 text-sm font-medium text-gray-300">Frequency</label>
                    <select name="frequency" id="frequency" class="form-input">
                        <option value="daily" <?php echo ($transaction['frequency'] == 'daily') ? 'selected' : ''; ?>>Daily</option>
                        <option value="weekly" <?php echo ($transaction['frequency'] == 'weekly') ? 'selected' : ''; ?>>Weekly</option>
                        <option value="monthly" <?php echo ($transaction['frequency'] == 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                        <option value="yearly" <?php echo ($transaction['frequency'] == 'yearly') ? 'selected' : ''; ?>>Yearly</option>
                    </select>
                </div>
                <div>
                    <label for="start_date" class="block mb-2 text-sm font-medium text-gray-300">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-input" value="<?php echo htmlspecialchars($transaction['start_date'] ?? $transaction['transaction_date']); ?>" required>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="end_date" class="block mb-2 text-sm font-medium text-gray-300">End Date (Optional)</label>
                    <input type="date" name="end_date" id="end_date" class="form-input" value="<?php echo htmlspecialchars($transaction['end_date'] ?? ''); ?>">
                </div>
                 <div>
                    <label for="occurrences" class="block mb-2 text-sm font-medium text-gray-300">Max Occurrences (Optional)</label>
                    <input type="number" name="occurrences" id="occurrences" class="form-input" min="0" step="1" value="<?php echo htmlspecialchars($transaction['occurrences'] ?? ''); ?>">
                </div>
            </div>
        </div>
        
        <!-- Non-recurring date field -->
        <div id="single_date_container" class="mb-6 <?php echo $is_recurring ? 'hidden' : ''; ?>">
            <label for="transaction_date" class="block mb-2 text-sm font-medium text-gray-300">Date</label>
            <input type="date" name="transaction_date" id="transaction_date" class="form-input" value="<?php echo htmlspecialchars($transaction['transaction_date']); ?>" required>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-between mt-6">
            <a href="/transactions" class="text-gray-400 hover:text-white">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <?php echo $is_editing ? 'Update' : 'Create'; ?>
            </button>
        </div>
    </form>
    
    <!-- Delete Button (only shows when editing) -->
    <?php if ($is_editing): ?>
    <div class="border-t border-gray-700 mt-6 pt-6 text-center">
        <form action="/transaction/delete" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this?');">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($transaction['id']); ?>">
            <input type="hidden" name="type" value="<?php echo $is_recurring ? 'recurring' : 'single'; ?>">
            <button type="submit" class="btn text-red-500 text-sm hover:underline">
                Delete <?php echo $is_recurring ? 'Recurring Rule' : 'Transaction'; ?>
            </button>
        </form>
    </div>
    <?php endif; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const fromContainer = document.getElementById('from_account_container');
    const toContainer = document.getElementById('to_account_container');
    const recurringCheckbox = document.getElementById('is_recurring');
    const recurringFields = document.getElementById('recurring_fields');
    const singleDateContainer = document.getElementById('single_date_container');

    function toggleAccountFields() {
        const type = typeSelect.value;
        fromContainer.style.display = (type === 'expense' || type === 'transfer') ? 'block' : 'none';
        toContainer.style.display = (type === 'income' || type === 'transfer') ? 'block' : 'none';
    }

    function toggleRecurringFields() {
        if (recurringCheckbox.checked) {
            recurringFields.classList.remove('hidden');
            singleDateContainer.classList.add('hidden');
        } else {
            recurringFields.classList.add('hidden');
            singleDateContainer.classList.remove('hidden');
        }
    }

    toggleAccountFields();
    toggleRecurringFields();

    typeSelect.addEventListener('change', toggleAccountFields);
    recurringCheckbox.addEventListener('change', toggleRecurringFields);
});
</script>
