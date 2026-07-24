<?php
/**
 * Template Name: Saved Homes
 *
 * The other half of the save heart. Hearts have been rendered on every listing
 * card for some time, but nothing ever read the list back -- a visitor could
 * save homes and had no way to see them again.
 *
 * Rendered entirely from localStorage by the client. No MLS call, deliberately:
 * a saved list that goes blank because a third-party feed is having a bad day
 * is worse than useless, and this is the one part of the site a visitor
 * considers their own. It also means the page works instantly and offline.
 *
 * Nothing here is indexable -- the content is per-visitor and there is nothing
 * for a crawler to see but the empty state.
 *
 * @package CB_Legacy_Luxury
 */

cb_set_seo_meta([
    'title'       => 'Your Saved Homes | Coldwell Banker Legacy',
    'description' => 'The San Angelo homes you have saved, kept on this device.',
    'canonical'   => get_permalink(),
    // The key is `robots`, not `noindex` -- cb_set_seo_meta() has no noindex
    // argument and would have discarded one silently.
    'robots'      => 'noindex, follow',
]);

get_header();
?>

<div style="padding-top:var(--header-height);">

<section class="cb-section">
    <div class="cb-container">
        <div class="cb-section__header cb-reveal">
            <span class="cb-section__subtitle">Your Shortlist</span>
            <h1 class="cb-section__title">Saved Homes</h1>
            <div class="cb-section__divider"></div>
            <p class="cb-section__desc" id="cb-saved-intro">
                Homes you have saved are kept on this device &mdash; no account needed.
            </p>
        </div>

        <?php /* Populated by JS. The empty state is the default so a reader with
             scripts off, or with nothing saved, still gets a sentence and a way
             onward rather than a blank column. */ ?>
        <div class="cb-property-grid" id="cb-saved-grid" hidden></div>

        <div id="cb-saved-empty" style="text-align:center;max-width:38rem;margin:0 auto;padding:1rem 0 2rem;">
            <p style="font-size:1.0625rem;line-height:1.75;">
                You haven&rsquo;t saved any homes yet.
            </p>
            <p style="line-height:1.75;">
                Tap the bookmark on any listing and it will appear here.
            </p>
            <p style="margin-top:1.75rem;">
                <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb-btn cb-btn--primary">Browse San Angelo Homes</a>
            </p>
        </div>

        <div id="cb-saved-actions" style="text-align:center;margin-top:2.5rem;" hidden>
            <a href="<?php echo esc_url(home_url('/find-a-home/')); ?>" class="cb-btn cb-btn--primary">Keep Looking</a>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="cb-btn cb-btn--navy">Ask About These</a>
        </div>
    </div>
</section>

</div>

<script>
/* Renders from window.cbFavorites, which owns the storage format -- this page
   never parses localStorage itself, so there is one definition of "saved".
   cb-favorites.js is enqueued site-wide in the footer, so it is already
   defined by the time this runs. */
(function () {
  'use strict';
  function el(tag, cls, txt) {
    var n = document.createElement(tag);
    if (cls) { n.className = cls; }
    if (txt) { n.textContent = txt; }
    return n;
  }

  function card(item) {
    // A v1 entry carries only an id, so there is nothing to draw a card from.
    // Show it as a plain row rather than an empty card or, worse, dropping it.
    var wrap = document.createElement(item.url ? 'a' : 'div');
    wrap.className = 'cb-property-card';
    if (item.url) { wrap.href = item.url; }

    var imgWrap = el('div', 'cb-property-card__image');
    if (item.photo) {
      var img = document.createElement('img');
      img.src = item.photo;
      img.alt = item.address || 'Saved home';
      img.loading = 'lazy';
      imgWrap.appendChild(img);
    }
    if (item.badge) { imgWrap.appendChild(el('span', 'cb-property-card__badge', item.badge)); }
    if (item.price) { imgWrap.appendChild(el('span', 'cb-property-card__price', item.price)); }

    // Remove control. A button, not a heart: on this page the action is
    // unambiguous and needs a real label.
    var rm = el('button', 'cb-saved-remove', '×');
    rm.type = 'button';
    rm.title = 'Remove from saved homes';
    rm.setAttribute('aria-label', 'Remove ' + (item.address || 'this home') + ' from saved homes');
    rm.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      window.cbFavorites.remove(item.id);
      render();
    });
    imgWrap.appendChild(rm);
    wrap.appendChild(imgWrap);

    var body = el('div', 'cb-property-card__body');
    body.appendChild(el('div', 'cb-property-card__address', item.address || ('Listing ' + item.id)));
    if (item.locale) { body.appendChild(el('div', 'cb-property-card__location', item.locale)); }
    if (item.details && item.details.length) {
      var d = el('div', 'cb-property-card__details');
      item.details.forEach(function (t) { d.appendChild(el('span', 'cb-property-card__detail', t)); });
      body.appendChild(d);
    }
    wrap.appendChild(body);
    return wrap;
  }

  function render() {
    var grid = document.getElementById('cb-saved-grid');
    var empty = document.getElementById('cb-saved-empty');
    var actions = document.getElementById('cb-saved-actions');
    var intro = document.getElementById('cb-saved-intro');
    if (!grid || !window.cbFavorites) { return; }

    var items = window.cbFavorites.all();
    var ids = Object.keys(items);

    grid.textContent = '';
    ids.forEach(function (id) { grid.appendChild(card(items[id])); });

    var has = ids.length > 0;
    grid.hidden = !has;
    empty.hidden = has;
    actions.hidden = !has;
    if (intro && has) {
      intro.textContent = ids.length === 1
        ? 'One home saved on this device.'
        : ids.length + ' homes saved on this device.';
    }
  }

  if (document.readyState !== 'loading') { render(); }
  else { document.addEventListener('DOMContentLoaded', render); }
})();
</script>

<?php get_footer(); ?>
