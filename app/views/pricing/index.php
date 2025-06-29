<!-- app/views/subscriptions/pricing.php -->
<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8">
    <div class="text-center max-w-2xl mx-auto">
        <h1 class="text-4xl font-extrabold text-white tracking-tight">Find the Plan That's Right For You</h1>
        <p class="mt-4 text-lg text-gray-400">Start for free, or unlock powerful new features with a Premium subscription. All plans are backed by our commitment to clarity and stress-free financial planning.</p>
    </div>

    <!-- Pricing Table -->
    <div class="mt-16 grid grid-cols-1 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
        
        <!-- Free Plan -->
        <div class="bg-gray-900 rounded-lg p-8 border border-gray-700 flex flex-col">
            <h2 class="text-2xl font-bold">Free</h2>
            <p class="mt-2 text-gray-400">The essential tools to see what's safe to spend.</p>
            <div class="mt-6">
                <span class="text-4xl font-bold">£0</span>
                <span class="text-lg font-medium text-gray-400">/ forever</span>
            </div>
            <ul class="mt-8 space-y-4 text-gray-300 flex-grow">
                <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-3"></i>"Safe to Spend" Calculation</li>
                <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-3"></i>Link Unlimited Accounts</li>
                <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-3"></i>Recurring Transactions</li>
            </ul>
            <a href="/register" class="mt-8 w-full text-center btn bg-gray-600 hover:bg-gray-500 text-white">Get Started</a>
        </div>

        <!-- Household Plan (Highlighted) -->
        <div class="bg-gray-900 rounded-lg p-8 border-2 border-blue-500 flex flex-col relative transform scale-105">
            <span class="absolute top-0 right-8 -mt-3 bg-blue-500 text-white text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full">Best Value</span>
            <h2 class="text-2xl font-bold text-blue-400">Household</h2>
            <p class="mt-2 text-gray-400">Shared clarity for the whole family or house.</p>
            <div class="mt-6">
                <span class="text-4xl font-bold">£4.99</span>
                <span class="text-lg font-medium text-gray-400">/ month</span>
            </div>
            <ul class="mt-8 space-y-4 text-gray-300 flex-grow">
                <li class="flex items-center"><i class="fas fa-check-circle text-blue-400 mr-3"></i><strong>All Premium features, plus:</strong></li>
                <li class="flex items-center"><i class="fas fa-users text-blue-400 mr-3"></i><strong>Up to 6 Members</strong></li>
                <li class="flex items-center"><i class="fas fa-wallet text-blue-400 mr-3"></i>Shared Accounts & Budgets</li>
                <li class="flex items-center"><i class="fas fa-eye text-blue-400 mr-3"></i>Simplified View for Kids</li>
            </ul>
            <button id="upgrade-household-button" class="mt-8 w-full btn btn-primary">Choose Household</button>
            <p class="text-center text-xs text-gray-500 mt-2">or £49.99 per year</p>
        </div>

        <!-- Premium Plan (Single User) -->
        <div class="bg-gray-900 rounded-lg p-8 border border-gray-700 flex flex-col">
            <h2 class="text-2xl font-bold">Premium</h2>
            <p class="mt-2 text-gray-400">Deeper insights for individual users.</p>
            <div class="mt-6">
                <span class="text-4xl font-bold">£2.99</span>
                <span class="text-lg font-medium text-gray-400">/ month</span>
            </div>
            <ul class="mt-8 space-y-4 text-gray-300 flex-grow">
                <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-3"></i><strong>All Free features, plus:</strong></li>
                <li class="flex items-center"><i class="fas fa-chart-pie text-green-500 mr-3"></i>Custom Spending Categories</li>
                <li class="flex items-center"><i class="fas fa-flag-checkered text-green-500 mr-3"></i>Multiple Savings Goals</li>
                <li class="flex items-center"><i class="fas fa-question-circle text-green-500 mr-3"></i>"What If?" Scenario Planner</li>
                <li class="flex items-center"><i class="fas fa-ad text-green-500 mr-3"></i>Ad-Free Experience</li>
            </ul>
            <button id="upgrade-premium-button" class="mt-8 w-full btn btn-primary">Choose Premium</button>
             <p class="text-center text-xs text-gray-500 mt-2">or £29.99 per year</p>
        </div>

    </div>
</section>
