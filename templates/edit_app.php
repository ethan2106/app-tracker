<?php
// templates/edit_app.php
$id = $_GET['id'] ?? 0;
$app = getApp($id);
if (!$app) {
    echo "App non trouvée.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $_SESSION['message'] = 'Jeton CSRF invalide.';
        header('Location: ?page=list');
        exit;
    }
    $name = $_POST['name'] ?? '';
    $version = $_POST['version'] ?? '';
    $update_source = $_POST['update_source'] ?? null;
    if ($name && $version) {
        updateApp($id, $name, $version, $update_source);
        header('Location: ?page=list');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>App Tracker - Modifier une App</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Modifier l'Application</h1>
        <form method="post" class="form">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label for="name">Nom:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($app['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="version">Version:</label>
                <input type="text" id="version" name="version" value="<?php echo htmlspecialchars($app['version']); ?>" required>
            </div>
            <div class="form-group">
                <label for="update_source">Source de MAJ:</label>
                <input type="text" id="update_source" name="update_source" value="<?php echo htmlspecialchars($app['update_source'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Modifier</button>
        </form>
        <a href="?page=list" class="btn btn-secondary">Retour à la liste</a>
    </div>
</body>
</html>
