<!-- app/views/dashboard/index.php -->

<!-- Main "Safe to Spend" Section -->
<!-- Main "Safe to Spend" Section -->
<section class="text-center mb-8 p-6 bg-gray-800 rounded-lg shadow-lg">
    <h2 class="text-2xl font-bold mb-2">
        Hello <?php 
            if (isset($user['name']) && !empty($user['name'])) {
                $firstName = explode(' ', $user['name'])[0];
                echo htmlspecialchars($firstName);
            } else {
                echo 'there';
            }
        ?>!
    </h2>
    
    <?php echo $safeToSpendMessage; ?>
        
    <p class="text-sm text-gray-500 mt-2">
        <?php echo htmlspecialchars($nextIncomeMessage); ?>
    </p>

    <?php if (isset($dailyAllowanceMessage)): ?>
    <div class="mt-4 pt-4 border-t border-gray-700">
        <p class="text-lg font-bold text-blue-300">
            <?php echo htmlspecialchars($dailyAllowanceMessage); ?>
        </p>
    </div>
    <?php endif; ?>

     <!-- Savings Goal Message -->
    <?php //if (isset($savingsMessage)): ?>
    <div class="mt-4 pt-4 border-t border-gray-700 bg-green-900/50 rounded-b-lg -mx-6 -mb-6 px-6 py-4">
        <p class="text-lg font-semibold text-green-300">
            <i class="fas fa-piggy-bank mr-2"></i>
            <?php echo $savingsMessage; ?>
        </p>
    </div>
    <?php //endif; ?>
</section>

<!-- Savings Goals Mini-View (Premium Only) -->
<?php if (isset($user) && $user['subscription_tier'] === 'premium' && !empty($topSavingsGoals)): ?>
<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 mb-8">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold flex items-center">
            <i class="fas fa-piggy-bank mr-3 text-blue-400"></i>
            Top Savings Goals
        </h2>
        <a href="/savings" class="text-sm text-blue-400 hover:underline">Manage All &rarr;</a>
    </div>
    <div class="space-y-4">
        <?php foreach ($topSavingsGoals as $goal): ?>
            <div>
                <div class="flex justify-between items-center mb-1 text-sm">
                    <span class="font-semibold text-gray-300"><?php echo htmlspecialchars($goal['goal_name']); ?></span>
                    
                    <?php if (isset($safeToSpend) && $safeToSpend > 0): ?>
                    <button class="js-save-button text-sm text-green-400 hover:text-green-300 font-bold"
                            data-goal-id="<?php echo $goal['goal_id']; ?>"
                            data-goal-name="<?php echo htmlspecialchars($goal['goal_name']); ?>">
                        <i class="fas fa-plus-circle mr-1"></i> Save
                    </button>
                    <?php endif; ?>
                </div>
                <!-- Progress Bar -->
                <div class="w-full bg-gray-700 rounded-full h-2.5">
                    <div class="bg-blue-500 h-2.5 rounded-full" style="width: <?php echo (($goal['target_amount'] > 0) ? ($goal['current_amount'] / $goal['target_amount']) * 100 : 0); ?>%"></div>
                </div>
                <div class="flex justify-end text-xs text-gray-400 mt-1">
                    <span>£<?php echo number_format($goal['current_amount'], 2); ?> / £<?php echo number_format($goal['target_amount'], 2); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Chart Section -->
<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 mb-8">
    <h2 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-chart-line mr-3 text-blue-400"></i>
        Last 30 Days of Spending
    </h2>
    <div>
        <!-- The chart will be drawn on this canvas element -->
        <canvas 
            id="expenseChart" 
            data-chart-data="<?php echo htmlspecialchars(json_encode($expenseChartData)); ?>"
        ></canvas>
    </div>
</section>

