/**
 * SFP Page Config - Sitewide Sticky CTA
 * Version: 1.9.6
 *
 * Builds a sticky CTA bar based on window.sfpStickyConfig.
 * Uses dual IntersectionObservers:
 *   - Appears when the hero CTA scrolls out of view.
 *   - Disappears when the form/anchor section scrolls into view.
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

        // Observe hero CTA and mark it for CSS targeting.
        var heroCTA = document.querySelector(cfg.hero || '.uagb-block-0b4df88b');
        if (heroCTA) {
            heroCTA.classList.add('sfp-hero-section');
            new IntersectionObserver(function (entries) {
                heroVisible = entries[0].isIntersecting;
                if (heroVisible) heroEverSeen = true;
                updateVisibility();
            }, { threshold: 0 }).observe(heroCTA);
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
