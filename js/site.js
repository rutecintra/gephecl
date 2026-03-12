/* GEPHECL - interacoes da UI */

document.addEventListener('DOMContentLoaded', function () {
  setupSharedComponents();
  setupNavBrand();
  setupMobileNavToggle();
  setupMobileDropdown();
  setupDesktopDropdownHover();
  setupStickyNavBackground();
  setupRevealOnScroll();
  setupCounterAnimations();
  setupTiltCards();
  setupGalleryFromManifest();
});

function setupSharedComponents() {
  renderNavbarComponent();
  renderFooterComponent();
  renderHomeFeatureCardsComponent();
}

function renderNavbarComponent() {
  var navRoot = document.querySelector('[data-component="navbar"]');
  if (!navRoot) return;

  var activePage = (document.body && document.body.getAttribute('data-page')) || '';

  var navItems = [
    { label: 'Início', href: 'index.html', active: ['home'] },
    {
      label: 'Membros',
      href: 'membros.html',
      active: ['membros', 'fotos'],
      dropdown: [
        { label: 'Membros', href: 'membros.html' },
        { label: 'Galeria de Fotos', href: 'fotos.html' }
      ]
    },
    {
      label: 'Produção Acadêmica',
      href: 'projetos-pesquisa.html',
      active: ['projetos', 'dissertacoes'],
      dropdown: [
        { label: 'Projetos de Pesquisa', href: 'projetos-pesquisa.html' },
        { label: 'Dissertações', href: 'dissertacoes.html' }
      ]
    },
    {
      label: 'Catálogo de Fontes',
      href: 'catalogo-fontes.html',
      active: ['catalogo'],
      dropdown: [
        { label: 'Catálogo de Fontes', href: 'catalogo-fontes.html' }
      ]
    },
    { label: 'Livros e Fragmentos (1840-1963)', href: 'livros-fragmentos.html', active: ['livros'] },
    { label: 'Contato', href: 'contato.html', active: ['contato'] },
    { label: 'Links', href: 'links.html', active: ['links'] }
  ];

  var html = '<ul class="nav-list nav-bar">';
  navItems.forEach(function (item) {
    var isActive = item.active && item.active.indexOf(activePage) >= 0;
    var hasDropdown = Array.isArray(item.dropdown) && item.dropdown.length > 0;
    html += '<li' + (hasDropdown ? ' class="' + (isActive ? 'has-dropdown active' : 'has-dropdown') + '"' : (isActive ? ' class="active"' : '')) + '>';
    html += '<a href="' + item.href + '"' + (hasDropdown ? ' class="dropdown-toggle"' : '') + '>' + item.label + '</a>';

    if (hasDropdown) {
      html += '<ul class="nav-dropdown">';
      item.dropdown.forEach(function (subitem) {
        html += '<li><a href="' + subitem.href + '">' + subitem.label + '</a></li>';
      });
      html += '</ul>';
    }

    html += '</li>';
  });
  html += '</ul>';

  navRoot.innerHTML = html;
}

function renderFooterComponent() {
  var footerRoot = document.querySelector('[data-component="footer"]');
  if (!footerRoot) return;
  footerRoot.textContent = 'História da Educação, Cultura e Literatura';
}

function renderHomeFeatureCardsComponent() {
  var cardsRoot = document.querySelector('[data-component="home-feature-cards"]');
  if (!cardsRoot) return;

  var cards = [
    {
      title: 'Produção Acadêmica',
      description: 'Projetos e pesquisas que articulam história da educação e formação docente.'
    },
    {
      title: 'Catálogo de Fontes',
      description: 'Mapeamento de acervos e documentos para estudos históricos e educacionais.'
    },
    {
      title: 'Livros e Fragmentos',
      description: 'Repertório de materiais históricos do período de 1840 a 1963.'
    }
  ];

  cardsRoot.innerHTML = cards.map(function (card) {
    return '<article class="feature-card reveal-on-scroll"><h3>' + card.title + '</h3><p>' + card.description + '</p></article>';
  }).join('');
}

