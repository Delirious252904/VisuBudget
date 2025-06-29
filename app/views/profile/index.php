<!-- app/views/profile/index.php -->

<div class="space-y-8">
    <!-- Section 1: Update Profile Details -->
    <section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8">
        <h2 class="text-2xl font-bold mb-6 flex items-center">
            <i class="fas fa-user-edit mr-3 text-blue-400"></i>
            Your Profile
        </h2>
        
        <form action="/profile" method="POST" class="max-w-md">
            <!-- Name Field -->
            <div class="mb-4">
                <label for="name" class="block mb-2 text-sm font-medium text-gray-300">Your Name</label>
                <input type="text" name="name" id="name" class="form-input" placeholder="Enter your name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
            </div>
            <!-- Email Field (Read-only) -->
            <div class="mb-6">
                <label for="email" class="block mb-2 text-sm font-medium text-gray-300">Email Address</label>
                <input type="email" name="email" id="email" class="form-input bg-gray-700 cursor-not-allowed" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                <p class="text-xs text-gray-500 mt-1">Email addresses cannot be changed.</p>
            </div>
            <!-- Savings Percentage Field -->
            <div class="mb-6">
                <label for="savings_percentage" class="block mb-2 text-sm font-medium text-gray-300">Automatic Savings Goal (%)</label>
                <input type="number" name="savings_percentage" id="savings_percentage" class="form-input" placeholder="e.g., 10" step="0.1" min="0" max="100" value="<?php echo htmlspecialchars($user['savings_percentage'] ?? '0'); ?>">
                <p class="text-xs text-gray-500 mt-1">Set a percentage of your 'Safe to Spend' amount to automatically set aside for savings.</p>
            </div>

            <div>
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </div>
        </form>
    </section>

    <!-- Section 2: Change Password -->
     <section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8">
        <h2 class="text-2xl font-bold mb-6 flex items-center">
            <i class="fas fa-key mr-3 text-blue-400"></i>
            Change Password
        </h2>

        <form action="/profile/password" method="POST" class="max-w-md">
            <!-- Current Password -->
            <div class="mb-4">
                <label for="current_password" class="block mb-2 text-sm font-medium text-gray-300">Current Password</label>
                <input type="password" name="current_password" id="current_password" class="form-input" required>
            </div>
            <!-- New Password -->
            <div class="mb-4">
                <label for="new_password" class="block mb-2 text-sm font-medium text-gray-300">New Password</label>
                <input type="password" name="new_password" id="new_password" class="form-input" required>
            </div>
            <!-- Confirm New Password -->
            <div class="mb-6">
                <label for="confirm_password" class="block mb-2 text-sm font-medium text-gray-300">Confirm New Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-input" required>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Change Password</button>
            </div>
        </form>
    </section>
    
    <!-- Notifications Section -->
    <section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8">
        <h2 class="text-2xl font-bold mb-6 flex items-center">
            <i class="fas fa-bell mr-3 text-blue-400"></i>
            Notifications
        </h2>
        <div class="max-w-md">
            <p id="notification-status" class="text-gray-400 mb-4">Checking notification status...</p>
            
            <!-- This button is what our JavaScript will look for. -->
            <!-- We safely pass the public key from our .env file to the button as a data attribute -->
            <button 
                id="enable-notifications-button" 
                class="btn btn-primary"
                data-vapid-public-key="<?php echo htmlspecialchars($_ENV['VAPID_PUBLIC_KEY']); ?>"
            >
                Enable Daily Reminders
            </button>
        </div>
    </section>

     <!-- Section 3: Subscription Status -->
    <section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8">
        <h2 class="text-2xl font-bold mb-6 flex items-center">
            <i class="fas fa-star mr-3 text-blue-400"></i>
            Subscription
        </h2>
        <div class="max-w-md">
            <!-- 
                We check if the 'subscription_tier' key exists before trying to use it.
                If it doesn't, we just default to showing 'free'.
            -->
            <p class="text-gray-300">You are currently on the <span class="font-bold capitalize text-green-400"><?php echo htmlspecialchars($user['subscription_tier'] ?? 'free'); ?></span> plan.</p>
            <p class="text-gray-400 mt-4">Premium features coming soon!</p>
        </div>
    </section>

    <!-- Danger Zone -->
    <section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 border-2 border-red-500/50">
        <h2 class="text-2xl font-bold mb-4 flex items-center text-red-400">
            <i class="fas fa-exclamation-triangle mr-3"></i>
            Danger Zone
        </h2>
        <div class="space-y-6">
            <!-- Reset Data -->
            <div>
                <h3 class="text-lg font-semibold">Reset All Data</h3>
                <p class="text-gray-400 mt-1 mb-3">This will permanently delete all of your accounts, transactions, and recurring rules, but will keep your user account. This is useful if you want to start over from scratch.</p>
                <form action="/profile/reset-data" method="POST" class="js-delete-form">
                    <button type="submit" class="btn bg-yellow-600 hover:bg-yellow-500 text-white">Reset My Data</button>
                </form>
            </div>
            <!-- Delete Account -->
            <div>
                <h3 class="text-lg font-semibold">Delete Account</h3>
                <p class="text-gray-400 mt-1 mb-3">This will permanently delete your user account and all of your data. This action cannot be undone.</p>
                <form action="/profile/delete-account" method="POST" class="js-delete-form">
                    <button type="submit" class="btn bg-red-600 hover:bg-red-500 text-white">Delete My Account</button>
                </form>
            </div>
        </div>
    </section>
</div>
<script src="/assets/js/push-notifications.js" defer></script>