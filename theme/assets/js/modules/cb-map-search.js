/**
 * CB Map Search — split-view listings + Leaflet map on /find-a-home/.
 *
 * Reads data-lat / data-lng / data-price / data-listing-id off the listing
 * cards rendered by CB_Spark_Shortcodes::render_card() and plots them as
 * price-bubble pins on an OSM-tile Leaflet map.
 *
 * - Hover sync: card hover highlights pin; pin hover highlights card.
 * - Click sync: clicking a pin scrolls the card into view + opens popup.
 * - Clustering: marker cluster plugin handles dense San Angelo zip codes.
 * - Graceful degradation: if no cards have coords, the map shows a regional
 *   overview centered on San Angelo. If Leaflet fails to load, cards still
 *   render normally (the script is enqueued, not blocking).
 */
(function () {
    'use strict';

    var ready = function (cb) {
        if (document.readyState !== 'loading') { cb(); }
        else { document.addEventListener('DOMContentLoaded', cb); }
    };

    ready(function () {
        var mapEl = document.getElementById('cb-map');
        var listEl = document.getElementById('cb-search-listings');
        if (!mapEl || !listEl || typeof L === 'undefined') { return; }

        var defaultLat  = parseFloat(mapEl.dataset.defaultLat)  || 31.4377;
        var defaultLng  = parseFloat(mapEl.dataset.defaultLng)  || -100.4503;
        var defaultZoom = parseInt(mapEl.dataset.defaultZoom, 10) || 11;

        var loadingEl = mapEl.querySelector('.cb-search-split__map-loading');
        if (loadingEl) { loadingEl.style.display = 'none'; }

        // ----- Map init -----
        var map = L.map(mapEl, {
            center: [defaultLat, defaultLng],
            zoom: defaultZoom,
            scrollWheelZoom: false,
            zoomControl: true,
        });

        // Activate scroll zoom only after click — avoids hijacking page scroll.
        map.once('focus', function () { map.scrollWheelZoom.enable(); });
        map.on('click', function () { map.scrollWheelZoom.enable(); });

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(map);

        // ----- Build markers from cards -----
        var clusterGroup = (typeof L.markerClusterGroup === 'function')
            ? L.markerClusterGroup({
                showCoverageOnHover: false,
                spiderfyOnMaxZoom: true,
                maxClusterRadius: 50,
            })
            : L.featureGroup();

        var formatPrice = function (price) {
            price = parseFloat(price) || 0;
            if (price >= 1000000) {
                return '$' + (price / 1000000).toFixed(price >= 10000000 ? 0 : 1).replace(/\.0$/, '') + 'M';
            }
            if (price >= 1000) {
                return '$' + Math.round(price / 1000) + 'K';
            }
            return '$' + price;
        };

        var cards = listEl.querySelectorAll('.cb-property-card[data-lat][data-lng]');
        var allCards = Array.prototype.slice.call(listEl.querySelectorAll('.cb-property-card'));
        var totalCards = allCards.length;
        var markersById = {};
        var cardsById = {};
        var bounds = [];

        cards.forEach(function (card) {
            var lat = parseFloat(card.dataset.lat);
            var lng = parseFloat(card.dataset.lng);
            if (!lat || !lng) { return; }

            var price = card.dataset.price;
            var id = card.dataset.listingId;
            var url = card.getAttribute('href');
            var addrEl = card.querySelector('.cb-property-card__address');
            var address = addrEl ? addrEl.textContent.trim() : '';
            var beds = card.dataset.beds || '';
            var baths = card.dataset.baths || '';

            cardsById[id] = card;

            var label = formatPrice(price);
            var icon = L.divIcon({
                className: 'cb-map-pin',
                html: '<span class="cb-map-pin__label">' + label + '</span>',
                iconSize: null,
                iconAnchor: [28, 14],
            });

            var marker = L.marker([lat, lng], { icon: icon, listingId: id });

            var popupHtml =
                '<div class="cb-map-popup">' +
                  (card.querySelector('img') ? '<img src="' + card.querySelector('img').src + '" alt="">' : '') +
                  '<div class="cb-map-popup__body">' +
                    '<strong>' + label + '</strong>' +
                    '<div class="cb-map-popup__addr">' + address + '</div>' +
                    '<div class="cb-map-popup__meta">' + beds + ' bd &middot; ' + baths + ' ba</div>' +
                    '<a href="' + url + '" class="cb-map-popup__cta">View Listing</a>' +
                  '</div>' +
                '</div>';

            marker.bindPopup(popupHtml, { maxWidth: 280, autoPan: true });
            markersById[id] = marker;
            bounds.push([lat, lng]);

            // Hover sync — pin → card
            marker.on('mouseover', function () {
                card.classList.add('cb-property-card--hover');
            });
            marker.on('mouseout', function () {
                card.classList.remove('cb-property-card--hover');
            });

            // Click pin → scroll card into view
            marker.on('click', function () {
                cards.forEach(function (c) { c.classList.remove('cb-property-card--active'); });
                card.classList.add('cb-property-card--active');
                if (card.scrollIntoView) {
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

            clusterGroup.addLayer(marker);
        });

        clusterGroup.addTo(map);

        if (bounds.length > 0) {
            try {
                map.fitBounds(bounds, { padding: [40, 40], maxZoom: 14 });
            } catch (e) {}
        }

        // ----- Hover sync — card → pin -----
        Object.keys(cardsById).forEach(function (id) {
            var card = cardsById[id];
            var marker = markersById[id];
            if (!marker) { return; }

            card.addEventListener('mouseenter', function () {
                var el = marker.getElement();
                if (el) { el.classList.add('cb-map-pin--hover'); }
            });
            card.addEventListener('mouseleave', function () {
                var el = marker.getElement();
                if (el) { el.classList.remove('cb-map-pin--hover'); }
            });
        });

        // ----- Results count (right-column header) -----
        var countEl = document.getElementById('cb-results-count');
        var nounEl = document.getElementById('cb-results-noun');
        function setCount(n) {
            if (countEl) { countEl.textContent = n; }
            if (nounEl) { nounEl.textContent = (n === 1 ? 'home' : 'homes'); }
        }
        setCount(totalCards);

        // ----- "Search this area" / "Remove boundary" (client-side bounds filter) -----
        var ctrl = document.createElement('div');
        ctrl.className = 'cb-map-controls';
        ctrl.innerHTML =
            '<button type="button" class="cb-map-areabtn" id="cb-search-area">' +
              '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>' +
              '<span>Search this area</span>' +
            '</button>' +
            '<button type="button" class="cb-map-areabtn cb-map-areabtn--ghost" id="cb-remove-boundary" hidden>Remove boundary</button>';
        mapEl.appendChild(ctrl);
        var searchAreaBtn = ctrl.querySelector('#cb-search-area');
        var removeBoundaryBtn = ctrl.querySelector('#cb-remove-boundary');

        function applyBounds() {
            var b = map.getBounds();
            var visible = 0;
            Object.keys(cardsById).forEach(function (id) {
                var card = cardsById[id];
                var marker = markersById[id];
                var inside = b.contains([parseFloat(card.dataset.lat), parseFloat(card.dataset.lng)]);
                card.style.display = inside ? '' : 'none';
                if (marker) {
                    if (inside) { if (!clusterGroup.hasLayer(marker)) { clusterGroup.addLayer(marker); } }
                    else { clusterGroup.removeLayer(marker); }
                }
                if (inside) { visible++; }
            });
            // Listings without coordinates can't be on the map, so hide them while bounded.
            allCards.forEach(function (card) {
                if (!card.hasAttribute('data-lat')) { card.style.display = 'none'; }
            });
            removeBoundaryBtn.hidden = false;
            searchAreaBtn.classList.add('is-active');
            setCount(visible);
        }

        function clearBounds() {
            allCards.forEach(function (card) { card.style.display = ''; });
            Object.keys(markersById).forEach(function (id) {
                var marker = markersById[id];
                if (marker && !clusterGroup.hasLayer(marker)) { clusterGroup.addLayer(marker); }
            });
            removeBoundaryBtn.hidden = true;
            searchAreaBtn.classList.remove('is-active');
            setCount(totalCards);
            if (bounds.length > 0) { try { map.fitBounds(bounds, { padding: [40, 40], maxZoom: 14 }); } catch (e) {} }
        }

        searchAreaBtn.addEventListener('click', applyBounds);
        removeBoundaryBtn.addEventListener('click', clearBounds);

        // ----- Save current search (localStorage) -----
        var saveBtn = document.getElementById('cb-save-search');
        if (saveBtn) {
            var SKEY = 'cb_saved_searches';
            var current = window.location.pathname + window.location.search;
            var readSaved = function () { try { return JSON.parse(localStorage.getItem(SKEY)) || []; } catch (e) { return []; } };
            var paintSave = function (on) {
                saveBtn.classList.toggle('is-saved', on);
                saveBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
                var lbl = saveBtn.querySelector('.cb-filter-bar__save-label');
                if (lbl) { lbl.textContent = on ? 'Saved' : 'Save search'; }
            };
            paintSave(readSaved().indexOf(current) !== -1);
            saveBtn.addEventListener('click', function () {
                var list = readSaved();
                var idx = list.indexOf(current);
                if (idx === -1) { list.push(current); paintSave(true); }
                else { list.splice(idx, 1); paintSave(false); }
                try { localStorage.setItem(SKEY, JSON.stringify(list)); } catch (e) {}
            });
        }

        // ----- "No coords" indicator -----
        var mappedCount = bounds.length;
        if (totalCards > 0 && mappedCount === 0) {
            mapEl.insertAdjacentHTML(
                'beforeend',
                '<div class="cb-search-split__map-empty">No mappable listings &mdash; these properties haven\'t been geocoded by MLS yet.</div>'
            );
        }

        // Refresh map size in case it loaded hidden (mobile toggle).
        setTimeout(function () { map.invalidateSize(); }, 100);

        // ----- Mobile toggle -----
        var toggleBtn = document.getElementById('cb-map-toggle');
        var split = document.getElementById('cb-search-split');
        if (toggleBtn && split) {
            toggleBtn.addEventListener('click', function () {
                var on = split.classList.toggle('cb-search-split--map-visible');
                toggleBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
                if (on) { setTimeout(function () { map.invalidateSize(); }, 250); }
            });
        }

        // ----- Auto-submit filter form on select change for snappier UX -----
        var filterForm = document.getElementById('cb-filter-form');
        if (filterForm) {
            filterForm.querySelectorAll('select').forEach(function (sel) {
                sel.addEventListener('change', function () {
                    filterForm.submit();
                });
            });
        }
    });
})();
