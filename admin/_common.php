<?php
declare(strict_types=1);

session_start();

const ADMIN_PASSWORD_HASH = '$2y$10$IatKWdOX0C91JVCVr.OeaOo8poQt8Wk292x6Fs3lW9jZuaFsOC4rm'; // senha padrao: gephecl2022
const ADMIN_SESSION_TTL = 7200; // 2h
const MAX_FILE_SIZE = 12582912; // 12MB
const ALLOWED_IMAGE_EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
const ALLOWED_DOC_EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar'];

define('ROOT_DIR', dirname(__DIR__));
define('CONFIG_BASE_FILE', ROOT_DIR . '/data/site.json');
define('CONFIG_RUNTIME_FILE', ROOT_DIR . '/data/site.runtime.json');
define('PHOTOS_DIR', ROOT_DIR . '/uploads/fotos');
define('DOCS_DIR', ROOT_DIR . '/uploads/docs');
define('HOME_DIR', ROOT_DIR . '/uploads/home');
define('MANIFEST_FILE', PHOTOS_DIR . '/manifest.json');

ensureDir(PHOTOS_DIR);
ensureDir(DOCS_DIR);
ensureDir(HOME_DIR);

if (!file_exists(MANIFEST_FILE)) {
    file_put_contents(MANIFEST_FILE, "[]\n");
}
if (!file_exists(CONFIG_BASE_FILE)) {
    failHard('Arquivo de configuracao nao encontrado em data/site.json.');
}

handleLogoutAndSession();

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function ensureDir(string $path): void
{
    if (!is_dir($path)) {
        mkdir($path, 0775, true);
    }
}

function failHard(string $message): void
{
    http_response_code(500);
    echo '<h1>Erro</h1><p>' . h($message) . '</p>';
    exit;
}

function addFlash(string $type, string $message): void
{
    if (!isset($_SESSION['admin_flash']) || !is_array($_SESSION['admin_flash'])) {
        $_SESSION['admin_flash'] = ['messages' => [], 'errors' => []];
    }
    if (!isset($_SESSION['admin_flash'][$type]) || !is_array($_SESSION['admin_flash'][$type])) {
        $_SESSION['admin_flash'][$type] = [];
    }
    $_SESSION['admin_flash'][$type][] = $message;
}

function pullFlash(): array
{
    $flash = $_SESSION['admin_flash'] ?? ['messages' => [], 'errors' => []];
    unset($_SESSION['admin_flash']);
    if (!isset($flash['messages']) || !is_array($flash['messages'])) {
        $flash['messages'] = [];
    }
    if (!isset($flash['errors']) || !is_array($flash['errors'])) {
        $flash['errors'] = [];
    }
    return $flash;
}

function handleLogoutAndSession(): void
{
    if (isset($_GET['logout'])) {
        logoutAndRedirect();
    }

    if (!isLoggedIn()) {
        return;
    }

    $lastActivity = (int)($_SESSION['gephecl_last_activity'] ?? 0);
    if ($lastActivity > 0 && (time() - $lastActivity) > ADMIN_SESSION_TTL) {
        $_SESSION = [];
        session_regenerate_id(true);
        addFlash('errors', 'Sessao expirada por inatividade. Entre novamente.');
        header('Location: index.php');
        exit;
    }

    $_SESSION['gephecl_last_activity'] = time();
}

function logoutAndRedirect(): void
{
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['gephecl_admin']);
}

function requireLogin(): void
{
    if (isLoggedIn()) {
        return;
    }
    addFlash('errors', 'Faça login para acessar o painel.');
    header('Location: index.php');
    exit;
}

function loginAdmin(string $password): bool
{
    if (!password_verify($password, ADMIN_PASSWORD_HASH)) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['gephecl_admin'] = true;
    $_SESSION['gephecl_last_activity'] = time();
    return true;
}

function loadJsonFile(string $path)
{
    if (!file_exists($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    $decoded = json_decode((string)$raw, true);
    return is_array($decoded) ? $decoded : [];
}

function saveJsonFile(string $path, array $data): void
{
    file_put_contents(
        $path,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL
    );
}

function loadConfig(): array
{
    if (file_exists(CONFIG_RUNTIME_FILE)) {
        $runtime = loadJsonFile(CONFIG_RUNTIME_FILE);
        if (is_array($runtime) && !empty($runtime)) {
            return $runtime;
        }
    }
    return loadJsonFile(CONFIG_BASE_FILE);
}

function saveConfig(array $config): void
{
    saveJsonFile(CONFIG_RUNTIME_FILE, $config);
}

function normalizeSlug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9-]+/', '-', $value);
    $value = preg_replace('/-+/', '-', (string)$value);
    return trim((string)$value, '-');
}

