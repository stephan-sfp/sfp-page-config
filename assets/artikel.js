/* SFP Page Config: artikelopmaak. Panelen, link kopiëren, warming-up, inhoudsopgave en hoofdstukbalk. Zonder dit script blijft het artikel volledig leesbaar. */
(function () {
    'use strict';
    var doc = document, root = doc.documentElement;
    function alle(sel, ctx) { return [].slice.call((ctx || doc).querySelectorAll(sel)); }
    function el(id) { return id ? doc.getElementById(id) : null; }

    /* Panelen: een knop met data-sfp-paneel opent het paneel uit aria-controls; binnen een groep is er één tegelijk open. */
    var knoppen = alle('[data-sfp-paneel]');
    function zet(knop, open) {
        var paneel = el(knop.getAttribute('aria-controls'));
        if (!paneel) { return; }
        paneel.hidden = !open;
        knop.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    knoppen.forEach(function (knop) {
        knop.addEventListener('click', function () {
            var open = knop.getAttribute('aria-expanded') !== 'true';
            var groep = knop.getAttribute('data-sfp-groep');
            if (groep) {
                knoppen.forEach(function (ander) {
                    if (ander !== knop && ander.getAttribute('data-sfp-groep') === groep) { zet(ander, false); }
                });
            }
            zet(knop, open);
        });
    });

    /* Link kopiëren. */
    alle('[data-sfp-kopieer]').forEach(function (knop) {
        var label = knop.querySelector('span');
        var tekst = label ? label.textContent : '';
        knop.addEventListener('click', function () {
            var url = knop.getAttribute('data-sfp-kopieer');
            function klaar() {
                if (!label) { return; }
                label.textContent = knop.getAttribute('data-sfp-klaar') || tekst;
                setTimeout(function () { label.textContent = tekst; }, 2000);
            }
            function oud() {
                var veld = doc.createElement('textarea');
                veld.value = url;
                veld.setAttribute('readonly', '');
                veld.style.cssText = 'position:fixed;top:0;left:0;opacity:0';
                doc.body.appendChild(veld);
                veld.select();
                try { if (doc.execCommand('copy')) { klaar(); } } catch (e) {}
                doc.body.removeChild(veld);
                knop.focus();
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(klaar, oud);
            } else {
                oud();
            }
        });
    });

    /* Warming-up. */
    alle('.sfp-art-warm').forEach(function (warm) {
        var vragen = alle('.sfp-art-warm__vraag', warm);
        var volgende = warm.querySelector('.sfp-art-warm__volgende');
        var stippen = alle('.sfp-art-warm__stip i', warm);
        var nr = warm.querySelector('.sfp-art-warm__nr');
        var nu = 0;
        if (!vragen.length || !volgende) { return; }
        vragen.forEach(function (vraag) {
            var opties = alle('.sfp-art-warm__opties button', vraag);
            opties.forEach(function (optie) {
                optie.addEventListener('click', function () {
                    opties.forEach(function (o) { o.setAttribute('aria-pressed', 'false'); });
                    optie.setAttribute('aria-pressed', 'true');
                    var hint = vraag.querySelector('.sfp-art-warm__hint');
                    if (hint) { hint.hidden = false; }
                    volgende.textContent = warm.getAttribute(nu < vragen.length - 1 ? 'data-sfp-volgende' : 'data-sfp-start');
                    volgende.hidden = false;
                });
            });
        });
        volgende.addEventListener('click', function () {
            if (nu < vragen.length - 1) {
                vragen[nu].hidden = true;
                nu++;
                vragen[nu].hidden = false;
                if (stippen[nu]) { stippen[nu].classList.add('aan'); }
                if (nr) { nr.textContent = String(nu + 1); }
                volgende.hidden = true;
                var eerste = vragen[nu].querySelector('button');
                if (eerste) { eerste.focus(); }
                return;
            }
            var paneel = warm.closest('.sfp-art-paneel');
            knoppen.forEach(function (knop) {
                if (paneel && knop.getAttribute('aria-controls') === paneel.id) { zet(knop, false); }
            });
            var begin = el(warm.getAttribute('data-sfp-begin'));
            if (begin) { begin.scrollIntoView(); }
        });
    });

    /* Inhoudsopgave en hoofdstukbalk. */
    var artikel = doc.querySelector('.sfp-artikel');
    if (!artikel) { return; }
    var balk = el('sfp-art-balk');
    var naSprong = function () {};

    /* Links naar een anker in het artikel (inhoudsopgave, hoofdstukbalk, warming-up, affiliatemelding): zelf springen, zodat het doel altijd onder een vaste header uitkomt. De afstand regelt scroll-margin-top in de CSS. */
    doc.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return; }
        var a = e.target.closest ? e.target.closest('a[href^="#"]') : null;
        if (!a || !(artikel.contains(a) || (balk && balk.contains(a)))) { return; }
        var id = a.getAttribute('href').slice(1);
        var doel = null;
        try { doel = el(decodeURIComponent(id)); } catch (fout) { doel = el(id); }
        if (!doel || !artikel.contains(doel)) { return; }
        e.preventDefault();
        e.stopPropagation();
        if (doel.tagName === 'DETAILS') { doel.open = true; }
        naSprong();
        doel.scrollIntoView();
        if (window.history && history.replaceState) { history.replaceState(null, '', '#' + id); }
        naSprong();
    }, true);
    var toc = alle('.sfp-art-toc a');
    var bron = balk ? alle('.sfp-art-balk__lijst a', balk) : toc;
    var koppen = [];
    bron.forEach(function (a) {
        var kop = el(decodeURIComponent((a.getAttribute('href') || '').slice(1)));
        if (kop) { koppen.push(kop); }
    });
    if (koppen.length < 2) { return; }

    var lijst = balk ? el('sfp-art-balk-lijst') : null;
    var lijstLinks = lijst ? alle('a', lijst) : [];
    var titelKnop = balk ? balk.querySelector('.sfp-art-balk__titel') : null;
    var titel = titelKnop ? titelKnop.querySelector('span') : null;
    var pijlen = balk ? alle('[data-sfp-stap]', balk) : [];
    var kopkaart = artikel.querySelector('.sfp-art-kop') || artikel.querySelector('.sfp-art-titel');
    var rustTitel = balk ? (balk.getAttribute('aria-label') || '') : '';
    var actief = -2, zichtbaar = null, offset = -1, wacht = false;

    /* Hoogte van een vaste header (en de beheerbalk), zodat ankers en de rechterkolom eronder uitkomen. */
    function meetOffset() {
        var hoogte = 0;
        alle('#wpadminbar,#ast-fixed-header,.ast-sticky-active,.main-header-bar-wrap.ast-sticky-active,.site-header').forEach(function (h) {
            var stijl = getComputedStyle(h);
            if (stijl.position !== 'fixed' && stijl.position !== 'sticky') { return; }
            if (stijl.display === 'none' || stijl.visibility === 'hidden') { return; }
            var r = h.getBoundingClientRect();
            if (r.height > 0 && r.top <= 1 && r.bottom > hoogte && r.bottom < window.innerHeight / 2) { hoogte = r.bottom; }
        });
        hoogte = Math.round(hoogte);
        if (hoogte !== offset) {
            offset = hoogte;
            root.style.setProperty('--sfp-art-kop-offset', hoogte + 'px');
        }
    }

    function markeer(links, i) {
        links.forEach(function (a, n) {
            var aan = n === i;
            a.classList.toggle('sfp-actief', aan);
            if (aan) { a.setAttribute('aria-current', 'true'); } else { a.removeAttribute('aria-current'); }
        });
    }

    function sluitLijst(focus) {
        if (!lijst || lijst.hidden) { return; }
        lijst.hidden = true;
        titelKnop.setAttribute('aria-expanded', 'false');
        if (focus) { titelKnop.focus(); }
    }

    function bij() {
        wacht = false;
        meetOffset();
        var grens = offset + 120, i = -1;
        for (var n = 0; n < koppen.length; n++) {
            if (koppen[n].getBoundingClientRect().top <= grens) { i = n; } else { break; }
        }
        if (i !== actief) {
            actief = i;
            markeer(toc, i < 0 ? 0 : i);
            markeer(lijstLinks, i);
            if (titel) { titel.textContent = i < 0 ? rustTitel : koppen[i].textContent; }
            pijlen.forEach(function (p) {
                var doel = i + parseInt(p.getAttribute('data-sfp-stap'), 10);
                p.disabled = doel < 0 || doel >= koppen.length;
            });
        }
        if (balk) {
            /* Zichtbaar zolang de lezer in het artikel is: voorbij de kopkaart, en tot het einde van het artikel in beeld komt (zo blijft alles eronder vrij). */
            var toon = kopkaart.getBoundingClientRect().bottom < offset && artikel.getBoundingClientRect().bottom > window.innerHeight;
            if (toon !== zichtbaar) {
                zichtbaar = toon;
                if (!toon) { sluitLijst(false); }
                balk.hidden = !toon;
                doc.body.classList.toggle('sfp-art-balk-aan', toon);
            }
        }
    }
    function plan() {
        if (!wacht) { wacht = true; window.requestAnimationFrame(bij); }
    }

    naSprong = function () { sluitLijst(false); plan(); };

    function ga(i) {
        if (i < 0 || i >= koppen.length) { return; }
        sluitLijst(false);
        koppen[i].scrollIntoView();
        if (window.history && history.replaceState) { history.replaceState(null, '', '#' + koppen[i].id); }
        plan();
    }

    if (balk) {
        pijlen.forEach(function (p) {
            p.addEventListener('click', function () { ga(actief + parseInt(p.getAttribute('data-sfp-stap'), 10)); });
        });
        titelKnop.addEventListener('click', function () {
            var open = lijst.hidden;
            lijst.hidden = !open;
            titelKnop.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) {
                var nu = lijst.querySelector('.sfp-actief');
                if (nu && nu.scrollIntoView) { nu.scrollIntoView({ block: 'nearest' }); }
            }
        });
        doc.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !lijst.hidden) { sluitLijst(true); }
        });
        doc.addEventListener('click', function (e) {
            if (!lijst.hidden && !balk.contains(e.target)) { sluitLijst(false); }
        });
    }

    window.addEventListener('scroll', plan, { passive: true });
    window.addEventListener('resize', plan);
    window.addEventListener('load', function () {
        bij();
        /* Een directe link naar een hoofdstuk: opnieuw uitlijnen nu de hoogte van de header bekend is. */
        var doel = location.hash.length > 1 ? el(decodeURIComponent(location.hash.slice(1))) : null;
        if (doel && artikel.contains(doel) && offset > 0) { doel.scrollIntoView(); }
    });
    bij();
})();