<!-- Main content grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- Accounts Section -->
    <div class="lg:col-span-2">
        <section class="bg-gray-800 rounded-lg shadow-lg p-6 mb-8">
            <h3 class="text-xl font-bold mb-4 flex items-center"><i class="fas fa-wallet mr-3 text-blue-400"></i> Accounts</h3>
            <div class="space-y-4">
                <?php if (empty($accounts)): ?>
                     <p class="text-gray-400 text-center">No accounts found. Add one to get started!</p>
                <?php else: ?>
                    <?php foreach ($accounts as $account): ?>
                        <!-- MODIFIED: The div is now a clickable link -->
                        <a href="/accounts#account-<?php echo $account['account_id']; ?>" class="block p-4 bg-gray-700 rounded-md hover:bg-gray-600 transition-colors duration-200">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-semibold"><?php echo htmlspecialchars($account['account_name']); ?></p>
                                    <p class="text-sm text-gray-400">Current Balance</p>
                                </div>
                                <p class="text-lg font-bold">£<?php echo number_format($account['current_balance'], 2); ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="flex justify-end items-center mt-4">
                <a href="/account/add" class="text-sm bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">
                    <i class="fas fa-plus"></i> Add Account
                </a>
                <a href="/accounts" class="text-sm bg-blue-400 hover:bg-blue-300 text-white font-semibold py-2 px-4 mx-2 rounded-lg">
                    <i class="fas fa-sliders-h"></i> Manage Accounts
                </a>
            </div>
        </section>
    </div>

    <!-- Upcoming Transactions Section -->
    <div class="lg:col-span-1">
        <section class="bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-bold mb-4 flex items-center"><i class="fas fa-calendar-alt mr-3 text-blue-400"></i> Upcoming</h3>
            <div class="space-y-4">
                <?php if (empty($upcomingTransactions)): ?>
                    <p class="text-gray-400 text-center">No upcoming transactions.</p>
                <?php else: ?>
                    <?php foreach ($upcomingTransactions as $tx):
                        $txDateObj = new DateTime($tx['transaction_date']); ?>
                        <!-- MODIFIED: The div is now a clickable link -->
                        <a href="/transactions#transaction-<?php echo $tx['transaction_id']; ?>" class="block p-2 rounded-md hover:bg-gray-700 transition-colors duration-200">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="text-center mr-4">
                                        <p class="font-bold text-sm uppercase"><?php echo $txDateObj->format('M'); ?></p>
                                        <p class="text-2xl font-bold"><?php echo $txDateObj->format('d'); ?></p>
                                    </div>
                                    <div>
                                        <p class="font-semibold"><?php echo htmlspecialchars($tx['description']); ?></p>
                                    </div>
                                </div>
                                <p class="font-bold <?php echo $tx['type'] === 'income' ? 'text-green-400' : 'text-red-400'; ?>">
                                    £<?php echo number_format(abs($tx['amount']), 2); ?>
                                </p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
                 <a href="/transactions" class="block text-center mt-6 text-blue-400 hover:text-blue-300 font-semibold">View all &rarr;</a>
            </div>
        </section>
    </div>
</div>

<!-- **NEW** - Contribution Modal HTML -->
<!-- This is the hidden pop-up form that our JavaScript will show. -->
<div id="contribution-modal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center p-4 hidden">
    <div class="bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-sm text-left">
        <h3 id="modal-title" class="text-xl font-bold mb-4">Save towards Goal</h3>
        <form id="contribution-form" action="" method="POST">
            <div class="mb-4">
                <label for="amount" class="block mb-2 text-sm font-medium">Amount to Save (£)</label>
                <input type="number" name="amount" id="amount" class="form-input" step="0.01" min="0.01" max="<?php echo number_format($safeToSpend ?? 0, 2, '.', ''); ?>" required>
                <p class="text-xs text-gray-500 mt-1">You can save up to your "Safe to Spend" amount.</p>
            </div>
            <div class="mb-6">
                <label for="from_account_id" class="block mb-2 text-sm font-medium">From which account?</label>
                <select name="from_account_id" id="from_account_id" class="form-input" required>
                    <option value="">-- Select Account --</option>
                    <?php if (isset($accounts)): ?>
                        <?php foreach ($accounts as $account): ?>
                            <option value="<?php echo $account['account_id']; ?>"><?php echo htmlspecialchars($account['account_name']); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" id="modal-cancel-button" class="btn bg-gray-600 hover:bg-gray-500">Cancel</button>
                <button type="submit" class="btn btn-primary">Save to Goal</button>
            </div>
        </form>
    </div>
</div>

<!-- Floating Action Button -->
<a href="/transaction/add" class="fixed bottom-6 right-6 bg-blue-500 hover:bg-blue-600 text-white w-16 h-16 rounded-full flex items-center justify-center shadow-lg text-3xl">
    <i class="fas fa-plus"></i>
</a>

<script src="/assets/js/dashboard-charts.js"></script>
<script src="/assets/js/savings.js"></script>
