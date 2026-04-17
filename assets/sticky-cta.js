/**
 * SFP Page Config - Sitewide Sticky CTA
 * Version: 2.5.0
 *
 * Builds a sticky CTA bar based on window.sfpStickyConfig.
 *
 * Hero detection (three layers, first match wins):
 *   1. Manual override: cfg.hero selector from admin settings.
 *   2. Auto-detect: first top-level Spectra container in .entry-content.
 *   3. Scroll fallback: show after cfg.scrollThreshold (default 400px).
 *
 * When a hero element is found, dual IntersectionObservers control
 * visibility:
 *   - Appears when the hero section scrolls out of view.
 *   - Disappears when the anchor/form section scrolls into view.
 *
 * Supports WP Rocket delay-JS by listening for both standard
 * DOMContentLoaded and rocket-DOMContentLoaded.
 */
(function () {
    'use strict';

    var initialized = false;

    function init() {
        if (initialized) return;

        if (typeof IntersectionObserver === 'undefined') return;

        var cfg = window.sfpStickyConfig;
        if (!cfg) return;

        initialized = true;

        // Build the sticky bar.
        var bar = document.createElement('div');
        bar.id = 'sticky-mobile-cta';
        bar.className = 'sticky-mobile-cta';

        var link = document.createElement('a');
        link.href = cfg.href;
        link.textContent = cfg.text;
        if (cfg.target) link.target = cfg.target;

        link.setAttribute('role', 'button');

        bar.appendChild(link);
        document.body.appendChild(bar);

        // Visibility state.
        var heroEverSeen = false;
        var heroVisible = false;
        var anchorVisible = false;

        function updateVisibility() {
            var show = heroEverSeen && !heroVisible && !anchorVisible;
            bar.classList.toggle('visible', show);
        }

        // --- Hero detection: three layers ---

        var heroCTA = null;

        // Layer 1: manual override from admin settings.
        if (cfg.hero && cfg.hero.length > 0) {
            try {
                heroCTA = document.querySelector(cfg.hero);
            } catch (e) {
                // Invalid selector; fall through to auto-detect.
                heroCTA = null;
            }
        }

        // Layer 2: auto-detect first top-level Spectra container.
        if (!heroCTA) {
            heroCTA = document.querySelector(
                '.entry-content > .wp-block-uagb-container'
            );
        }

        if (heroCTA) {
            // Mark the hero for CSS targeting (outline-button visibility).
            heroCTA.classList.add('sfp-hero-section');

            new IntersectionObserver(function (entries) {
                heroVisible = entries[0].isIntersecting;
                if (heroVisible) heroEverSeen = true;
                updateVisibility();
            }, { threshold: 0 }).observe(heroCTA);
        } else {
            // Layer 3: scroll-threshold fallback.
            // No hero element found; show after scrolling past threshold.
            var threshold = parseInt(cfg.scrollThreshold, 10) || 400;
            var ticking = false;

            function onScroll() {
                if (ticking) return;
                ticking = true;
                requestAnimationFrame(function () {
                    ticking = false;
                    var scrolled = window.pageYOffset || document.documentElement.scrollTop;
                    var pastThreshold = scrolled >= threshold;
                    if (pastThreshold && !heroEverSeen) {
                        heroEverSeen = true;
                    }
                    heroVisible = !pastThreshold;
                    updateVisibility();
                });
            }

            window.addEventListener('scroll', onScroll, { passive: true });
            // Run once to set initial state.
            onScroll();
        }

        // Observe anchor (form section).
        var anchor = cfg.anchor ? document.getElementById(cfg.anchor) : null;
        if (anchor) {
            new IntersectionObserver(function (entries) {
                anchorVisible = entries[0].isIntersecting;
                updateVisibility();
            }, { threshold: 0 }).observe(anchor);
        }
    }

    // Run on standard DOMContentLoaded or immediately if already loaded.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // WP Rocket delay-JS dispatches its own event after deferred scripts run.
    window.addEventListener('rocket-DOMContentLoaded', init);
})();
