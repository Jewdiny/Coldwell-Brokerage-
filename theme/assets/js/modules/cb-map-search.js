/**
 * CB Map Search — split-view listings + Leaflet map on /find-a-home/.
 *
 * Plots EVERY active listing matching the current search as price-bubble pins,
 * not just the ~50 rendered cards. The map's marker data comes from the
 * cb/v1/markers REST endpoint (data-markers-url on #cb-map), which pages the
 * Spark feed past its 50-per-request cap server-side and caches the result.
 * The rendered cards (a page of the results) still drive hover/click sync for
 * the listings actually shown in the right column.
 *
 * - All-listings pins: one per active listing in the region (clustered).
 * - Hover/click sync: for pins that correspond to a visible card.
 * - "Search this area": client-side bounds filter over every pin.
 * - Graceful degradation: if the endpoint fails, falls back to building pins
 *   from the rendered cards; if Leaflet fails, cards still render normally.
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
        if (!mapEl || typeof L === 'undefined') { return; }

        var defaultLat  = parseFloat(mapEl.dataset.defaultLat)  || 31.4377;
        var defaultLng  = parseFloat(mapEl.dataset.defaultLng)  || -100.4503;
        var defaultZoom = parseInt(mapEl.dataset.defaultZoom, 10) || 11;
        var markersUrl  = mapEl.dataset.markersUrl || '';

        var loadingEl = mapEl.querySelector('.cb-search-split__map-loading');

        // ----- Map init -----
        var map = L.map(mapEl, {
            center: [defaultLat, defaultLng],
            zoom: defaultZoom,
            scrollWheelZoom: false,
            zoomControl: true,
        });
        map.once('focus', function () { map.scrollWheelZoom.enable(); });
        map.on('click', function () { map.scrollWheelZoom.enable(); });

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(map);

        var clusterGroup = (typeof L.markerClusterGroup === 'function')
            ? L.markerClusterGroup({ showCoverageOnHover: false, spiderfyOnMaxZoom: true, maxClusterRadius: 50 })
            : L.featureGroup();

        var formatPrice = function (price) {
            price = parseFloat(price) || 0;
            if (price >= 1000000) {
                return '$' + (price / 1000000).toFixed(price >= 10000000 ? 0 : 1).replace(/\.0$/, '') + 'M';
            }
            if (price >= 1000) { return '$' + Math.round(price / 1000) + 'K'; }
            return '$' + price;
        };

        // Rendered cards (a page of results), indexed by listing id for sync.
        var cardEls = listEl ? Array.prototype.slice.call(listEl.querySelectorAll('.cb-property-card')) : [];
        var cardsById = {};
        cardEls.forEach(function (card) {
            var id = card.dataset.listingId;
            if (id) { cardsById[id] = card; }
        });

        var markersById = {};
        var markerLatLng = {};
        var bounds = [];
        var totalCount = 0;

        // ----- Results count (right-column header) -----
        var countEl = document.getElementById('cb-results-count');
        var nounEl  = document.getElementById('cb-results-noun');
        function setCount(n) {
            if (countEl) { countEl.textContent = (typeof n === 'number' ? n.toLocaleString() : n); }
            if (nounEl)  { nounEl.textContent = (n === 1 ? 'home' : 'homes'); }
        }

        function addMarker(m) {
            var lat = parseFloat(m.lat), lng = parseFloat(m.lng);
            if (!lat || !lng) { return; }
            var id = m.id || (m.lat + ',' + m.lng);
            var card = cardsById[id];

            var label = formatPrice(m.price);
            var icon = L.divIcon({
                className: 'cb-map-pin',
                html: '<span class="cb-map-pin__label">' + label + '</span>',
                iconSize: null,
                iconAnchor: [28, 14],
            });
            var marker = L.marker([lat, lng], { icon: icon });

            var imgSrc = card && card.querySelector('img') ? card.querySelector('img').src : '';
            var address = m.addr
                || (card && card.querySelector('.cb-property-card__address') ? card.querySelector('.cb-property-card__address').textContent.trim() : '');
            var beds = (m.beds !== undefined && m.beds !== '') ? m.beds : (card ? (card.dataset.beds || '') : '');
            var baths = (m.baths !== undefined && m.baths !== '') ? m.baths : (card ? (card.dataset.baths || '') : '');
            var url = m.url || (card ? card.getAttribute('href') : '#');

            var popupHtml =
                '<div class="cb-map-popup">' +
                  (imgSrc ? '<img src="' + imgSrc + '" alt="">' : '') +
                  '<div class="cb-map-popup__body">' +
                    '<strong>' + label + '</strong>' +
                    '<div class="cb-map-popup__addr">' + address + '</div>' +
                    '<div class="cb-map-popup__meta">' + beds + ' bd &middot; ' + baths + ' ba</div>' +
                    '<a href="' + url + '" class="cb-map-popup__cta">View Listing</a>' +
                  '</div>' +
                '</div>';
            marker.bindPopup(popupHtml, { maxWidth: 280, autoPan: true });

            markersById[id] = marker;
            markerLatLng[id] = [lat, lng];
            bounds.push([lat, lng]);

            if (card) {
                marker.on('mouseover', function () { card.classList.add('cb-property-card--hover'); });
                marker.on('mouseout', function () { card.classList.remove('cb-property-card--hover'); });
                marker.on('click', function () {
                    cardEls.forEach(function (c) { c.classList.remove('cb-property-card--active'); });
                    card.classList.add('cb-property-card--active');
                    if (card.scrollIntoView) { card.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                });
                card.addEventListener('mouseenter', function () {
                    var el = marker.getElement(); if (el) { el.classList.add('cb-map-pin--hover'); }
                });
                card.addEventListener('mouseleave', function () {
                    var el = marker.getElement(); if (el) { el.classList.remove('cb-map-pin--hover'); }
                });
            }

            clusterGroup.addLayer(marker);
        }

        function buildFromCards() {
            cardEls.forEach(function (card) {
                if (!card.hasAttribute('data-lat')) { return; }
                addMarker({
                    id: card.dataset.listingId,
                    lat: card.dataset.lat,
                    lng: card.dataset.lng,
                    price: card.dataset.price,
                    beds: card.dataset.beds,
                    baths: card.dataset.baths,
                    addr: card.querySelector('.cb-property-card__address') ? card.querySelector('.cb-property-card__address').textContent.trim() : '',
                    url: card.getAttribute('href'),
                });
            });
            if (!totalCount) { totalCount = cardEls.length; }
        }

        function finish() {
            if (loadingEl) { loadingEl.style.display = 'none'; }
            clusterGroup.addTo(map);
            if (bounds.length > 0) {
                try { map.fitBounds(bounds, { padding: [40, 40], maxZoom: 14 }); } catch (e) {}
            }
            setCount(totalCount || bounds.length);

            if ((totalCount || cardEls.length) > 0 && bounds.length === 0) {
                mapEl.insertAdjacentHTML('beforeend',
                    '<div class="cb-search-split__map-empty">No mappable listings &mdash; these properties haven\'t been geocoded by MLS yet.</div>');
            }
            setTimeout(function () { map.invalidateSize(); }, 100);
            wireControls();
        }

        // ----- Load markers: endpoint first, cards as fallback -----
        if (markersUrl && window.fetch) {
            fetch(markersUrl, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (data && data.markers && data.markers.length) {
                        totalCount = data.total || data.count || data.markers.length;
                        data.markers.forEach(addMarker);
                    } else {
                        buildFromCards();
                    }
                    finish();
                })
                .catch(function () { buildFromCards(); finish(); });
        } else {
            buildFromCards();
            finish();
        }

        // ----- Controls that depend on the marker set being built -----
        function wireControls() {
            // "Search this area" / "Remove boundary" (client-side bounds filter)
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
                Object.keys(markersById).forEach(function (id) {
                    var ll = markerLatLng[id];
                    var inside = ll && b.contains(ll);
                    var marker = markersById[id];
                    if (inside) {
                        visible++;
                        if (marker && !clusterGroup.hasLayer(marker)) { clusterGroup.addLayer(marker); }
                    } else if (marker) {
                        clusterGroup.removeLayer(marker);
                    }
                    var card = cardsById[id];
                    if (card) { card.style.display = inside ? '' : 'none'; }
                });
                cardEls.forEach(function (card) {
                    if (!card.hasAttribute('data-lat')) { card.style.display = 'none'; }
                });
                removeBoundaryBtn.hidden = false;
                searchAreaBtn.classList.add('is-active');
                setCount(visible);
            }

            function clearBounds() {
                cardEls.forEach(function (card) { card.style.display = ''; });
                Object.keys(markersById).forEach(function (id) {
                    var marker = markersById[id];
                    if (marker && !clusterGroup.hasLayer(marker)) { clusterGroup.addLayer(marker); }
                });
                removeBoundaryBtn.hidden = true;
                searchAreaBtn.classList.remove('is-active');
                setCount(totalCount || bounds.length);
                if (bounds.length > 0) { try { map.fitBounds(bounds, { padding: [40, 40], maxZoom: 14 }); } catch (e) {} }
            }

            searchAreaBtn.addEventListener('click', applyBounds);
            removeBoundaryBtn.addEventListener('click', clearBounds);
        }

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

        // ----- Auto-submit filter form on select change -----
        var filterForm = document.getElementById('cb-filter-form');
        if (filterForm) {
            filterForm.querySelectorAll('select').forEach(function (sel) {
                sel.addEventListener('change', function () { filterForm.submit(); });
            });
        }
    });
})();
