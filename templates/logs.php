<?php
// templates/logs.php
$apps = getAppsWithErrors();
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
$page_title = 'Logs d\'Erreurs de Vérification';
ob_start();
?>
    <div class="container">
        
        <?php if ($message): ?>
            <p class="message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <div class="actions">
            <a href="?page=list" class="btn btn-primary">Retour à la liste</a>
            <a href="?page=check_all" class="btn btn-secondary">Vérifier toutes les MAJ</a>
        </div>
        <?php if (empty($apps)): ?>
            <p class="message">Aucune erreur détectée. Toutes les vérifications se sont bien passées ! 🎉</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Version Installée</th>
                        <th>Dernière Vérification</th>
                        <th>Erreur</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apps as $app): ?>
                    <tr class="error-row">
                        <td><?php echo htmlspecialchars($app['name']); ?></td>
                        <td><?php echo htmlspecialchars($app['version']); ?></td>
                        <td><?php echo htmlspecialchars($app['last_checked'] ?? 'Jamais'); ?></td>
                        <td class="error-message"><?php echo htmlspecialchars($app['last_error']); ?></td>
                        <td>
                            <a href="?page=edit&id=<?php echo $app['id']; ?>" class="btn btn-edit">Modifier</a>
                            <a href="?page=check&id=<?php echo $app['id']; ?>" class="btn btn-check">Retenter</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php
$page_content = ob_get_clean();
include __DIR__ . '/layout.php';
?>
