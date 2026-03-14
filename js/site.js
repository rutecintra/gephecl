/* GEPHECL - interacoes da UI */

var FALLBACK_CONFIG = {
  site: {
    brand: 'gephecl',
    footerText: 'Historia da Educacao, Cultura e Literatura',
    homeImage: 'uploads/home/capa.jpg',
    galleryDefaultView: 'grid'
  },
  pages: {},
  pageComponents: {},
  menu: [],
  documents: {},
  galleryManifest: 'uploads/fotos/manifest.json'
};

document.addEventListener('DOMContentLoaded', function () {
  resetTransientUiState();
  loadSiteConfig().then(function (config) {
    applySiteTheme(config);
    setupSharedComponents(config);
    applyPageContent(config);
    setupNavBrand(config);
    setupMobileNavToggle();
    setupMobileDropdown();
    setupDesktopDropdownHover();
    setupStickyNavBackground();
    setupRevealOnScroll();
    setupCounterAnimations();
    setupTiltCards();
    setupGalleryFromManifest(config);
    setupGalleryLightbox();
  });
});

window.addEventListener('pageshow', function () {
  resetTransientUiState();
});

function resetTransientUiState() {
  if (document.body) {
    document.body.classList.remove('nav-sidebar-open');
  }
  var navWrap = document.querySelector('.nav-wrap');
  if (!navWrap) return;
  navWrap.classList.remove('nav-open');
  navWrap.querySelectorAll('.nav-list > li.dropdown-open').forEach(function (item) {
    item.classList.remove('dropdown-open');
  });
  var toggle = navWrap.querySelector('.nav-toggle');
  if (toggle) {
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Abrir menu de navegacao');
  }
}

function loadSiteConfig() {
  return fetchJsonNoStore('data/site.runtime.json')
    .then(function (runtimeConfig) {
      if (runtimeConfig && typeof runtimeConfig === 'object') {
        return runtimeConfig;
      }
      return fetchJsonNoStore('data/site.json');
    })
    .then(function (config) {
      if (!config || typeof config !== 'object') return FALLBACK_CONFIG;
      return mergeThemeTokensIntoConfig(config);
    })
    .catch(function () {
      return mergeThemeTokensIntoConfig(FALLBACK_CONFIG);
    });
}

function mergeThemeTokensIntoConfig(config) {
  return fetchJsonNoStore('data/theme.tokens.json')
    .then(function (themeTokens) {
      if (!themeTokens || typeof themeTokens !== 'object') {
        return config;
      }
      var primary = normalizeHexColor(themeTokens.primaryColor);
      var background = normalizeHexColor(themeTokens.backgroundColor);
      var navFontSize = normalizeThemeFontSize(themeTokens.navFontSize);
      var buttonFontSize = normalizeThemeFontSize(themeTokens.buttonFontSize);
      var heroSubtitleFontSize = normalizeThemeFontSize(themeTokens.heroSubtitleFontSize);
      var heroTitleFontSize = normalizeThemeFontSize(themeTokens.heroTitleFontSize);
      var heroDescriptionFontSize = normalizeThemeFontSize(themeTokens.heroDescriptionFontSize);
      var pageTitleFontSize = normalizeThemeFontSize(themeTokens.pageTitleFontSize);
      var pageSubtitleFontSize = normalizeThemeFontSize(themeTokens.pageSubtitleFontSize);
      var pageDescriptionFontSize = normalizeThemeFontSize(themeTokens.pageDescriptionFontSize);
      var sectionTitleFontSize = normalizeThemeFontSize(themeTokens.sectionTitleFontSize);
      var sectionDescriptionFontSize = normalizeThemeFontSize(themeTokens.sectionDescriptionFontSize);
      var legacyTitleFontSize = normalizeThemeFontSize(themeTokens.titleFontSize);
      var legacySubtitleFontSize = normalizeThemeFontSize(themeTokens.subtitleFontSize);
      var legacyDescriptionFontSize = normalizeThemeFontSize(themeTokens.descriptionFontSize);

      if (
        !primary && !background &&
        navFontSize === null && buttonFontSize === null &&
        heroSubtitleFontSize === null && heroTitleFontSize === null && heroDescriptionFontSize === null &&
        pageTitleFontSize === null && pageSubtitleFontSize === null && pageDescriptionFontSize === null &&
        sectionTitleFontSize === null && sectionDescriptionFontSize === null &&
        legacyTitleFontSize === null && legacySubtitleFontSize === null && legacyDescriptionFontSize === null
      ) {
        return config;
      }

      if (!config.site || typeof config.site !== 'object') {
        config.site = {};
      }
      if (!config.site.theme || typeof config.site.theme !== 'object') {
        config.site.theme = {};
      }
      if (primary) config.site.theme.primaryColor = primary;
      if (background) config.site.theme.backgroundColor = background;
      if (navFontSize !== null) config.site.theme.navFontSize = navFontSize;
      if (buttonFontSize !== null) config.site.theme.buttonFontSize = buttonFontSize;
      if (heroSubtitleFontSize !== null) config.site.theme.heroSubtitleFontSize = heroSubtitleFontSize;
      if (heroTitleFontSize !== null) config.site.theme.heroTitleFontSize = heroTitleFontSize;
      if (heroDescriptionFontSize !== null) config.site.theme.heroDescriptionFontSize = heroDescriptionFontSize;
      if (pageTitleFontSize !== null) config.site.theme.pageTitleFontSize = pageTitleFontSize;
      if (pageSubtitleFontSize !== null) config.site.theme.pageSubtitleFontSize = pageSubtitleFontSize;
      if (pageDescriptionFontSize !== null) config.site.theme.pageDescriptionFontSize = pageDescriptionFontSize;
      if (sectionTitleFontSize !== null) config.site.theme.sectionTitleFontSize = sectionTitleFontSize;
      if (sectionDescriptionFontSize !== null) config.site.theme.sectionDescriptionFontSize = sectionDescriptionFontSize;

      // Backward compatibility with old generic font tokens.
      if (legacyTitleFontSize !== null) {
        if (config.site.theme.heroTitleFontSize === undefined) config.site.theme.heroTitleFontSize = legacyTitleFontSize;
        if (config.site.theme.pageTitleFontSize === undefined) config.site.theme.pageTitleFontSize = legacyTitleFontSize;
        if (config.site.theme.sectionTitleFontSize === undefined) config.site.theme.sectionTitleFontSize = legacyTitleFontSize;
      }
      if (legacySubtitleFontSize !== null) {
        if (config.site.theme.heroSubtitleFontSize === undefined) config.site.theme.heroSubtitleFontSize = legacySubtitleFontSize;
        if (config.site.theme.pageSubtitleFontSize === undefined) config.site.theme.pageSubtitleFontSize = legacySubtitleFontSize;
      }
      if (legacyDescriptionFontSize !== null) {
        if (config.site.theme.heroDescriptionFontSize === undefined) config.site.theme.heroDescriptionFontSize = legacyDescriptionFontSize;
        if (config.site.theme.pageDescriptionFontSize === undefined) config.site.theme.pageDescriptionFontSize = legacyDescriptionFontSize;
        if (config.site.theme.sectionDescriptionFontSize === undefined) config.site.theme.sectionDescriptionFontSize = legacyDescriptionFontSize;
      }
      return config;
    })
    .catch(function () {
      return config;
    });
}

