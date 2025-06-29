<!-- Create new file: app/views/layout/admin_header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VisuBudget Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
<body class="bg-gray-700 text-white">
    <div class="flex h-screen">
        <!-- Admin Sidebar Navigation -->
        <aside class="w-64 bg-gray-800 text-white p-4">
            <h1 class="text-2xl font-bold text-blue-400 mb-8">VisuBudget Admin</h1>
            <nav class="space-y-2">
                <a href="/admin/dashboard" class="flex items-center p-2 rounded hover:bg-gray-700"><i class="fas fa-tachometer-alt w-6 mr-3"></i> Dashboard</a>
                <a href="/admin/users" class="flex items-center p-2 rounded hover:bg-gray-700"><i class="fas fa-users w-6 mr-3"></i> Manage Users</a>
                <a href="/admin/email" class="flex items-center p-2 rounded hover:bg-gray-700"><i class="fas fa-envelope w-6 mr-3"></i> Send Update</a>
                <hr class="border-gray-600 my-4">
                <a href="/logout" class="flex items-center p-2 rounded hover:bg-gray-700"><i class="fas fa-sign-out-alt w-6 mr-3"></i> Logout</a>
            </nav>
        </aside>
        <main class="flex-1 p-8 overflow-y-auto">