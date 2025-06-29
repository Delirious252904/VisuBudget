<!-- -------------------------------------------------- -->
<!-- File: app/views/savings/index.php                    -->
<!-- This page lists all the user's savings goals.      -->
<!-- -------------------------------------------------- -->
<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold flex items-center">
            <i class="fas fa-piggy-bank mr-3 text-blue-400"></i>
            Your Savings Goals
        </h2>
        <a href="/savings/add" class="btn btn-primary text-sm"><i class="fas fa-plus"></i> New Goal</a>
    </div>
    
    <?php if (empty($goals)): ?>
        <div class="text-center text-gray-400 py-8 border-2 border-dashed border-gray-700 rounded-lg">
            <p>You haven't set any savings goals yet.</p>
            <a href="/savings/add" class="mt-4 btn btn-primary">Create Your First Goal</a>
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($goals as $goal): ?>
                <?php 
                    $progress_percentage = ($goal['target_amount'] > 0) ? ($goal['current_amount'] / $goal['target_amount']) * 100 : 0;
                    if ($progress_percentage > 100) $progress_percentage = 100;
                ?>
                <div class="bg-gray-700/50 p-4 rounded-lg">
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-bold"><?php echo htmlspecialchars($goal['goal_name']); ?></span>
                        <div class="text-sm">
                            <a href="/savings/edit/<?php echo $goal['goal_id']; ?>" class="text-blue-400 hover:text-blue-300 mr-4">Edit</a>
                            <form action="/savings/delete/<?php echo $goal['goal_id']; ?>" method="POST" class="inline-block js-delete-form">
                                <button type="submit" class="text-red-400 hover:text-red-300 bg-transparent border-none p-0 cursor-pointer font-medium">Delete</button>
                            </form>
                        </div>
                    </div>
                    <div class="text-sm text-gray-400 mb-2">
                        £<?php echo number_format($goal['current_amount'], 2); ?> of £<?php echo number_format($goal['target_amount'], 2); ?>
                    </div>
                    <!-- Progress Bar -->
                    <div class="w-full bg-gray-600 rounded-full h-2.5">
                        <div class="bg-blue-500 h-2.5 rounded-full" style="width: <?php echo $progress_percentage; ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

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