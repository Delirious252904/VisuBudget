
    </main>

    <?php // Check if the user is on the free tier before showing an ad.
    if (isset($user) && $user['subscription_tier'] === 'free'): ?>
    <div class="container mx-auto px-4 md:px-6 my-8">
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            <p class="text-xs text-gray-500 mb-2">Advertisement</p>
            <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3575546389942702"
            crossorigin="anonymous"></script>
            <!-- One -->
            <ins class="adsbygoogle"
                style="display:block"
                data-ad-client="ca-pub-3575546389942702"
                data-ad-slot="3822814993"
                data-ad-format="auto"
                data-full-width-responsive="true"></ins>
            <script>
                (adsbygoogle = window.adsbygoogle || []).push({});
            </script>
        </div>
    </div>
    <?php endif; ?>

    <!-- Cookie Consent Banner -->
    <div id="cookie-consent-banner" class="fixed bottom-0 left-0 right-0 bg-gray-700 text-white p-4 shadow-lg z-50 hidden">
        <div class="container mx-auto flex flex-col sm:flex-row items-center justify-between">
            <p class="text-sm mb-4 sm:mb-0 mr-4">
                We use an essential cookie to keep you logged in securely. By using the site, you acknowledge this. Please see our <a href="/cookies" class="font-bold underline hover:text-blue-300">Cookie Policy</a> for more details.
            </p>
            <button id="accept-cookies-button" class="btn btn-primary flex-shrink-0">Got it!</button>
        </div>
    </div>

    <footer class="bg-gray-800 text-center text-sm text-gray-400 py-4 mt-8">
        <div class="container mx-auto text-center px-4">
            <div class="mb-4 space-x-2 sm:space-x-4">
                <a href="/terms" class="hover:text-white">Terms of Service</a>
                <span class="text-gray-600">&middot;</span>
                <a href="/privacy" class="hover:text-white">Privacy Policy</a>
                <span class="text-gray-600">&middot;</span>
                <a href="/cookies" class="hover:text-white">Cookie Policy</a>
            </div>
            <p>&copy; <?php echo date('Y'); ?> VisuBudget. All Rights Reserved.</p>
        </div>
    </footer>

    <!-- Main JS file for page-specific interactive logic -->
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/cookie-consent.js"></script>

    <!-- 
        Service Worker Registration Script 
        This tells the browser to install our sw.js file, enabling offline capabilities.
    -->
    <!-- Service Worker Registration Script -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker
                    .register('/sw.js')
                    .then(reg => console.log('Service Worker: Registered'))
                    .catch(err => console.log(`Service Worker: Error: ${err}`));
            });
        }
    </script>
</body>
</html>
