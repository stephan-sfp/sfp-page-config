/**
 * SFP Page Config - De knop onderaan
 * Sinds 3.0.0
 *
 * Bouwt de knop die op een telefoon of tablet onderaan een dienstpagina in
 * beeld blijft, uit window.sfpStickyConfig:
 *
 *   text    tekst op de knop
 *   doel    form (het formulier op de pagina), pagina of link
 *   href    adres bij pagina of link
 *   target  _self of _blank
 *   ankers  ankers waaraan het formulier op oudere pagina's te herkennen is
 *
 * De plugin vindt zelf waar de knop heen gaat en wanneer hij te zien is:
 *
 *   Het formulier (bij doel form), eerste treffer wint:
 *     1. een onderdeel met een van de bekende ankers;
 *     2. de boekkaart (.sfp-boek-sectie);
 *     3. het eerste formulier in de inhoud, met de sectie eromheen.
 *   Zonder formulier is er geen knop.
 *
 *   De bovenkant van de pagina, eerste treffer wint:
 *     1. het blok Held (.sfp-held);
 *     2. de eerste Spectra-container met een omlijnde knop, anders de
 *        eerste Spectra-container;
 *     3. de eerste sectie van de inhoud;
 *     4. geen bovenkant gevonden: de knop verschijnt na 400 px scrollen.
 *
 * De knop verschijnt als de bovenkant uit beeld is en verdwijnt zolang het
 * formulier in beeld is. Sinds 3.13.0 wijkt hij ook zolang een eigen oproep
 * in de inhoud onderin beeld is: de knop van de Zelftest-oproep of een
 * gevulde knop (geen omlijnde), zodat de balk die niet afdekt.
 *
 * Het id van de balk (sticky-mobile-cta) en de ene link erin zijn een
 * afspraak: het snippet "LatePoint: boekkaart in merkrollen" zoekt ze op.
 *
 * Werkt ook als WP Rocket scripts uitstelt: luistert naar DOMContentLoaded
 * en naar rocket-DOMContentLoaded.
 */
