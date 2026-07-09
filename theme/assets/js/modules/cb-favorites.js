/**
 * CB Favorites — save/un-save listing cards (no login required).
 *
 * The heart (.cb-fav) is rendered inside each card's <a> wrapper, so clicks
 * must be intercepted to avoid following the card link. Saved IDs persist in
 * localStorage and are re-applied on every page load. Loaded site-wide so
 * hearts work anywhere cb-property-card appears.
 */
(function () {
  'use strict';

  var KEY = 'cb_favorites';

  function read() {
    try { return JSON.parse(localStorage.getItem(KEY)) || []; }
    catch (e) { return []; }
  }
  function write(ids) {
    try { localStorage.setItem(KEY, JSON.stringify(ids)); } catch (e) {}
  }

  function setState(el, saved) {
    el.classList.toggle('is-saved', saved);
    el.setAttribute('aria-pressed', saved ? 'true' : 'false');
    el.setAttribute('aria-label', saved ? 'Remove from saved homes' : 'Save this home');
  }

  function toggle(el) {
    var id = el.getAttribute('data-fav-id');
    if (!id) { return; }
    var ids = read();
    var i = ids.indexOf(id);
    if (i === -1) { ids.push(id); setState(el, true); }
    else { ids.splice(i, 1); setState(el, false); }
    write(ids);
  }

  function init() {
    var saved = read();
    Array.prototype.forEach.call(document.querySelectorAll('.cb-fav[data-fav-id]'), function (el) {
      setState(el, saved.indexOf(el.getAttribute('data-fav-id')) !== -1);
    });

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

  if (document.readyState !== 'loading') { init(); }
  else { document.addEventListener('DOMContentLoaded', init); }
})();
