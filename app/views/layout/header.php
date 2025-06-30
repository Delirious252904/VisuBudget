<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VisuBudget - Your Future-Proof Wallet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Google Analytics & AdSense Scripts -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-0N71LVNHS4"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-0N71LVNHS4');
    </script>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3575546389942702" crossorigin="anonymous"></script>
    
    <link rel="icon" href="/assets/images/icons/favicon.ico" sizes="any">
    <link rel="icon" href="/assets/images/icons/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/assets/images/icons/apple-touch-icon.png">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="manifest" href="/site.webmanifest">
</head>
<body class="bg-gray-900 text-white pb-20 md:pb-0"> <!-- Padding-bottom for mobile nav -->

    <header class="bg-gray-800 shadow-md sticky top-0 z-40">
        <nav class="container mx-auto px-4 sm:px-6 py-3">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="/" class="flex items-center">
                        <div>
                            <span class="text-2xl font-bold text-blue-400">VisuBudget</span>
                        </div>
                    </a>
                </div>

                <!-- Desktop Menu (No changes here, Logout removed) -->
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="/" class="nav-link text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                        <a href="/accounts" class="nav-link text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Accounts</a>
                        <a href="/transactions" class="nav-link text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Transactions</a>
                        <?php if (isset($user) && $user['subscription_tier'] === 'premium'): ?>
                            <a href="/savings" class="nav-link text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Savings Goals</a>
                        <?php endif; ?>
                        <a href="/recurring" class="nav-link text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Recurring</a>
                        <a href="/profile" class="nav-link text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Profile</a>
                    </div>
                </div>
                <!-- The old mobile menu button is no longer needed -->
                <div class="-mr-2 flex md:hidden"></div>
            </div>
        </nav>
    </header>

    <!-- NEW: Mobile Bottom Navigation -->
    <nav class="md:hidden fixed flex align-end justify-center bottom-0 left-0 right-0 bg-gray-800 border-t border-gray-700 flex justify-around py-5 z-50">
        <a href="/" class="nav-link flex flex-col items-center text-gray-400 hover:text-white w-full py-1">
            <i class="fas fa-tachometer-alt fa-lg m-3"></i>
            <span class="text-xs mt-1">Dashboard</span>
        </a>
        <a href="/accounts" class="nav-link flex flex-col items-center text-gray-400 hover:text-white w-full py-1">
            <i class="fas fa-wallet fa-lg m-3"></i>
            <span class="text-xs mt-1">Accounts</span>
        </a>
        <a href="/transactions" class="nav-link flex flex-col items-center text-gray-400 hover:text-white w-full py-1">
            <i class="fas fa-exchange-alt fa-lg m-3"></i>
            <span class="text-xs mt-1">Transactions</span>
        </a>
        <a href="/recurring" class="nav-link flex flex-col items-center text-gray-400 hover:text-white w-full py-1">
            <i class="fas fa-sync-alt fa-lg m-3"></i>
            <span class="text-xs mt-1">Recurring</span>
        </a>
        <a href="/profile" class="nav-link flex flex-col items-center text-gray-400 hover:text-white w-full py-1">
            <i class="fas fa-user-circle fa-lg m-3"></i>
            <span class="text-xs mt-1">Profile</span>
        </a>
    </nav>

    <main class="container mx-auto p-4 md:p-6">
    <script>
        // Simple script to highlight the active navigation link.
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.nav-link');

            navLinks.forEach(link => {
                const linkPath = link.getAttribute('href');
                // Use startsWith for dashboard ('/') and exact match for others
                if ((currentPath === '/' && linkPath === '/') || (linkPath !== '/' && currentPath.startsWith(linkPath))) {
                    // For mobile icon links
                    link.classList.remove('text-gray-400');
                    link.classList.add('text-white', 'bg-gray-700', 'rounded-md');
                    // For desktop text links
                    link.classList.add('bg-gray-900');
                }
            });
        });
    </script>