<!-- -------------------------------------------------- -->
<!-- File: app/views/accounts/reset.php                 -->
<!-- This is the form for manually resetting a balance. -->
<!-- -------------------------------------------------- -->
<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 max-w-lg mx-auto text-left">
    <h2 class="text-2xl font-bold mb-4">Reset Balance for "<?php echo htmlspecialchars($account['account_name']); ?>"</h2>
    <p class="text-gray-400 mb-6">
        Enter the correct current balance for this account as it appears on your actual bank statement or app. This will override the app's calculated balance and set it to the new amount you provide.
    </p>
    
    <form action="/account/reset/<?php echo $account['account_id']; ?>" method="POST">
        <div class="mb-6">
            <label for="current_balance" class="block mb-2 text-sm font-medium text-gray-300">Correct Current Balance (£)</label>
            <input 
                type="number" 
                name="current_balance" 
                id="current_balance" 
                class="form-input text-lg" 
                step="0.01" 
                value="<?php echo number_format($account['current_balance'], 2, '.', ''); ?>" 
                required
            >
        </div>
        
        <div class="flex items-center justify-end space-x-4">
             <a href="/accounts" class="text-gray-400 hover:text-white">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-2"></i>
                Set New Balance
            </button>
        </div>
    </form>
</section>
