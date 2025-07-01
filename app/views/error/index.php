<?php
// app/views/error/index.php
// This view is rendered without the standard header and footer for full-page control.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VisuBudget - Error</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="manifest" href="/site.webmanifest">
</head>
<body class="bg-gray-900 text-white">
    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        <div class="w-full max-w-2xl mx-auto bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 text-center">
            <i class="fas fa-bug fa-3x text-red-400 mb-4"></i>
            <h1 class="text-3xl font-bold text-white mb-2">Oops! Something Went Wrong.</h1>
            <p class="text-gray-400 mb-6">We're sorry for the inconvenience. Our team has been notified, but you can help us fix this faster by sending a report.</p>

            <div class="bg-gray-900 rounded p-4 text-left mb-6">
                <p class="text-sm text-red-400 font-mono break-words"><?php echo htmlspecialchars($error_data['message']); ?></p>
            </div>

            <form action="/error/send-report" method="POST">
                <!-- Hidden fields with error details -->
                <input type="hidden" name="user_email" value="<?php echo htmlspecialchars($error_data['user']['email'] ?? 'anonymous'); ?>">
                <textarea name="error_details" class="hidden">
Error: <?php echo htmlspecialchars($error_data['message']); ?>

File: <?php echo htmlspecialchars($error_data['file']); ?> on line <?php echo htmlspecialchars($error_data['line']); ?>

---
Trace:
<?php echo htmlspecialchars($error_data['trace']); ?>
                </textarea>

                <div class="mb-4 text-left">
                    <label for="user_description" class="block mb-2 text-sm font-medium text-gray-300">Can you tell us what you were doing when this happened? (Optional)</label>
                    <textarea name="user_description" id="user_description" rows="4" class="form-input" placeholder="For example: I was trying to add a new savings goal..."></textarea>
                </div>

                <div class="flex items-center justify-center space-x-4">
                    <a href="/" class="text-gray-400 hover:text-white">Go to Dashboard</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-2"></i>Send Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
