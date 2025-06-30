<!-- app/views/transactions/edit.php -->
<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 max-w-lg mx-auto">
    <h2 class="text-2xl font-bold mb-6">Edit Transaction</h2>
    
    <!-- Update Form -->
    <form action="/transaction/edit/<?php echo $transaction['transaction_id']; ?>" method="POST">
        
        <div class="mb-4">
            <label for="description" class="block mb-2 text-sm font-medium text-gray-300">Description</label>
            <input type="text" name="description" id="description" class="form-input" value="<?php echo htmlspecialchars($transaction['description']); ?>" required>
        </div>
        
        <div class="mb-4">
            <label for="amount" class="block mb-2 text-sm font-medium text-gray-300">Amount (£)</label>
            <input type="number" name="amount" id="amount" class="form-input" step="0.01" value="<?php echo number_format($transaction['amount'], 2, '.', ''); ?>" required>
        </div>

        <div class="mb-6">
            <label for="transaction_date" class="block mb-2 text-sm font-medium text-gray-300">Date</label>
            <input type="date" name="transaction_date" id="transaction_date" class="form-input" value="<?php echo htmlspecialchars($transaction['transaction_date']); ?>" required>
        </div>
        
        <p class="text-xs text-gray-500 mb-6 text-center">Note: The transaction type and accounts cannot be changed. To modify these, please delete this transaction and create a new one.</p>
        
        <div class="flex items-center justify-between">
            <!-- Delete Button Form -->
            <form action="/transaction/delete/<?php echo $transaction['transaction_id']; ?>" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this transaction?');">
                <button type="submit" class="btn bg-red-600 hover:bg-red-500 text-white">
                    <i class="fas fa-trash-alt mr-2"></i>Delete
                </button>
            </form>
            
            <!-- Update and Cancel Buttons -->
            <div class="flex items-center space-x-4">
                <a href="/transactions" class="text-gray-400 hover:text-white">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Transaction</button>
            </div>
        </div>
    </form>
</section>
