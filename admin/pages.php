<?php
declare(strict_types=1);

require __DIR__ . '/_common.php';
requireLogin();

$config = loadConfig();
$selectedPage = normalizeSlug((string)($_GET['page'] ?? 'home'));
if ($selectedPage === '' || empty($config['pages'][$selectedPage])) {
    $selectedPage = (string)(array_key_first($config['pages']) ?: 'home');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'update-page') {
    $slug = normalizeSlug((string)($_POST['page_slug'] ?? ''));
    if ($slug === '' || empty($config['pages'][$slug])) {
        addFlash('errors', 'Pagina invalida.');
        header('Location: pages.php');
        exit;
    }

    $config['pages'][$slug]['title'] = trim((string)($_POST['title'] ?? ''));
    $config['pages'][$slug]['subtitle'] = trim((string)($_POST['subtitle'] ?? ''));
    $config['pages'][$slug]['description'] = trim((string)($_POST['description'] ?? ''));
    $config['pages'][$slug]['browserTitle'] = trim((string)($_POST['browser_title'] ?? ''));
    saveConfig($config);

    addFlash('messages', 'Conteudo da pagina atualizado.');
    header('Location: pages.php?page=' . rawurlencode($slug));
    exit;
}

$config = loadConfig();
$selectedPage = normalizeSlug((string)($_GET['page'] ?? $selectedPage));
if ($selectedPage === '' || empty($config['pages'][$selectedPage])) {
    $selectedPage = (string)(array_key_first($config['pages']) ?: 'home');
}
$pageData = $config['pages'][$selectedPage] ?? ['title' => '', 'subtitle' => '', 'description' => '', 'browserTitle' => ''];

renderAdminStart('Paginas', 'pages', pullFlash());
?>
<section class="admin-section">
  <h2>Editar titulos e descricoes</h2>
  <form method="get" class="admin-grid admin-inline">
    <div class="admin-field">
      <label for="page">Pagina para editar</label>
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

  <form method="post" class="admin-grid">
    <input type="hidden" name="action" value="update-page">
    <input type="hidden" name="page_slug" value="<?php echo h($selectedPage); ?>">
    <div class="admin-field">
      <label for="title">Titulo</label>
      <input type="text" id="title" name="title" value="<?php echo h((string)($pageData['title'] ?? '')); ?>" required>
    </div>
    <div class="admin-field">
      <label for="subtitle">Subtitulo</label>
      <input type="text" id="subtitle" name="subtitle" value="<?php echo h((string)($pageData['subtitle'] ?? '')); ?>">
    </div>
    <div class="admin-field">
      <label for="description">Descricao</label>
      <textarea id="description" name="description" rows="4"><?php echo h((string)($pageData['description'] ?? '')); ?></textarea>
    </div>
    <div class="admin-field">
      <label for="browser_title">Titulo da aba do navegador</label>
      <input type="text" id="browser_title" name="browser_title" value="<?php echo h((string)($pageData['browserTitle'] ?? '')); ?>">
    </div>
    <div class="admin-actions">
      <button type="submit" class="btn-admin">Salvar alteracoes</button>
    </div>
  </form>
</section>
<?php
renderAdminEnd();
