<?php
declare(strict_types=1);

require __DIR__ . '/_common.php';
requireLogin();

const COMPONENT_TYPES = [
    'title-subtitle',
    'home-hero',
    'cards',
    'details',
    'photo-carousel',
    'photo-slider',
    'documents',
    'links',
    'gallery',
    'gallery-folder'
];

function componentLabel(string $type): string
{
    $labels = [
        'title-subtitle' => 'Titulo e subtitulo',
        'home-hero' => 'Secao principal',
        'cards' => 'Cards',
        'details' => 'Detalhes',
        'photo-carousel' => 'Carrossel de fotos',
        'photo-slider' => 'Slide de fotos',
        'documents' => 'Documentos',
        'links' => 'Links',
        'gallery' => 'Galeria',
        'gallery-folder' => 'Pasta de imagens'
    ];
    return $labels[$type] ?? $type;
}

function componentDescription(string $type): string
{
    $map = [
        'title-subtitle' => 'Secao padrao para paginas internas: titulo e subtitulo opcional.',
        'home-hero' => 'Imagem de destaque, titulo, descricao e botoes de acao.',
        'cards' => 'Cards com titulo/descricao. O site organiza em no maximo 3 por linha.',
        'details' => 'Bloco de texto livre com titulo e conteudo.',
        'photo-carousel' => 'Fotos grandes com setas esquerda/direita.',
        'photo-slider' => 'Slide automatico de fotos.',
        'documents' => 'Lista de documentos com titulo e descricao opcional.',
        'links' => 'Lista de links com titulo e URL.',
        'gallery' => 'Galeria com escolha de visualizacao padrao: grade ou carrossel.',
        'gallery-folder' => 'Galeria por pasta com URL automatica e adicao guiada de fotos.'
    ];
    return $map[$type] ?? '';
}

function normalizeGalleryFolder(string $value): string
{
    $value = trim(str_replace('\\', '/', $value));
    if ($value === '') {
        return '';
    }

    $segments = explode('/', $value);
    $safeSegments = [];
    foreach ($segments as $segment) {
        $segment = trim($segment);
        if ($segment === '' || $segment === '.' || $segment === '..') {
            continue;
        }
        $segment = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $segment);
        $segment = trim((string)$segment, '-_');
        if ($segment !== '') {
            $safeSegments[] = $segment;
        }
    }

    return implode('/', $safeSegments);
}

function fileTitleFromName(string $fileName, int $index): string
{
    $base = pathinfo($fileName, PATHINFO_FILENAME);
    $base = preg_replace('/[_-]+/', ' ', (string)$base);
    $base = trim((string)$base);
    return $base !== '' ? ucwords($base) : ('Foto ' . $index);
}

function buildGalleryItemsFromFolder(string $folder, array $previousItems = []): array
{
    $folder = normalizeGalleryFolder($folder);
    if ($folder === '') {
        return [];
    }

    $targetDir = PHOTOS_DIR . '/' . $folder;
    if (!is_dir($targetDir)) {
        return [];
    }

    $entries = scandir($targetDir);
    if (!is_array($entries)) {
        return [];
    }

    $existingByFile = [];
    foreach ($previousItems as $item) {
        $file = trim((string)($item['file'] ?? ''));
        if ($file !== '') {
            $existingByFile[$file] = [
                'title' => trim((string)($item['title'] ?? '')),
                'subtitle' => trim((string)($item['subtitle'] ?? ''))
            ];
        }
    }

    $imageFiles = [];
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $fullPath = $targetDir . '/' . $entry;
        if (!is_file($fullPath)) {
            continue;
        }
        $ext = strtolower((string)pathinfo($entry, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_IMAGE_EXT, true)) {
            continue;
        }
        $imageFiles[] = $entry;
    }

    natsort($imageFiles);
    $items = [];
    $position = 1;
    foreach ($imageFiles as $fileName) {
        $relativeFile = 'uploads/fotos/' . $folder . '/' . $fileName;
        $existing = $existingByFile[$relativeFile] ?? ['title' => '', 'subtitle' => ''];
        $items[] = [
            'id' => randomToken(10),
            'title' => $existing['title'] !== '' ? $existing['title'] : fileTitleFromName((string)$fileName, $position),
            'subtitle' => $existing['subtitle'],
            'file' => $relativeFile
        ];
        $position += 1;
    }

    return $items;
}

function buildPageSelectOptions(array $config): array
{
    $pages = is_array($config['pages'] ?? null) ? $config['pages'] : [];
    $menu = is_array($config['menu'] ?? null) ? $config['menu'] : [];
    $options = [];

    foreach ($menu as $item) {
        if (!is_array($item)) {
            continue;
        }
        if (($item['visible'] ?? true) === false) {
            continue;
        }
        $page = normalizeSlug((string)($item['page'] ?? ''));
        if ($page === '' || !isset($pages[$page]) || isset($options[$page])) {
            continue;
        }
        $label = trim((string)($item['label'] ?? ''));
        if ($label === '') {
            $label = (string)($pages[$page]['title'] ?? $page);
        }
        $options[$page] = $label;
    }

    if (!empty($options)) {
        return $options;
    }

    foreach ($pages as $slug => $page) {
        $slug = normalizeSlug((string)$slug);
        if ($slug === '') {
            continue;
        }
        $options[$slug] = (string)($page['title'] ?? $slug);
    }

    return $options;
}

