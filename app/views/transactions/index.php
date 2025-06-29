<!-- -------------------------------------------------- -->
<!-- File: app/views/transactions/index.php               -->
<!-- This page lists all of the user's transactions.    -->
<!-- -------------------------------------------------- -->
<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold flex items-center">
            <i class="fas fa-history mr-3 text-blue-400"></i>
            Transaction History
        </h2>
        <a href="/transaction/add" class="btn btn-primary text-sm"><i class="fas fa-plus"></i> Add New Transaction</a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-gray-700">
                <tr>
                    <th class="p-3">Date</th>
                    <th class="p-3">Description</th>
                    <th class="p-3">Type</th>
                    <th class="p-3 text-right">Amount</th>
                    <th class="p-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-gray-400 py-8">No transactions found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $tx): ?>
                        <tr class="border-b border-gray-700">
                            <td class="p-3 whitespace-nowrap"><?php echo (new DateTime($tx['transaction_date']))->format('M j, Y'); ?></td>
                            <td class="p-3"><?php echo htmlspecialchars($tx['description']); ?></td>
                            <td class="p-3 capitalize"><?php echo htmlspecialchars($tx['type']); ?></td>
                            <td class="p-3 text-right font-mono <?php echo $tx['type'] === 'income' ? 'text-green-400' : ($tx['type'] === 'transfer' ? 'text-gray-300' : 'text-red-400'); ?>">
                                £<?php echo number_format($tx['amount'], 2); ?>
                            </td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <a href="/transaction/edit/<?php echo $tx['transaction_id']; ?>" class="text-blue-400 hover:text-blue-300 mr-4">Edit</a>
                                <form action="/transaction/delete/<?php echo $tx['transaction_id']; ?>" method="POST" class="inline-block js-delete-form">
                                    <button type="submit" class="text-red-400 hover:text-red-300 bg-transparent border-none p-0 cursor-pointer font-medium">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Links -->
    <div class="mt-6 flex justify-between items-center">
        <div>
            <?php if ($currentPage > 1): ?>
                <a href="/transactions?page=<?php echo $currentPage - 1; ?>" class="btn bg-gray-700 hover:bg-gray-600 text-sm">&larr; Previous</a>
            <?php else: ?>
                <span class="btn bg-gray-700 text-sm opacity-50 cursor-not-allowed">&larr; Previous</span>
            <?php endif; ?>
        </div>

        <div class="text-sm text-gray-400">
            Page <?php echo $currentPage; ?> of <?php echo $totalPages > 0 ? $totalPages : 1; ?>
        </div>

        <div>
            <?php if ($currentPage < $totalPages): ?>
                <a href="/transactions?page=<?php echo $currentPage + 1; ?>" class="btn bg-gray-700 hover:bg-gray-600 text-sm">Next &rarr;</a>
            <?php else: ?>
                <span class="btn bg-gray-700 text-sm opacity-50 cursor-not-allowed">Next &rarr;</span>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- AdSense Display Logic for this page -->
<?php if (isset($user) && $user['subscription_tier'] === 'free'): ?>
<div class="mt-8">
    <div class="bg-gray-800 rounded-lg p-4 text-center">
        <p class="text-xs text-gray-500 mb-2">Advertisement</p>
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3575546389942702"
        crossorigin="anonymous"></script>
        <!-- One -->
        <ins class="adsbygoogle"
            style="display:block"
            data-ad-client="ca-pub-3575546389942702"
            data-ad-slot="3822814993"
            data-ad-format="auto"
            data-full-width-responsive="true"></ins>
        <script>
            (adsbygoogle = window.adsbygoogle || []).push({});
        </script>
    </div>
</div>
<?php endif; ?>


<!-- Confirmation Modal -->
<div id="confirmation-modal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center p-4 hidden">
    <div class="bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-sm text-center">
        <h3 class="text-xl font-bold mb-4">Are you sure?</h3>
        <p class="text-gray-400 mb-6">This will permanently delete this transaction and update your account balance accordingly. This action cannot be undone.</p>
        <div class="flex justify-center space-x-4">
            <button id="modal-cancel-button" class="btn bg-gray-600 hover:bg-gray-500">Cancel</button>
            <button id="modal-confirm-button" class="btn bg-red-600 hover:bg-red-500 text-white">Yes, Delete It</button>
        </div>
    </div>
</div>