function fetchJsonNoStore(path) {
  return fetch(path + '?ts=' + Date.now(), { cache: 'no-store' })
    .then(function (response) {
      if (!response.ok) return null;
      return response.json();
    })
    .catch(function () {
      return null;
    });
}

function applySiteTheme(config) {
  if (!document || !document.documentElement) return;
  var theme = (config && config.site && config.site.theme) || {};
  var primary = normalizeHexColor(theme.primaryColor) || '#0F5EA6';
  var background = normalizeHexColor(theme.backgroundColor) || '#F6F2E9';
  var navFontSize = normalizeThemeFontSize(theme.navFontSize);
  var buttonFontSize = normalizeThemeFontSize(theme.buttonFontSize);
  var heroSubtitleFontSize = normalizeThemeFontSize(theme.heroSubtitleFontSize);
  var heroTitleFontSize = normalizeThemeFontSize(theme.heroTitleFontSize);
  var heroDescriptionFontSize = normalizeThemeFontSize(theme.heroDescriptionFontSize);
  var pageTitleFontSize = normalizeThemeFontSize(theme.pageTitleFontSize);
  var pageSubtitleFontSize = normalizeThemeFontSize(theme.pageSubtitleFontSize);
  var pageDescriptionFontSize = normalizeThemeFontSize(theme.pageDescriptionFontSize);
  var sectionTitleFontSize = normalizeThemeFontSize(theme.sectionTitleFontSize);
  var sectionDescriptionFontSize = normalizeThemeFontSize(theme.sectionDescriptionFontSize);
  var primaryDark = adjustHexLightness(primary, -28);
  var primaryLight = adjustHexLightness(primary, 16);
  var menuSoft = hexToRgba(primary, 0.3);

  var rootStyle = document.documentElement.style;
  rootStyle.setProperty('--brand', primary);
  rootStyle.setProperty('--brand-dark', primaryDark);
  rootStyle.setProperty('--brand-2', primaryLight);
  rootStyle.setProperty('--brand-soft', menuSoft);
  rootStyle.setProperty('--bg', background);
  rootStyle.setProperty('--font-nav-size', String(navFontSize !== null ? navFontSize : 15.36) + 'px');
  rootStyle.setProperty('--font-button-size', String(buttonFontSize !== null ? buttonFontSize : 14.4) + 'px');
  rootStyle.setProperty('--font-hero-subtitle-size', String(heroSubtitleFontSize !== null ? heroSubtitleFontSize : 12.48) + 'px');
  rootStyle.setProperty('--font-hero-title-size', String(heroTitleFontSize !== null ? heroTitleFontSize : 56) + 'px');
  rootStyle.setProperty('--font-hero-description-size', String(heroDescriptionFontSize !== null ? heroDescriptionFontSize : 16) + 'px');
  rootStyle.setProperty('--font-page-title-size', String(pageTitleFontSize !== null ? pageTitleFontSize : 36.8) + 'px');
  rootStyle.setProperty('--font-page-subtitle-size', String(pageSubtitleFontSize !== null ? pageSubtitleFontSize : 13.12) + 'px');
  rootStyle.setProperty('--font-page-description-size', String(pageDescriptionFontSize !== null ? pageDescriptionFontSize : 16) + 'px');
  rootStyle.setProperty('--font-section-title-size', String(sectionTitleFontSize !== null ? sectionTitleFontSize : 17.28) + 'px');
  rootStyle.setProperty('--font-section-description-size', String(sectionDescriptionFontSize !== null ? sectionDescriptionFontSize : 16) + 'px');
}

function normalizeHexColor(value) {
  if (typeof value !== 'string') return null;
  var raw = value.trim();
  if (!raw) return null;
  if (raw.charAt(0) !== '#') raw = '#' + raw;
  if (!/^#[0-9a-fA-F]{6}$/.test(raw)) return null;
  return raw.toUpperCase();
}

function normalizeThemeFontSize(value) {
  if (value === null || value === undefined || value === '') return null;
  var parsed = Number(value);
  if (!Number.isFinite(parsed)) return null;
  if (parsed < 10 || parsed > 96) return null;
  return Math.round(parsed * 100) / 100;
}

function adjustHexLightness(hex, percent) {
  var rgb = hexToRgb(hex);
  if (!rgb) return hex;
  var p = Math.max(-100, Math.min(100, Number(percent) || 0)) / 100;
  var mix = p < 0 ? 0 : 255;
  var amount = Math.abs(p);

  var r = Math.round(rgb.r + (mix - rgb.r) * amount);
  var g = Math.round(rgb.g + (mix - rgb.g) * amount);
  var b = Math.round(rgb.b + (mix - rgb.b) * amount);
  return rgbToHex(r, g, b);
}

function hexToRgba(hex, alpha) {
  var rgb = hexToRgb(hex);
  if (!rgb) return 'rgba(15, 94, 166, 0.3)';
  var safeAlpha = Math.max(0, Math.min(1, Number(alpha)));
  return 'rgba(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ', ' + safeAlpha + ')';
}

function hexToRgb(hex) {
  var normalized = normalizeHexColor(hex);
  if (!normalized) return null;
  return {
    r: parseInt(normalized.slice(1, 3), 16),
    g: parseInt(normalized.slice(3, 5), 16),
    b: parseInt(normalized.slice(5, 7), 16)
  };
}

function rgbToHex(r, g, b) {
  function toHex(channel) {
    var safe = Math.max(0, Math.min(255, channel | 0));
    var out = safe.toString(16).toUpperCase();
    return out.length === 1 ? '0' + out : out;
  }
  return '#' + toHex(r) + toHex(g) + toHex(b);
}

function setupSharedComponents(config) {
  renderNavbarComponent(config);
  renderFooterComponent(config);
}

function buildMenuTree(menuItems) {
  var visibleItems = (Array.isArray(menuItems) ? menuItems : []).filter(function (item) {
    return item && item.visible !== false;
  });

  var byParent = {};
  visibleItems.forEach(function (item) {
    var parentId = item.parentId || '__root__';
    if (!byParent[parentId]) byParent[parentId] = [];
    byParent[parentId].push(item);
  });

  Object.keys(byParent).forEach(function (key) {
    byParent[key].sort(function (a, b) {
      var pa = Number(a.position || 0);
      var pb = Number(b.position || 0);
      return pa - pb;
    });
  });

  return byParent;
}

function resolveHref(item) {
  if (item && item.href) return item.href;
  if (!item || !item.page) return '#';
  return item.page === 'home' ? 'index.html' : item.page + '.html';
}

function collectActivePages(item, byParent) {
  var pages = [];
  if (item && item.page) pages.push(item.page);
  var children = byParent[item.id] || [];
  children.forEach(function (child) {
    pages = pages.concat(collectActivePages(child, byParent));
  });
  return pages;
}

