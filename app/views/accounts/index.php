<!-- app/views/accounts/index.php -->

<div class="container mx-auto p-4 md:p-8">
    <h1 class="text-3xl font-bold mb-6 text-white">Manage Accounts</h1>

    <div class="bg-gray-800 rounded-lg shadow-lg p-6">
        <div class="space-y-6">
            <?php if (empty($accounts)): ?>
                <p class="text-center text-gray-400 py-8">You haven't added any accounts yet.</p>
            <?php else: ?>
                <?php foreach ($accounts as $account): ?>
                    <div id="account-<?php echo $account['account_id']; ?>" class="bg-gray-700 p-4 rounded-lg shadow-md transition-all duration-300">
                        <div class="flex justify-between items-center">
                            <div class="flex-grow">
                                <h3 class="font-bold text-lg text-white"><?php echo htmlspecialchars($account['account_name']); ?></h3>
                                <p class="text-sm text-gray-400">
                                    Current Balance: 
                                    <span class="font-semibold text-white">£<?php echo number_format($account['current_balance'], 2); ?></span>
                                </p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <a href="/account/edit/<?php echo $account['account_id']; ?>" class="text-blue-400 hover:text-blue-300"><i class="fas fa-edit"></i></a>
                                <form action="/account/delete/<?php echo $account['account_id']; ?>" method="post" onsubmit="return confirm('Are you sure? This will delete the account and all its associated transactions.');">
                                    <button type="submit" class="text-red-500 hover:text-red-400"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </div>
                        </div>

                        <!-- **NEW**: Display Recurring Totals -->
                        <?php if ($account['recurring_income'] > 0 || $account['recurring_expenses'] > 0): ?>
                        <div class="border-t border-gray-600 mt-4 pt-3 flex flex-col sm:flex-row justify-start sm:space-x-6 space-y-2 sm:space-y-0 text-sm">
                            <?php if ($account['recurring_income'] > 0): ?>
                                <div class="flex items-center text-green-400">
                                    <i class="fas fa-arrow-up mr-2"></i>
                                    <span>Regular Income: <strong>£<?php echo number_format($account['recurring_income'], 2); ?></strong>/month</span>
                                </div>
                            <?php endif; ?>

                            <?php if ($account['recurring_expenses'] > 0): ?>
                                <div class="flex items-center text-red-400">
                                    <i class="fas fa-arrow-down mr-2"></i>
                                    <span>Regular Expenses: <strong>£<?php echo number_format($account['recurring_expenses'], 2); ?></strong>/month</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <!-- End of New Section -->
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="mt-8 text-center">
            <a href="/account/add" class="btn btn-primary">
                <i class="fas fa-plus mr-2"></i> Add New Account
            </a>
        </div>
    </div>
</div>
