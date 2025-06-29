<!-- app/views/recurring/index.php -->
<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold flex items-center">
            <i class="fas fa-sync-alt mr-3 text-blue-400"></i>
            Manage Recurring Transactions
        </h2>
        <a href="/transaction/add" class="btn btn-primary text-sm">
            <i class="fas fa-plus"></i> Add New
        </a>
    </div>

    <div class="overflow-x-auto">
        <?php if (empty($rules)): ?>
            <p class="text-center text-gray-400 py-8">You haven't set up any recurring transactions yet.</p>
        <?php else: ?>
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="p-3">Description</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Frequency</th>
                        <th class="p-3">Next Due</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rules as $rule): ?>
                        <tr class="border-b border-gray-700">
                            <td class="p-3"><?php echo htmlspecialchars($rule['description']); ?></td>
                            <td class="p-3 font-mono <?php echo $rule['type'] === 'income' ? 'text-green-400' : 'text-red-400'; ?>">
                                £<?php echo number_format(abs($rule['amount']), 2); ?>
                            </td>
                            <td class="p-3 capitalize">
                                Every <?php echo $rule['interval_value'] > 1 ? $rule['interval_value'] : ''; ?> <?php echo rtrim($rule['frequency'], 'ly') . ($rule['interval_value'] > 1 ? 's' : ''); ?>
                            </td>
                            <td class="p-3">
                                <!-- We'll add logic to calculate this accurately later -->
                                <?php echo (new DateTime($rule['start_date']))->format('M j, Y'); ?>
                            </td>
                            <td class="p-3 text-right">
                                <a href="/recurring/edit/<?php echo $rule['rule_id']; ?>" class="text-blue-400 hover:text-blue-300 mr-4"><i class="fas fa-edit"></i></a>
                                <form action="/recurring/delete/<?php echo $rule['rule_id']; ?>" method="POST" class="inline-block js-delete-form">
                                    <button type="submit" class="text-red-400 hover:text-red-300 bg-transparent border-none p-0 cursor-pointer font-medium"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Pagination Links -->
    <div class="mt-6 flex justify-between items-center">
        <div>
            <?php if ($currentPage > 1): ?>
                <a href="/recurring?page=<?php echo $currentPage - 1; ?>" class="btn bg-gray-700 hover:bg-gray-600 text-sm">&larr; Previous</a>
            <?php else: ?>
                <span class="btn bg-gray-700 text-sm opacity-50 cursor-not-allowed">&larr; Previous</span>
            <?php endif; ?>
        </div>
        <div class="text-sm text-gray-400">
            Page <?php echo $currentPage; ?> of <?php echo $totalPages > 0 ? $totalPages : 1; ?>
        </div>
        <div>
             <?php if ($currentPage < $totalPages): ?>
                <a href="/recurring?page=<?php echo $currentPage + 1; ?>" class="btn bg-gray-700 hover:bg-gray-600 text-sm">Next &rarr;</a>
            <?php else: ?>
                <span class="btn bg-gray-700 text-sm opacity-50 cursor-not-allowed">Next &rarr;</span>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Confirmation Modal HTML -->
<div id="confirmation-modal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center p-4 hidden">
    <div class="bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-sm text-center">
        <h3 class="text-xl font-bold mb-4">Are you sure?</h3>
        <p class="text-gray-400 mb-6">This action cannot be undone. This will only delete the rule, not any transactions it has already created.</p>
        <div class="flex justify-center space-x-4">
            <button id="modal-cancel-button" class="btn bg-gray-600 hover:bg-gray-500">Cancel</button>
            <a id="modal-confirm-button" href="#" class="btn bg-red-600 hover:bg-red-500 text-white">Yes, Delete It</a>
        </div>
    </div>
</div>