(function () {
    'use strict';

    var initialized = false;

    function inhoud() {
        return document.querySelector('.entry-content') || document.querySelector('main') || document.body;
    }

    /**
     * Zoek het formulier waar de knop heen springt.
     *
     * @param {Object} cfg Instelling.
     * @return {Element|null}
     */
    function vindFormulier(cfg) {
        var i, el;
        var ankers = Array.isArray(cfg.ankers) ? cfg.ankers : [];
        for (i = 0; i < ankers.length; i++) {
            el = ankers[i] ? document.getElementById(ankers[i]) : null;
            if (el) return el;
        }

        el = document.querySelector('.sfp-boek-sectie');
        if (el) {
            if (!el.id) el.id = 'plan-een-gesprek';
            return el;
        }

        var bak = inhoud();
        var form = bak.querySelector(
            '.srfm-form-container, .latepoint-book-form-wrapper, form:not([role="search"]):not(.search-form)'
        );
        if (!form) return null;
        el = form.closest('.sfp-sectie, .wp-block-uagb-container, section');
        if (!el || !bak.contains(el) || el === bak) el = form;
        if (!el.id) el.id = 'sfp-formulier';
        return el;
    }

    /**
     * Zoek de bovenkant van de pagina.
     *
     * @return {Element|null}
     */
    function inBeeldTeKrijgen(el) {
        // Een onderdeel dat op dit scherm verborgen is, komt nooit in beeld.
        return !!el && el.getClientRects().length > 0;
    }

    function vindBovenkant(cfg) {
        var bak = inhoud();

        // Een bovenkant die tot en met 2.12.0 met de hand was aangewezen.
        if (cfg && cfg.hero) {
            try {
                var eigen = document.querySelector(cfg.hero);
                if (inBeeldTeKrijgen(eigen)) return eigen;
            } catch (e) { /* geen geldige selector: verder zoeken */ }
        }

        var held = bak.querySelector('.sfp-held');
        if (inBeeldTeKrijgen(held)) return held;

        var spectra = bak.querySelectorAll(':scope > .wp-block-uagb-container');
        if (!spectra.length) spectra = document.querySelectorAll('.entry-content > .wp-block-uagb-container');
        var i;
        for (i = 0; i < spectra.length; i++) {
            if (inBeeldTeKrijgen(spectra[i]) && spectra[i].querySelector('.ast-outline-button, .wp-block-button.is-style-outline')) {
                return spectra[i];
            }
        }
        for (i = 0; i < spectra.length; i++) {
            if (inBeeldTeKrijgen(spectra[i])) return spectra[i];
        }

        var secties = bak.querySelectorAll(':scope > .sfp-sectie, :scope > section');
        for (i = 0; i < secties.length; i++) {
            if (inBeeldTeKrijgen(secties[i])) return secties[i];
        }
        return null;
    }

    /**
     * Eigen oproepen in de inhoud: de knop van de Zelftest-oproep en gevulde
     * knoppen (core en Spectra), niet omlijnd, niet in de bovenkant of het
     * formulier, niet in kop of voet.
     *
     * @param {Element|null} bovenkant Bovenkant van de pagina.
     * @param {Element|null} formulier Formulier.
     * @return {Element[]}
     */
    function vindKnoppen(bovenkant, formulier) {
        var bak = inhoud();
        var lijst = bak.querySelectorAll(
            '.sfp-zt-cta__knop a, .wp-block-button:not(.is-style-outline) > .wp-block-button__link, a.uagb-buttons-repeater:not(.ast-outline-button)'
        );
        var uit = [];
        for (var i = 0; i < lijst.length; i++) {
            var k = lijst[i];
            if (k.closest('#sticky-mobile-cta, header, footer, .site-header, .site-footer')) continue;
            if (bovenkant && bovenkant.contains(k)) continue;
            if (formulier && formulier.contains(k)) continue;
            if (k.closest('.wp-block-button.is-style-outline, .ast-outline-button')) continue;
            uit.push(k);
        }
        return uit;
    }

    function init() {
        if (initialized) return;
        if (typeof IntersectionObserver === 'undefined') return;

        var cfg = window.sfpStickyConfig;
        if (!cfg) return;

        initialized = true;

        var formulier = null;
        var href = '';

        if (cfg.doel === 'form') {
            formulier = vindFormulier(cfg);
            href = formulier ? '#' + formulier.id : '';
        } else {
            href = cfg.href || '';
        }

        // De balk, alleen als de knop ergens heen kan. De bovenkant wordt
        // hieronder hoe dan ook gezocht: de klasse .sfp-hero-section houdt
        // de omlijnde knop in de bovenkant zichtbaar onder 1024 px.
        var bar = null;
        if (href) {
            bar = document.createElement('div');
            bar.id = 'sticky-mobile-cta';
            bar.className = 'sticky-mobile-cta';

            var link = document.createElement('a');
            // Astra bepaalt maat, ruimte, rand en ronding van .button.
            link.className = 'button';
            link.href = href;
            link.textContent = cfg.text || '';
            if (cfg.target && cfg.target !== '_self') {
                link.target = cfg.target;
                link.rel = 'noopener';
            }
            link.setAttribute('role', 'button');

            bar.appendChild(link);
            document.body.appendChild(bar);
        }

        // De knop verschijnt zodra de bezoeker de bovenkant voorbij is. Dat
        // geldt ook voor wie lager op de pagina binnenkomt (een link met een
        // anker, of een herladen pagina).
        var voorbijBovenkant = false;
        var formulierVisible = false;
        var knopInBeeld = false;

        // Is de balk echt te zien? Een ander script (zoals het LatePoint-
        // snippet bij de boekkaart) kan hem verbergen.
        function balkTeZien() {
            if (!bar || !bar.classList.contains('visible') || !bar.isConnected) return false;
            var stijl = window.getComputedStyle(bar);
            return stijl.display !== 'none' && stijl.visibility !== 'hidden' && parseFloat(stijl.opacity || '1') > 0;
        }

        function zetKlasse() {
            document.documentElement.classList.toggle('sfp-knop-in-beeld', balkTeZien());
        }

        function updateVisibility() {
            if (!bar) return;
            var show = voorbijBovenkant && !formulierVisible && !knopInBeeld;
            bar.classList.toggle('visible', show);
            zetKlasse();
        }

        // Verbergt of verwijdert een ander script de balk, dan gaat de klasse
        // sfp-knop-in-beeld mee weg en schuift de WhatsApp-knop terug.
        if (bar && typeof MutationObserver !== 'undefined') {
            new MutationObserver(zetKlasse).observe(bar, { attributes: true, attributeFilter: ['style', 'class', 'hidden'] });
            new MutationObserver(zetKlasse).observe(document.body, { childList: true });
            bar.addEventListener('transitionend', zetKlasse);
        }

        var bovenkant = vindBovenkant(cfg);
        if (bovenkant && bovenkant !== formulier) {
            bovenkant.classList.add('sfp-hero-section');
            if (!bar) return;
            new IntersectionObserver(function (entries) {
                var laatste = entries[entries.length - 1];
                voorbijBovenkant = !laatste.isIntersecting && laatste.boundingClientRect.bottom <= 0;
                updateVisibility();
            }, { threshold: 0 }).observe(bovenkant);
        } else {
            if (!bar) return;
            var threshold = parseInt(cfg.scrollThreshold, 10) || 400;
            var ticking = false;
            var onScroll = function () {
                if (ticking) return;
                ticking = true;
                requestAnimationFrame(function () {
                    ticking = false;
                    var scrolled = window.pageYOffset || document.documentElement.scrollTop;
                    voorbijBovenkant = scrolled >= threshold;
                    updateVisibility();
                });
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        }

        // Sinds 3.13.0: eigen oproepen en gevulde knoppen in de inhoud, buiten
        // de bovenkant en het formulier. Is er een onderin beeld (het onderste
        // derde van het scherm), dan wijkt de balk.
        var knoppenOnder = 0;
        var knoppen = vindKnoppen(bovenkant, formulier);
        if (knoppen.length) {
            var inZone = typeof WeakSet !== 'undefined' ? new WeakSet() : null;
            var zone = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (!inZone) return;
                    if (e.isIntersecting && !inZone.has(e.target)) { inZone.add(e.target); knoppenOnder++; }
                    else if (!e.isIntersecting && inZone.has(e.target)) { inZone.delete(e.target); knoppenOnder--; }
                });
                knopInBeeld = knoppenOnder > 0;
                updateVisibility();
            }, { rootMargin: '-66% 0px 0px 0px', threshold: 0 });
            knoppen.forEach(function (k) { zone.observe(k); });
        }

        if (formulier) {
            new IntersectionObserver(function (entries) {
                formulierVisible = entries[entries.length - 1].isIntersecting;
                updateVisibility();
            }, { threshold: 0 }).observe(formulier);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.addEventListener('rocket-DOMContentLoaded', init);
})();
