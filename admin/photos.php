<?php
declare(strict_types=1);

require __DIR__ . '/_common.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'update-gallery-default-view') {
        $config = loadConfig();
        $view = trim((string)($_POST['gallery_default_view'] ?? 'grid'));
        if (!in_array($view, ['grid', 'slider'], true)) {
            addFlash('errors', 'Visualizacao padrao invalida.');
            header('Location: photos.php');
            exit;
        }
        if (!isset($config['site']) || !is_array($config['site'])) {
            $config['site'] = [];
        }
        $config['site']['galleryDefaultView'] = $view;
        saveConfig($config);
        addFlash('messages', 'Visualizacao padrao da galeria atualizada.');
        header('Location: photos.php');
        exit;
    }

    if ($action !== 'upload-photos') {
        addFlash('errors', 'Acao invalida.');
        header('Location: photos.php');
        exit;
    }

    $title = trim((string)($_POST['photo_title'] ?? ''));
    $caption = trim((string)($_POST['photo_caption'] ?? ''));
    if ($title === '') {
        addFlash('errors', 'Informe um titulo para a imagem.');
        header('Location: photos.php');
        exit;
    }
    if (empty($_FILES['photo'])) {
        addFlash('errors', 'Selecione uma imagem.');
        header('Location: photos.php');
        exit;
    }

    $manifest = loadJsonFile(MANIFEST_FILE);
    if (!is_array($manifest)) {
        $manifest = [];
    }

    $saved = saveUploadedFile($_FILES['photo'], PHOTOS_DIR, ALLOWED_IMAGE_EXT, MAX_FILE_SIZE, true);
    if (!$saved['ok']) {
        addFlash('errors', (string)$saved['error']);
        header('Location: photos.php');
        exit;
    }

    $manifest[] = [
        'file' => $saved['file'],
        'title' => $title,
        'caption' => $caption,
        'uploadedAt' => date('c')
    ];
    saveJsonFile(MANIFEST_FILE, $manifest);
    addFlash('messages', 'Imagem enviada com sucesso.');
    header('Location: photos.php');
    exit;
}

$config = loadConfig();
$defaultView = (string)($config['site']['galleryDefaultView'] ?? 'grid');
if (!in_array($defaultView, ['grid', 'slider'], true)) {
    $defaultView = 'grid';
}

renderAdminStart('Galeria de fotos', 'photos', pullFlash());
?>
<section class="admin-section">
  <h2>Visualizacao padrao da galeria</h2>
  <form method="post" class="admin-grid">
    <input type="hidden" name="action" value="update-gallery-default-view">
    <div class="admin-field">
      <label for="gallery_default_view">Modo inicial em <code>fotos.html</code></label>
      <select id="gallery_default_view" name="gallery_default_view">
        <option value="grid" <?php echo $defaultView === 'grid' ? 'selected' : ''; ?>>Itens pequenos</option>
        <option value="slider" <?php echo $defaultView === 'slider' ? 'selected' : ''; ?>>Slide (carrossel)</option>
      </select>
    </div>
    <div class="admin-actions">
      <button type="submit" class="btn-admin">Salvar visualizacao padrao</button>
    </div>
  </form>
</section>

<section class="admin-section">
  <h2>Adicionar imagens na pagina de fotos</h2>
  <p class="link-desc">As imagens vao para <code>uploads/fotos</code>. Titulo e obrigatorio, legenda e opcional.</p>
  <form method="post" enctype="multipart/form-data" class="admin-grid">
    <input type="hidden" name="action" value="upload-photos">
    <div class="admin-field">
      <label for="photo_title">Titulo (obrigatorio)</label>
      <input type="text" id="photo_title" name="photo_title" required>
    </div>
    <div class="admin-field">
      <label for="photo_caption">Legenda (opcional)</label>
      <input type="text" id="photo_caption" name="photo_caption">
    </div>
    <div class="admin-field">
      <label for="photo">Imagem</label>
      <input type="file" id="photo" name="photo" accept=".jpg,.jpeg,.png,.webp,.gif" required>
    </div>
    <div class="admin-actions">
      <button type="submit" class="btn-admin">Enviar imagem</button>
      <a href="../fotos.html" class="btn-admin secundario" style="text-decoration:none;">Ver galeria</a>
    </div>
  </form>
</section>
<?php
renderAdminEnd();
