<!-- -------------------------------------------------- -->
<!-- File: app/views/transactions/edit.php                -->
<!-- This is the form for editing a single transaction. -->
<!-- -------------------------------------------------- -->
<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 max-w-lg mx-auto">
    <h2 class="text-2xl font-bold mb-6">Edit Transaction</h2>
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
        
        <p class="text-xs text-gray-500 mb-6 text-center">Note: The transaction type and associated accounts cannot be changed after creation. To change these, please delete this transaction and create a new one.</p>
        
        <div class="flex items-center justify-end space-x-4">
             <a href="/transactions" class="text-gray-400 hover:text-white">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Transaction</button>
        </div>
    </form>
</section>