<?php
declare(strict_types=1);

require __DIR__ . '/_common.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'save-theme') {
    $primary = normalizeHexColorToken((string)($_POST['primary_color'] ?? ''));
    $background = normalizeHexColorToken((string)($_POST['background_color'] ?? ''));
    $navFontSize = normalizeFontSizeToken($_POST['nav_font_size'] ?? null);
    $buttonFontSize = normalizeFontSizeToken($_POST['button_font_size'] ?? null);
    $heroSubtitleFontSize = normalizeFontSizeToken($_POST['hero_subtitle_font_size'] ?? null);
    $heroTitleFontSize = normalizeFontSizeToken($_POST['hero_title_font_size'] ?? null);
    $heroDescriptionFontSize = normalizeFontSizeToken($_POST['hero_description_font_size'] ?? null);
    $pageTitleFontSize = normalizeFontSizeToken($_POST['page_title_font_size'] ?? null);
    $pageSubtitleFontSize = normalizeFontSizeToken($_POST['page_subtitle_font_size'] ?? null);
    $pageDescriptionFontSize = normalizeFontSizeToken($_POST['page_description_font_size'] ?? null);
    $sectionTitleFontSize = normalizeFontSizeToken($_POST['section_title_font_size'] ?? null);
    $sectionDescriptionFontSize = normalizeFontSizeToken($_POST['section_description_font_size'] ?? null);

    if (
        $primary === null || $background === null ||
        $navFontSize === null || $buttonFontSize === null ||
        $heroSubtitleFontSize === null || $heroTitleFontSize === null || $heroDescriptionFontSize === null ||
        $pageTitleFontSize === null || $pageSubtitleFontSize === null || $pageDescriptionFontSize === null ||
        $sectionTitleFontSize === null || $sectionDescriptionFontSize === null
    ) {
        addFlash('errors', 'Informe valores validos para cores e tamanhos de fonte.');
        header('Location: personalizar.php');
        exit;
    }

    saveThemeTokens([
        'primaryColor' => $primary,
        'backgroundColor' => $background,
        'navFontSize' => $navFontSize,
        'buttonFontSize' => $buttonFontSize,
        'heroSubtitleFontSize' => $heroSubtitleFontSize,
        'heroTitleFontSize' => $heroTitleFontSize,
        'heroDescriptionFontSize' => $heroDescriptionFontSize,
        'pageTitleFontSize' => $pageTitleFontSize,
        'pageSubtitleFontSize' => $pageSubtitleFontSize,
        'pageDescriptionFontSize' => $pageDescriptionFontSize,
        'sectionTitleFontSize' => $sectionTitleFontSize,
        'sectionDescriptionFontSize' => $sectionDescriptionFontSize
    ]);
    addFlash('messages', 'Personalizacao atualizada com sucesso.');
    header('Location: personalizar.php');
    exit;
}

$theme = loadThemeTokens();
$primaryColor = (string)($theme['primaryColor'] ?? DEFAULT_THEME_PRIMARY_COLOR);
$backgroundColor = (string)($theme['backgroundColor'] ?? DEFAULT_THEME_BACKGROUND_COLOR);
$navFontSize = (float)($theme['navFontSize'] ?? DEFAULT_THEME_NAV_FONT_SIZE_PX);
$buttonFontSize = (float)($theme['buttonFontSize'] ?? DEFAULT_THEME_BUTTON_FONT_SIZE_PX);
$heroSubtitleFontSize = (float)($theme['heroSubtitleFontSize'] ?? DEFAULT_THEME_HERO_SUBTITLE_FONT_SIZE_PX);
$heroTitleFontSize = (float)($theme['heroTitleFontSize'] ?? DEFAULT_THEME_HERO_TITLE_FONT_SIZE_PX);
$heroDescriptionFontSize = (float)($theme['heroDescriptionFontSize'] ?? DEFAULT_THEME_HERO_DESCRIPTION_FONT_SIZE_PX);
$pageTitleFontSize = (float)($theme['pageTitleFontSize'] ?? DEFAULT_THEME_PAGE_TITLE_FONT_SIZE_PX);
$pageSubtitleFontSize = (float)($theme['pageSubtitleFontSize'] ?? DEFAULT_THEME_PAGE_SUBTITLE_FONT_SIZE_PX);
$pageDescriptionFontSize = (float)($theme['pageDescriptionFontSize'] ?? DEFAULT_THEME_PAGE_DESCRIPTION_FONT_SIZE_PX);
$sectionTitleFontSize = (float)($theme['sectionTitleFontSize'] ?? DEFAULT_THEME_SECTION_TITLE_FONT_SIZE_PX);
$sectionDescriptionFontSize = (float)($theme['sectionDescriptionFontSize'] ?? DEFAULT_THEME_SECTION_DESCRIPTION_FONT_SIZE_PX);