function normalizeUploadedFilesList(string $inputName): array
{
    if (empty($_FILES[$inputName]) || !is_array($_FILES[$inputName])) {
        return [];
    }
    $files = $_FILES[$inputName];
    if (!isset($files['name'])) {
        return [];
    }

    if (!is_array($files['name'])) {
        return [$files];
    }

    $normalized = [];
    $total = count($files['name']);
    for ($i = 0; $i < $total; $i++) {
        $normalized[] = [
            'name' => (string)($files['name'][$i] ?? ''),
            'type' => (string)($files['type'][$i] ?? ''),
            'tmp_name' => (string)($files['tmp_name'][$i] ?? ''),
            'error' => (int)($files['error'][$i] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int)($files['size'][$i] ?? 0)
        ];
    }
    return $normalized;
}

function findComponentIndex(array $components, string $componentId): int
{
    foreach ($components as $idx => $component) {
        if ((string)($component['id'] ?? '') === $componentId) {
            return (int)$idx;
        }
    }
    return -1;
}

function findComponentItemIndex(array $items, string $itemId): int
{
    foreach ($items as $idx => $item) {
        if ((string)($item['id'] ?? '') === $itemId) {
            return (int)$idx;
        }
    }
    return -1;
}

function removeUploadIfExists(string $relativePath): void
{
    $relativePath = trim($relativePath);
    if ($relativePath === '') {
        return;
    }
    $fullPath = ROOT_DIR . '/' . ltrim($relativePath, '/');
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

function ensureComponentDefaults(array &$component): void
{
    $component['id'] = (string)($component['id'] ?? makeComponentId((string)($component['type'] ?? 'component')));
    $component['type'] = (string)($component['type'] ?? 'details');
    $component['title'] = trim((string)($component['title'] ?? ''));
    if (!isset($component['settings']) || !is_array($component['settings'])) {
        $component['settings'] = [];
    }
    if (!isset($component['items']) || !is_array($component['items'])) {
        $component['items'] = [];
    }
}

$config = loadConfig();
ensurePageComponents($config);
$selectedPage = normalizeSlug((string)($_GET['page'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $slug = normalizeSlug((string)($_POST['page_slug'] ?? ''));
    if ($slug === '' || empty($config['pages'][$slug])) {
        addFlash('errors', 'Pagina invalida.');
        header('Location: pages.php');
        exit;
    }
    if (!isset($config['pageComponents'][$slug]) || !is_array($config['pageComponents'][$slug])) {
        $config['pageComponents'][$slug] = [];
    }

    if ($action === 'add-component') {
        $type = trim((string)($_POST['component_type'] ?? ''));
        if (!in_array($type, COMPONENT_TYPES, true)) {
            addFlash('errors', 'Tipo de secao invalido.');
            header('Location: pages.php?page=' . rawurlencode($slug));
            exit;
        }
        $newComponent = [
            'id' => makeComponentId($type),
            'type' => $type,
            'title' => '',
            'settings' => [],
            'items' => []
        ];
        if ($type === 'gallery') {
            $newComponent['settings']['view'] = 'grid';
        }
        if ($type === 'gallery-folder') {
            $newComponent['settings']['view'] = 'grid';
            $newComponent['settings']['folderName'] = '';
            $newComponent['settings']['folder'] = '';
        }
        $config['pageComponents'][$slug][] = $newComponent;
        saveConfig($config);
        addFlash('messages', 'Secao adicionada na pagina.');
        header('Location: pages.php?page=' . rawurlencode($slug));
        exit;
    }

    $componentId = trim((string)($_POST['component_id'] ?? ''));
    $componentIdx = findComponentIndex($config['pageComponents'][$slug], $componentId);
    if ($componentIdx < 0) {
        addFlash('errors', 'Secao nao encontrada.');
        header('Location: pages.php?page=' . rawurlencode($slug));
        exit;
    }
    $component = $config['pageComponents'][$slug][$componentIdx];
    ensureComponentDefaults($component);

    if ($action === 'remove-component') {
        if (in_array($component['type'], ['photo-carousel', 'photo-slider', 'gallery', 'documents'], true)) {
            foreach ($component['items'] as $item) {
                $filePath = (string)($item['file'] ?? '');
                removeUploadIfExists($filePath);
            }
        }
        array_splice($config['pageComponents'][$slug], $componentIdx, 1);
        saveConfig($config);
        addFlash('messages', 'Secao removida.');
        header('Location: pages.php?page=' . rawurlencode($slug));
        exit;
    }

    if ($action === 'move-component') {
        $direction = (string)($_POST['direction'] ?? '');
        $targetIdx = $direction === 'up' ? $componentIdx - 1 : $componentIdx + 1;
        if ($targetIdx >= 0 && $targetIdx < count($config['pageComponents'][$slug])) {
            $tmp = $config['pageComponents'][$slug][$componentIdx];
            $config['pageComponents'][$slug][$componentIdx] = $config['pageComponents'][$slug][$targetIdx];
            $config['pageComponents'][$slug][$targetIdx] = $tmp;
            saveConfig($config);
            addFlash('messages', 'Ordem das secoes atualizada.');
        }
        header('Location: pages.php?page=' . rawurlencode($slug));
        exit;
    }

    if ($action === 'update-component') {
        $component['title'] = $component['type'] === 'gallery-folder'
            ? ''
            : trim((string)($_POST['component_title'] ?? ''));

        if ($component['type'] === 'title-subtitle') {
            $component['settings']['title'] = trim((string)($_POST['title_subtitle_title'] ?? ''));
            $component['settings']['subtitle'] = trim((string)($_POST['title_subtitle_subtitle'] ?? ''));
        } elseif ($component['type'] === 'home-hero') {
            $component['settings']['subtitle'] = trim((string)($_POST['hero_subtitle'] ?? ''));
            $component['settings']['title'] = trim((string)($_POST['hero_title'] ?? ''));
            $component['settings']['description'] = trim((string)($_POST['hero_description'] ?? ''));
            $component['settings']['primaryLabel'] = trim((string)($_POST['hero_primary_label'] ?? ''));
            $component['settings']['primaryLink'] = trim((string)($_POST['hero_primary_link'] ?? ''));
            $component['settings']['secondaryLabel'] = trim((string)($_POST['hero_secondary_label'] ?? ''));
            $component['settings']['secondaryLink'] = trim((string)($_POST['hero_secondary_link'] ?? ''));
        } elseif ($component['type'] === 'details') {
            $component['settings']['text'] = trim((string)($_POST['details_text'] ?? ''));
        } elseif ($component['type'] === 'gallery') {
            $view = trim((string)($_POST['gallery_view'] ?? 'grid'));
            $component['settings']['view'] = $view === 'slider' ? 'slider' : 'grid';
        } elseif ($component['type'] === 'gallery-folder') {
            $folderName = trim((string)($_POST['gallery_folder_name'] ?? ''));
            $folder = normalizeGalleryFolder($folderName);
            if ($folder === '') {
                addFlash('errors', 'Informe um nome valido para a pasta de imagens.');
                header('Location: pages.php?page=' . rawurlencode($slug));
                exit;
            }
            $previousFolder = normalizeGalleryFolder((string)($component['settings']['folder'] ?? ''));
            $component['settings']['view'] = 'grid';
            $component['settings']['folderName'] = $folderName;
            $component['settings']['folder'] = $folder;
            if ($previousFolder !== '' && $previousFolder !== $folder) {
                $component['items'] = [];
            }
            $component['items'] = buildGalleryItemsFromFolder($folder, $component['items']);
        }

        if ($component['type'] === 'cards') {
            $titles = $_POST['item_title'] ?? [];
            $descriptions = $_POST['item_description'] ?? [];
            foreach ($component['items'] as $idx => $item) {
                $itemId = (string)($item['id'] ?? '');
                if ($itemId === '') {
                    continue;
                }
                $component['items'][$idx]['title'] = trim((string)($titles[$itemId] ?? ($item['title'] ?? '')));
                $component['items'][$idx]['description'] = trim((string)($descriptions[$itemId] ?? ($item['description'] ?? '')));
            }
        }
        if (in_array($component['type'], ['photo-carousel', 'photo-slider', 'gallery', 'gallery-folder'], true)) {
            $titles = $_POST['item_title'] ?? [];
            $subtitles = $_POST['item_subtitle'] ?? [];
            foreach ($component['items'] as $idx => $item) {
                $itemId = (string)($item['id'] ?? '');
                if ($itemId === '') {
                    continue;
                }
                $component['items'][$idx]['title'] = trim((string)($titles[$itemId] ?? ($item['title'] ?? '')));
                $component['items'][$idx]['subtitle'] = trim((string)($subtitles[$itemId] ?? ($item['subtitle'] ?? '')));
            }
        }
        if ($component['type'] === 'documents') {
            $titles = $_POST['item_title'] ?? [];
            $descriptions = $_POST['item_description'] ?? [];
            foreach ($component['items'] as $idx => $item) {
                $itemId = (string)($item['id'] ?? '');
                if ($itemId === '') {
                    continue;
                }
                $component['items'][$idx]['title'] = trim((string)($titles[$itemId] ?? ($item['title'] ?? '')));
                $component['items'][$idx]['description'] = trim((string)($descriptions[$itemId] ?? ($item['description'] ?? '')));
            }
        }
        if ($component['type'] === 'links') {
            $titles = $_POST['item_title'] ?? [];
            $urls = $_POST['item_url'] ?? [];
            foreach ($component['items'] as $idx => $item) {
                $itemId = (string)($item['id'] ?? '');
                if ($itemId === '') {
                    continue;
                }
                $component['items'][$idx]['title'] = trim((string)($titles[$itemId] ?? ($item['title'] ?? '')));
                $component['items'][$idx]['url'] = trim((string)($urls[$itemId] ?? ($item['url'] ?? '')));
            }
        }

        $config['pageComponents'][$slug][$componentIdx] = $component;
        saveConfig($config);
        addFlash('messages', 'Secao atualizada.');
        header('Location: pages.php?page=' . rawurlencode($slug));
        exit;
    }

    if ($action === 'upload-home-hero-image') {
        if ($component['type'] !== 'home-hero') {
            addFlash('errors', 'Essa secao nao aceita imagem principal.');
            header('Location: pages.php?page=' . rawurlencode($slug));
            exit;
        }
        if (empty($_FILES['hero_image'])) {
            addFlash('errors', 'Selecione uma imagem.');
            header('Location: pages.php?page=' . rawurlencode($slug));
            exit;
        }
        $saved = saveUploadedFile($_FILES['hero_image'], HOME_DIR, ALLOWED_IMAGE_EXT, MAX_FILE_SIZE, true);
        if (!$saved['ok']) {
            addFlash('errors', (string)$saved['error']);
            header('Location: pages.php?page=' . rawurlencode($slug));
            exit;
        }
        $relativePath = 'uploads/home/' . $saved['file'];
        $component['settings']['image'] = $relativePath;
        $config['site']['homeImage'] = $relativePath;
        $config['pageComponents'][$slug][$componentIdx] = $component;
        saveConfig($config);
        addFlash('messages', 'Imagem da secao principal atualizada.');
        header('Location: pages.php?page=' . rawurlencode($slug));
        exit;
    }

    if ($action === 'add-item') {
        if (!isset($component['items']) || !is_array($component['items'])) {
            $component['items'] = [];
        }
        $newItem = ['id' => randomToken(10)];

        if ($component['type'] === 'cards') {
            $newItem['title'] = trim((string)($_POST['new_title'] ?? ''));
            $newItem['description'] = trim((string)($_POST['new_description'] ?? ''));
            if ($newItem['title'] === '') {
                addFlash('errors', 'Informe o titulo do card.');
                header('Location: pages.php?page=' . rawurlencode($slug));
                exit;
            }
        } elseif (in_array($component['type'], ['photo-carousel', 'photo-slider', 'gallery', 'gallery-folder'], true)) {
            if (empty($_FILES['new_image'])) {
                addFlash('errors', 'Selecione uma imagem para adicionar.');
                header('Location: pages.php?page=' . rawurlencode($slug));
                exit;
            }
            $targetDir = PHOTOS_DIR;
            $relativeFilePrefix = 'uploads/fotos/';
            if ($component['type'] === 'gallery-folder') {
                $folder = normalizeGalleryFolder((string)($component['settings']['folder'] ?? ''));
                if ($folder === '') {
                    addFlash('errors', 'Salve a pasta de imagens antes de adicionar fotos.');
                    header('Location: pages.php?page=' . rawurlencode($slug));
                    exit;
                }
                $targetDir = PHOTOS_DIR . '/' . $folder;
                ensureDir($targetDir);
                $relativeFilePrefix = 'uploads/fotos/' . $folder . '/';
            }
            $saved = saveUploadedFile($_FILES['new_image'], $targetDir, ALLOWED_IMAGE_EXT, MAX_FILE_SIZE, true);
            if (!$saved['ok']) {
                addFlash('errors', (string)$saved['error']);
                header('Location: pages.php?page=' . rawurlencode($slug));
                exit;
            }
            $newItem['title'] = trim((string)($_POST['new_title'] ?? ''));
            $newItem['subtitle'] = trim((string)($_POST['new_subtitle'] ?? ''));
            $newItem['file'] = $relativeFilePrefix . $saved['file'];
            if ($component['type'] === 'gallery-folder') {
                $folder = normalizeGalleryFolder((string)($component['settings']['folder'] ?? ''));
                $component['items'][] = $newItem;
                $component['items'] = buildGalleryItemsFromFolder($folder, $component['items']);
                $config['pageComponents'][$slug][$componentIdx] = $component;
                saveConfig($config);
                addFlash('messages', 'Foto adicionada na pasta e sincronizada com a galeria.');
                header('Location: pages.php?page=' . rawurlencode($slug));
                exit;
            }
        } elseif ($component['type'] === 'documents') {
            if (empty($_FILES['new_document'])) {
                addFlash('errors', 'Selecione um documento para adicionar.');
                header('Location: pages.php?page=' . rawurlencode($slug));
                exit;
            }
            $saved = saveUploadedFile($_FILES['new_document'], DOCS_DIR, ALLOWED_DOC_EXT, MAX_FILE_SIZE, false);
            if (!$saved['ok']) {
                addFlash('errors', (string)$saved['error']);
                header('Location: pages.php?page=' . rawurlencode($slug));
                exit;
            }
            $newItem['title'] = trim((string)($_POST['new_title'] ?? ''));
            $newItem['description'] = trim((string)($_POST['new_description'] ?? ''));
            $newItem['file'] = 'uploads/docs/' . $saved['file'];
        } elseif ($component['type'] === 'links') {
            $newItem['title'] = trim((string)($_POST['new_title'] ?? ''));
            $newItem['url'] = trim((string)($_POST['new_url'] ?? ''));
            if ($newItem['title'] === '' || $newItem['url'] === '') {
                addFlash('errors', 'Informe titulo e URL para o link.');
                header('Location: pages.php?page=' . rawurlencode($slug));
                exit;
            }
        } else {
            addFlash('errors', 'Essa secao nao aceita itens.');
            header('Location: pages.php?page=' . rawurlencode($slug));
            exit;
        }

        $component['items'][] = $newItem;
        $config['pageComponents'][$slug][$componentIdx] = $component;
        saveConfig($config);
        addFlash('messages', 'Item adicionado na secao.');
        header('Location: pages.php?page=' . rawurlencode($slug));
        exit;
    }

    if ($action === 'remove-item') {
        $itemId = trim((string)($_POST['item_id'] ?? ''));
        $itemIdx = findComponentItemIndex($component['items'], $itemId);
        if ($itemIdx < 0) {
            addFlash('errors', 'Item nao encontrado.');
            header('Location: pages.php?page=' . rawurlencode($slug));
            exit;
        }
        $item = $component['items'][$itemIdx];
        $filePath = (string)($item['file'] ?? '');
        if ($filePath !== '') {
            removeUploadIfExists($filePath);
        }
        array_splice($component['items'], $itemIdx, 1);
        $config['pageComponents'][$slug][$componentIdx] = $component;
        saveConfig($config);
        addFlash('messages', 'Item removido.');
        header('Location: pages.php?page=' . rawurlencode($slug));
        exit;
    }

    addFlash('errors', 'Acao nao suportada.');
    header('Location: pages.php?page=' . rawurlencode($slug));
    exit;
}

$config = loadConfig();
ensurePageComponents($config);
$selectedPage = normalizeSlug((string)($_GET['page'] ?? $selectedPage));
$pageSelectOptions = buildPageSelectOptions($config);
$pageIsValid = $selectedPage !== '' && !empty($config['pages'][$selectedPage]);
$pageComponents = $pageIsValid ? ($config['pageComponents'][$selectedPage] ?? []) : [];

renderAdminStart('Paginas', 'pages', pullFlash());
?>
<section class="admin-section">
  <h2>Selecionar pagina</h2>
  <form method="get" class="admin-grid admin-inline">
    <div class="admin-field">
      <label for="page">Pagina para editar</label>
      <select id="page" name="page" onchange="this.form.submit()">
        <option value="">Selecione uma pagina...</option>
        <?php foreach ($pageSelectOptions as $slug => $label): ?>
          <option value="<?php echo h((string)$slug); ?>" <?php echo $selectedPage === $slug ? 'selected' : ''; ?>>
            <?php echo h((string)$label); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</section>

<?php if (!$pageIsValid): ?>
  <section class="admin-section">
    <p class="link-desc">Selecione uma pagina acima para liberar o formulario e as secoes.</p>
  </section>
<?php else: ?>
  <?php if (empty($pageComponents)): ?>
    <section class="admin-section">
      <p class="link-desc">Essa pagina ainda nao possui secoes.</p>
    </section>
  <?php endif; ?>

  <?php foreach ($pageComponents as $component): ?>
    <?php
      $componentId = (string)($component['id'] ?? '');
      $updateFormId = 'update-section-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $componentId);
      $type = (string)($component['type'] ?? '');
      $title = (string)($component['title'] ?? '');
      $settings = is_array($component['settings'] ?? null) ? $component['settings'] : [];
      $items = is_array($component['items'] ?? null) ? $component['items'] : [];
      $isHeavyUploadType = in_array($type, ['photo-carousel', 'photo-slider', 'gallery', 'gallery-folder', 'documents'], true);
      $componentFormClass = 'admin-grid';
      if (in_array($type, ['title-subtitle', 'home-hero', 'links'], true)) {
          $componentFormClass = 'admin-grid admin-form-compact';
      }
    ?>
    <section class="admin-section admin-component-card">
      <div class="admin-component-head">
        <div>
          <h3><?php echo h(componentLabel($type)); ?></h3>
          <p class="link-desc"><?php echo h(componentDescription($type)); ?></p>
        </div>
        <div class="admin-actions">
          <form method="post">
            <input type="hidden" name="action" value="move-component">
            <input type="hidden" name="page_slug" value="<?php echo h($selectedPage); ?>">
            <input type="hidden" name="component_id" value="<?php echo h($componentId); ?>">
            <input type="hidden" name="direction" value="up">
            <button type="submit" class="admin-icon-btn" title="Mover para cima">↑</button>
          </form>
          <form method="post">
            <input type="hidden" name="action" value="move-component">
            <input type="hidden" name="page_slug" value="<?php echo h($selectedPage); ?>">
            <input type="hidden" name="component_id" value="<?php echo h($componentId); ?>">
            <input type="hidden" name="direction" value="down">
            <button type="submit" class="admin-icon-btn" title="Mover para baixo">↓</button>
          </form>
          <form method="post">
            <input type="hidden" name="action" value="remove-component">
            <input type="hidden" name="page_slug" value="<?php echo h($selectedPage); ?>">
            <input type="hidden" name="component_id" value="<?php echo h($componentId); ?>">
            <button type="submit" class="btn-admin secundario">Remover seção</button>
          </form>
        </div>
      </div>

      <form method="post" id="<?php echo h($updateFormId); ?>" class="<?php echo h($componentFormClass); ?>">
        <input type="hidden" name="action" value="update-component">
        <input type="hidden" name="page_slug" value="<?php echo h($selectedPage); ?>">
        <input type="hidden" name="component_id" value="<?php echo h($componentId); ?>">
        <?php if ($type !== 'gallery-folder'): ?>
          <div class="admin-field admin-field-full">
            <label>Titulo da secao (opcional)</label>
            <input type="text" name="component_title" value="<?php echo h($title); ?>">
          </div>
        <?php endif; ?>

        <?php if ($type === 'title-subtitle'): ?>
          <div class="admin-field">
            <label>Titulo</label>
            <input type="text" name="title_subtitle_title" value="<?php echo h((string)($settings['title'] ?? '')); ?>" required>
          </div>
          <div class="admin-field">
            <label>Subtitulo (opcional)</label>
            <input type="text" name="title_subtitle_subtitle" value="<?php echo h((string)($settings['subtitle'] ?? '')); ?>">
          </div>
        <?php endif; ?>

        <?php if ($type === 'home-hero'): ?>
          <div class="admin-field">
            <label>Subtitulo</label>
            <input type="text" name="hero_subtitle" value="<?php echo h((string)($settings['subtitle'] ?? '')); ?>">
          </div>
          <div class="admin-field">
            <label>Titulo principal</label>
            <input type="text" name="hero_title" value="<?php echo h((string)($settings['title'] ?? '')); ?>">
          </div>
          <div class="admin-field admin-field-full">
            <label>Descricao</label>
            <textarea name="hero_description" rows="4"><?php echo h((string)($settings['description'] ?? '')); ?></textarea>
          </div>
          <div class="admin-field">
            <label>Texto botao 1</label>
            <input type="text" name="hero_primary_label" value="<?php echo h((string)($settings['primaryLabel'] ?? '')); ?>">
          </div>
          <div class="admin-field">
            <label>Link botao 1</label>
            <input type="text" name="hero_primary_link" value="<?php echo h((string)($settings['primaryLink'] ?? '')); ?>">
          </div>
          <div class="admin-field">
            <label>Texto botao 2</label>
            <input type="text" name="hero_secondary_label" value="<?php echo h((string)($settings['secondaryLabel'] ?? '')); ?>">
          </div>
          <div class="admin-field">
            <label>Link botao 2</label>
            <input type="text" name="hero_secondary_link" value="<?php echo h((string)($settings['secondaryLink'] ?? '')); ?>">
          </div>
        <?php endif; ?>

        <?php if ($type === 'details'): ?>
          <div class="admin-field">
            <label>Texto</label>
            <textarea name="details_text" rows="5"><?php echo h((string)($settings['text'] ?? '')); ?></textarea>
          </div>
        <?php endif; ?>

        <?php if ($type === 'gallery'): ?>
          <div class="admin-field">
            <label>Visualizacao padrao</label>
            <select name="gallery_view">
              <option value="grid" <?php echo (($settings['view'] ?? 'grid') === 'grid') ? 'selected' : ''; ?>>Imagens pequenas</option>
              <option value="slider" <?php echo (($settings['view'] ?? 'grid') === 'slider') ? 'selected' : ''; ?>>Carousel</option>
            </select>
          </div>
        <?php endif; ?>

        <?php if ($type === 'gallery-folder'): ?>
          <?php
            $folderNameValue = trim((string)($settings['folderName'] ?? $settings['folder'] ?? ''));
            $folderSlugValue = normalizeGalleryFolder((string)($settings['folder'] ?? ''));
            if ($folderSlugValue === '' && $folderNameValue !== '') {
                $folderSlugValue = normalizeGalleryFolder($folderNameValue);
            }
          ?>
          <div class="admin-field">
            <label>Nome da pasta</label>
            <input type="text" name="gallery_folder_name" value="<?php echo h($folderNameValue); ?>" placeholder="Evento X" required>
            <p class="link-desc">Use um nome simples. Ao salvar, o sistema gera automaticamente a URL da pasta.</p>
          </div>
          <?php if (!empty($items)): ?>
            <div class="admin-field admin-field-full">
              <p class="link-desc"><?php echo h((string)count($items)); ?> foto(s) adicionada(s) nesta pasta.</p>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <?php if ($type === 'cards' && !empty($items)): ?>
          <?php foreach ($items as $item): $itemId = (string)($item['id'] ?? ''); ?>
            <div class="admin-subitem">
              <div class="admin-field">
                <label>Titulo do card</label>
                <input type="text" name="item_title[<?php echo h($itemId); ?>]" value="<?php echo h((string)($item['title'] ?? '')); ?>">
              </div>
              <div class="admin-field">
                <label>Descricao do card</label>
                <textarea rows="3" name="item_description[<?php echo h($itemId); ?>]"><?php echo h((string)($item['description'] ?? '')); ?></textarea>
              </div>
              <div class="admin-actions admin-subitem-actions">
                <button type="submit" form="<?php echo h('remove-item-' . $componentId . '-' . $itemId); ?>" class="btn-admin secundario">Remover item</button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <?php if (in_array($type, ['photo-carousel', 'photo-slider', 'gallery', 'gallery-folder'], true) && !empty($items)): ?>
          <details class="admin-items-collapse">
            <summary>Fotos adicionadas (<?php echo h((string)count($items)); ?>)</summary>
            <div class="admin-items-collapse-body">
              <?php foreach ($items as $item): $itemId = (string)($item['id'] ?? ''); ?>
                <details class="admin-item-collapse">
                  <summary>
                    <span><?php echo h((string)($item['title'] ?? '')); ?></span>
                    <span><code><?php echo h((string)basename((string)($item['file'] ?? ''))); ?></code></span>
                  </summary>
                  <div class="admin-subitem">
                    <p class="link-desc">Arquivo: <code><?php echo h((string)($item['file'] ?? '')); ?></code></p>
                    <div class="admin-grid admin-grid-2">
                      <div class="admin-field">
                        <label>Titulo da foto</label>
                        <input type="text" name="item_title[<?php echo h($itemId); ?>]" value="<?php echo h((string)($item['title'] ?? '')); ?>">
                      </div>
                      <div class="admin-field">
                        <label>Subtitulo</label>
                        <input type="text" name="item_subtitle[<?php echo h($itemId); ?>]" value="<?php echo h((string)($item['subtitle'] ?? '')); ?>">
                      </div>
                    </div>
                    <div class="admin-actions admin-subitem-actions">
                      <button type="submit" form="<?php echo h('remove-item-' . $componentId . '-' . $itemId); ?>" class="btn-admin secundario">Remover item</button>
                    </div>
                  </div>
                </details>
              <?php endforeach; ?>
            </div>
          </details>
        <?php endif; ?>

        <?php if ($type === 'documents' && !empty($items)): ?>
          <details class="admin-items-collapse">
            <summary>Documentos adicionados (<?php echo h((string)count($items)); ?>)</summary>
            <div class="admin-items-collapse-body">
              <?php foreach ($items as $item): $itemId = (string)($item['id'] ?? ''); ?>
                <details class="admin-item-collapse">
                  <summary>
                    <span><?php echo h((string)($item['title'] ?? 'Documento')); ?></span>
                    <span><code><?php echo h((string)basename((string)($item['file'] ?? ''))); ?></code></span>
                  </summary>
                  <div class="admin-subitem">
                    <p class="link-desc">Arquivo: <code><?php echo h((string)($item['file'] ?? '')); ?></code></p>
                    <div class="admin-field">
                      <label>Titulo do documento (opcional)</label>
                      <input type="text" name="item_title[<?php echo h($itemId); ?>]" value="<?php echo h((string)($item['title'] ?? '')); ?>">
                    </div>
                    <div class="admin-field">
                      <label>Descricao (opcional)</label>
                      <textarea rows="3" name="item_description[<?php echo h($itemId); ?>]"><?php echo h((string)($item['description'] ?? '')); ?></textarea>
                    </div>
                    <div class="admin-actions admin-subitem-actions">
                      <button type="submit" form="<?php echo h('remove-item-' . $componentId . '-' . $itemId); ?>" class="btn-admin secundario">Remover item</button>
                    </div>
                  </div>
                </details>
              <?php endforeach; ?>
            </div>
          </details>
        <?php endif; ?>

        <?php if ($type === 'links' && !empty($items)): ?>
          <?php foreach ($items as $item): $itemId = (string)($item['id'] ?? ''); ?>
            <div class="admin-subitem">
              <div class="admin-field">
                <label>Titulo do link</label>
                <input type="text" name="item_title[<?php echo h($itemId); ?>]" value="<?php echo h((string)($item['title'] ?? '')); ?>">
              </div>
              <div class="admin-field">
                <label>URL</label>
                <input type="text" name="item_url[<?php echo h($itemId); ?>]" value="<?php echo h((string)($item['url'] ?? '')); ?>">
              </div>
              <div class="admin-actions admin-subitem-actions">
                <button type="submit" form="<?php echo h('remove-item-' . $componentId . '-' . $itemId); ?>" class="btn-admin secundario">Remover item</button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

      </form>

      <?php if ($type === 'home-hero'): ?>
        <div class="admin-upload-panel">
          <h4>Imagem de capa da seção principal</h4>
          <p class="link-desc">Atualize a foto de capa separadamente para manter o bloco organizado.</p>
          <form method="post" enctype="multipart/form-data" class="admin-grid admin-form-compact admin-upload-form">
            <input type="hidden" name="action" value="upload-home-hero-image">
            <input type="hidden" name="page_slug" value="<?php echo h($selectedPage); ?>">
            <input type="hidden" name="component_id" value="<?php echo h($componentId); ?>">
            <div class="admin-field">
              <label>Imagem atual</label>
              <p class="link-desc"><code><?php echo h((string)($settings['image'] ?? '')); ?></code></p>
            </div>
            <div class="admin-field">
              <label for="hero-image-<?php echo h($componentId); ?>">Selecionar nova imagem</label>
              <input type="file" id="hero-image-<?php echo h($componentId); ?>" name="hero_image" accept=".jpg,.jpeg,.png,.webp,.gif" required>
            </div>
            <div class="admin-actions">
              <button type="submit" class="btn-admin secundario">Atualizar imagem</button>
            </div>
          </form>
        </div>
      <?php endif; ?>

      <?php
        $itemTypes = ['cards', 'photo-carousel', 'photo-slider', 'documents', 'links', 'gallery', 'gallery-folder'];
        $removeFormTypes = ['cards', 'photo-carousel', 'photo-slider', 'documents', 'links', 'gallery', 'gallery-folder'];
      ?>
      <?php if (in_array($type, $itemTypes, true)): ?>
        <?php if ($type === 'gallery-folder'): ?>
          <?php $savedFolder = normalizeGalleryFolder((string)($settings['folder'] ?? '')); ?>
          <?php if ($savedFolder !== ''): ?>
            <details class="admin-folder-gallery-panel">
              <summary>Adicionar fotos da pasta</summary>
              <div class="admin-folder-gallery-body">
                <p class="link-desc">Envie uma imagem por vez e preencha titulo/subtitulo para organizar a galeria.</p>
                <form method="post" enctype="multipart/form-data" class="admin-grid admin-form-compact">
                  <input type="hidden" name="action" value="add-item">
                  <input type="hidden" name="page_slug" value="<?php echo h($selectedPage); ?>">
                  <input type="hidden" name="component_id" value="<?php echo h($componentId); ?>">
                  <div class="admin-field"><label>Titulo da foto (opcional)</label><input type="text" name="new_title"></div>
                  <div class="admin-field"><label>Subtitulo (opcional)</label><input type="text" name="new_subtitle"></div>
                  <div class="admin-field"><label>Imagem</label><input type="file" name="new_image" accept=".jpg,.jpeg,.png,.webp,.gif" required></div>
                  <div class="admin-actions">
                    <button type="submit" class="btn-admin secundario">Adicionar foto</button>
                  </div>
                </form>
              </div>
            </details>
          <?php else: ?>
            <div class="admin-add-item-panel">
              <h4>Adicionar fotos da pasta</h4>
              <p class="link-desc">Salve primeiro o nome da pasta para liberar o cadastro de fotos.</p>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="admin-add-item-panel">
            <h4>Adicionar novo item</h4>
            <form method="post" enctype="multipart/form-data" class="admin-grid admin-form-compact">
              <input type="hidden" name="action" value="add-item">
              <input type="hidden" name="page_slug" value="<?php echo h($selectedPage); ?>">
              <input type="hidden" name="component_id" value="<?php echo h($componentId); ?>">

              <?php if ($type === 'cards'): ?>
                <div class="admin-field"><label>Titulo</label><input type="text" name="new_title" required></div>
                <div class="admin-field"><label>Descricao</label><input type="text" name="new_description"></div>
              <?php elseif (in_array($type, ['photo-carousel', 'photo-slider', 'gallery'], true)): ?>
                <div class="admin-field"><label>Titulo da foto (opcional)</label><input type="text" name="new_title"></div>
                <div class="admin-field"><label>Subtitulo (opcional)</label><input type="text" name="new_subtitle"></div>
                <div class="admin-field"><label>Imagem</label><input type="file" name="new_image" accept=".jpg,.jpeg,.png,.webp,.gif" required></div>
              <?php elseif ($type === 'documents'): ?>
                <div class="admin-field"><label>Titulo (opcional)</label><input type="text" name="new_title"></div>
                <div class="admin-field"><label>Descricao (opcional)</label><input type="text" name="new_description"></div>
                <div class="admin-field"><label>Documento</label><input type="file" name="new_document" required></div>
              <?php elseif ($type === 'links'): ?>
                <div class="admin-field"><label>Titulo</label><input type="text" name="new_title" required></div>
                <div class="admin-field"><label>URL</label><input type="text" name="new_url" required></div>
              <?php endif; ?>

              <div class="admin-actions">
                <button type="submit" class="btn-admin secundario">Adicionar item</button>
              </div>
            </form>
          </div>
        <?php endif; ?>

      <?php endif; ?>

      <?php if (in_array($type, $removeFormTypes, true) && !empty($items)): ?>
        <?php foreach ($items as $item): $itemId = (string)($item['id'] ?? ''); ?>
          <form method="post" id="<?php echo h('remove-item-' . $componentId . '-' . $itemId); ?>" class="admin-table-inline-form">
            <input type="hidden" name="action" value="remove-item">
            <input type="hidden" name="page_slug" value="<?php echo h($selectedPage); ?>">
            <input type="hidden" name="component_id" value="<?php echo h($componentId); ?>">
            <input type="hidden" name="item_id" value="<?php echo h($itemId); ?>">
          </form>
        <?php endforeach; ?>
      <?php endif; ?>

      <div class="admin-actions admin-section-footer-actions">
        <button type="submit" form="<?php echo h($updateFormId); ?>" class="btn-admin">Salvar alteracoes</button>
      </div>
    </section>
  <?php endforeach; ?>

  <section class="admin-section">
    <h2>Adicionar secao</h2>
    <form method="post" class="admin-grid admin-inline">
      <input type="hidden" name="action" value="add-component">
      <input type="hidden" name="page_slug" value="<?php echo h($selectedPage); ?>">
      <div class="admin-field">
        <label for="component_type">Tipo</label>
        <div class="admin-dropup" data-dropup>
          <input type="hidden" id="component_type" name="component_type" value="<?php echo h((string)COMPONENT_TYPES[0]); ?>">
          <button type="button" class="admin-dropup-trigger" data-dropup-trigger aria-haspopup="listbox" aria-expanded="false">
            <?php echo h(componentLabel((string)COMPONENT_TYPES[0])); ?>
          </button>
          <ul class="admin-dropup-menu" data-dropup-menu role="listbox">
            <?php foreach (COMPONENT_TYPES as $type): ?>
              <li>
                <button
                  type="button"
                  class="admin-dropup-option"
                  data-dropup-option
                  data-value="<?php echo h($type); ?>"
                  data-label="<?php echo h(componentLabel($type)); ?>"
                >
                  <?php echo h(componentLabel($type)); ?>
                </button>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
      <div class="admin-actions">
        <button type="submit" class="btn-admin">Adicionar secao</button>
      </div>
    </form>
    <p class="link-desc">Todos os tipos acima podem ser usados em qualquer pagina.</p>
  </section>
  <script>
    (function () {
      var dropups = document.querySelectorAll('[data-dropup]');
      if (!dropups.length) return;

      dropups.forEach(function (dropup) {
        var trigger = dropup.querySelector('[data-dropup-trigger]');
        var hiddenInput = dropup.querySelector('input[type="hidden"]');
        var options = dropup.querySelectorAll('[data-dropup-option]');
        if (!trigger || !hiddenInput || !options.length) return;

        function closeDropup() {
          dropup.classList.remove('is-open');
          trigger.setAttribute('aria-expanded', 'false');
        }

        trigger.addEventListener('click', function () {
          var isOpen = dropup.classList.contains('is-open');
          document.querySelectorAll('[data-dropup].is-open').forEach(function (opened) {
            opened.classList.remove('is-open');
            var openedTrigger = opened.querySelector('[data-dropup-trigger]');
            if (openedTrigger) openedTrigger.setAttribute('aria-expanded', 'false');
          });
          if (!isOpen) {
            dropup.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
          }
        });

        options.forEach(function (optionButton) {
          optionButton.addEventListener('click', function () {
            hiddenInput.value = optionButton.getAttribute('data-value') || '';
            trigger.textContent = optionButton.getAttribute('data-label') || '';
            closeDropup();
          });
        });
      });

      document.addEventListener('click', function (event) {
        if (event.target.closest('[data-dropup]')) return;
        document.querySelectorAll('[data-dropup].is-open').forEach(function (opened) {
          opened.classList.remove('is-open');
          var openedTrigger = opened.querySelector('[data-dropup-trigger]');
          if (openedTrigger) openedTrigger.setAttribute('aria-expanded', 'false');
        });
      });
    })();
  </script>
  <script>
    (function () {
      var scrollKey = 'admin-pages-scroll:' + window.location.pathname + window.location.search;

      var stored = sessionStorage.getItem(scrollKey);
      if (stored !== null) {
        var y = parseInt(stored, 10);
        if (!Number.isNaN(y)) {
          requestAnimationFrame(function () {
            window.scrollTo({ top: y, behavior: 'auto' });
          });
        }
        sessionStorage.removeItem(scrollKey);
      }

      document.querySelectorAll('form[method="post"], form[method="POST"]').forEach(function (form) {
        form.addEventListener('submit', function () {
          sessionStorage.setItem(scrollKey, String(window.scrollY || window.pageYOffset || 0));
        });
      });
    })();
  </script>
<?php endif; ?>
<?php
renderAdminEnd();
