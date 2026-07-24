/**
 * CB Favorites — save/un-save listing cards (no login required).
 *
 * The heart (.cb-fav) is rendered inside each card's <a> wrapper, so clicks
 * must be intercepted to avoid following the card link. Saved homes persist in
 * localStorage and are re-applied on every page load. Loaded site-wide so
 * hearts work anywhere cb-property-card appears.
 *
 * WHY A SNAPSHOT AND NOT JUST AN ID
 * ---------------------------------
 * This used to store a bare array of MLS ids. Saving worked, but nothing ever
 * read the list back -- there was no way to see what you had saved. Rebuilding
 * it later would mean asking the MLS about every id, which fails outright
 * whenever the feed is down and leaves a reader staring at an empty page of
 * their own saved homes.
 *
 * Everything needed to redraw a card is already in the DOM when the heart is
 * clicked: address, price, photo, and the card's own href. So it is captured
 * then. The saved page is pure localStorage and needs no network, which is the
 * behaviour you want from something a visitor thinks of as theirs.
 *
 * Storage is versioned. v1 was ["id","id"]; v2 is {v:2, items:{id:{...}}}.
 * migrate() upgrades v1 in place rather than discarding it, so anyone who saved
 * homes before this shipped keeps them -- they simply carry no detail until
 * re-saved, and the saved page renders those as a plain link.
 */
(function () {
  'use strict';

  var KEY = 'cb_favorites';

  function readRaw() {
    try { return JSON.parse(localStorage.getItem(KEY)); }
    catch (e) { return null; }
  }

  function migrate(raw) {
    if (Array.isArray(raw)) {
      var items = {};
      raw.forEach(function (id) { if (id) { items[String(id)] = { id: String(id) }; } });
      return { v: 2, items: items };
    }
    if (raw && raw.v === 2 && raw.items && typeof raw.items === 'object') { return raw; }
    return { v: 2, items: {} };
  }

  function read() { return migrate(readRaw()); }

  function write(store) {
    try { localStorage.setItem(KEY, JSON.stringify(store)); } catch (e) {}
    updateCounts(Object.keys(store.items).length);
  }

  function text(el, sel) {
    var n = el.querySelector(sel);
    return n ? (n.textContent || '').trim() : '';
  }

  /** Capture what the card is showing, so the saved page can redraw it offline. */
  function snapshot(fav) {
    var id = fav.getAttribute('data-fav-id');
    var card = fav.closest ? fav.closest('.cb-property-card') : null;
    var out = { id: id };
    if (!card) { return out; }

    var img = card.querySelector('.cb-property-card__image img');
    out.url = card.getAttribute('href') || '';
    out.photo = img ? (img.getAttribute('src') || '') : '';
    out.address = text(card, '.cb-property-card__address');
    out.locale = text(card, '.cb-property-card__location');
    out.price = text(card, '.cb-property-card__price');
    out.badge = text(card, '.cb-property-card__badge');

    var details = card.querySelectorAll('.cb-property-card__detail');
    out.details = Array.prototype.map.call(details, function (d) {
      return (d.textContent || '').replace(/\s+/g, ' ').trim();
    }).filter(Boolean).slice(0, 3);

    return out;
  }

  function setState(el, saved) {
    el.classList.toggle('is-saved', saved);
    el.setAttribute('aria-pressed', saved ? 'true' : 'false');
    el.setAttribute('aria-label', saved ? 'Remove from saved homes' : 'Save this home');
  }

  function toggle(el) {
    var id = el.getAttribute('data-fav-id');
    if (!id) { return; }
    var store = read();
    if (store.items[id]) {
      delete store.items[id];
      setState(el, false);
    } else {
      store.items[id] = snapshot(el);
      setState(el, true);
    }
    write(store);
  }

  /** Keep every saved-count badge on the page in step, and hide it at zero. */
  function updateCounts(n) {
    Array.prototype.forEach.call(document.querySelectorAll('[data-cb-fav-count]'), function (el) {
      el.textContent = String(n);
      el.hidden = n === 0;
    });
  }

  function init() {
    var wasV1 = Array.isArray(readRaw());
    var store = read();
    // Persist the migration once, so a v1 reader is not re-converted every load.
    if (wasV1) { write(store); }

    Array.prototype.forEach.call(document.querySelectorAll('.cb-fav[data-fav-id]'), function (el) {
      setState(el, !!store.items[el.getAttribute('data-fav-id')]);
    });
    updateCounts(Object.keys(store.items).length);

    document.addEventListener('click', function (e) {
      var fav = e.target.closest ? e.target.closest('.cb-fav') : null;
      if (!fav) { return; }
      e.preventDefault();
      e.stopPropagation();
      toggle(fav);
    });

    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter' && e.key !== ' ') { return; }
      var fav = e.target.closest ? e.target.closest('.cb-fav') : null;
      if (!fav) { return; }
      e.preventDefault();
      e.stopPropagation();
      toggle(fav);
    });
  }

  /* Exposed so the saved-homes page can read and remove without restating the
     storage format. One definition of what "saved" means. */
  window.cbFavorites = {
    all: function () { return read().items; },
    remove: function (id) {
      var store = read();
      delete store.items[String(id)];
      write(store);
    }
  };

  if (document.readyState !== 'loading') { init(); }
  else { document.addEventListener('DOMContentLoaded', init); }
})();
