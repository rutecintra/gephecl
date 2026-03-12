<?php
declare(strict_types=1);

require __DIR__ . '/_common.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'upload-photos') {
    if (empty($_FILES['photos'])) {
        addFlash('errors', 'Selecione ao menos uma imagem.');
        header('Location: photos.php');
        exit;
    }

    $manifest = loadJsonFile(MANIFEST_FILE);
    if (!is_array($manifest)) {
        $manifest = [];
    }

    $names = $_FILES['photos']['name'] ?? [];
    $tmpNames = $_FILES['photos']['tmp_name'] ?? [];
    $uploadErrors = $_FILES['photos']['error'] ?? [];
    $sizes = $_FILES['photos']['size'] ?? [];
    $savedCount = 0;

    foreach ($names as $i => $originalName) {
        $fileData = [
            'name' => $originalName,
            'type' => $_FILES['photos']['type'][$i] ?? '',
            'tmp_name' => $tmpNames[$i] ?? '',
            'error' => $uploadErrors[$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $sizes[$i] ?? 0
        ];

        $saved = saveUploadedFile($fileData, PHOTOS_DIR, ALLOWED_IMAGE_EXT, MAX_FILE_SIZE, true);
        if (!$saved['ok']) {
            addFlash('errors', (string)$saved['error']);
            continue;
        }

        $caption = trim(pathinfo((string)$originalName, PATHINFO_FILENAME));
        $caption = preg_replace('/[-_]+/', ' ', $caption);
        $manifest[] = [
            'file' => $saved['file'],
            'caption' => $caption !== '' ? $caption : 'Foto',
            'uploadedAt' => date('c')
        ];
        $savedCount++;
    }

    saveJsonFile(MANIFEST_FILE, $manifest);
    if ($savedCount > 0) {
        addFlash('messages', "{$savedCount} foto(s) enviada(s) com sucesso.");
    } elseif (empty($_SESSION['admin_flash']['errors'])) {
        addFlash('errors', 'Nenhuma foto foi enviada.');
    }
    header('Location: photos.php');
    exit;
}

renderAdminStart('Galeria de fotos', 'photos', pullFlash());
?>
<section class="admin-section">
  <h2>Adicionar imagens na pagina de fotos</h2>
  <p class="link-desc">As imagens vao para <code>uploads/fotos</code> e aparecem automaticamente na galeria.</p>
  <form method="post" enctype="multipart/form-data" class="admin-grid">
    <input type="hidden" name="action" value="upload-photos">
    <div class="admin-field">
      <label for="photos">Selecione uma ou mais imagens</label>
      <input type="file" id="photos" name="photos[]" accept=".jpg,.jpeg,.png,.webp,.gif" multiple required>
    </div>
    <div class="admin-actions">
      <button type="submit" class="btn-admin">Enviar fotos</button>
      <a href="../fotos.html" class="btn-admin secundario" style="text-decoration:none;">Ver galeria</a>
    </div>
  </form>
</section>
<?php
renderAdminEnd();
