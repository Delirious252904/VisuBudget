<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold text-white mb-6">Send Update to All Users</h1>

    <div class="bg-gray-800 p-8 rounded-lg shadow-lg max-w-4xl mx-auto">
        <form action="/admin/email/send" method="POST" onsubmit="return confirm('Are you sure you want to send this email to all users?');">
            
            <div class="mb-6">
                <label for="subject" class="block text-gray-300 text-sm font-bold mb-2">Subject:</label>
                <input type="text" id="subject" name="subject" required class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3 text-white leading-tight focus:outline-none focus:bg-gray-600 focus:border-blue-500" placeholder="Important Update for VisuBudget Users">
            </div>

            <div class="mb-6">
                <label for="message" class="block text-gray-300 text-sm font-bold mb-2">Message:</label>
                <textarea id="message" name="message" rows="12" required class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3 text-white leading-tight focus:outline-none focus:bg-gray-600 focus:border-blue-500" placeholder="Hello everyone,..."></textarea>
                <p class="text-gray-500 text-xs mt-2">You can use basic HTML tags for formatting (e.g., &lt;b&gt;, &lt;i&gt;, &lt;p&gt;, &lt;br&gt;, &lt;a href='...'&gt;).</p>
            </div>

            <div class="flex items-center justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline">
                    <i class="fas fa-paper-plane mr-2"></i> Send Email
                </button>
            </div>
        </form>
    </div>
</div>
