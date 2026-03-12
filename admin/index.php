<?php
declare(strict_types=1);

session_start();

$adminPasswordHash = '$2y$10$HxHTUOYiDrl0KYs9dfPQu.qJ8coDSDmz4QFaLZ1h9pNvVcey5fbFe'; // senha padrão: admin123
$maxFileSize = 8 * 1024 * 1024; // 8MB por arquivo
$allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

$photosDir = dirname(__DIR__) . '/uploads/fotos';
$manifestFile = $photosDir . '/manifest.json';

if (!is_dir($photosDir)) {
    mkdir($photosDir, 0775, true);
}
if (!file_exists($manifestFile)) {
    file_put_contents($manifestFile, "[]\n");
}

$isLogged = !empty($_SESSION['gephecl_admin']);
$messages = [];
$errors = [];

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $password = $_POST['password'] ?? '';
        if (password_verify($password, $adminPasswordHash)) {
            $_SESSION['gephecl_admin'] = true;
            header('Location: index.php');
            exit;
        }
        $errors[] = 'Senha inválida.';
    }

    if ($action === 'upload' && $isLogged) {
        if (empty($_FILES['photos'])) {
            $errors[] = 'Selecione pelo menos uma imagem.';
        } else {
            $manifestRaw = file_get_contents($manifestFile);
            $manifest = json_decode((string)$manifestRaw, true);
            if (!is_array($manifest)) {
                $manifest = [];
            }

            $names = $_FILES['photos']['name'] ?? [];
            $tmpNames = $_FILES['photos']['tmp_name'] ?? [];
            $errorsUpload = $_FILES['photos']['error'] ?? [];
            $sizes = $_FILES['photos']['size'] ?? [];

            $saved = 0;
            foreach ($names as $i => $originalName) {
                $errorCode = $errorsUpload[$i] ?? UPLOAD_ERR_NO_FILE;
                if ($errorCode === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if ($errorCode !== UPLOAD_ERR_OK) {
                    $errors[] = "Falha ao enviar {$originalName}.";
                    continue;
                }

                $tmpPath = $tmpNames[$i] ?? '';
                $size = (int)($sizes[$i] ?? 0);
                if (!is_uploaded_file($tmpPath)) {
                    $errors[] = "Arquivo inválido: {$originalName}.";
                    continue;
                }
                if ($size <= 0 || $size > $maxFileSize) {
                    $errors[] = "Arquivo fora do limite (8MB): {$originalName}.";
                    continue;
                }

                $ext = strtolower(pathinfo((string)$originalName, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt, true)) {
                    $errors[] = "Formato não permitido: {$originalName}.";
                    continue;
                }

                $imageInfo = @getimagesize($tmpPath);
                if ($imageInfo === false) {
                    $errors[] = "Arquivo não é uma imagem válida: {$originalName}.";
                    continue;
                }

                try {
                    $finalName = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                } catch (Throwable $e) {
                    $finalName = date('Ymd-His') . '-' . mt_rand(1000, 9999) . '.' . $ext;
                }

                $destination = $photosDir . '/' . $finalName;
                if (!move_uploaded_file($tmpPath, $destination)) {
                    $errors[] = "Não foi possível salvar {$originalName}.";
                    continue;
                }

                $caption = trim(pathinfo((string)$originalName, PATHINFO_FILENAME));
                $caption = preg_replace('/[-_]+/', ' ', $caption);

                $manifest[] = [
                    'file' => $finalName,
                    'caption' => $caption !== '' ? $caption : 'Foto',
                    'uploadedAt' => date('c')
                ];
                $saved++;
            }

            file_put_contents(
                $manifestFile,
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL
            );

            if ($saved > 0) {
                $messages[] = "{$saved} foto(s) enviada(s) com sucesso.";
            }
            if ($saved === 0 && empty($errors)) {
                $errors[] = 'Nenhuma foto foi enviada.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Painel Admin - GEPHECL</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/site.css">
</head>
<body>
  <header class="site-header">
    <div>
      <h1>gephecl</h1>
    </div>
  </header>

  <main class="admin-wrap">
    <div class="admin-card">
      <?php foreach ($messages as $msg): ?>
        <div class="admin-alert ok"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endforeach; ?>
      <?php foreach ($errors as $err): ?>
        <div class="admin-alert erro"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endforeach; ?>

      <?php if (!$isLogged): ?>
        <h1>Entrar</h1>
        <form method="post" class="admin-grid">
          <input type="hidden" name="action" value="login">
          <div class="admin-field">
            <label for="password">Senha do admin</label>
            <input type="password" id="password" name="password" required>
          </div>
          <div class="admin-actions">
            <button type="submit" class="btn-admin">Entrar</button>
            <a href="../fotos.html" class="btn-admin secundario" style="text-decoration:none;">Voltar para galeria</a>
          </div>
        </form>
        <p class="link-desc" style="margin-top: 1rem;">Senha padrão inicial: <code>admin123</code>. Altere no arquivo <code>admin/index.php</code> antes de publicar.</p>
      <?php else: ?>
        <h1>Upload de Fotos</h1>
        <p>As imagens enviadas serão salvas em <code>uploads/fotos/</code> e exibidas automaticamente em <code>fotos.html</code>.</p>
        <form method="post" enctype="multipart/form-data" class="admin-grid">
          <input type="hidden" name="action" value="upload">
          <div class="admin-field">
            <label for="photos">Selecione uma ou mais imagens</label>
            <input type="file" id="photos" name="photos[]" accept=".jpg,.jpeg,.png,.webp,.gif" multiple required>
          </div>
          <div class="admin-actions">
            <button type="submit" class="btn-admin">Enviar fotos</button>
            <a href="?logout=1" class="btn-admin secundario" style="text-decoration:none;">Sair</a>
            <a href="../fotos.html" class="btn-admin secundario" style="text-decoration:none;">Ver galeria</a>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </main>

  <footer class="site-footer">
    História da Educação, Cultura e Literatura
  </footer>
</body>
</html>
