<?php
declare(strict_types=1);

require __DIR__ . '/_common.php';
requireLogin();

$config = loadConfig();
ensurePageComponents($config);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create-page-menu') {
        $label = trim((string)($_POST['page_name'] ?? ''));
        $rawLink = trim((string)($_POST['page_link'] ?? ''));
        $parentId = trim((string)($_POST['parent_id'] ?? ''));
        $visible = isset($_POST['menu_visible']);

        if ($label === '') {
            addFlash('errors', 'Informe o nome da nova pagina.');
            header('Location: menu.php');
            exit;
        }
        if ($rawLink === '') {
            addFlash('errors', 'Informe o link da nova pagina.');
            header('Location: menu.php');
            exit;
        }

        $normalizedLink = ltrim($rawLink, '/');
        if (!preg_match('/\.html$/i', $normalizedLink)) {
            $normalizedLink .= '.html';
        }
        $filename = basename($normalizedLink);
        $slugBase = preg_replace('/\.html$/i', '', $filename);
        $slug = normalizeSlug((string)$slugBase);
        if ($filename === 'index.html') {
            $slug = 'home';
        }
        if ($slug === '') {
            addFlash('errors', 'Nao foi possivel gerar um slug valido com esse link.');
            header('Location: menu.php');
            exit;
        }
        if (isset($config['pages'][$slug])) {
            addFlash('errors', 'Ja existe uma pagina com esse link/slug.');
            header('Location: menu.php');
            exit;
        }
        if ($parentId !== '' && !menuItemExists($config['menu'], $parentId)) {
            addFlash('errors', 'Item pai informado nao existe.');
            header('Location: menu.php');
            exit;
        }

        $filepath = ROOT_DIR . '/' . $filename;
        if (file_exists($filepath)) {
            addFlash('errors', 'Ja existe um arquivo com esse nome. Use outro link.');
            header('Location: menu.php');
            exit;
        }

        $config['pages'][$slug] = [
            'title' => $label,
            'subtitle' => '',
            'description' => '',
            'browserTitle' => $label . ' - GEPHECL | UFAL CEDU'
        ];
        $config['documents'][$slug] = [];
        $config['pageComponents'][$slug] = getDefaultComponentsForPage($slug, $config);

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
        header('Location: menu.php');
        exit;
    }

    if ($action === 'update-menu-item') {
        $itemId = trim((string)($_POST['item_id'] ?? ''));
        $label = trim((string)($_POST['item_label'] ?? ''));
        $parentId = trim((string)($_POST['item_parent_id'] ?? ''));
        $visible = isset($_POST['item_visible']);

        $idx = findMenuIndex($config['menu'], $itemId);
        if ($idx < 0) {
            addFlash('errors', 'Item de menu nao encontrado.');
            header('Location: menu.php');
            exit;
        }
        if ($label === '') {
            addFlash('errors', 'Nome do menu e obrigatorio.');
            header('Location: menu.php');
            exit;
        }
        if ($parentId !== '' && !menuItemExists($config['menu'], $parentId)) {
            addFlash('errors', 'Item pai invalido.');
            header('Location: menu.php');
            exit;
        }
        if ($parentId !== '' && $parentId === $itemId) {
            addFlash('errors', 'Um item nao pode ser pai dele mesmo.');
            header('Location: menu.php');
            exit;
        }

        $oldParent = $config['menu'][$idx]['parentId'] ?? null;
        $newParent = $parentId !== '' ? $parentId : null;
        $config['menu'][$idx]['label'] = $label;
        $config['menu'][$idx]['parentId'] = $newParent;
        $config['menu'][$idx]['visible'] = $visible;
        if ($oldParent !== $newParent) {
            $config['menu'][$idx]['position'] = nextPositionForParent($config['menu'], $newParent);
        }

        normalizeMenuPositions($config['menu']);
        saveConfig($config);
        addFlash('messages', 'Item do menu atualizado.');
        header('Location: menu.php');
        exit;
    }

    if ($action === 'move-menu-item') {
        $itemId = trim((string)($_POST['item_id'] ?? ''));
        $direction = trim((string)($_POST['direction'] ?? ''));
        if (!in_array($direction, ['up', 'down'], true)) {
            addFlash('errors', 'Direcao invalida.');
            header('Location: menu.php');
            exit;
        }
        if (!moveMenuItem($config['menu'], $itemId, $direction)) {
            addFlash('errors', 'Nao foi possivel mover o item.');
            header('Location: menu.php');
            exit;
        }

        saveConfig($config);
        addFlash('messages', 'Ordem do menu atualizada.');
        header('Location: menu.php');
        exit;
    }
}

$config = loadConfig();
$config['menu'] = $config['menu'] ?? [];
$menuRows = buildMenuDisplayRows($config['menu'] ?? []);

