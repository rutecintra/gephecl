/* GEPHECL - UFAL CEDU - Scripts do site */

document.addEventListener('DOMContentLoaded', function () {

  // Dropdown no mobile: clique no item com dropdown abre/fecha submenu
  var navItems = document.querySelectorAll('.nav-list > li.has-dropdown');
  navItems.forEach(function (li) {
    var link = li.querySelector(':scope > a');
    if (!link) return;
    link.addEventListener('click', function (e) {
      if (window.innerWidth <= 768) {
        e.preventDefault();
        li.classList.toggle('dropdown-open');
      }
    });
  });

  // Slider do banner (home)
  var banner = document.querySelector('.banner-slider');
  if (banner) {
    var slides = banner.querySelectorAll('.banner-slide');
    var prevBtn = banner.querySelector('.banner-nav.prev');
    var nextBtn = banner.querySelector('.banner-nav.next');
    var current = 0;
    var total = slides.length;

    function showSlide(index) {
      if (total === 0) return;
      current = (index + total) % total;
      slides.forEach(function (s, i) {
        s.style.display = i === current ? 'block' : 'none';
      });
    }

    if (prevBtn) {
      prevBtn.addEventListener('click', function () { showSlide(current - 1); });
    }
    if (nextBtn) {
      nextBtn.addEventListener('click', function () { showSlide(current + 1); });
    }

    showSlide(0);
  }
});