function renderNavbarComponent(config) {
  var navRoot = document.querySelector('[data-component="navbar"]');
  if (!navRoot) return;

  var activePage = (document.body && document.body.getAttribute('data-page')) || '';
  var byParent = buildMenuTree(config.menu || []);
  var topItems = byParent.__root__ || [];

  var html = '<ul class="nav-list nav-bar">';
  topItems.forEach(function (item) {
    var children = byParent[item.id] || [];
    var hasDropdown = children.length > 0;
    var activePages = collectActivePages(item, byParent);
    var isActive = activePages.indexOf(activePage) >= 0;

    var classNames = [];
    if (hasDropdown) classNames.push('has-dropdown');
    if (isActive) classNames.push('active');

    html += '<li' + (classNames.length ? ' class="' + classNames.join(' ') + '"' : '') + '>';
    if (hasDropdown) {
      html += '<button type="button" class="dropdown-toggle nav-parent-toggle" aria-expanded="false">' + escapeHtml(item.label || 'Item') + '</button>';
    } else {
      html += '<a href="' + escapeHtml(resolveHref(item)) + '">' + escapeHtml(item.label || 'Item') + '</a>';
    }

    if (hasDropdown) {
      html += '<ul class="nav-dropdown">';
      children.forEach(function (child) {
        html += '<li><a href="' + escapeHtml(resolveHref(child)) + '">' + escapeHtml(child.label || 'Subitem') + '</a></li>';
      });
      html += '</ul>';
    }
    html += '</li>';
  });
  html += '</ul>';

  navRoot.innerHTML = html;
}

function renderFooterComponent(config) {
  var footerRoot = document.querySelector('[data-component="footer"]');
  if (!footerRoot) return;

  var footerText = (config.site && config.site.footerText) || 'Historia da Educacao, Cultura e Literatura';
  footerRoot.textContent = footerText;

  var sep = document.createElement('span');
  sep.textContent = ' \u00b7 ';
  var adminLink = document.createElement('a');
  adminLink.href = 'admin/index.php';
  adminLink.className = 'admin-subtle-link';
  adminLink.textContent = 'Painel administrativo';
  footerRoot.appendChild(sep);
  footerRoot.appendChild(adminLink);
}

function renderHomeFeatureCardsComponent() {
  renderHomeFeatureCardsComponentFromItems([]);
}

function renderHomeFeatureCardsComponentFromItems(cards) {
  var cardsRoot = document.querySelector('[data-component="home-feature-cards"]');
  if (!cardsRoot) return;

  var normalizedCards = Array.isArray(cards) && cards.length ? cards : [
    {
      title: 'Produção Acadêmica',
      description: 'Projetos e pesquisas que articulam historia da educacao e formacao docente.'
    },
    {
      title: 'Catalogo de Fontes',
      description: 'Mapeamento de acervos e documentos para estudos historicos e educacionais.'
    },
    {
      title: 'Livros e Fragmentos',
      description: 'Repertorio de materiais historicos do periodo de 1840 a 1963.'
    }
  ];

  cardsRoot.innerHTML = normalizedCards.map(function (card) {
    return '<article class="feature-card reveal-on-scroll"><h3>' + escapeHtml(card.title) + '</h3><p>' + escapeHtml(card.description) + '</p></article>';
  }).join('');
}

function applyPageContent(config) {
  var pageKey = (document.body && document.body.getAttribute('data-page')) || '';
  if (!pageKey) return;

  var pageConfig = config.pages && config.pages[pageKey];
  if (!pageConfig) {
    renderPageDocuments(config, pageKey);
    return;
  }

  if (typeof pageConfig.browserTitle === 'string' && pageConfig.browserTitle.trim() !== '') {
    document.title = pageConfig.browserTitle.trim();
  }

  var pageComponents = getPageComponentsForPage(config, pageKey);

  if (pageKey === 'home') {
    var heroComponent = findFirstComponent(pageComponents, 'home-hero');
    var heroSettings = heroComponent && heroComponent.settings ? heroComponent.settings : {};
    var cardsComponent = findFirstComponent(pageComponents, 'cards');
    var detailsComponents = filterComponentsByType(pageComponents, 'details');

    var eyebrow = document.querySelector('.hero-eyebrow');
    var heroTitle = document.querySelector('.hero-title');
    var heroDescription = document.querySelector('.hero-description');
    var heroImage = document.querySelector('.hero-media img');
    var heroPrimary = document.querySelector('.hero-actions .btn-primary');
    var heroSecondary = document.querySelector('.hero-actions .btn-secondary');
    var homeImage = (heroSettings && heroSettings.image) || (config.site && config.site.homeImage);

    if (eyebrow && (heroSettings.subtitle || pageConfig.subtitle)) eyebrow.textContent = (heroSettings.subtitle || pageConfig.subtitle);
    if (heroTitle && (heroSettings.title || pageConfig.title)) heroTitle.textContent = (heroSettings.title || pageConfig.title);
    if (heroDescription && (heroSettings.description || pageConfig.description)) heroDescription.textContent = (heroSettings.description || pageConfig.description);
    if (heroImage && homeImage) heroImage.src = homeImage;
    if (heroPrimary && heroSettings.primaryLabel) {
      heroPrimary.textContent = heroSettings.primaryLabel;
      if (heroSettings.primaryLink) heroPrimary.href = heroSettings.primaryLink;
    }
    if (heroSecondary && heroSettings.secondaryLabel) {
      heroSecondary.textContent = heroSettings.secondaryLabel;
      if (heroSettings.secondaryLink) heroSecondary.href = heroSettings.secondaryLink;
    }

    if (cardsComponent && Array.isArray(cardsComponent.items)) {
      renderHomeFeatureCardsComponentFromItems(cardsComponent.items.map(function (item) {
        return {
          title: item.title || 'Card',
          description: item.description || ''
        };
      }));
    } else {
      renderHomeFeatureCardsComponent();
    }

    if (detailsComponents.length) {
      renderDetailsComponents(document.querySelector('.conteudo'), detailsComponents, true);
    }
  } else {
    var titleSubtitleComponent = findFirstComponent(pageComponents, 'title-subtitle');
    var titleSubtitleSettings = titleSubtitleComponent && titleSubtitleComponent.settings ? titleSubtitleComponent.settings : {};
    var resolvedTitle = titleSubtitleSettings.title || pageConfig.title || '';
    var resolvedSubtitle = titleSubtitleSettings.subtitle || '';

    var titleEl = document.querySelector('.page-title');
    if (titleEl && resolvedTitle) titleEl.textContent = resolvedTitle;

    var breadcrumb = document.querySelector('.breadcrumb');
    if (breadcrumb && resolvedTitle) {
      breadcrumb.innerHTML = '<a href="index.html">Home</a> / ' + escapeHtml(resolvedTitle);
    }

    var pageTop = document.querySelector('.page-top');
    if (pageTop) {
      upsertTextElement(pageTop, '.page-subtitle', 'page-subtitle', resolvedSubtitle);
      upsertTextElement(pageTop, '.page-description', 'page-description', '');
    }
  }

  renderDynamicComponents(config, pageKey, pageComponents);
  renderPageDocuments(config, pageKey);
}

function getPageComponentsForPage(config, pageKey) {
  if (!config || !config.pageComponents || !config.pageComponents[pageKey]) return [];
  return Array.isArray(config.pageComponents[pageKey]) ? config.pageComponents[pageKey] : [];
}

function findFirstComponent(components, type) {
  if (!Array.isArray(components)) return null;
  for (var i = 0; i < components.length; i += 1) {
    if (components[i] && components[i].type === type) {
      return components[i];
    }
  }
  return null;
}

