<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 max-w-md mx-auto">
    <h2 class="text-2xl font-bold mb-6 text-center">Reset Your Password</h2>

    <?php if (isset($error)): ?>
        <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-4" role="alert">
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <form action="/reset-password" method="POST">
        <!-- Hidden field to carry the token through the form submission -->
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

        <div class="mb-4">
            <label for="password" class="block mb-2 text-sm font-medium text-gray-300">New Password</label>
            <input type="password" name="password" id="password" class="form-input" required>
        </div>
         <div class="mb-6">
            <label for="password_confirm" class="block mb-2 text-sm font-medium text-gray-300">Confirm New Password</label>
            <input type="password" name="password_confirm" id="password_confirm" class="form-input" required>
        </div>
        <div>
            <button type="submit" class="w-full btn btn-primary">Update Password</button>
        </div>
    </form>
</section>
