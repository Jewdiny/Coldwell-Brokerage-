/**
 * CB Legacy Luxury - Form Handlers
 *
 * Handles contact form and home valuation form AJAX submissions.
 */
(function () {
  'use strict';

  /**
   * Contact Form
   */
  function initContactForm() {
    var form = document.getElementById('cb-contact-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var btn = form.querySelector('button[type="submit"]');
      var originalText = btn.textContent;
      btn.textContent = 'Sending...';
      btn.disabled = true;

      var data = new FormData();
      data.append('action', 'cb_contact_form');
      data.append('nonce', cbLegacy.nonce);
      data.append('first_name', form.querySelector('#contact-first').value);
      data.append('last_name', form.querySelector('#contact-last').value);
      data.append('email', form.querySelector('#contact-email').value);
      data.append('phone', form.querySelector('#contact-phone').value);
      data.append('subject', form.querySelector('#contact-subject').value);
      data.append('message', form.querySelector('#contact-message').value);

      fetch(cbLegacy.ajaxUrl, { method: 'POST', body: data })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res.success) {
            btn.textContent = 'Message Sent!';
            btn.style.background = 'var(--cb-navy)';
            form.reset();
            setTimeout(function () {
              btn.textContent = originalText;
              btn.style.background = '';
              btn.disabled = false;
            }, 3000);
          } else {
            btn.textContent = res.data.message || 'Error - Please try again';
            btn.style.background = '#EF4444';
            setTimeout(function () {
              btn.textContent = originalText;
              btn.style.background = '';
              btn.disabled = false;
            }, 3000);
          }
        })
        .catch(function () {
          btn.textContent = 'Error - Please call (325) 944-9559';
          setTimeout(function () {
            btn.textContent = originalText;
            btn.style.background = '';
            btn.disabled = false;
          }, 3000);
        });
    });
  }

  /**
   * Home Valuation Form
   */
  function initValuationForm() {
    var form = document.getElementById('cb-valuation-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var btn = form.querySelector('.cb-multistep__submit');
      if (!btn) return;

      var originalText = btn.textContent;
      btn.textContent = 'Sending...';
      btn.disabled = true;

      var data = new FormData();
      data.append('action', 'cb_valuation_form');
      data.append('nonce', cbLegacy.nonce);
      data.append('address', (document.getElementById('val-address') || {}).value || '');
      data.append('city', (document.getElementById('val-city') || {}).value || '');
      data.append('zip', (document.getElementById('val-zip') || {}).value || '');
      data.append('name', (document.getElementById('val-name') || {}).value || '');
      data.append('phone', (document.getElementById('val-phone') || {}).value || '');
      data.append('email', (document.getElementById('val-email') || {}).value || '');
      data.append('motivation', (document.getElementById('val-motivation') || {}).value || '');
      data.append('notes', (document.getElementById('val-notes') || {}).value || '');

      fetch(cbLegacy.ajaxUrl, { method: 'POST', body: data })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res.success) {
            btn.innerHTML = '&#10003; Report Requested!';
            btn.style.background = 'var(--cb-navy)';
          } else {
            btn.textContent = res.data.message || 'Error';
            btn.style.background = '#EF4444';
            setTimeout(function () {
              btn.textContent = originalText;
              btn.style.background = '';
              btn.disabled = false;
            }, 3000);
          }
        })
        .catch(function () {
          btn.textContent = 'Error - Please call us';
          setTimeout(function () {
            btn.textContent = originalText;
            btn.style.background = '';
            btn.disabled = false;
          }, 3000);
        });
    });
  }

  /**
   * Rentals / Property Management enquiry form.
   *
   * Serialised from the form itself rather than by looking up each field by id,
   * because the radio that decides where this lead is delivered has no id -- and
   * a hand-written field list is exactly where a new field gets forgotten and
   * silently dropped. FormData(form) carries whatever the form actually holds.
   *
   * Status goes to a live region under the button instead of overwriting the
   * button label. The reply here is a sentence, not two words, and a screen
   * reader should hear it.
   */
  function initPmForm() {
    var form = document.getElementById('cb-pm-inquiry-form');
    if (!form) return;
    var status = document.getElementById('cb-pm-status');

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var btn = form.querySelector('button[type="submit"]');
      var originalText = btn.textContent;
      btn.textContent = 'Sending...';
      btn.disabled = true;
      if (status) { status.textContent = ''; status.style.color = ''; }

      var data = new FormData(form);
      data.append('action', 'cb_pm_form');
      data.append('nonce', cbLegacy.nonce);

      var done = function (msg, ok) {
        btn.textContent = originalText;
        btn.disabled = false;
        if (status) {
          status.textContent = msg;
          status.style.color = ok ? 'var(--cb-navy)' : '#B91C1C';
        }
        if (ok) { form.reset(); }
      };

      fetch(cbLegacy.ajaxUrl, { method: 'POST', body: data })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res && res.success) {
            done((res.data && res.data.message) || 'Thank you — we will be in touch.', true);
          } else {
            done((res && res.data && res.data.message) || 'Something went wrong. Please call (325) 944-9559.', false);
          }
        })
        .catch(function () {
          done('Could not send. Please call (325) 944-9559.', false);
        });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initContactForm();
      initValuationForm();
      initPmForm();
    });
  } else {
    initContactForm();
    initValuationForm();
    initPmForm();
  }
})();