renderAdminStart('Personalizar', 'personalizar', pullFlash());
?>
<section class="admin-section">
  <h2>Cores da plataforma</h2>
  <p class="link-desc">
    A cor principal define botoes primarios, titulos/subtitulos e destaques do menu.
    A cor de fundo define o fundo geral das paginas.
  </p>

  <form method="post" class="admin-grid admin-form-compact">
    <input type="hidden" name="action" value="save-theme">

    <div class="admin-field">
      <label for="primary_color">Cor principal</label>
      <input
        type="color"
        id="primary_color"
        name="primary_color"
        value="<?php echo h($primaryColor); ?>"
        required
      >
      <p class="link-desc">Usada nos botoes primarios e titulos.</p>
    </div>

    <div class="admin-field">
      <label for="background_color">Cor de fundo</label>
      <input
        type="color"
        id="background_color"
        name="background_color"
        value="<?php echo h($backgroundColor); ?>"
        required
      >
      <p class="link-desc">Usada como fundo geral das paginas.</p>
    </div>

    <div class="admin-field admin-field-full">
      <h3 style="margin:0;">Tamanhos de fonte (px)</h3>
      <p class="link-desc" style="margin:0.25rem 0 0;">Cada campo altera um texto especifico do site.</p>
    </div>

    <div class="admin-field">
      <label for="nav_font_size">Menu principal (itens de navegacao)</label>
      <input
        type="number"
        id="nav_font_size"
        name="nav_font_size"
        value="<?php echo h(number_format($navFontSize, 2, '.', '')); ?>"
        min="10"
        max="96"
        step="0.01"
        required
      >
    </div>

    <div class="admin-field">
      <label for="button_font_size">Botoes (texto dos botoes)</label>
      <input
        type="number"
        id="button_font_size"
        name="button_font_size"
        value="<?php echo h(number_format($buttonFontSize, 2, '.', '')); ?>"
        min="10"
        max="96"
        step="0.01"
        required
      >
    </div>

    <div class="admin-field">
      <label for="hero_title_font_size">Home: titulo principal (bloco inicial)</label>
      <input
        type="number"
        id="hero_title_font_size"
        name="hero_title_font_size"
        value="<?php echo h(number_format($heroTitleFontSize, 2, '.', '')); ?>"
        min="10"
        max="96"
        step="0.01"
        required
      >
    </div>

    <div class="admin-field">
      <label for="hero_subtitle_font_size">Home: subtitulo (linha pequena acima do titulo)</label>
      <input
        type="number"
        id="hero_subtitle_font_size"
        name="hero_subtitle_font_size"
        value="<?php echo h(number_format($heroSubtitleFontSize, 2, '.', '')); ?>"
        min="10"
        max="96"
        step="0.01"
        required
      >
    </div>

    <div class="admin-field">
      <label for="hero_description_font_size">Home: descricao (paragrafo principal)</label>
      <input
        type="number"
        id="hero_description_font_size"
        name="hero_description_font_size"
        value="<?php echo h(number_format($heroDescriptionFontSize, 2, '.', '')); ?>"
        min="10"
        max="96"
        step="0.01"
        required
      >
    </div>

    <div class="admin-field">
      <label for="page_title_font_size">Paginas internas: titulo da pagina</label>
      <input
        type="number"
        id="page_title_font_size"
        name="page_title_font_size"
        value="<?php echo h(number_format($pageTitleFontSize, 2, '.', '')); ?>"
        min="10"
        max="96"
        step="0.01"
        required
      >
    </div>

    <div class="admin-field">
      <label for="page_subtitle_font_size">Paginas internas: subtitulo</label>
      <input
        type="number"
        id="page_subtitle_font_size"
        name="page_subtitle_font_size"
        value="<?php echo h(number_format($pageSubtitleFontSize, 2, '.', '')); ?>"
        min="10"
        max="96"
        step="0.01"
        required
      >
    </div>

    <div class="admin-field">
      <label for="page_description_font_size">Paginas internas: descricao</label>
      <input
        type="number"
        id="page_description_font_size"
        name="page_description_font_size"
        value="<?php echo h(number_format($pageDescriptionFontSize, 2, '.', '')); ?>"
        min="10"
        max="96"
        step="0.01"
        required
      >
    </div>

    <div class="admin-field">
      <label for="section_title_font_size">Componentes: titulos de secao/cards</label>
      <input
        type="number"
        id="section_title_font_size"
        name="section_title_font_size"
        value="<?php echo h(number_format($sectionTitleFontSize, 2, '.', '')); ?>"
        min="10"
        max="96"
        step="0.01"
        required
      >
    </div>

    <div class="admin-field">
      <label for="section_description_font_size">Componentes: descricoes e textos de apoio</label>
      <input
        type="number"
        id="section_description_font_size"
        name="section_description_font_size"
        value="<?php echo h(number_format($sectionDescriptionFontSize, 2, '.', '')); ?>"
        min="10"
        max="96"
        step="0.01"
        required
      >
    </div>

    <div class="admin-actions">
      <button type="submit" class="btn-admin">Salvar personalizacao</button>
    </div>
  </form>
</section>
<?php
renderAdminEnd();
