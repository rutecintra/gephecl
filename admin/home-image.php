<?php
declare(strict_types=1);

require __DIR__ . '/_common.php';
requireLogin();

$config = loadConfig();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'upload-home-image') {
    if (empty($_FILES['home_image'])) {
        addFlash('errors', 'Selecione uma imagem.');
        header('Location: home-image.php');
        exit;
    }

    $saved = saveUploadedFile($_FILES['home_image'], HOME_DIR, ALLOWED_IMAGE_EXT, MAX_FILE_SIZE, true);
    if (!$saved['ok']) {
        addFlash('errors', (string)$saved['error']);
        header('Location: home-image.php');
        exit;
    }

    $config['site']['homeImage'] = 'uploads/home/' . $saved['file'];
    saveConfig($config);
    addFlash('messages', 'Imagem da home atualizada.');
    header('Location: home-image.php');
    exit;
}

$config = loadConfig();
$current = (string)($config['site']['homeImage'] ?? 'uploads/home/capa.jpg');

renderAdminStart('Imagem da home', 'home-image', pullFlash());
?>
<section class="admin-section">
  <h2>Trocar imagem da capa da home</h2>
  <p class="link-desc">Imagem atual: <code><?php echo h($current); ?></code></p>
  <div class="admin-image-preview">
    <img src="../<?php echo h($current); ?>" alt="Preview da imagem da home" onerror="this.style.display='none'">
  </div>
  <form method="post" enctype="multipart/form-data" class="admin-grid">
    <input type="hidden" name="action" value="upload-home-image">
    <div class="admin-field">
      <label for="home_image">Nova imagem da home</label>
      <input type="file" id="home_image" name="home_image" accept=".jpg,.jpeg,.png,.webp,.gif" required>
    </div>
    <div class="admin-actions">
      <button type="submit" class="btn-admin">Atualizar capa</button>
    </div>
  </form>
</section>
<?php
renderAdminEnd();
