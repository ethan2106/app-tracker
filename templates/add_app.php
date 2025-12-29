<?php
// templates/add_app.php
$page_title = 'Ajouter une Application';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $_SESSION['message'] = 'Jeton CSRF invalide.';
        header('Location: ?page=list');
        exit;
    }
    $name = $_POST['name'] ?? '';
    $version = $_POST['version'] ?? '';
    $update_source = $_POST['update_source'] ?? null;
    $update_type = $_POST['update_type'] ?? null;
    $update_url = $_POST['update_url'] ?? null;
    $update_regex = $_POST['update_regex'] ?? null;
    if ($update_type !== 'custom') {
        $update_url = null;
        $update_regex = null;
    }
    if ($name && $version) {
        addApp($name, $version, $update_source, $update_type, $update_url, $update_regex);
        header('Location: ?page=list');
        exit;
    }
}
$page_title = 'App Tracker - Ajouter une App';
ob_start();
?>
    <div class="container">
        
        <form method="post" class="form">
            <?php echo csrf_field(); ?>
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
            <div class="form-group">
                <label for="update_type">Type de source:</label>
                <select id="update_type" name="update_type">
                    <option value="">Standard / GitHub</option>
                    <option value="custom">URL + Regex</option>
                </select>
            </div>
            <div class="form-group">
                <label for="update_url">URL personnalisée (optionnel):</label>
                <input type="text" id="update_url" name="update_url" placeholder="https://exemple.com/versions">
            </div>
            <div class="form-group">
                <label for="update_regex">Regex version (optionnel):</label>
                <input type="text" id="update_regex" name="update_regex" placeholder="/Version\\s*([0-9\\.]+)/">
            </div>
            <button type="submit" class="btn btn-primary">Ajouter</button>
        </form>
        <a href="?page=list" class="btn btn-secondary">Retour à la liste</a>
    </div>
<?php
$page_content = ob_get_clean();
include __DIR__ . '/layout.php';
?>
