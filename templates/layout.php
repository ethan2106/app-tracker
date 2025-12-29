<?php
// templates/layout.php
$page_title = $page_title ?? 'App Tracker';
// Ensure $page_content is defined
$page_content = $page_content ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php echo $page_content; ?>
    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