function menuItemExists(array $menu, string $itemId): bool
{
    foreach ($menu as $item) {
        if (($item['id'] ?? '') === $itemId) {
            return true;
        }
    }
    return false;
}

function findMenuIndex(array $menu, string $itemId): int
{
    foreach ($menu as $idx => $item) {
        if (($item['id'] ?? '') === $itemId) {
            return (int)$idx;
        }
    }
    return -1;
}

function nextPositionForParent(array $menu, ?string $parentId): int
{
    $max = 0;
    foreach ($menu as $item) {
        $itemParent = $item['parentId'] ?? null;
        if ($itemParent === $parentId) {
            $pos = (int)($item['position'] ?? 0);
            if ($pos > $max) {
                $max = $pos;
            }
        }
    }
    return $max + 1;
}

function normalizeMenuPositions(array &$menu): void
{
    $groups = [];
    foreach ($menu as $idx => $item) {
        $parent = $item['parentId'] ?? null;
        $key = $parent ?? '__root__';
        if (!isset($groups[$key])) {
            $groups[$key] = [];
        }
        $groups[$key][] = $idx;
    }

    foreach ($groups as $indices) {
        usort($indices, static function ($a, $b) use ($menu): int {
            $pa = (int)($menu[$a]['position'] ?? 0);
            $pb = (int)($menu[$b]['position'] ?? 0);
            return $pa <=> $pb;
        });

        $position = 1;
        foreach ($indices as $idx) {
            $menu[$idx]['position'] = $position++;
        }
    }
}

function moveMenuItem(array &$menu, string $itemId, string $direction): bool
{
    $idx = findMenuIndex($menu, $itemId);
    if ($idx < 0) {
        return false;
    }

    $parent = $menu[$idx]['parentId'] ?? null;
    $siblings = [];
    foreach ($menu as $i => $item) {
        if (($item['parentId'] ?? null) === $parent) {
            $siblings[] = $i;
        }
    }
    usort($siblings, static function ($a, $b) use ($menu): int {
        return ((int)($menu[$a]['position'] ?? 0)) <=> ((int)($menu[$b]['position'] ?? 0));
    });

    $currentPos = array_search($idx, $siblings, true);
    if ($currentPos === false) {
        return false;
    }

    $swapPos = $direction === 'up' ? $currentPos - 1 : $currentPos + 1;
    if ($swapPos < 0 || $swapPos >= count($siblings)) {
        return false;
    }

    $currentIdx = $siblings[$currentPos];
    $swapIdx = $siblings[$swapPos];
    $tmp = $menu[$currentIdx]['position'] ?? 0;
    $menu[$currentIdx]['position'] = $menu[$swapIdx]['position'] ?? 0;
    $menu[$swapIdx]['position'] = $tmp;
    normalizeMenuPositions($menu);
    return true;
}

function saveUploadedFile(array $file, string $targetDir, array $allowedExt, int $maxSize, bool $mustBeImage): array
{
    $name = (string)($file['name'] ?? '');
    $tmp = (string)($file['tmp_name'] ?? '');
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    $size = (int)($file['size'] ?? 0);

    if ($error !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => "Falha no upload de {$name}."];
    }
    if (!is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => "Arquivo invalido: {$name}."];
    }
    if ($size <= 0 || $size > $maxSize) {
        return ['ok' => false, 'error' => "Arquivo fora do limite ({$name})."];
    }

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        return ['ok' => false, 'error' => "Formato nao permitido: {$name}."];
    }

    if ($mustBeImage && @getimagesize($tmp) === false) {
        return ['ok' => false, 'error' => "Arquivo nao e imagem valida: {$name}."];
    }

    $base = pathinfo($name, PATHINFO_FILENAME);
    $base = preg_replace('/[^a-zA-Z0-9-_]+/', '-', $base);
    $base = trim((string)$base, '-_');
    if ($base === '') {
        $base = 'arquivo';
    }

    $finalName = date('Ymd-His') . '-' . randomToken(8) . '-' . strtolower($base) . '.' . $ext;
    $destination = $targetDir . '/' . $finalName;

    if (!move_uploaded_file($tmp, $destination)) {
        return ['ok' => false, 'error' => "Nao foi possivel salvar {$name}."];
    }

    return ['ok' => true, 'file' => $finalName];
}

