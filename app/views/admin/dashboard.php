<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold text-white mb-6">Admin Dashboard</h1>
    
    <!-- Flash Messages -->
    <?php if (isset($flash['success'])): ?>
        <div class="bg-green-600 border border-green-700 text-white px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($flash['success']); ?></span>
        </div>
    <?php endif; ?>
    <?php if (isset($flash['danger'])): ?>
        <div class="bg-red-600 border border-red-700 text-white px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($flash['danger']); ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Stat Card 1: Total Users -->
        <div class="bg-gray-800 p-6 rounded-lg">
            <h2 class="text-xl font-semibold text-blue-400">Total Users</h2>
            <p class="text-3xl font-bold mt-2"><?php echo htmlspecialchars($stats['total_users'] ?? '0'); ?></p>
            <p class="text-sm text-gray-400 mt-1">Currently registered</p>
        </div>
        
        <!-- Stat Card 2: New Users -->
        <div class="bg-gray-800 p-6 rounded-lg">
            <h2 class="text-xl font-semibold text-blue-400">New Users (24h)</h2>
            <p class="text-3xl font-bold mt-2"><?php echo htmlspecialchars($stats['new_users_24h'] ?? '0'); ?></p>
            <p class="text-sm text-gray-400 mt-1">Joined in the last day</p>
        </div>
        
        <!-- Stat Card 3: Placeholder -->
        <div class="bg-gray-800 p-6 rounded-lg">
            <h2 class="text-xl font-semibold text-blue-400">Subscriptions</h2>
            <p class="text-3xl font-bold mt-2">N/A</p>
            <p class="text-sm text-gray-400 mt-1">Premium members</p>
        </div>

        <!-- Stat Card 4: Server Status -->
        <div class="bg-gray-800 p-6 rounded-lg">
            <h2 class="text-xl font-semibold text-blue-400">Server Status</h2>
            <p class="text-3xl font-bold mt-2 text-green-500">Online</p>
            <p class="text-sm text-gray-400 mt-1">All systems operational</p>
        </div>
    </div>
</div>