function filterComponentsByType(components, type) {
  if (!Array.isArray(components)) return [];
  return components.filter(function (component) {
    return component && component.type === type;
  });
}

function renderDynamicComponents(config, pageKey, pageComponents) {
  if (!Array.isArray(pageComponents) || !pageComponents.length) return;
  var contentRoot = document.querySelector('.conteudo');
  if (!contentRoot) return;

  var reservedTypes = pageKey === 'home' ? ['home-hero', 'cards', 'details'] : ['title-subtitle'];
  var dynamicComponents = pageComponents.filter(function (component) {
    return component && reservedTypes.indexOf(component.type) < 0;
  });
  if (pageKey === 'fotos' && document.querySelector('[data-auto-gallery-grid]')) {
    dynamicComponents = dynamicComponents.filter(function (component) {
      return component.type !== 'gallery';
    });
  }
  if (!dynamicComponents.length) return;

  var dynamicRoot = contentRoot.querySelector('[data-dynamic-root]');
  if (!dynamicRoot) {
    dynamicRoot = document.createElement('div');
    dynamicRoot.setAttribute('data-dynamic-root', '1');
    contentRoot.appendChild(dynamicRoot);
  }
  dynamicRoot.innerHTML = '';

  dynamicComponents.forEach(function (component, index) {
    var type = component.type || '';
    if (type === 'details') {
      dynamicRoot.appendChild(buildDetailsNode(component, index));
      return;
    }
    if (type === 'cards') {
      dynamicRoot.appendChild(buildCardsNode(component, index));
      return;
    }
    if (type === 'links') {
      dynamicRoot.appendChild(buildLinksNode(component, index));
      return;
    }
    if (type === 'documents') {
      dynamicRoot.appendChild(buildDocumentsNode(component, index));
      return;
    }
    if (type === 'photo-carousel') {
      dynamicRoot.appendChild(buildPhotoCarouselNode(component, index));
      return;
    }
    if (type === 'photo-slider') {
      dynamicRoot.appendChild(buildPhotoSliderNode(component, index));
      return;
    }
    if (type === 'gallery' || type === 'gallery-folder') {
      dynamicRoot.appendChild(buildGalleryNode(component, index));
    }
  });
}

function renderDetailsComponents(contentRoot, components, replaceContent) {
  if (!contentRoot || !Array.isArray(components) || !components.length) return;
  if (replaceContent) {
    contentRoot.innerHTML = '';
  }
  components.forEach(function (component, index) {
    contentRoot.appendChild(buildDetailsNode(component, index));
  });
}

function buildSectionTitle(title, tagName) {
  if (!title || !String(title).trim()) return null;
  var el = document.createElement(tagName || 'h3');
  el.textContent = String(title).trim();
  return el;
}

function buildDetailsNode(component, index) {
  var node = document.createElement('section');
  node.className = 'dynamic-section dynamic-details reveal-on-scroll';
  node.setAttribute('data-dynamic-index', String(index));
  var title = buildSectionTitle(component.title, 'h3');
  if (title) node.appendChild(title);
  var text = (component.settings && component.settings.text) || '';
  if (text) {
    var p = document.createElement('p');
    p.textContent = text;
    node.appendChild(p);
  }
  return node;
}

function buildCardsNode(component, index) {
  var node = document.createElement('section');
  node.className = 'dynamic-section reveal-on-scroll';
  node.setAttribute('data-dynamic-index', String(index));
  var title = buildSectionTitle(component.title, 'h3');
  if (title) node.appendChild(title);
  var grid = document.createElement('div');
  grid.className = 'feature-grid';
  (component.items || []).forEach(function (item) {
    var card = document.createElement('article');
    card.className = 'feature-card';
    card.innerHTML = '<h3>' + escapeHtml(item.title || 'Card') + '</h3><p>' + escapeHtml(item.description || '') + '</p>';
    grid.appendChild(card);
  });
  node.appendChild(grid);
  return node;
}

function buildLinksNode(component, index) {
  var node = document.createElement('section');
  node.className = 'dynamic-section reveal-on-scroll';
  node.setAttribute('data-dynamic-index', String(index));
  var title = buildSectionTitle(component.title, 'h3');
  if (title) node.appendChild(title);
  var list = document.createElement('ul');
  list.className = 'lista-links';
  (component.items || []).forEach(function (item) {
    if (!item.url) return;
    var li = document.createElement('li');
    li.innerHTML = '<span class="link-titulo">' + escapeHtml(item.title || item.url) + '</span><div class="link-url"><a href="' + escapeHtml(item.url) + '" target="_blank" rel="noopener">' + escapeHtml(item.url) + '</a></div>';
    list.appendChild(li);
  });
  node.appendChild(list);
  return node;
}

function buildDocumentsNode(component, index) {
  var node = document.createElement('section');
  node.className = 'dynamic-section page-documents reveal-on-scroll';
  node.setAttribute('data-dynamic-index', String(index));
  var title = buildSectionTitle(component.title || 'Documentos', 'h3');
  if (title) node.appendChild(title);
  var list = document.createElement('ul');
  list.className = 'page-documents-list';
  (component.items || []).forEach(function (item) {
    if (!item.file) return;
    var li = document.createElement('li');
    var href = item.file;
    var label = item.title || item.file.split('/').pop();
    var desc = item.description ? '<span class="dynamic-doc-desc">' + escapeHtml(item.description) + '</span>' : '';
    li.innerHTML = '<a href="' + escapeHtml(href) + '" target="_blank" rel="noopener">' + escapeHtml(label) + '</a>' + desc;
    list.appendChild(li);
  });
  node.appendChild(list);
  return node;
}

function buildPhotoCarouselNode(component, index) {
  var node = document.createElement('section');
  node.className = 'dynamic-section reveal-on-scroll';
  node.setAttribute('data-dynamic-index', String(index));
  var title = buildSectionTitle(component.title, 'h3');
  if (title) node.appendChild(title);

  var items = component.items || [];
  if (!items.length) return node;
  var slider = document.createElement('div');
  slider.className = 'galeria-slider';
  slider.innerHTML = '<button type="button" class="galeria-slider-nav" data-dyn-prev>&larr;</button><figure class="galeria-slider-figure"><img src="" alt="" data-dyn-image><figcaption><strong data-dyn-title></strong><span data-dyn-caption></span></figcaption></figure><button type="button" class="galeria-slider-nav" data-dyn-next>&rarr;</button>';
  setupManualSlider(slider, items);
  node.appendChild(slider);
  return node;
}

function buildPhotoSliderNode(component, index) {
  var node = buildPhotoCarouselNode(component, index);
  var slider = node.querySelector('.galeria-slider');
  if (slider) {
    slider.classList.add('dynamic-autoplay-slider');
    setupAutoPlaySlider(slider, component.items || []);
  }
  return node;
}

