/**
 * CB Legacy Luxury - GSAP Animation Controller
 *
 * Master animation controller that initializes ScrollTrigger,
 * handles prefers-reduced-motion, and manages mobile vs desktop animations.
 */
(function () {
  'use strict';

  // Check reduced motion preference
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (prefersReducedMotion) {
    // Show all elements immediately
    document.querySelectorAll('.cb-reveal, .cb-reveal--left, .cb-reveal--right, .cb-reveal--scale').forEach(function (el) {
      el.style.opacity = '1';
      el.style.transform = 'none';
    });
    return;
  }

  // Register ScrollTrigger
  gsap.registerPlugin(ScrollTrigger);

  // Detect mobile
  var isMobile = window.innerWidth < 768;

  // Default animation settings
  var defaults = {
    duration: isMobile ? 0.5 : 0.8,
    ease: 'power3.out',
    stagger: isMobile ? 0.08 : 0.15,
  };

  /**
   * Animate .cb-reveal elements on scroll
   */
  function initRevealAnimations() {
    // Fade up reveals
    gsap.utils.toArray('.cb-reveal').forEach(function (el) {
      if (el.closest('[data-cb-scroll]')) { return; } // homepage scroll controller owns these
      gsap.fromTo(el,
        { opacity: 0, y: isMobile ? 25 : 40 },
        {
          opacity: 1,
          y: 0,
          duration: defaults.duration,
          ease: defaults.ease,
          scrollTrigger: {
            trigger: el,
            start: 'top 88%',
            toggleActions: 'play none none none',
          },
        }
      );
    });

    // Slide from left
    gsap.utils.toArray('.cb-reveal--left').forEach(function (el) {
      if (el.closest('[data-cb-scroll]')) { return; }
      gsap.fromTo(el,
        { opacity: 0, x: isMobile ? -20 : -40 },
        {
          opacity: 1,
          x: 0,
          duration: defaults.duration,
          ease: defaults.ease,
          scrollTrigger: {
            trigger: el,
            start: 'top 88%',
            toggleActions: 'play none none none',
          },
        }
      );
    });

    // Slide from right
    gsap.utils.toArray('.cb-reveal--right').forEach(function (el) {
      if (el.closest('[data-cb-scroll]')) { return; }
      gsap.fromTo(el,
        { opacity: 0, x: isMobile ? 20 : 40 },
        {
          opacity: 1,
          x: 0,
          duration: defaults.duration,
          ease: defaults.ease,
          scrollTrigger: {
            trigger: el,
            start: 'top 88%',
            toggleActions: 'play none none none',
          },
        }
      );
    });

    // Scale reveals
    gsap.utils.toArray('.cb-reveal--scale').forEach(function (el) {
      if (el.closest('[data-cb-scroll]')) { return; }
      gsap.fromTo(el,
        { opacity: 0, scale: 0.9 },
        {
          opacity: 1,
          scale: 1,
          duration: defaults.duration,
          ease: defaults.ease,
          scrollTrigger: {
            trigger: el,
            start: 'top 88%',
            toggleActions: 'play none none none',
          },
        }
      );
    });
  }

  /**
   * Animated counter for stats section
   */
  function initCounters() {
    var counters = document.querySelectorAll('[data-count]');
    if (!counters.length) return;

    counters.forEach(function (counter) {
      if (counter.closest('[data-cb-scroll]')) { return; } // homepage owns its counters
      var target = parseInt(counter.getAttribute('data-count'), 10);
      var suffix = counter.getAttribute('data-suffix') || '+';

      ScrollTrigger.create({
        trigger: counter,
        start: 'top 85%',
        once: true,
        onEnter: function () {
          gsap.fromTo(counter,
            { innerText: 0 },
            {
              innerText: target,
              duration: 2,
              ease: 'power2.out',
              snap: { innerText: 1 },
              onUpdate: function () {
                counter.textContent = Math.floor(parseFloat(counter.textContent)).toLocaleString() + suffix;
              },
            }
          );
        },
      });
    });
  }

  /**
   * Testimonial slider
   */
  function initTestimonialSlider() {
    var testimonials = document.querySelectorAll('.cb-testimonial');
    var dotsContainer = document.getElementById('cb-testimonial-dots');

    if (!testimonials.length || !dotsContainer) return;

    var current = 0;
    var autoplayInterval;

    // Create dots
    testimonials.forEach(function (_, i) {
      var dot = document.createElement('button');
      dot.className = 'cb-testimonials__dot' + (i === 0 ? ' cb-testimonials__dot--active' : '');
      dot.setAttribute('aria-label', 'Testimonial ' + (i + 1));
      dot.addEventListener('click', function () {
        goTo(i);
      });
      dotsContainer.appendChild(dot);
    });

    function goTo(index) {
      testimonials[current].classList.remove('cb-testimonial--active');
      dotsContainer.children[current].classList.remove('cb-testimonials__dot--active');

      current = index;

      testimonials[current].classList.add('cb-testimonial--active');
      dotsContainer.children[current].classList.add('cb-testimonials__dot--active');

      resetAutoplay();
    }

    function next() {
      goTo((current + 1) % testimonials.length);
    }

    function resetAutoplay() {
      clearInterval(autoplayInterval);
      autoplayInterval = setInterval(next, 5000);
    }

    resetAutoplay();
  }

  /**
   * Staggered grid animations
   */
  function initGridAnimations() {
    var grids = ['.cb-actions-grid', '.cb-property-grid', '.cb-community-grid', '.cb-blog-grid'];

    grids.forEach(function (selector) {
      var grid = document.querySelector(selector);
      if (!grid || grid.closest('[data-cb-scroll]')) return;

      var children = grid.children;
      if (!children.length) return;

      gsap.fromTo(children,
        { opacity: 0, y: isMobile ? 20 : 40 },
        {
          opacity: 1,
          y: 0,
          duration: defaults.duration,
          stagger: defaults.stagger,
          ease: defaults.ease,
          scrollTrigger: {
            trigger: grid,
            start: 'top 85%',
            toggleActions: 'play none none none',
          },
        }
      );
    });
  }

  /**
   * Gold divider line animation
   */
  function initDividerAnimations() {
    gsap.utils.toArray('.cb-section__divider').forEach(function (divider) {
      gsap.fromTo(divider,
        { scaleX: 0 },
        {
          scaleX: 1,
          duration: 0.8,
          ease: 'power2.inOut',
          scrollTrigger: {
            trigger: divider,
            start: 'top 90%',
            toggleActions: 'play none none none',
          },
        }
      );
    });
  }

  /**
   * Initialize all animations
   */
  function init() {
    initRevealAnimations();
    initCounters();
    initTestimonialSlider();
    initGridAnimations();
    initDividerAnimations();
  }

  // Run when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Expose for page-specific modules
  window.cbAnimations = {
    defaults: defaults,
    isMobile: isMobile,
  };
})();
