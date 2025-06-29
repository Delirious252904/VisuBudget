<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 max-w-md mx-auto">
    <h2 class="text-2xl font-bold mb-6 text-center">Create Your VisuBudget Account</h2>

    <?php if (isset($error)): ?>
        <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-4" role="alert">
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <form action="/register" method="POST">
        <div class="mb-4">
            <label for="email" class="block mb-2 text-sm font-medium text-gray-300">Email Address</label>
            <input type="email" name="email" id="email" class="form-input" required>
        </div>
        <div class="mb-6">
            <label for="password" class="block mb-2 text-sm font-medium text-gray-300">Password</label>
            <input type="password" name="password" id="password" class="form-input" required>
        </div>
        <div>
            <button type="submit" class="w-full btn btn-primary">Register</button>
        </div>
    </form>
    <p class="text-center text-sm text-gray-400 mt-6">
        Already have an account? <a href="/login" class="font-medium text-blue-400 hover:underline">Log In</a>
    </p>
</section>