function buildGalleryNode(component, index) {
  if (component && component.type === 'gallery-folder') {
    return buildGalleryFolderNode(component, index);
  }
  var node = document.createElement('section');
  node.className = 'dynamic-section reveal-on-scroll';
  node.setAttribute('data-dynamic-index', String(index));
  var title = buildSectionTitle(component.title, 'h3');
  if (title) node.appendChild(title);

  var items = component.items || [];
  if (!items.length) return node;
  var view = component.settings && component.settings.view === 'slider' ? 'slider' : 'grid';

  if (view === 'slider') {
    var slider = document.createElement('div');
    slider.className = 'galeria-slider';
    slider.innerHTML = '<button type="button" class="galeria-slider-nav" data-dyn-prev>&larr;</button><figure class="galeria-slider-figure"><img src="" alt="" data-dyn-image><figcaption><strong data-dyn-title></strong><span data-dyn-caption></span></figcaption></figure><button type="button" class="galeria-slider-nav" data-dyn-next>&rarr;</button>';
    setupManualSlider(slider, items);
    node.appendChild(slider);
    return node;
  }

  var grid = document.createElement('div');
  grid.className = 'galeria';
  items.forEach(function (item, itemIdx) {
    if (!item.file) return;
    var figure = document.createElement('figure');
    figure.innerHTML = '<img src="' + escapeHtml(item.file) + '" alt="' + escapeHtml(item.title || ('Foto ' + (itemIdx + 1))) + '"><figcaption><strong class="gallery-card-title">' + escapeHtml(item.title || ('Foto ' + (itemIdx + 1))) + '</strong><span class="gallery-card-caption">' + escapeHtml(item.subtitle || '') + '</span></figcaption>';
    grid.appendChild(figure);
  });
  node.appendChild(grid);
  return node;
}

function buildGalleryFolderNode(component, index) {
  var node = document.createElement('section');
  node.className = 'dynamic-section dynamic-folder-gallery reveal-on-scroll';
  node.setAttribute('data-dynamic-index', String(index));

  var settings = component && component.settings ? component.settings : {};
  var folderName = (settings.folderName || '').trim();
  var folderSlug = (settings.folder || '').trim();
  var folderLabel = folderName || folderSlug || 'Pasta de imagens';
  var items = Array.isArray(component.items) ? component.items : [];

  var trigger = document.createElement('button');
  trigger.type = 'button';
  trigger.className = 'folder-gallery-trigger';
  trigger.setAttribute('aria-label', 'Abrir galeria da pasta ' + folderLabel);
  trigger.innerHTML =
    '<span class="folder-gallery-icon" aria-hidden="true"></span>' +
    '<span class="folder-gallery-text"><strong>' + escapeHtml(folderLabel) + '</strong><small>' + (items.length ? (items.length + ' foto(s)') : 'Sem fotos ainda') + '</small></span>' +
    '<span class="folder-gallery-arrow" aria-hidden="true">&rarr;</span>';
  trigger.addEventListener('click', function () {
    openFolderGalleryModal(folderLabel, items);
  });

  node.appendChild(trigger);
  return node;
}

function openFolderGalleryModal(folderLabel, items) {
  var existing = document.querySelector('[data-folder-gallery-modal]');
  if (existing) existing.remove();

  var modal = document.createElement('div');
  modal.className = 'folder-gallery-modal';
  modal.setAttribute('data-folder-gallery-modal', '1');

  var dialog = document.createElement('div');
  dialog.className = 'folder-gallery-modal-dialog';
  dialog.setAttribute('role', 'dialog');
  dialog.setAttribute('aria-modal', 'true');
  dialog.setAttribute('aria-label', 'Galeria da pasta ' + folderLabel);

  var head = document.createElement('div');
  head.className = 'folder-gallery-modal-head';

  var title = document.createElement('strong');
  title.textContent = folderLabel;
  var close = document.createElement('button');
  close.type = 'button';
  close.className = 'folder-gallery-modal-close';
  close.setAttribute('aria-label', 'Fechar galeria da pasta');
  close.textContent = 'x';
  close.addEventListener('click', function () {
    modal.remove();
    document.body.classList.remove('folder-gallery-open');
  });
  head.appendChild(title);
  head.appendChild(close);

  var body = document.createElement('div');
  body.className = 'folder-gallery-modal-body';
  if (Array.isArray(items) && items.length) {
    var grid = document.createElement('div');
    grid.className = 'galeria';
    items.forEach(function (item, itemIdx) {
      if (!item || !item.file) return;
      var figure = document.createElement('figure');
      figure.innerHTML = '<img src="' + escapeHtml(item.file) + '" alt="' + escapeHtml(item.title || ('Foto ' + (itemIdx + 1))) + '"><figcaption><strong class="gallery-card-title">' + escapeHtml(item.title || ('Foto ' + (itemIdx + 1))) + '</strong><span class="gallery-card-caption">' + escapeHtml(item.subtitle || '') + '</span></figcaption>';
      grid.appendChild(figure);
    });
    body.appendChild(grid);
  } else {
    var empty = document.createElement('p');
    empty.className = 'link-desc';
    empty.textContent = 'Essa pasta ainda nao possui fotos.';
    body.appendChild(empty);
  }

  dialog.appendChild(head);
  dialog.appendChild(body);
  modal.appendChild(dialog);
  modal.addEventListener('click', function (event) {
    if (event.target === modal) {
      modal.remove();
      document.body.classList.remove('folder-gallery-open');
    }
  });

  document.body.appendChild(modal);
  document.body.classList.add('folder-gallery-open');
}

function setupManualSlider(sliderRoot, items) {
  var imageEl = sliderRoot.querySelector('[data-dyn-image]');
  var titleEl = sliderRoot.querySelector('[data-dyn-title]');
  var captionEl = sliderRoot.querySelector('[data-dyn-caption]');
  var prevBtn = sliderRoot.querySelector('[data-dyn-prev]');
  var nextBtn = sliderRoot.querySelector('[data-dyn-next]');
  if (!imageEl || !titleEl || !captionEl || !prevBtn || !nextBtn || !items.length) return;

  var idx = 0;
  var normalized = items.filter(function (item) { return item && item.file; });
  if (!normalized.length) return;

  function render() {
    var item = normalized[idx];
    imageEl.src = item.file;
    imageEl.alt = item.title || 'Foto';
    titleEl.textContent = item.title || 'Foto';
    captionEl.textContent = item.subtitle || '';
    captionEl.style.display = item.subtitle ? '' : 'none';
  }
  prevBtn.addEventListener('click', function () {
    idx = (idx - 1 + normalized.length) % normalized.length;
    render();
  });
  nextBtn.addEventListener('click', function () {
    idx = (idx + 1) % normalized.length;
    render();
  });
  render();
}

function setupAutoPlaySlider(sliderRoot, items) {
  var nextBtn = sliderRoot.querySelector('[data-dyn-next]');
  if (!nextBtn || !items || items.length < 2) return;
  setInterval(function () {
    nextBtn.click();
  }, 4200);
}

function upsertTextElement(parent, selector, className, text) {
  var element = parent.querySelector(selector);
  if (!text || text.trim() === '') {
    if (element) element.remove();
    return;
  }
  if (!element) {
    element = document.createElement('p');
    element.className = className;
    parent.appendChild(element);
  }
  element.textContent = text.trim();
}

