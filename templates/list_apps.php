<?php
// templates/list_apps.php
$apps = getApps();
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
$page_title = 'Liste des Applications Windows';
ob_start();
?>
    <div class="container">
        
        <?php if ($message): ?>
            <p class="message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <div class="actions">
            <a href="?page=add" class="btn btn-primary">Ajouter une App</a>
            <a href="?page=check_all" class="btn btn-secondary">Vérifier toutes les MAJ</a>
            <a href="?page=logs" class="btn btn-warning">Voir les Erreurs</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Version</th>
                    <th>Dernière Version</th>
                    <th>Télécharger</th>
                    <th>MAJ Disponible</th>
                    <th>Dernière Vérification</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($apps as $app): 
                    $needsUpdate = $app['update_available'];
                    $statusClass = $needsUpdate === 1 ? 'needs-update' : ($needsUpdate === 0 ? 'up-to-date' : 'unknown');
                ?>
                <tr class="<?php echo $statusClass; ?>">
                    <td><?php echo htmlspecialchars($app['name']); ?></td>
                    <td><?php echo htmlspecialchars($app['version']); ?></td>
                    <td><?php echo htmlspecialchars($app['latest_version'] ?? 'N/A'); ?></td>
                    <td>
                        <?php 
                        $downloadUrl = getDownloadUrlForApp($app);
                        $downloadClass = $needsUpdate === 1 ? 'btn-download btn-download-update' : 'btn-download';
                        if ($downloadUrl): ?>
                            <a href="<?php echo htmlspecialchars($downloadUrl); ?>" target="_blank" rel="noopener noreferrer" class="<?php echo $downloadClass; ?>" title="Télécharger la dernière version">📥</a>
                        <?php else: ?>
                            <span class="no-link">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="status"><?php 
                    if ($needsUpdate === 1) echo 'Oui';
                    elseif ($needsUpdate === 0) echo 'Non';
                    else echo 'N/A';
                ?></td>
                    <td><?php echo htmlspecialchars($app['last_checked'] ?? 'Jamais'); ?></td>
                    <td class="status-cell">
                        <?php if ($app['last_error']): ?>
                            <span class="error-icon" title="<?php echo htmlspecialchars($app['last_error']); ?>">⚠️</span>
                        <?php elseif ($needsUpdate === 1): ?>
                            <span class="warning-icon" title="Mise à jour disponible">⚠️</span>
                        <?php else: ?>
                            <span class="ok-icon">✓</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions-cell">
                        <a href="?page=edit&id=<?php echo $app['id']; ?>" class="btn btn-edit">Modifier</a>
                        <a href="?page=check&id=<?php echo $app['id']; ?>" class="btn btn-check">Vérifier MAJ</a>
                        <form method="post" action="?page=delete&id=<?php echo $app['id']; ?>" style="display:inline;">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-delete">Supprimer</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php
$page_content = ob_get_clean();
include __DIR__ . '/layout.php';
?>