function setupNavBrand() {
  var navWrap = document.querySelector('.nav-wrap');
  if (!navWrap) return;

  // Remove legacy brand node when it was injected inside nav.
  var oldInnerBrand = navWrap.querySelector('.nav-brand');
  if (oldInnerBrand) oldInnerBrand.remove();

  var headerTitle = document.querySelector('.site-header h1');
  var text = headerTitle ? headerTitle.textContent : 'gephecl';

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
  button.textContent = 'Menu';

  navWrap.insertBefore(button, navList);

  button.addEventListener('click', function () {
    var expanded = navWrap.classList.toggle('nav-open');
    button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
  });

  navWrap.querySelectorAll('.nav-list > li > a').forEach(function (link) {
    link.addEventListener('click', function () {
      if (window.innerWidth > 860) return;

      var parent = link.parentElement;
      var hasDropdown = parent && parent.classList.contains('has-dropdown');
      if (!hasDropdown) {
        navWrap.classList.remove('nav-open');
        button.setAttribute('aria-expanded', 'false');
      }
    });
  });

  window.addEventListener('resize', function () {
    if (window.innerWidth > 860) {
      navWrap.classList.remove('nav-open');
      button.setAttribute('aria-expanded', 'false');
      navWrap.querySelectorAll('.nav-list > li.dropdown-open').forEach(function (li) {
        li.classList.remove('dropdown-open');
      });
    }
  });
}

function setupMobileDropdown() {
  var navItems = document.querySelectorAll('.nav-list > li.has-dropdown');
  navItems.forEach(function (li) {
    var link = li.querySelector(':scope > a');
    if (!link) return;

    link.addEventListener('click', function (e) {
      if (window.innerWidth <= 860) {
        e.preventDefault();

        navItems.forEach(function (item) {
          if (item !== li) item.classList.remove('dropdown-open');
        });
        li.classList.toggle('dropdown-open');
      }
    });
  });

  document.addEventListener('click', function (e) {
    if (window.innerWidth > 860) return;
    if (e.target.closest('.nav-wrap')) return;

    navItems.forEach(function (li) {
      li.classList.remove('dropdown-open');
    });
    var navWrap = document.querySelector('.nav-wrap');
    var toggle = navWrap ? navWrap.querySelector('.nav-toggle') : null;
    if (navWrap) navWrap.classList.remove('nav-open');
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
  });
}

function setupDesktopDropdownHover() {
  var navItems = document.querySelectorAll('.nav-list > li.has-dropdown');
  if (!navItems.length) return;

  navItems.forEach(function (li) {
    var closeTimer = null;

    li.addEventListener('mouseenter', function () {
      if (window.innerWidth <= 860) return;
      if (closeTimer) clearTimeout(closeTimer);
      li.classList.add('dropdown-open');
    });

    li.addEventListener('mouseleave', function () {
      if (window.innerWidth <= 860) return;
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

function setupGalleryFromManifest() {
  var autoGallery = document.querySelector('.galeria[data-auto-gallery]');
  if (!autoGallery) return;

  var status = document.querySelector('[data-galeria-status]');
  fetch('uploads/fotos/manifest.json?ts=' + Date.now(), { cache: 'no-store' })
    .then(function (response) {
      if (!response.ok) throw new Error('Manifesto não encontrado');
      return response.json();
    })
    .then(function (items) {
      if (!Array.isArray(items) || items.length === 0) {
        if (status) status.textContent = 'Nenhuma foto publicada ainda.';
        return;
      }

      autoGallery.innerHTML = '';
      if (status) status.style.display = 'none';

      items.forEach(function (item, idx) {
        var file = typeof item === 'string' ? item : item.file;
        if (!file) return;

        var caption = (typeof item === 'object' && item.caption) ? item.caption : ('Foto ' + (idx + 1));
        var figure = document.createElement('figure');
        figure.className = 'reveal-on-scroll';
        var img = document.createElement('img');
        var figcaption = document.createElement('figcaption');

        img.src = 'uploads/fotos/' + encodeURIComponent(file);
        img.alt = caption;
        figcaption.textContent = caption;

        figure.appendChild(img);
        figure.appendChild(figcaption);
        autoGallery.appendChild(figure);
      });

      setupRevealOnScroll();
    })
    .catch(function () {
      if (status) status.textContent = 'Nenhuma foto publicada ainda.';
    });
}
