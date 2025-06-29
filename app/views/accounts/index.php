<!-- -------------------------------------------------- -->
<!-- File: app/views/accounts/index.php                 -->
<!-- This page lists all of the user's accounts.        -->
<!-- -------------------------------------------------- -->
<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold flex items-center">
            <i class="fas fa-university mr-3 text-blue-400"></i> Manage Accounts
        </h2>
        <a href="/account/add" class="btn btn-primary text-sm"><i class="fas fa-plus"></i> Add New Account</a>
    </div>
    <div class="space-y-4">
        <?php if (empty($accounts)): ?>
            <p class="text-center text-gray-400 py-8">You haven't added any accounts yet.</p>
        <?php else: ?>
            <?php foreach ($accounts as $account): ?>
                <div class="bg-gray-700 rounded-lg p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between">
                    <div class="mb-4 sm:mb-0">
                        <p class="font-bold text-lg"><?php echo htmlspecialchars($account['account_name']); ?></p>
                        <p class="text-sm text-gray-400">Current Balance: <span class="font-mono">£<?php echo number_format($account['current_balance'], 2); ?></span></p>
                    </div>
                    <div class="flex-shrink-0">
                        <a href="/account/reset/<?php echo $account['account_id']; ?>" class="text-green-400 hover:text-green-300 mr-4 text-sm font-medium">Reset Balance</a>
                        <a href="/account/edit/<?php echo $account['account_id']; ?>" class="text-blue-400 hover:text-blue-300 mr-4 text-sm font-medium">Edit</a>
                        <form action="/account/delete/<?php echo $account['account_id']; ?>" method="POST" class="inline-block js-delete-form">
                            <button type="submit" class="text-red-400 hover:text-red-300 bg-transparent border-none p-0 cursor-pointer text-sm font-medium">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- This is a copy of the modal from the recurring page. -->
<!-- Later, we could move this to footer.php to avoid repeating code. -->
<div id="confirmation-modal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center p-4 hidden">
    <div class="bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-sm text-center">
        <h3 class="text-xl font-bold mb-4">Are you sure?</h3>
        <p id="modal-text" class="text-gray-400 mb-6">This will permanently delete the account and all of its transactions. This action cannot be undone.</p>
        <div class="flex justify-center space-x-4">
            <button id="modal-cancel-button" class="btn bg-gray-600 hover:bg-gray-500">Cancel</button>
            <button id="modal-confirm-button" class="btn bg-red-600 hover:bg-red-500 text-white">Yes, Delete It</button>
        </div>
    </div>
</div>