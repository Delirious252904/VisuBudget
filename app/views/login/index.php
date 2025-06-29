<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 max-w-md mx-auto">
    <h2 class="text-2xl font-bold mb-6 text-center">Login to VisuBudget</h2>

    <?php if (isset($error)): ?>
        <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-4" role="alert">
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>
     <?php if (isset($success)): ?>
        <div class="bg-green-500/20 border border-green-500 text-green-300 px-4 py-3 rounded-lg mb-4" role="alert">
            <p><?php echo htmlspecialchars($success); ?></p>
        </div>
    <?php endif; ?>

    <form action="/login" method="POST">
        <div class="mb-4">
            <label for="email" class="block mb-2 text-sm font-medium text-gray-300">Email Address</label>
            <input type="email" name="email" id="email" class="form-input" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
        </div>
        <div class="mb-6">
            <label for="password" class="block mb-2 text-sm font-medium text-gray-300">Password</label>
            <input type="password" name="password" id="password" class="form-input" required>
        </div>

        <!-- Forgot Password Link -->
        <div class="text-right mb-6">
            <a href="/forgot-password" class="text-sm text-blue-400 hover:underline">Forgot Password?</a>
        </div>

        <div>
            <button type="submit" class="w-full btn btn-primary">Login</button>
        </div>
    </form>

    <?php if (isset($show_resend) && $show_resend): ?>
    <div class="text-center border-t border-gray-700 mt-6 pt-6">
        <p class="mb-4 text-gray-300">Didn't get the email?</p>
        <form action="/resend-verification" method="POST">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <button type="submit" class="btn bg-gray-600 hover:bg-gray-500 text-white">Resend Verification Email</button>
        </form>
    </div>
    <?php endif; ?>

     <p class="text-center text-sm text-gray-400 mt-6">
        Don't have an account? <a href="/register?from=twa" class="font-medium text-blue-400 hover:underline">Register Here</a>
    </p>
</section>
