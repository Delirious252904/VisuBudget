<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VisuBudget | Your Future-Proof Wallet</title>
    <meta name="description" content="Tired of financial stress? VisuBudget is a neurodivergent-friendly budgeting app that shows you what's truly safe to spend, helping you plan, save, and reduce anxiety.">
    
    <!-- SEO and Social Sharing Tags -->
    <meta property="og:title" content="VisuBudget | Your Future-Proof Wallet" />
    <meta property="og:description" content="Finally, a budgeting app that reduces stress instead of adding to it. See what's safe to spend, track your recurring bills, and take control of your finances with clarity." />
    <meta property="og:image" content="/assets/images/icons/web-app-manifest-512x512.png" /> <!-- Make sure this image exists -->
    <meta property="og:url" content="https://visubudget.co.uk/" /> <!-- Change to your live domain -->
    <meta name="twitter:card" content="summary_large_image" />

    <!-- Assets -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <!-- Google Analytics Script -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-0N71LVNHS4"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-0N71LVNHS4');
    </script>

    <!-- Google AdSense Script -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3575546389942702"
     crossorigin="anonymous"></script>
    <link rel="icon" href="/assets/images/icons/favicon.ico" sizes="any">
    <link rel="icon" href="/assets/images/icons/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/assets/images/icons/apple-touch-icon.png">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="manifest" href="/site.webmanifest">
</head>
<body class="bg-gray-900 text-white antialiased">

    <!-- Header -->
    <header class="bg-gray-800 shadow-md sticky top-0 z-50">
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

                <!-- Desktop Menu -->
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="/about" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">About</a>
                        <a href="/pricing" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Pricing</a>
                        <a href="/contact" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Contact</a>
                    </div>
                </div>

                <!-- Mobile Menu Button -->
                <div class="-mr-2 flex md:hidden">
                    <button id="mobile-menu-button" type="button" class="bg-gray-800 inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white" aria-controls="mobile-menu" aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <!-- Icon for hamburger menu (changes to 'X' with JS) -->
                        <i id="mobile-menu-icon" class="fas fa-bars h-6 w-6"></i>
                    </button>
                </div>
            </div>
        </nav>

        <!-- Mobile Menu, show/hide based on menu state. -->
        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="/about" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">About</a>
                <a href="/pricing" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Pricing</a>
                <a href="/contact" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Contact</a>
            </div>
        </div>
    </header>