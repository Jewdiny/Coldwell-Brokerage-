/**
 * CB Legacy Luxury — Listing Gallery Lightbox
 * Vanilla JS, no dependencies. Activates on any [data-cb-lightbox] container.
 */
(function () {
  'use strict';

  var gallery = document.querySelector('[data-cb-lightbox]');
  var box     = document.getElementById('cb-lightbox');
  if (!gallery || !box) return;

  var img      = box.querySelector('.cb-lightbox__img');
  var counter  = box.querySelector('.cb-lightbox__counter');
  var btnClose = box.querySelector('.cb-lightbox__close');
  var btnPrev  = box.querySelector('.cb-lightbox__prev');
  var btnNext  = box.querySelector('.cb-lightbox__next');
  var items    = Array.prototype.slice.call(gallery.querySelectorAll('[data-full]'));
  var current  = 0;

  function show(i) {
    if (i < 0) i = items.length - 1;
    if (i >= items.length) i = 0;
    current = i;
    var el = items[i];
    img.src = el.getAttribute('data-full');
    img.alt = el.querySelector('img') ? el.querySelector('img').alt : '';
    counter.textContent = (i + 1) + ' / ' + items.length;
  }

  function open(i) {
    show(i);
    box.hidden = false;
    document.documentElement.classList.add('cb-lightbox-open');
    document.body.style.overflow = 'hidden';
  }

  function close() {
    box.hidden = true;
    document.documentElement.classList.remove('cb-lightbox-open');
    document.body.style.overflow = '';
    img.src = '';
  }

  items.forEach(function (el, i) {
    el.addEventListener('click', function (e) {
      e.preventDefault();
      open(i);
    });
  });

  btnClose.addEventListener('click', close);
  btnPrev.addEventListener('click', function () { show(current - 1); });
  btnNext.addEventListener('click', function () { show(current + 1); });

  box.addEventListener('click', function (e) {
    if (e.target === box) close();
  });

  document.addEventListener('keydown', function (e) {
    if (box.hidden) return;
    if (e.key === 'Escape') close();
    else if (e.key === 'ArrowLeft') show(current - 1);
    else if (e.key === 'ArrowRight') show(current + 1);
  });
})();