function renderPageDocuments(config, pageKey) {
  var docs = (config.documents && config.documents[pageKey]) || [];
  var contentRoot = document.querySelector('.conteudo');
  if (!contentRoot) return;

  var docsRoot = contentRoot.querySelector('[data-page-documents]');
  if (!docs.length) {
    if (docsRoot) docsRoot.remove();
    return;
  }

  if (!docsRoot) {
    docsRoot = document.createElement('section');
    docsRoot.className = 'page-documents';
    docsRoot.setAttribute('data-page-documents', 'true');
    contentRoot.appendChild(docsRoot);
  }

  var html = '<ul class="page-documents-list">';
  docs.forEach(function (doc) {
    if (!doc || !doc.file) return;
    var title = doc.title || doc.file;
    html += '<li><a href="uploads/docs/' + encodeURIComponent(doc.file) + '" target="_blank" rel="noopener">' + escapeHtml(title) + '</a></li>';
  });
  html += '</ul>';
  docsRoot.innerHTML = html;
}

function setupNavBrand(config) {
  var navWrap = document.querySelector('.nav-wrap');
  if (!navWrap) return;

  // Remove legacy brand node when it was injected inside nav.
  var oldInnerBrand = navWrap.querySelector('.nav-brand');
  if (oldInnerBrand) oldInnerBrand.remove();

  var headerTitle = document.querySelector('.site-header h1');
  var text = (config.site && config.site.brand) || (headerTitle ? headerTitle.textContent : 'gephecl');

  var shell = navWrap.closest('.nav-shell');
  if (!shell) {
    shell = document.createElement('div');
    shell.className = 'nav-shell';
    navWrap.parentNode.insertBefore(shell, navWrap);
    shell.appendChild(navWrap);
  }

  var brand = shell.querySelector('.nav-brand');
  if (!brand) {
    brand = document.createElement('a');
    brand.className = 'nav-brand';
    brand.href = 'index.html';
    brand.setAttribute('aria-label', 'Ir para a página inicial');
    shell.insertBefore(brand, navWrap);
  }

  brand.textContent = (text || 'gephecl').trim();
}

function setupMobileNavToggle() {
  var navWrap = document.querySelector('.nav-wrap');
  var navList = document.querySelector('.nav-list');
  if (!navWrap || !navList) return;
  resetTransientUiState();

  if (navWrap.querySelector('.nav-toggle')) return;

  var button = document.createElement('button');
  button.className = 'nav-toggle';
  button.type = 'button';
  button.setAttribute('aria-expanded', 'false');
  button.setAttribute('aria-label', 'Abrir menu de navegacao');
  button.innerHTML = '<span class="nav-toggle-icon" aria-hidden="true"></span><span class="nav-toggle-text">Menu</span>';

  navWrap.insertBefore(button, navList);
  var sidebarClose = document.createElement('button');
  sidebarClose.className = 'nav-sidebar-close';
  sidebarClose.type = 'button';
  sidebarClose.setAttribute('aria-label', 'Fechar menu de navegacao');
  sidebarClose.innerHTML = '<span class="nav-sidebar-close-icon" aria-hidden="true"></span><span class="nav-sidebar-close-text">Voltar</span>';
  navList.insertBefore(sidebarClose, navList.firstChild);

  function isCollapsedMode() {
    return navWrap.classList.contains('nav-collapsed');
  }

  function closeNav() {
    navWrap.classList.remove('nav-open');
    document.body.classList.remove('nav-sidebar-open');
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-label', 'Abrir menu de navegacao');
    var activeElement = document.activeElement;
    if (activeElement && navWrap.contains(activeElement) && typeof activeElement.blur === 'function') {
      activeElement.blur();
    }
    navWrap.querySelectorAll('.nav-list > li.dropdown-open').forEach(function (li) {
      li.classList.remove('dropdown-open');
    });
    navWrap.querySelectorAll('.nav-list > li > .dropdown-toggle').forEach(function (toggle) {
      toggle.setAttribute('aria-expanded', 'false');
    });
  }

  function navHasWrappedItems() {
    var items = Array.prototype.filter.call(navList.children, function (child) {
      return child && child.tagName === 'LI';
    });
    if (items.length <= 1) return false;
    var firstTop = items[0].offsetTop;
    var lastTop = items[items.length - 1].offsetTop;
    if (Math.abs(lastTop - firstTop) > 1) return true;

    // Fallback: detects horizontal overflow when wrapping is prevented.
    return navList.scrollWidth > (navWrap.clientWidth - 12);
  }

  function updateAdaptiveMode() {
    var wasOpen = navWrap.classList.contains('nav-open');
    var navShell = navWrap.closest('.nav-shell');
    navWrap.classList.remove('nav-collapsed');
    if (navShell) navShell.classList.remove('nav-shell-collapsed');
    closeNav();

    if (navHasWrappedItems()) {
      navWrap.classList.add('nav-collapsed');
      if (navShell) navShell.classList.add('nav-shell-collapsed');
      if (wasOpen) {
        navWrap.classList.add('nav-open');
        document.body.classList.add('nav-sidebar-open');
        button.setAttribute('aria-expanded', 'true');
        button.setAttribute('aria-label', 'Fechar menu de navegacao');
      }
      return;
    }

    closeNav();
  }

  button.addEventListener('click', function () {
    if (!isCollapsedMode()) return;
    var expanded = navWrap.classList.toggle('nav-open');
    document.body.classList.toggle('nav-sidebar-open', expanded);
    button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    button.setAttribute('aria-label', expanded ? 'Fechar menu de navegacao' : 'Abrir menu de navegacao');
  });

  sidebarClose.addEventListener('click', function () {
    closeNav();
  });

  navWrap.querySelectorAll('.nav-list > li > a, .nav-list > li > .nav-parent-toggle').forEach(function (link) {
    link.addEventListener('click', function () {
      var parent = link.parentElement;
      var hasDropdown = parent && parent.classList.contains('has-dropdown');
      if (isCollapsedMode() && !hasDropdown) closeNav();
    });
  });

  document.addEventListener('click', function (e) {
    if (!isCollapsedMode()) return;
    if (e.target.closest('.nav-wrap')) return;
    closeNav();
  });

  var resizeTimer = null;
  function requestAdaptiveUpdate() {
    if (resizeTimer) {
      window.cancelAnimationFrame(resizeTimer);
    }
    resizeTimer = window.requestAnimationFrame(function () {
      updateAdaptiveMode();
    });
  }

  updateAdaptiveMode();
  window.addEventListener('resize', requestAdaptiveUpdate);

  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(function () {
      requestAdaptiveUpdate();
    });
  }
}

function setupMobileDropdown() {
  var navWrap = document.querySelector('.nav-wrap');
  var navItems = document.querySelectorAll('.nav-list > li.has-dropdown');
  function syncExpandedState() {
    navItems.forEach(function (item) {
      var toggle = item.querySelector(':scope > .dropdown-toggle');
      if (!toggle) return;
      toggle.setAttribute('aria-expanded', item.classList.contains('dropdown-open') ? 'true' : 'false');
    });
  }
  navItems.forEach(function (li) {
    var link = li.querySelector(':scope > .dropdown-toggle');
    if (!link) return;

    link.addEventListener('click', function (e) {
      e.preventDefault();
      navItems.forEach(function (item) {
        if (item !== li) item.classList.remove('dropdown-open');
      });
      li.classList.toggle('dropdown-open');
      syncExpandedState();
    });
  });

  document.addEventListener('click', function (e) {
    if (!navWrap || !navWrap.classList.contains('nav-collapsed')) return;
    if (e.target.closest('.nav-wrap')) return;

    navItems.forEach(function (li) {
      li.classList.remove('dropdown-open');
    });
    syncExpandedState();
    var toggle = navWrap ? navWrap.querySelector('.nav-toggle') : null;
    if (navWrap) {
      navWrap.classList.remove('nav-open');
      document.body.classList.remove('nav-sidebar-open');
    }
    if (toggle) {
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', 'Abrir menu de navegacao');
    }
  });
  syncExpandedState();
}

