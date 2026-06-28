/**
 * WP Acessível JINC — Frontend Accessibility Bar (Vanilla JS)
 *
 * Toggles accessibility classes on <html>, updates ARIA attributes,
 * and persists state in localStorage.
 *
 * @spec-ref Phase 3 — Frontend Accessibility Bar
 */
(function () {
  'use strict';

  // ── Constants ──

  var STORAGE_KEY_CONTRAST = 'jinc_high_contrast';
  var STORAGE_KEY_FONTSIZE = 'jinc_large_font';
  var CLASS_CONTRAST = 'jinc-high-contrast';
  var CLASS_FONTSIZE = 'jinc-large-font';

  // ── State Management ──

  /**
   * Read a boolean flag from localStorage.
   * @param {string} key
   * @returns {boolean}
   */
  function getStoredState(key) {
    try {
      return localStorage.getItem(key) === 'true';
    } catch (e) {
      // localStorage may be unavailable (private browsing, disabled, etc.)
      return false;
    }
  }

  /**
   * Write a boolean flag to localStorage.
   * @param {string} key
   * @param {boolean} value
   */
  function setStoredState(key, value) {
    try {
      localStorage.setItem(key, value ? 'true' : 'false');
    } catch (e) {
      // Silently fail if localStorage is unavailable
    }
  }

  // ── DOM Helpers ──

  /**
   * Toggle a class on <html> and update a button's aria-pressed attribute.
   * @param {HTMLElement} button
   * @param {string} className
   * @param {string} storageKey
   */
  function toggleFeature(button, className, storageKey) {
    var htmlEl = document.documentElement;
    var isActive = htmlEl.classList.toggle(className);

    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    setStoredState(storageKey, isActive);
  }

  /**
   * Restore a feature's state from localStorage on page load.
   * @param {HTMLElement|null} button
   * @param {string} className
   * @param {string} storageKey
   */
  function restoreFeature(button, className, storageKey) {
    var isActive = getStoredState(storageKey);

    if (isActive) {
      document.documentElement.classList.add(className);
    }

    if (button) {
      button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    }
  }

  // ── Initialization ──

  function init() {
    var contrastBtn = document.getElementById('jinc-toggle-contrast');
    var fontsizeBtn = document.getElementById('jinc-toggle-fontsize');

    // Restore saved state before any user interaction
    restoreFeature(contrastBtn, CLASS_CONTRAST, STORAGE_KEY_CONTRAST);
    restoreFeature(fontsizeBtn, CLASS_FONTSIZE, STORAGE_KEY_FONTSIZE);

    // Bind click handlers
    if (contrastBtn) {
      contrastBtn.addEventListener('click', function () {
        toggleFeature(contrastBtn, CLASS_CONTRAST, STORAGE_KEY_CONTRAST);
      });
    }

    if (fontsizeBtn) {
      fontsizeBtn.addEventListener('click', function () {
        toggleFeature(fontsizeBtn, CLASS_FONTSIZE, STORAGE_KEY_FONTSIZE);
      });
    }

    // Skip-to-content: ensure target exists, add fallback anchor
    var skipLink = document.getElementById('jinc-skip-link');
    if (skipLink) {
      skipLink.addEventListener('click', function (e) {
        var targetId = skipLink.getAttribute('href');
        if (!targetId) return;

        // Try #main first, then #content
        var target = document.querySelector(targetId);
        if (!target) {
          target = document.getElementById('content');
        }

        // Last resort: find the first <main> element
        if (!target) {
          target = document.querySelector('main');
        }

        if (target) {
          e.preventDefault();
          // Ensure the target can receive focus
          if (!target.hasAttribute('tabindex')) {
            target.setAttribute('tabindex', '-1');
          }
          target.focus();
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    }
  }

  // ── Boot ──

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
