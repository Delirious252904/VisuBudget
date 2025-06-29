<section class="bg-gray-800 rounded-lg shadow-lg p-6 md:p-8 max-w-lg mx-auto text-center">
    <h2 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($title); ?></h2>
    <p class="text-gray-300"><?php echo htmlspecialchars($message); ?></p>
    <?php if (isset($link_href) && isset($link_text)): ?>
        <a href="<?php echo htmlspecialchars($link_href); ?>" class="inline-block mt-6 btn btn-primary"><?php echo htmlspecialchars($link_text); ?></a>
    <?php endif; ?>
</section>
