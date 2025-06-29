<?php
// This acts as the main wrapper. It expects $bodyContent, $pageTitle, etc.
// Note: header.php and footer.php will use the variables defined in index.php
// which are passed to base.php and then become available via PHP's include scope.
include 'header.php';
?>

    <?php echo $bodyContent ?? ''; ?>

<?php
include 'footer.php';
?>