function setupDesktopDropdownHover() {
  var navWrap = document.querySelector('.nav-wrap');
  var navItems = document.querySelectorAll('.nav-list > li.has-dropdown');
  if (!navItems.length) return;

  navItems.forEach(function (li) {
    var closeTimer = null;

    li.addEventListener('mouseenter', function () {
      if (navWrap && navWrap.classList.contains('nav-collapsed')) return;
      if (closeTimer) clearTimeout(closeTimer);
      li.classList.add('dropdown-open');
    });

    li.addEventListener('mouseleave', function () {
      if (navWrap && navWrap.classList.contains('nav-collapsed')) return;
      closeTimer = setTimeout(function () {
        li.classList.remove('dropdown-open');
      }, 180);
    });
  });
}

function setupStickyNavBackground() {
  var nav = document.querySelector('.nav-wrap');
  if (!nav) return;
  var body = document.body;

  function updateNavState() {
    if (window.scrollY > 10) {
      nav.classList.add('is-scrolled');
      body.classList.add('has-scrolled-nav');
    } else {
      nav.classList.remove('is-scrolled');
      body.classList.remove('has-scrolled-nav');
    }
  }

  updateNavState();
  window.addEventListener('scroll', updateNavState, { passive: true });
}

function setupRevealOnScroll() {
  var items = document.querySelectorAll('.reveal-on-scroll');
  if (!items.length) return;

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  items.forEach(function (el) { observer.observe(el); });
}

function setupCounterAnimations() {
  var counters = document.querySelectorAll('[data-countup]');
  if (!counters.length) return;

  var played = new WeakSet();
  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting || played.has(entry.target)) return;
      animateCount(entry.target);
      played.add(entry.target);
    });
  }, { threshold: 0.5 });

  counters.forEach(function (counter) { observer.observe(counter); });
}

function animateCount(el) {
  var target = parseInt(el.getAttribute('data-countup') || '0', 10);
  var suffix = el.getAttribute('data-suffix') || '';
  var duration = 1200;
  var startTime = performance.now();

  function step(now) {
    var progress = Math.min((now - startTime) / duration, 1);
    var eased = 1 - Math.pow(1 - progress, 3);
    var value = Math.round(target * eased);
    el.textContent = String(value) + suffix;
    if (progress < 1) {
      requestAnimationFrame(step);
    }
  }
  requestAnimationFrame(step);
}

function setupTiltCards() {
  var cards = document.querySelectorAll('.tilt-card');
  if (!cards.length) return;

  cards.forEach(function (card) {
    card.addEventListener('mousemove', function (e) {
      var rect = card.getBoundingClientRect();
      var x = (e.clientX - rect.left) / rect.width;
      var y = (e.clientY - rect.top) / rect.height;
      var rotateY = (x - 0.5) * 8;
      var rotateX = (0.5 - y) * 8;
      card.style.transform = 'perspective(900px) rotateX(' + rotateX.toFixed(2) + 'deg) rotateY(' + rotateY.toFixed(2) + 'deg)';
    });
    card.addEventListener('mouseleave', function () {
      card.style.transform = 'perspective(900px) rotateX(0deg) rotateY(0deg)';
    });
  });
}

function setupGalleryFromManifest(config) {
  var autoGallery = document.querySelector('[data-auto-gallery-grid]') || document.querySelector('.galeria[data-auto-gallery]');
  if (!autoGallery) return;

  var status = document.querySelector('[data-galeria-status]');
  var defaultView = getDefaultGalleryView(config);
  setupGalleryViewSwitch(false, defaultView);
  var fallbackItems = getGalleryFallbackItemsFromConfig(config);
  var manifestPath = FALLBACK_CONFIG.galleryManifest;
  if (config && config.galleryManifest) {
    manifestPath = config.galleryManifest;
  }

  fetch(manifestPath + '?ts=' + Date.now(), { cache: 'no-store' })
    .then(function (response) {
      if (!response.ok) throw new Error('Manifesto não encontrado');
      return response.json();
    })
    .then(function (items) {
      var normalizedItems = normalizeGalleryItems(items);
      if (!normalizedItems.length && fallbackItems.length) {
        normalizedItems = fallbackItems;
      }
      if (!normalizedItems.length) {
        setupGalleryViewSwitch(false, defaultView);
        if (status) status.textContent = 'Nenhuma foto disponivel no momento.';
        return;
      }

      autoGallery.innerHTML = '';
      if (status) status.style.display = 'none';

      normalizedItems.forEach(function (item, idx) {
        var figure = document.createElement('figure');
        figure.className = 'reveal-on-scroll';
        var img = document.createElement('img');
        var figcaption = document.createElement('figcaption');
        var titleEl = document.createElement('strong');
        var captionEl = document.createElement('span');

        img.src = resolveGalleryImageSrc(item.file);
        img.alt = item.title || ('Foto ' + (idx + 1));

        titleEl.className = 'gallery-card-title';
        titleEl.textContent = item.title || ('Foto ' + (idx + 1));
        captionEl.className = 'gallery-card-caption';
        captionEl.textContent = item.caption || '';

        figcaption.appendChild(titleEl);
        if (item.caption) {
          figcaption.appendChild(captionEl);
        }

        figure.appendChild(img);
        figure.appendChild(figcaption);
        autoGallery.appendChild(figure);
      });

      setupGallerySlider(normalizedItems);
      setupGalleryViewSwitch(true, defaultView);
      setupRevealOnScroll();
    })
    .catch(function () {
      if (fallbackItems.length) {
        autoGallery.innerHTML = '';
        if (status) status.style.display = 'none';
        fallbackItems.forEach(function (item, idx) {
          var figure = document.createElement('figure');
          figure.className = 'reveal-on-scroll';
          figure.innerHTML = '<img src="' + escapeHtml(item.file) + '" alt="' + escapeHtml(item.title || ('Foto ' + (idx + 1))) + '"><figcaption><strong class="gallery-card-title">' + escapeHtml(item.title || ('Foto ' + (idx + 1))) + '</strong><span class="gallery-card-caption">' + escapeHtml(item.caption || '') + '</span></figcaption>';
          autoGallery.appendChild(figure);
        });
        setupGallerySlider(fallbackItems);
        setupGalleryViewSwitch(true, defaultView);
        setupRevealOnScroll();
      } else {
        setupGalleryViewSwitch(false, defaultView);
        if (status) status.textContent = 'Nao foi possivel carregar as fotos.';
      }
    });
}

