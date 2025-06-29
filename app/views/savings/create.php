<!-- -------------------------------------------------- -->
<!-- File: app/views/savings/create.php                   -->
<!-- -------------------------------------------------- -->
<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 max-w-lg mx-auto">
    <h2 class="text-2xl font-bold mb-6">Create a New Savings Goal</h2>
    <form action="/savings/add" method="POST">
        <div class="mb-4">
            <label for="goal_name" class="block mb-2 text-sm font-medium">Goal Name</label>
            <input type="text" name="goal_name" id="goal_name" class="form-input" placeholder="e.g., Summer Holiday" required>
        </div>
        <div class="mb-6">
            <label for="target_amount" class="block mb-2 text-sm font-medium">Target Amount (£)</label>
            <input type="number" name="target_amount" id="target_amount" class="form-input" step="0.01" placeholder="800.00" required>
        </div>
        <div class="flex items-center justify-end space-x-4">
            <a href="/savings" class="text-gray-400 hover:text-white">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Goal</button>
        </div>
    </form>
</section>