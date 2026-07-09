/**
 * CB Legacy Luxury - Page Interactions
 *
 * Handles tabs, accordions, multi-step forms, and agent search filtering.
 */
(function () {
  'use strict';

  /**
   * Tabs (Resources page)
   */
  function initTabs() {
    var tabContainer = document.getElementById('cb-resource-tabs');
    if (!tabContainer) return;

    var buttons = tabContainer.querySelectorAll('.cb-tabs__btn');

    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var tabId = btn.getAttribute('data-tab');

        // Update buttons
        buttons.forEach(function (b) { b.classList.remove('cb-tabs__btn--active'); });
        btn.classList.add('cb-tabs__btn--active');

        // Update content
        document.querySelectorAll('.cb-tab-content').forEach(function (panel) {
          panel.classList.remove('cb-tab-content--active');
        });
        var target = document.getElementById('tab-' + tabId);
        if (target) target.classList.add('cb-tab-content--active');
      });
    });
  }

  /**
   * Accordion (FAQ)
   */
  function initAccordion() {
    var items = document.querySelectorAll('.cb-accordion__item');
    if (!items.length) return;

    items.forEach(function (item) {
      var trigger = item.querySelector('.cb-accordion__trigger');
      if (!trigger) return;

      trigger.addEventListener('click', function () {
        var isOpen = item.classList.contains('cb-accordion__item--open');

        // Close all
        items.forEach(function (i) {
          i.classList.remove('cb-accordion__item--open');
          var content = i.querySelector('.cb-accordion__content');
          if (content) content.style.maxHeight = '0';
        });

        // Open clicked (if was closed)
        if (!isOpen) {
          item.classList.add('cb-accordion__item--open');
          var content = item.querySelector('.cb-accordion__content');
          if (content) content.style.maxHeight = content.scrollHeight + 'px';
        }
      });
    });
  }

  /**
   * Multi-step form (Home Value page)
   */
  function initMultiStepForm() {
    var form = document.getElementById('cb-valuation-form');
    if (!form) return;

    var panels = form.querySelectorAll('.cb-multistep__panel');
    var progress = document.getElementById('cb-form-progress');
    var steps = form.querySelectorAll('.cb-multistep__step');
    var currentStep = 1;
    var totalSteps = panels.length;

    function goToStep(step) {
      panels.forEach(function (p) { p.classList.remove('cb-multistep__panel--active'); });
      steps.forEach(function (s) { s.classList.remove('cb-multistep__step--active'); });

      var targetPanel = form.querySelector('[data-step="' + step + '"]');
      if (targetPanel) targetPanel.classList.add('cb-multistep__panel--active');

      for (var i = 0; i < step; i++) {
        if (steps[i]) steps[i].classList.add('cb-multistep__step--active');
      }

      if (progress) {
        progress.style.width = ((step / totalSteps) * 100) + '%';
      }

      currentStep = step;
    }

    // Next buttons
    form.querySelectorAll('.cb-multistep__next').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (currentStep < totalSteps) {
          goToStep(currentStep + 1);
        }
      });
    });

    // Prev buttons
    form.querySelectorAll('.cb-multistep__prev').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (currentStep > 1) {
          goToStep(currentStep - 1);
        }
      });
    });

    // Submit
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var submitBtn = form.querySelector('.cb-multistep__submit');
      if (submitBtn) {
        submitBtn.textContent = 'Sending...';
        submitBtn.disabled = true;

        setTimeout(function () {
          submitBtn.innerHTML = '&#10003; Report Requested!';
          submitBtn.style.background = 'var(--cb-navy)';
        }, 1500);
      }
    });
  }

  /**
   * Agent Search/Filter
   */
  function initAgentSearch() {
    var searchInput = document.getElementById('cb-agent-search');
    var grid = document.getElementById('cb-agent-grid');
    if (!searchInput || !grid) return;

    searchInput.addEventListener('input', function () {
      var query = searchInput.value.toLowerCase();
      var cards = grid.querySelectorAll('.cb-agent-card');

      cards.forEach(function (card) {
        var name = card.getAttribute('data-name') || '';
        if (name.indexOf(query) !== -1) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    });
  }

  /**
   * Luxury page - gold frame SVG animation
   */
  function initGoldFrame() {
    var frameLine = document.querySelector('.cb-gold-frame-line');
    if (!frameLine || typeof gsap === 'undefined') return;

    gsap.to(frameLine, {
      strokeDashoffset: 0,
      duration: 2.5,
      ease: 'power2.inOut',
      delay: 0.5,
    });
  }

  /**
   * Luxury stats - progress ring animation
   */
  function initProgressRings() {
    var rings = document.querySelectorAll('.cb-progress-ring');
    if (!rings.length || typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;

    rings.forEach(function (ring) {
      var parent = ring.closest('.cb-luxury-stat__circle');
      if (!parent) return;

      var percent = parseInt(parent.getAttribute('data-percent'), 10) || 0;
      var circumference = 339.29;
      var offset = circumference - (percent / 100) * circumference;

      ScrollTrigger.create({
        trigger: parent,
        start: 'top 85%',
        once: true,
        onEnter: function () {
          gsap.to(ring, {
            strokeDashoffset: offset,
            duration: 1.5,
            ease: 'power2.out',
          });
        },
      });
    });
  }

  /**
   * Initialize all page interactions
   */
  function init() {
    initTabs();
    initAccordion();
    initMultiStepForm();
    initAgentSearch();
    initGoldFrame();
    initProgressRings();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
