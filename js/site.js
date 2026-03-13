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
  loadSiteConfig().then(function (config) {
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
  });
});

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
      return config;
    })
    .catch(function () {
      return FALLBACK_CONFIG;
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
    html += '<a href="' + escapeHtml(resolveHref(item)) + '"' + (hasDropdown ? ' class="dropdown-toggle"' : '') + '>' + escapeHtml(item.label || 'Item') + '</a>';

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
    if (type === 'gallery') {
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

  navWrap.querySelectorAll('.nav-list > li > a').forEach(function (link) {
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
  navItems.forEach(function (li) {
    var link = li.querySelector(':scope > a');
    if (!link) return;

    link.addEventListener('click', function (e) {
      if (navWrap && navWrap.classList.contains('nav-collapsed')) {
        e.preventDefault();

        navItems.forEach(function (item) {
          if (item !== li) item.classList.remove('dropdown-open');
        });
        li.classList.toggle('dropdown-open');
      }
    });
  });

  document.addEventListener('click', function (e) {
    if (!navWrap || !navWrap.classList.contains('nav-collapsed')) return;
    if (e.target.closest('.nav-wrap')) return;

    navItems.forEach(function (li) {
      li.classList.remove('dropdown-open');
    });
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
      if (!normalizedItems.length) {
        if (status) status.remove();
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

        img.src = 'uploads/fotos/' + encodeURIComponent(item.file);
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
      setupGalleryViewSwitch(normalizedItems.length > 0, getDefaultGalleryView(config));
      setupRevealOnScroll();
    })
    .catch(function () {
      if (status) status.remove();
    });
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
    var buttons = switchRoot.querySelectorAll('[data-gallery-view]');
    buttons.forEach(function (btn) {
      btn.classList.toggle('is-active', btn.getAttribute('data-gallery-view') === view);
    });
    if (view === 'slider') {
      slider.hidden = false;
      grid.style.display = 'none';
    } else {
      slider.hidden = true;
      grid.style.display = '';
    }
  }

  if (switchRoot.dataset.bound !== '1') {
    switchRoot.querySelectorAll('[data-gallery-view]').forEach(function (button) {
      button.addEventListener('click', function () {
        var view = button.getAttribute('data-gallery-view') || 'grid';
        applyView(view);
      });
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
    imageEl.src = 'uploads/fotos/' + encodeURIComponent(item.file);
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

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
