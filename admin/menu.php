<?php
declare(strict_types=1);

require __DIR__ . '/_common.php';
requireLogin();

$config = loadConfig();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'update-menu-item') {
        $itemId = trim((string)($_POST['item_id'] ?? ''));
        $label = trim((string)($_POST['item_label'] ?? ''));
        $href = trim((string)($_POST['item_href'] ?? ''));
        $parentId = trim((string)($_POST['item_parent_id'] ?? ''));
        $visible = isset($_POST['item_visible']);

        $idx = findMenuIndex($config['menu'], $itemId);
        if ($idx < 0) {
            addFlash('errors', 'Item de menu nao encontrado.');
            header('Location: menu.php');
            exit;
        }
        if ($label === '' || $href === '') {
            addFlash('errors', 'Nome e link do menu sao obrigatorios.');
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
        $config['menu'][$idx]['href'] = $href;
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
            <td><input type="text" name="item_href" form="<?php echo h($formId); ?>" value="<?php echo h((string)($item['href'] ?? '')); ?>" class="admin-table-input" required></td>
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
      </tbody>
    </table>
  </div>
</section>
<?php
renderAdminEnd();
