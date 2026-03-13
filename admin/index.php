<?php
declare(strict_types=1);

require __DIR__ . '/_common.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'login') {
    $password = (string)($_POST['password'] ?? '');
    if (loginAdmin($password)) {
        addFlash('messages', 'Login realizado com sucesso.');
        header('Location: pages.php');
        exit;
    }
    addFlash('errors', 'Senha invalida.');
    header('Location: index.php');
    exit;
}

$flash = pullFlash();

if (!isLoggedIn()):
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Painel Admin GEPHECL</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/site.css">
</head>
<body>
  <main class="admin-wrap admin-login-wrap">
    <div class="admin-card admin-login-card">
      <?php foreach ($flash['messages'] as $msg): ?>
        <div class="admin-alert ok"><?php echo h((string)$msg); ?></div>
      <?php endforeach; ?>
      <?php foreach ($flash['errors'] as $err): ?>
        <div class="admin-alert erro"><?php echo h((string)$err); ?></div>
      <?php endforeach; ?>

      <h1>Entrar no painel</h1>
      <form method="post" class="admin-grid">
        <input type="hidden" name="action" value="login">
        <div class="admin-field">
          <label for="password">Senha do admin</label>
          <input type="password" id="password" name="password" required>
        </div>
        <div class="admin-actions">
          <button type="submit" class="btn-admin">Entrar</button>
          <a href="../index.html" class="btn-admin secundario" style="text-decoration:none;">Voltar ao site</a>
        </div>
      </form>
    </div>
  </main>
</body>
</html>
<?php
exit;
endif;

renderAdminStart('Visao geral', 'dashboard', $flash);
$config = loadConfig();
?>
<section class="admin-section">
  <h2>Atalhos do painel</h2>
  <p class="link-desc">O painel foi reorganizado em duas areas principais.</p>
  <div class="admin-cta-grid">
    <a class="admin-cta" href="menu.php"><strong>Menu</strong><span>Organizar itens e criar nova pagina com um clique.</span></a>
    <a class="admin-cta" href="pages.php"><strong>Paginas</strong><span>Selecionar pagina e editar secoes, fotos, documentos e links.</span></a>
  </div>
</section>
<?php
renderAdminEnd();
