/**
 * CB Legacy Luxury - Header Module
 *
 * Handles scroll behavior, mobile menu toggle, and transparent-to-solid transition.
 */
(function () {
  'use strict';

  var header = document.getElementById('cb-header');
  var menuToggle = document.getElementById('cb-menu-toggle');
  var mobileMenu = document.getElementById('cb-mobile-menu');

  if (!header) return;

  // Scroll behavior - add scrolled class
  var lastScroll = 0;

  function handleScroll() {
    var scrollY = window.pageYOffset || document.documentElement.scrollTop;

    if (scrollY > 50) {
      header.classList.add('cb-header--scrolled');
    } else {
      header.classList.remove('cb-header--scrolled');
    }

    lastScroll = scrollY;
  }

  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll(); // Run on load

  // Mobile menu toggle
  if (menuToggle && mobileMenu) {
    var isOpen = false;

    menuToggle.addEventListener('click', function () {
      isOpen = !isOpen;

      menuToggle.classList.toggle('cb-menu-toggle--active', isOpen);
      mobileMenu.classList.toggle('cb-mobile-menu--open', isOpen);
      document.body.style.overflow = isOpen ? 'hidden' : '';

      // Stagger mobile links animation
      if (isOpen) {
        var links = mobileMenu.querySelectorAll('.cb-mobile-menu__link');
        links.forEach(function (link, i) {
          link.style.transitionDelay = (i * 0.05) + 's';
        });
      }
    });

    // Close on link click
    mobileMenu.querySelectorAll('.cb-mobile-menu__link').forEach(function (link) {
      link.addEventListener('click', function () {
        isOpen = false;
        menuToggle.classList.remove('cb-menu-toggle--active');
        mobileMenu.classList.remove('cb-mobile-menu--open');
        document.body.style.overflow = '';
      });
    });

    // Close on escape
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && isOpen) {
        isOpen = false;
        menuToggle.classList.remove('cb-menu-toggle--active');
        mobileMenu.classList.remove('cb-mobile-menu--open');
        document.body.style.overflow = '';
      }
    });
  }
})();
