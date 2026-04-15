/**
 * SFP Page Config - Promo Frequency, Conflict Resolution & GTM Classes
 *
 * Controls Convert Pro popup/infobar behaviour:
 *   - Scroll gate: popups visible only after 30% scroll.
 *   - Frequency: localStorage-based cooldown per style ID.
 *   - Conflict resolution: max 1 popup + 1 infobar visible at once.
 *   - GTM body classes for tracking.
 */
(function () {
    'use strict';

    /* =====================================================================
     * Configuration
     *
     * Defaults are overridden by window.sfpPromoConfig, which is injected
     * from PHP (see includes/body-class.php) with values from the
     * Instellingen tab.
     * =================================================================== */

    var cfg = window.sfpPromoConfig || {};
    var SCROLL_GATE_PERCENT = typeof cfg.scrollGate === 'number' ? cfg.scrollGate : 30;
    var COOLDOWN_HOURS      = typeof cfg.cooldownHours === 'number' ? cfg.cooldownHours : 24;
    var STORAGE_PREFIX      = 'sfp_promo_';

    /* =====================================================================
     * Helpers
     * =================================================================== */

    function getScrollPercent() {
        var h = document.documentElement;
        var b = document.body;
        var st = h.scrollTop || b.scrollTop;
        var sh = h.scrollHeight || b.scrollHeight;
        var ch = h.clientHeight;
        return (st / (sh - ch)) * 100;
    }

    function setCooldown(styleId) {
        try {
            localStorage.setItem(
                STORAGE_PREFIX + styleId,
                Date.now().toString()
            );
        } catch (e) {
            // localStorage unavailable; skip silently.
        }
    }

    function isInCooldown(styleId) {
        try {
            var stored = localStorage.getItem(STORAGE_PREFIX + styleId);
            if (!stored) return false;
            var elapsed = Date.now() - parseInt(stored, 10);
            return elapsed < COOLDOWN_HOURS * 3600 * 1000;
        } catch (e) {
            return false;
        }
    }

    /* =====================================================================
     * Scroll gate
     * =================================================================== */

    var scrollGatePassed = false;

    function checkScrollGate() {
        if (scrollGatePassed) return;
        if (getScrollPercent() >= SCROLL_GATE_PERCENT) {
            scrollGatePassed = true;
            document.body.classList.add('sfp-scroll-gate-passed');
        }
    }

    var scrollTicking = false;
    window.addEventListener('scroll', function() {
        if (!scrollTicking) {
            requestAnimationFrame(function() {
                checkScrollGate();
                scrollTicking = false;
            });
            scrollTicking = true;
        }
    }, { passive: true });
    checkScrollGate(); // Check immediately in case page is already scrolled.

    /* =====================================================================
     * Conflict resolution + frequency
     *
     * MutationObserver watches for Convert Pro elements being inserted.
     * Each popup/infobar is identified by its data-style attribute.
     * =================================================================== */

    document.addEventListener('DOMContentLoaded', function () {

        var activePopup = null;
        var activeInfobar = null;

        function resolveConflicts() {
            // Check if activePopup is still in the DOM
            if (activePopup && !document.body.contains(activePopup)) {
                activePopup = null;
            }

            // Popups: .cp-popup-container
            var popups = document.querySelectorAll('.cp-popup-container');
            popups.forEach(function (el) {
                var styleId = el.getAttribute('data-style') || '';

                // Frequency check.
                if (isInCooldown(styleId)) {
                    el.style.display = 'none';
                    return;
                }

                // Scroll gate.
                if (!scrollGatePassed) {
                    el.style.display = 'none';
                    return;
                }

                // Conflict: only 1 popup at a time.
                if (activePopup && activePopup !== el) {
                    el.style.display = 'none';
                    return;
                }

                activePopup = el;
                setCooldown(styleId);
            });

            // Check if activeInfobar is still in the DOM
            if (activeInfobar && !document.body.contains(activeInfobar)) {
                activeInfobar = null;
            }

            // Infobars: .cp-info-bar-container
            var infobars = document.querySelectorAll('.cp-info-bar-container');
            infobars.forEach(function (el) {
                var styleId = el.getAttribute('data-style') || '';

                if (isInCooldown(styleId)) {
                    el.style.display = 'none';
                    return;
                }

                if (activeInfobar && activeInfobar !== el) {
                    el.style.display = 'none';
                    return;
                }

                activeInfobar = el;
                setCooldown(styleId);
            });
        }

        // Watch for Convert Pro injecting elements.
        var resolving = false;
        var observer = new MutationObserver(function () {
            if (resolving) return; // Skip mutations caused by our own style changes.
            resolving = true;
            resolveConflicts();
            resolving = false;
        });

        observer.observe(document.body, {
            childList: true,
            subtree: false,
        });

        // Disconnect observer after 30 seconds (Convert Pro should be done by then).
        setTimeout(function () { observer.disconnect(); }, 30000);

        // Run once on load in case elements already exist.
        resolveConflicts();

        /* =================================================================
         * GTM body classes
         * =============================================================== */

        if (window.sfpCoursePromo) {
            document.body.classList.add('has-course-promo');
        }
        if (window.sfpStickyConfig) {
            document.body.classList.add('has-sticky-cta');
        }
    });
})();