function getGalleryFallbackItemsFromConfig(config) {
  if (!config || !config.pageComponents || !Array.isArray(config.pageComponents.fotos)) {
    return [];
  }
  var galleryComponent = config.pageComponents.fotos.find(function (component) {
    return component && component.type === 'gallery' && Array.isArray(component.items) && component.items.length > 0;
  });
  if (!galleryComponent) return [];
  return normalizeGalleryItems(galleryComponent.items.map(function (item, idx) {
    return {
      file: item.file || '',
      title: item.title || ('Foto ' + (idx + 1)),
      caption: item.subtitle || item.caption || ''
    };
  }));
}

function normalizeGalleryItems(items) {
  if (!Array.isArray(items)) return [];
  var normalized = [];

  items.forEach(function (item, idx) {
    if (typeof item === 'string' && item.trim() !== '') {
      normalized.push({
        file: item,
        title: 'Foto ' + (idx + 1),
        caption: ''
      });
      return;
    }
    if (!item || typeof item !== 'object' || !item.file) return;

    var title = '';
    if (typeof item.title === 'string' && item.title.trim() !== '') {
      title = item.title.trim();
    } else if (typeof item.caption === 'string' && item.caption.trim() !== '') {
      title = item.caption.trim();
    } else {
      title = 'Foto ' + (idx + 1);
    }

    normalized.push({
      file: item.file,
      title: title,
      caption: typeof item.caption === 'string' ? item.caption.trim() : ''
    });
  });

  return normalized;
}

function getDefaultGalleryView(config) {
  var defaultView = config && config.site ? config.site.galleryDefaultView : 'grid';
  return defaultView === 'slider' ? 'slider' : 'grid';
}

function setupGalleryViewSwitch(hasItems, defaultView) {
  var switchRoot = document.querySelector('[data-gallery-view-switch]');
  var grid = document.querySelector('[data-auto-gallery-grid]');
  var slider = document.querySelector('[data-auto-gallery-slider]');
  if (!switchRoot || !grid || !slider) return;

  if (!hasItems) {
    switchRoot.style.display = 'none';
    slider.hidden = true;
    return;
  }
  switchRoot.style.display = '';

  function applyView(view) {
    var normalizedView = view === 'slider' ? 'slider' : 'grid';
    var buttons = switchRoot.querySelectorAll('[data-gallery-view]');
    buttons.forEach(function (btn) {
      var isActive = btn.getAttribute('data-gallery-view') === normalizedView;
      btn.classList.toggle('is-active', isActive);
      btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
    if (normalizedView === 'slider') {
      slider.hidden = false;
      slider.style.display = 'grid';
      grid.hidden = true;
      grid.style.display = 'none';
    } else {
      slider.hidden = true;
      slider.style.display = 'none';
      grid.hidden = false;
      grid.style.display = '';
    }
  }

  if (switchRoot.dataset.bound !== '1') {
    var buttons = switchRoot.querySelectorAll('[data-gallery-view]');
    buttons.forEach(function (button) {
      function handleActivate(event) {
        event.preventDefault();
        applyView(button.getAttribute('data-gallery-view') || 'grid');
      }
      button.addEventListener('click', handleActivate);
      button.addEventListener('touchend', handleActivate, { passive: false });
    });
    switchRoot.dataset.bound = '1';
  }

  applyView(defaultView === 'slider' ? 'slider' : 'grid');
}

function setupGallerySlider(items) {
  var slider = document.querySelector('[data-auto-gallery-slider]');
  if (!slider || !items.length) return;

  var imageEl = slider.querySelector('[data-gallery-slider-image]');
  var titleEl = slider.querySelector('[data-gallery-slider-title]');
  var captionEl = slider.querySelector('[data-gallery-slider-caption]');
  var prevBtn = slider.querySelector('[data-gallery-prev]');
  var nextBtn = slider.querySelector('[data-gallery-next]');
  if (!imageEl || !titleEl || !captionEl || !prevBtn || !nextBtn) return;

  var index = 0;
  function renderCurrent() {
    var item = items[index];
    imageEl.src = resolveGalleryImageSrc(item.file);
    imageEl.alt = item.title || 'Foto da galeria';
    titleEl.textContent = item.title || 'Foto';
    captionEl.textContent = item.caption || '';
    captionEl.style.display = item.caption ? '' : 'none';
  }

  if (slider.dataset.bound !== '1') {
    prevBtn.addEventListener('click', function () {
      index = (index - 1 + items.length) % items.length;
      renderCurrent();
    });
    nextBtn.addEventListener('click', function () {
      index = (index + 1) % items.length;
      renderCurrent();
    });
    slider.dataset.bound = '1';
  }

  renderCurrent();
}

function setupGalleryLightbox() {
  if (!document.body || document.body.dataset.galleryLightboxBound === '1') return;

  var modal = document.createElement('div');
  modal.className = 'gallery-lightbox';
  modal.hidden = true;
  modal.innerHTML = '<div class="gallery-lightbox-dialog" role="dialog" aria-modal="true" aria-label="Visualizacao da imagem"><button type="button" class="gallery-lightbox-close" aria-label="Fechar imagem">×</button><img src="" alt="" class="gallery-lightbox-image"><p class="gallery-lightbox-caption"></p></div>';
  document.body.appendChild(modal);

  var imageEl = modal.querySelector('.gallery-lightbox-image');
  var captionEl = modal.querySelector('.gallery-lightbox-caption');
  var closeBtn = modal.querySelector('.gallery-lightbox-close');

  function closeLightbox() {
    modal.hidden = true;
    document.body.classList.remove('gallery-lightbox-open');
    imageEl.src = '';
  }

  function openLightbox(src, alt, caption) {
    imageEl.src = src;
    imageEl.alt = alt || 'Imagem da galeria';
    captionEl.textContent = caption || '';
    captionEl.style.display = caption ? '' : 'none';
    modal.hidden = false;
    document.body.classList.add('gallery-lightbox-open');
  }

  closeBtn.addEventListener('click', closeLightbox);
  modal.addEventListener('click', function (event) {
    if (event.target === modal) closeLightbox();
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.hidden) closeLightbox();
  });

  document.addEventListener('click', function (event) {
    var img = event.target.closest('.galeria figure img, .galeria-slider-figure img');
    if (!img) return;

    var source = img.currentSrc || img.src;
    if (!source) return;

    var figure = img.closest('figure');
    var title = figure ? figure.querySelector('.gallery-card-title, [data-gallery-slider-title], strong') : null;
    var subtitle = figure ? figure.querySelector('.gallery-card-caption, [data-gallery-slider-caption], span') : null;
    var caption = '';
    if (title && title.textContent) caption = title.textContent.trim();
    if (subtitle && subtitle.textContent) {
      caption = caption ? caption + ' - ' + subtitle.textContent.trim() : subtitle.textContent.trim();
    }

    event.preventDefault();
    openLightbox(source, img.alt || '', caption);
  });

  document.body.dataset.galleryLightboxBound = '1';
}

function resolveGalleryImageSrc(file) {
  var raw = String(file || '').trim();
  if (!raw) return '';
  if (/^https?:\/\//i.test(raw)) return raw;
  if (raw.indexOf('uploads/fotos/') === 0) return encodePathPreservingSlashes(raw);
  return 'uploads/fotos/' + encodeURIComponent(raw);
}

function encodePathPreservingSlashes(path) {
  return String(path)
    .split('/')
    .map(function (segment) {
      return encodeURIComponent(segment);
    })
    .join('/');
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
