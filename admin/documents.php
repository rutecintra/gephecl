<?php
declare(strict_types=1);

require __DIR__ . '/_common.php';
requireLogin();

$config = loadConfig();
$selectedPage = normalizeSlug((string)($_GET['page'] ?? 'home'));
if ($selectedPage === '' || empty($config['pages'][$selectedPage])) {
    $selectedPage = (string)(array_key_first($config['pages']) ?: 'home');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'upload-document') {
        $slug = normalizeSlug((string)($_POST['doc_page_slug'] ?? ''));
        $title = trim((string)($_POST['doc_title'] ?? ''));
        if ($slug === '' || empty($config['pages'][$slug])) {
            addFlash('errors', 'Pagina invalida para documento.');
            header('Location: documents.php');
            exit;
        }
        if ($title === '') {
            addFlash('errors', 'Informe o titulo do documento.');
            header('Location: documents.php?page=' . rawurlencode($slug));
            exit;
        }
        if (empty($_FILES['doc_file'])) {
            addFlash('errors', 'Selecione um arquivo.');
            header('Location: documents.php?page=' . rawurlencode($slug));
            exit;
        }

        $saved = saveUploadedFile($_FILES['doc_file'], DOCS_DIR, ALLOWED_DOC_EXT, MAX_FILE_SIZE, false);
        if (!$saved['ok']) {
            addFlash('errors', (string)$saved['error']);
            header('Location: documents.php?page=' . rawurlencode($slug));
            exit;
        }

        if (!isset($config['documents'][$slug]) || !is_array($config['documents'][$slug])) {
            $config['documents'][$slug] = [];
        }
        $config['documents'][$slug][] = [
            'id' => 'doc-' . randomToken(12),
            'title' => $title,
            'file' => $saved['file'],
            'uploadedAt' => date('c')
        ];
        saveConfig($config);
        addFlash('messages', 'Documento enviado com sucesso.');
        header('Location: documents.php?page=' . rawurlencode($slug));
        exit;
    }

    if ($action === 'delete-document') {
        $slug = normalizeSlug((string)($_POST['doc_page_slug'] ?? ''));
        $docId = trim((string)($_POST['doc_id'] ?? ''));
        if ($slug === '' || $docId === '' || empty($config['documents'][$slug])) {
            addFlash('errors', 'Documento nao encontrado.');
            header('Location: documents.php');
            exit;
        }

        $found = false;
        $newDocs = [];
        foreach ($config['documents'][$slug] as $doc) {
            if (($doc['id'] ?? '') === $docId) {
                $found = true;
                $filename = (string)($doc['file'] ?? '');
                if ($filename !== '') {
                    $fullpath = DOCS_DIR . '/' . $filename;
                    if (is_file($fullpath)) {
                        @unlink($fullpath);
                    }
                }
                continue;
            }
            $newDocs[] = $doc;
        }

        if (!$found) {
            addFlash('errors', 'Documento nao encontrado.');
            header('Location: documents.php?page=' . rawurlencode($slug));
            exit;
        }
        $config['documents'][$slug] = $newDocs;
        saveConfig($config);
        addFlash('messages', 'Documento removido.');
        header('Location: documents.php?page=' . rawurlencode($slug));
        exit;
    }
}

$config = loadConfig();
$selectedPage = normalizeSlug((string)($_GET['page'] ?? $selectedPage));
if ($selectedPage === '' || empty($config['pages'][$selectedPage])) {
    $selectedPage = (string)(array_key_first($config['pages']) ?: 'home');
}
$docsForSelectedPage = $config['documents'][$selectedPage] ?? [];

renderAdminStart('Documentos', 'documents', pullFlash());
?>
<section class="admin-section">
  <h2>Documentos por pagina (uploads/docs)</h2>
  <form method="get" class="admin-grid admin-inline">
    <div class="admin-field">
      <label for="page">Pagina</label>
      <select id="page" name="page">
        <?php foreach ($config['pages'] as $slug => $page): ?>
          <option value="<?php echo h((string)$slug); ?>" <?php echo $selectedPage === $slug ? 'selected' : ''; ?>>
            <?php echo h((string)$slug); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="admin-actions">
      <button type="submit" class="btn-admin secundario">Abrir</button>
    </div>
  </form>

  <form method="post" enctype="multipart/form-data" class="admin-grid admin-grid-2">
    <input type="hidden" name="action" value="upload-document">
    <div class="admin-field">
      <label for="doc_page_slug">Pagina</label>
      <select id="doc_page_slug" name="doc_page_slug">
        <?php foreach ($config['pages'] as $slug => $page): ?>
          <option value="<?php echo h((string)$slug); ?>" <?php echo $selectedPage === $slug ? 'selected' : ''; ?>>
            <?php echo h((string)$slug); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="admin-field">
      <label for="doc_title">Titulo do documento</label>
      <input type="text" id="doc_title" name="doc_title" required>
    </div>
    <div class="admin-field">
      <label for="doc_file">Arquivo</label>
      <input type="file" id="doc_file" name="doc_file" required>
    </div>
    <div class="admin-actions">
      <button type="submit" class="btn-admin">Enviar documento</button>
    </div>
  </form>

  <?php if (!empty($docsForSelectedPage)): ?>
    <h3>Documentos da pagina "<?php echo h($selectedPage); ?>"</h3>
    <?php foreach ($docsForSelectedPage as $doc): ?>
      <div class="admin-doc-row">
        <a href="../uploads/docs/<?php echo rawurlencode((string)($doc['file'] ?? '')); ?>" target="_blank" rel="noopener">
          <?php echo h((string)($doc['title'] ?? 'Documento')); ?>
        </a>
        <form method="post">
          <input type="hidden" name="action" value="delete-document">
          <input type="hidden" name="doc_page_slug" value="<?php echo h($selectedPage); ?>">
          <input type="hidden" name="doc_id" value="<?php echo h((string)($doc['id'] ?? '')); ?>">
          <button type="submit" class="btn-admin secundario">Remover</button>
        </form>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
<?php
renderAdminEnd();
