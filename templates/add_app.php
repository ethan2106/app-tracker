<?php
// templates/add_app.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $version = $_POST['version'] ?? '';
    $update_source = $_POST['update_source'] ?? null;
    if ($name && $version) {
        addApp($name, $version, $update_source);
        header('Location: ?page=list');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>App Tracker - Ajouter une App</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Ajouter une Application</h1>
        <form method="post" class="form">
            <div class="form-group">
                <label for="name">Nom:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="version">Version:</label>
                <input type="text" id="version" name="version" required>
            </div>
            <div class="form-group">
                <label for="update_source">Source de MAJ (ex: mozilla pour Firefox):</label>
                <input type="text" id="update_source" name="update_source">
            </div>
            <button type="submit" class="btn btn-primary">Ajouter</button>
        </form>
        <a href="?page=list" class="btn btn-secondary">Retour à la liste</a>
    </div>
</body>
</html>