function buildStandardPageTemplate(string $slug, string $title): string
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$safeTitle} - GEPHECL | UFAL CEDU</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/site.css">
</head>
<body data-page="{$slug}">
  <header class="site-header">
    <div>
      <h1>gephecl</h1>
    </div>
  </header>

  <nav class="nav-wrap" data-component="navbar"></nav>

  <main class="main-wrap">
    <section class="page-top reveal-on-scroll">
      <h1 class="page-title">{$safeTitle}</h1>
      <p class="breadcrumb"><a href="index.html">Home</a> / {$safeTitle}</p>
    </section>
    <div class="conteudo reveal-on-scroll">
      <p>Pagina criada pelo painel administrativo. Edite titulo, subtitulo, descricao e documentos em <code>admin/pages.php</code>.</p>
    </div>
  </main>

  <footer class="site-footer" data-component="footer"></footer>

  <script src="js/site.js"></script>
</body>
</html>
HTML;
}

function randomToken(int $length): string
{
    $length = max(1, $length);
    try {
        return substr(bin2hex(random_bytes((int)ceil($length / 2))), 0, $length);
    } catch (Throwable $e) {
        $pool = 'abcdef0123456789';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $pool[(int)mt_rand(0, strlen($pool) - 1)];
        }
        return $out;
    }
}

function buildMenuDisplayRows(array $menu): array
{
    $byParent = [];
    foreach ($menu as $item) {
        $parent = $item['parentId'] ?? '__root__';
        if ($parent === null || $parent === '') {
            $parent = '__root__';
        }
        if (!isset($byParent[$parent])) {
            $byParent[$parent] = [];
        }
        $byParent[$parent][] = $item;
    }

    foreach ($byParent as &$items) {
        usort($items, static function ($a, $b): int {
            $pa = (int)($a['position'] ?? 0);
            $pb = (int)($b['position'] ?? 0);
            return $pa <=> $pb;
        });
    }
    unset($items);

    $rows = [];
    $visited = [];

    $walk = static function (string $parentId, int $depth) use (&$walk, &$rows, &$visited, $byParent): void {
        $children = $byParent[$parentId] ?? [];
        foreach ($children as $child) {
            $id = (string)($child['id'] ?? '');
            if ($id === '' || isset($visited[$id])) {
                continue;
            }
            $visited[$id] = true;
            $rows[] = ['item' => $child, 'depth' => $depth];
            $walk($id, $depth + 1);
        }
    };

    $walk('__root__', 0);

    foreach ($menu as $item) {
        $id = (string)($item['id'] ?? '');
        if ($id !== '' && !isset($visited[$id])) {
            $rows[] = ['item' => $item, 'depth' => 0];
        }
    }
    return $rows;
}

function renderAdminStart(string $title, string $activeKey, array $flash): void
{
    $items = [
        'dashboard' => ['label' => 'Visao geral', 'href' => 'index.php'],
        'pages' => ['label' => 'Paginas', 'href' => 'pages.php'],
        'menu' => ['label' => 'Menu', 'href' => 'menu.php'],
        'new-page' => ['label' => 'Nova pagina', 'href' => 'new-page.php'],
        'documents' => ['label' => 'Documentos', 'href' => 'documents.php'],
        'home-image' => ['label' => 'Imagem da home', 'href' => 'home-image.php'],
        'photos' => ['label' => 'Galeria de fotos', 'href' => 'photos.php']
    ];
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo h($title); ?> - Painel Admin GEPHECL</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/site.css">
</head>
<body>
  <main class="admin-wrap">
    <div class="admin-card">
      <div class="admin-toolbar">
        <div>
          <h1><?php echo h($title); ?></h1>
          <p class="link-desc admin-toolbar-sub">Painel protegido por login. Sessao expira em 2 horas sem atividade.</p>
        </div>
        <div class="admin-actions">
          <a href="../index.html" class="btn-admin secundario" style="text-decoration:none;">Ver site</a>
          <a href="?logout=1" class="btn-admin secundario" style="text-decoration:none;">Sair</a>
        </div>
      </div>

      <?php foreach ($flash['messages'] as $msg): ?>
        <div class="admin-alert ok"><?php echo h((string)$msg); ?></div>
      <?php endforeach; ?>
      <?php foreach ($flash['errors'] as $err): ?>
        <div class="admin-alert erro"><?php echo h((string)$err); ?></div>
      <?php endforeach; ?>

      <div class="admin-shell">
        <aside class="admin-side-nav">
          <?php foreach ($items as $key => $item): ?>
            <a href="<?php echo h($item['href']); ?>" class="<?php echo $key === $activeKey ? 'is-active' : ''; ?>">
              <?php echo h($item['label']); ?>
            </a>
          <?php endforeach; ?>
        </aside>
        <div class="admin-main">
<?php
}

function renderAdminEnd(): void
{
    ?>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
<?php
}
