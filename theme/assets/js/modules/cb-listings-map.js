/**
 * Live map of the newest listings for the Home 10 walk's Listings panel.
 *
 * Each [cb_listings] card in the carousel below carries data-lat / data-lng /
 * data-price. This module drops one pin per card on a Leaflet map and lights up
 * the pin for whichever property the carousel is currently presenting -- so as
 * the carousel moves (auto or by hand), the home's location "shows up" on the
 * map above it.
 *
 * The map is deliberately INERT. Every Leaflet interaction handler -- dragging,
 * wheel zoom, touch zoom, double-click, keyboard, tap -- is turned off, and the
 * canvas is touch-action: pan-y, so the map never captures the walk's scroll.
 * It is driven entirely from the carousel; the full pan/zoom map lives one click
 * away on Find-a-Home (the button over the map).
 *
 * Progressive enhancement: if Leaflet is absent, or there are no geolocated
 * cards (e.g. the MLS feed rendered a notice instead), the map's fallback
 * background image is left showing and this quietly does nothing.
 */
(function () {
  'use strict';

  function fmtPrice(n) {
    n = parseInt(n, 10) || 0;
    if (n <= 0) { return ''; }
    if (n >= 1000000) { return '$' + (n / 1000000).toFixed(n % 1000000 === 0 ? 0 : 1) + 'M'; }
    if (n >= 1000) { return '$' + Math.round(n / 1000) + 'K'; }
    return '$' + n;
  }

  function findGrid(container) {
    var panel = container.closest('.cb9-page__body') || container.closest('.cb9-page') || document;
    return panel.querySelector('.cb9-carousel__grid');
  }

  function init(container) {
    if (container.__cb9MapDone) { return; }
    container.__cb9MapDone = true;

    if (typeof window.L === 'undefined') { return; }           // no Leaflet -> fallback bg stays
    var canvas = container.querySelector('.cb9-map__canvas');
    if (!canvas) { return; }

    var parts = (container.getAttribute('data-cb9-map-center') || '31.44,-100.45').split(',');
    var center = [parseFloat(parts[0]) || 31.44, parseFloat(parts[1]) || -100.45];
    var zoom = parseInt(container.getAttribute('data-cb9-map-zoom'), 10) || 12;

    var map = window.L.map(canvas, {
      center: center, zoom: zoom, zoomSnap: 0.25,
      dragging: false, scrollWheelZoom: false, doubleClickZoom: false,
      boxZoom: false, keyboard: false, touchZoom: false, tap: false,
      zoomControl: false, attributionControl: false, inertia: false
    });

    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19, subdomains: 'abc',
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // One pin per geolocated card, kept paired with its card.
    var grid = findGrid(container);
    var entries = [];
    if (grid) {
      var cards = grid.querySelectorAll('.cb-property-card[data-lat][data-lng]');
      Array.prototype.forEach.call(cards, function (card, i) {
        var lat = parseFloat(card.getAttribute('data-lat'));
        var lng = parseFloat(card.getAttribute('data-lng'));
        if (!isFinite(lat) || !isFinite(lng)) { return; }
        var price = fmtPrice(card.getAttribute('data-price'));
        var icon = window.L.divIcon({
          className: 'cb9-pin-marker',
          html: '<div class="cb9-pin"></div>' + (price ? '<span class="cb9-pin__price">' + price + '</span>' : ''),
          iconSize: [16, 16], iconAnchor: [8, 8]
        });
        var marker = window.L.marker([lat, lng], { icon: icon, keyboard: false, riseOnHover: true });
        marker.addTo(map);
        var idx = entries.length;
        marker.on('click', function () { centerCard(card); setActive(idx); });
        entries.push({ card: card, marker: marker });
      });
    }

    var bounds = null;
    if (entries.length) {
      bounds = window.L.featureGroup(entries.map(function (e) { return e.marker; })).getBounds();
    }

    function refit() {
      map.invalidateSize(false);
      if (bounds && bounds.isValid()) {
        map.fitBounds(bounds, { padding: [30, 34], maxZoom: 14, animate: false });
      }
    }

    var activeIdx = -1;
    function setActive(i) {
      if (i === activeIdx || i < 0 || i >= entries.length) { return; }
      if (activeIdx > -1 && entries[activeIdx].marker._icon) {
        entries[activeIdx].marker._icon.classList.remove('is-active');
      }
      activeIdx = i;
      var m = entries[i].marker;
      if (m._icon) { m._icon.classList.add('is-active'); }
      if (m.setZIndexOffset) { m.setZIndexOffset(1000); }
    }

    function centerCard(card) {
      if (!grid) { return; }
      var gr = grid.getBoundingClientRect();
      var cr = card.getBoundingClientRect();
      var target = grid.scrollLeft + (cr.left - gr.left) - (grid.clientWidth - cr.width) / 2;
      grid.scrollTo({ left: Math.max(0, target), behavior: 'smooth' });
    }

    // Which pinned card is nearest the carousel's viewport centre.
    function nearestEntry() {
      if (!grid || !entries.length) { return -1; }
      var gr = grid.getBoundingClientRect();
      var mid = gr.left + gr.width / 2;
      var best = 0, bestDist = Infinity;
      for (var i = 0; i < entries.length; i++) {
        var cr = entries[i].card.getBoundingClientRect();
        var d = Math.abs((cr.left + cr.width / 2) - mid);
        if (d < bestDist) { bestDist = d; best = i; }
      }
      return best;
    }

    if (grid) {
      var ticking = false;
      grid.addEventListener('scroll', function () {
        if (ticking) { return; }
        ticking = true;
        window.requestAnimationFrame(function () {
          ticking = false;
          setActive(nearestEntry());
        });
      }, { passive: true });
    }

    // Size can be wrong at first (the panel is transformed off-screen in the
    // walk) -- settle it a few times, on load, on resize, and whenever the
    // container itself changes size.
    refit();
    setActive(nearestEntry());
    [80, 450, 1300].forEach(function (t) { window.setTimeout(refit, t); });
    window.addEventListener('load', refit);
    window.addEventListener('resize', function () { window.requestAnimationFrame(refit); });
    if (window.ResizeObserver) {
      try { new window.ResizeObserver(function () { map.invalidateSize(false); }).observe(canvas); } catch (e) {}
    }
  }

  function boot() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-cb9-map]'), init);
  }

  if (document.readyState !== 'loading') { boot(); }
  else { document.addEventListener('DOMContentLoaded', boot); }
})();