renderAdminStart('Menu', 'menu', pullFlash());
?>
<section class="admin-section">
  <h2>Organizacao do menu</h2>
  <p class="link-desc">Tabela unica para editar, esconder e ordenar itens/subitens.</p>

  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Nivel</th>
          <th>Nome</th>
          <th>Link</th>
          <th>Pai</th>
          <th>Exibir</th>
          <th>Acoes</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($menuRows as $idx => $row):
            $item = $row['item'];
            $itemId = (string)($item['id'] ?? '');
            $depth = (int)($row['depth'] ?? 0);
            $formId = 'menu-update-' . $idx;
        ?>
          <tr>
            <td><span class="admin-level-badge"><?php echo $depth === 0 ? 'Principal' : 'Subitem'; ?></span></td>
            <td>
              <form id="<?php echo h($formId); ?>" method="post" class="admin-table-inline-form">
                <input type="hidden" name="action" value="update-menu-item">
                <input type="hidden" name="item_id" value="<?php echo h($itemId); ?>">
              </form>
              <input type="text" name="item_label" form="<?php echo h($formId); ?>" value="<?php echo h((string)($item['label'] ?? '')); ?>" class="admin-table-input" required>
            </td>
            <td>
              <input
                type="text"
                value="<?php echo h((string)($item['href'] ?? '')); ?>"
                class="admin-table-input"
                readonly
                aria-readonly="true"
                title="A URL nao pode ser alterada apos salvar o item."
              >
            </td>
            <td>
              <select name="item_parent_id" form="<?php echo h($formId); ?>" class="admin-table-select">
                <option value="">(item principal)</option>
                <?php foreach ($config['menu'] as $option): ?>
                  <?php if (($option['id'] ?? '') === $itemId) continue; ?>
                  <option value="<?php echo h((string)($option['id'] ?? '')); ?>" <?php echo (($item['parentId'] ?? '') === ($option['id'] ?? '')) ? 'selected' : ''; ?>>
                    <?php echo h((string)($option['label'] ?? '')); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
            <td>
              <label class="admin-table-check">
                <input type="checkbox" name="item_visible" form="<?php echo h($formId); ?>" <?php echo !empty($item['visible']) ? 'checked' : ''; ?>>
                <span>Sim</span>
              </label>
            </td>
            <td>
              <div class="admin-actions admin-actions-tight">
                <button type="submit" form="<?php echo h($formId); ?>" class="admin-icon-btn" title="Salvar item" aria-label="Salvar item">✓</button>
                <form method="post">
                  <input type="hidden" name="action" value="move-menu-item">
                  <input type="hidden" name="item_id" value="<?php echo h($itemId); ?>">
                  <input type="hidden" name="direction" value="up">
                  <button type="submit" class="admin-icon-btn" title="Mover para cima" aria-label="Mover para cima">↑</button>
                </form>
                <form method="post">
                  <input type="hidden" name="action" value="move-menu-item">
                  <input type="hidden" name="item_id" value="<?php echo h($itemId); ?>">
                  <input type="hidden" name="direction" value="down">
                  <button type="submit" class="admin-icon-btn" title="Mover para baixo" aria-label="Mover para baixo">↓</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <tr id="new-page-row-template" style="display:none;">
          <td><span class="admin-level-badge">Novo</span></td>
          <td>
            <form id="new-page-row-form" method="post" class="admin-table-inline-form">
              <input type="hidden" name="action" value="create-page-menu">
            </form>
            <input type="text" name="page_name" form="new-page-row-form" class="admin-table-input" placeholder="Nome da nova pagina" required>
          </td>
          <td>
            <input type="text" name="page_link" form="new-page-row-form" class="admin-table-input" placeholder="ex: eventos-2026.html" required>
          </td>
          <td>
            <select name="parent_id" form="new-page-row-form" class="admin-table-select">
              <option value="">(item principal)</option>
              <?php foreach ($config['menu'] as $item): ?>
                <option value="<?php echo h((string)($item['id'] ?? '')); ?>">
                  <?php echo h((string)($item['label'] ?? '')); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
          <td>
            <label class="admin-table-check">
              <input type="checkbox" name="menu_visible" form="new-page-row-form" checked>
              <span>Sim</span>
            </label>
          </td>
          <td>
            <div class="admin-actions admin-actions-tight">
              <button type="submit" form="new-page-row-form" class="admin-icon-btn" title="Salvar nova pagina" aria-label="Salvar nova pagina">✓</button>
              <button type="button" class="admin-icon-btn" id="cancel-new-page-row" title="Cancelar" aria-label="Cancelar">✕</button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="admin-actions" style="margin-top: 0.9rem;">
    <button type="button" id="add-new-page-row" class="btn-admin secundario">Nova pagina</button>
  </div>
</section>
<script>
  (function () {
    var addButton = document.getElementById('add-new-page-row');
    var row = document.getElementById('new-page-row-template');
    var cancelButton = document.getElementById('cancel-new-page-row');
    if (!addButton || !row || !cancelButton) return;

    function toggleNewRow(show) {
      row.style.display = show ? '' : 'none';
      addButton.disabled = !!show;
      if (show) {
        var input = row.querySelector('input[name="page_name"]');
        if (input) input.focus();
      } else {
        var form = document.getElementById('new-page-row-form');
        if (form) form.reset();
      }
    }

    addButton.addEventListener('click', function () {
      toggleNewRow(true);
    });
    cancelButton.addEventListener('click', function () {
      toggleNewRow(false);
    });
  })();
</script>
<?php
renderAdminEnd();
