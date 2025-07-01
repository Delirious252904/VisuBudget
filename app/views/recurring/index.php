<?php
// app/views/recurring/index.php
?>

<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Recurring Transactions</h2>
        <a href="/transaction/add" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i>Add New Rule
        </a>
    </div>

    <div class="overflow-x-auto">
        <!-- FIX: Table styling updated to match the transactions table -->
        <table class="min-w-full text-left text-sm">
            <thead class="bg-gray-700">
                <tr>
                    <th class="p-3">Description</th>
                    <th class="p-3 text-right">Amount</th>
                    <th class="p-3">Next Due</th>
                    <th class="p-3">Frequency</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rules)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-gray-400 py-8">You haven't added any recurring rules yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rules as $rule): ?>
                        <tr class="border-b border-gray-700 hover:bg-gray-700/50 cursor-pointer" data-href="/recurring/edit/<?php echo $rule['rule_id']; ?>">
                            <td class="p-3"><?php echo htmlspecialchars($rule['description']); ?></td>
                            <td class="p-3 text-right <?php echo ($rule['type'] === 'income') ? 'text-green-400' : 'text-red-400'; ?>">
                                <?php echo '£' . number_format($rule['amount'], 2); ?>
                            </td>
                            <td class="p-3">
                                <?php
                                    $transactionModel = new \models\Transaction();
                                    $last_date_str = $transactionModel->findLatestDateByRuleId($rule['rule_id']);
                                    $base_date = $last_date_str ? new DateTime($last_date_str) : new DateTime($rule['start_date']);
                                    
                                    if (!$last_date_str) {
                                        $next_due = $base_date;
                                    } else {
                                        $next_due = \models\RecurringRule::calculateNextDateForRule($base_date, $rule);
                                    }
                                    echo $next_due ? $next_due->format('M jS') : 'N/A';
                                ?>
                            </td>
                            <td class="p-3 capitalize"><?php echo htmlspecialchars($rule['frequency']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls -->
    <?php if ($totalPages > 0): ?>
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
    <?php endif; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('tr[data-href]');
    rows.forEach(row => {
        row.addEventListener('click', () => {
            window.location.href = row.dataset.href;
        });
    });
});
</script>
