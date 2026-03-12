<?php
declare(strict_types=1);

require __DIR__ . '/_common.php';
requireLogin();

$config = loadConfig();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'create-page-menu') {
    $label = trim((string)($_POST['menu_label'] ?? ''));
    $slug = normalizeSlug((string)($_POST['menu_slug'] ?? ''));
    $parentId = trim((string)($_POST['parent_id'] ?? ''));
    $visible = isset($_POST['menu_visible']);

    if ($label === '') {
        addFlash('errors', 'Informe o nome do item de menu.');
        header('Location: new-page.php');
        exit;
    }
    if ($slug === '') {
        addFlash('errors', 'Informe um slug valido para a pagina.');
        header('Location: new-page.php');
        exit;
    }
    if (isset($config['pages'][$slug])) {
        addFlash('errors', 'Ja existe uma pagina com esse slug.');
        header('Location: new-page.php');
        exit;
    }
    if ($parentId !== '' && !menuItemExists($config['menu'], $parentId)) {
        addFlash('errors', 'Item pai informado nao existe.');
        header('Location: new-page.php');
        exit;
    }

    $filename = $slug === 'home' ? 'index.html' : $slug . '.html';
    $filepath = ROOT_DIR . '/' . $filename;
    if (file_exists($filepath)) {
        addFlash('errors', 'Ja existe um arquivo com esse nome. Use outro slug.');
        header('Location: new-page.php');
        exit;
    }

    $config['pages'][$slug] = [
        'title' => $label,
        'subtitle' => 'Nova pagina',
        'description' => 'Descreva aqui o conteudo desta pagina.',
        'browserTitle' => $label . ' - GEPHECL | UFAL CEDU'
    ];
    $config['documents'][$slug] = [];

    $newId = 'menu-' . $slug . '-' . randomToken(6);
    $position = nextPositionForParent($config['menu'], $parentId !== '' ? $parentId : null);
    $config['menu'][] = [
        'id' => $newId,
        'label' => $label,
        'page' => $slug,
        'href' => $filename,
        'parentId' => $parentId !== '' ? $parentId : null,
        'visible' => $visible,
        'position' => $position
    ];

    file_put_contents($filepath, buildStandardPageTemplate($slug, $label));
    normalizeMenuPositions($config['menu']);
    saveConfig($config);

    addFlash('messages', "Nova pagina criada: {$filename}.");
    header('Location: new-page.php');
    exit;
}

$config = loadConfig();
renderAdminStart('Nova pagina', 'new-page', pullFlash());
?>
<section class="admin-section">
  <h2>Novo item de menu + nova pagina padrao</h2>
  <form method="post" class="admin-grid admin-grid-2">
    <input type="hidden" name="action" value="create-page-menu">
    <div class="admin-field">
      <label for="menu_label">Nome do item</label>
      <input type="text" id="menu_label" name="menu_label" required>
    </div>
    <div class="admin-field">
      <label for="menu_slug">Slug da pagina (ex: eventos-2026)</label>
      <input type="text" id="menu_slug" name="menu_slug" pattern="[a-z0-9-]+" required>
    </div>
    <div class="admin-field">
      <label for="parent_id">Pai (deixe vazio para item principal)</label>
      <select id="parent_id" name="parent_id">
        <option value="">(item principal)</option>
        <?php foreach ($config['menu'] as $item): ?>
          <option value="<?php echo h((string)($item['id'] ?? '')); ?>">
            <?php echo h((string)($item['label'] ?? '')); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="admin-field admin-checkbox">
      <label><input type="checkbox" name="menu_visible" checked> Exibir no menu</label>
    </div>
    <div class="admin-actions">
      <button type="submit" class="btn-admin">Criar pagina e item</button>
    </div>
  </form>
</section>
<?php
renderAdminEnd();
