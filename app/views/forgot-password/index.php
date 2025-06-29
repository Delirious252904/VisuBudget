<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 max-w-md mx-auto">
    <h2 class="text-2xl font-bold mb-6 text-center">Forgot Your Password?</h2>
    <p class="text-center text-gray-400 mb-6">No problem. Enter your email address below, and we'll send you a link to reset it.</p>

    <form action="/forgot-password" method="POST">
        <div class="mb-4">
            <label for="email" class="block mb-2 text-sm font-medium text-gray-300">Email Address</label>
            <input type="email" name="email" id="email" class="form-input" required>
        </div>
        <div>
            <button type="submit" class="w-full btn btn-primary">Send Reset Link</button>
        </div>
    </form>
    <p class="text-center text-sm text-gray-400 mt-6">
        Remembered it after all? <a href="/login" class="font-medium text-blue-400 hover:underline">Back to Login</a>
    </p>
</section>
