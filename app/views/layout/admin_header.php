// --- Create new file: app/views/layout/admin_header.php ---
<!DOCTYPE html>
<html lang="en">
<head>
    <title>VisuBudget Admin</title>
    <!-- Include all the same CSS/JS assets as the main app -->
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