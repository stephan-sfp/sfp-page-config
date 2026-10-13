/**
 * SFP Page Config - Placeholder editor warning
 *
 * After each successful publish/update of a PUBLISHED post, scans the editor
 * content for likely-unreplaced template placeholders and raises a dismissible
 * warning notice in the block editor. Warn only: it never blocks the save.
 *
 * The set of "known" shortcode tags is handed in from PHP via
 * window.sfpPlaceholderConfig, so the client and server agree on what counts
 * as a placeholder. Everything is guarded so a missing API just no-ops.
 */
(function (wp) {
    'use strict';

    if (!wp || !wp.data || typeof wp.data.subscribe !== 'function') {
        return;
    }

    var NOTICE_ID = 'sfp-placeholder-warning';
    var known = (window.sfpPlaceholderConfig && Array.isArray(window.sfpPlaceholderConfig.shortcodes))
        ? window.sfpPlaceholderConfig.shortcodes
        : [];

    /**
     * Alleen de leestekst, gelijk aan sfp_page_config_placeholder_leestekst():
     * zonder scripts, stijlen, HTML-opmerkingen (blokattributen) en tags.
     */
    /**
     * Zonder blokken die met "Blok verbergen" voor iedereen verborgen zijn,
     * gelijk aan sfp_page_config_zonder_verborgen_blokken(). Patroon van
     * @wordpress/block-serialization-default-parser.
     */
    function zonderVerborgen(content) {
        if (content.indexOf('"blockVisibility":false') === -1) {
            return content;
        }
        var patroon = /<!--\s+(\/)?wp:([a-z][a-z0-9_-]*\/)?([a-z][a-z0-9_-]*)\s+({(?:(?=([^}]+|}+(?=})|(?!}\s+\/?-->)[^])*)\5|[^]*?)}\s+)?(\/)?-->/g;
        var weg = [];
        var stapel = [];
        var m;
        while ((m = patroon.exec(content)) !== null) {
            var begin = m.index;
            var eind = begin + m[0].length;
            var naam = (m[2] || '') + m[3];
            if (m[1]) {
                for (var i = stapel.length - 1; i >= 0; i--) {
                    if (stapel[i].naam === naam) {
                        if (stapel[i].verborgen) {
                            weg.push([stapel[i].begin, eind]);
                        }
                        stapel = stapel.slice(0, i);
                        break;
                    }
                }
                continue;
            }
            var verborgen = false;
            if (m[4] && m[4].indexOf('"blockVisibility":false') !== -1) {
                try {
                    var attr = JSON.parse(m[4]);
                    verborgen = !!(attr && attr.metadata && attr.metadata.blockVisibility === false);
                } catch (e) {
                    verborgen = false;
                }
            }
            if (m[6]) {
                if (verborgen) {
                    weg.push([begin, eind]);
                }
                continue;
            }
            stapel.push({ naam: naam, begin: begin, verborgen: verborgen });
        }
        for (var j = 0; j < stapel.length; j++) {
            if (stapel[j].verborgen) {
                weg.push([stapel[j].begin, content.length]);
                break;
            }
        }
        if (!weg.length) {
            return content;
        }
        weg.sort(function (a, b) { return a[0] - b[0]; });
        var uit = '';
        var vanaf = 0;
        weg.forEach(function (stuk) {
            if (stuk[0] < vanaf) {
                return;
            }
            uit += content.slice(vanaf, stuk[0]) + ' ';
            vanaf = stuk[1];
        });
        return uit + content.slice(vanaf);
    }

    function leestekst(content) {
        return zonderVerborgen(content)
            .replace(/<script\b[\s\S]*?<\/script\s*>/gi, ' ')
            .replace(/<style\b[\s\S]*?<\/style\s*>/gi, ' ')
            .replace(/<!--[\s\S]*?-->/g, ' ')
            .replace(/<(?:!--|script\b|style\b)[\s\S]*$/i, ' ')
            .replace(/<[A-Za-z\/!][^<>]*>/g, ' ');
    }

    /**
     * Scan a content string, mirroring the PHP detector:
     *   - [bracketed] tokens starting with a letter that are not a known
     *     shortcode tag (case-sensitive, like shortcode_exists());
     *   - runs of four or more dots.
     */
    function scan(content) {
        var out = [];
        var seen = {};

        if (typeof content !== 'string' || content === '') {
            return out;
        }

        content = leestekst(content);

        var bracket = /\[([A-Za-z][A-Za-z0-9_-]*)[^\]\[]*\]/g;
        var m;
        while ((m = bracket.exec(content)) !== null) {
            if (known.indexOf(m[1]) !== -1) {
                continue;
            }
            var frag = m[0].replace(/\s+/g, ' ').trim();
            if (!seen[frag]) {
                seen[frag] = true;
                out.push(frag);
            }
        }

        var dots = content.match(/\.{4,}/g);
        if (dots) {
            dots.forEach(function (d) {
                if (!seen[d]) {
                    seen[d] = true;
                    out.push(d);
                }
            });
        }

        return out;
    }

    function check() {
        var editor = wp.data.select('core/editor');
        var notices = wp.data.dispatch('core/notices');
        if (!editor || !notices) {
            return;
        }

        // Clear the previous warning first so a fixed page stops nagging.
        if (typeof notices.removeNotice === 'function') {
            notices.removeNotice(NOTICE_ID);
        }

        // Only warn for content that is actually live.
        if ('publish' !== editor.getEditedPostAttribute('status')) {
            return;
        }

        var found = scan(editor.getEditedPostContent() || '');
        if (!found.length) {
            return;
        }

        notices.createWarningNotice(
            'Let op: mogelijk niet-vervangen invulplekken op deze gepubliceerde pagina: ' + found.join(', ') + '. Controleer dit voordat bezoekers het zien.',
            { id: NOTICE_ID, isDismissible: true }
        );
    }

    // Fire once a save transitions from "saving" back to "done".
    //
    // Two guards keep this from recursing. check() dispatches to
    // core/notices, and every dispatch re-runs this listener synchronously.
    // So wasSaving must be updated BEFORE check() runs, otherwise the
    // re-entrant call still sees the same "just finished saving" edge and
    // calls check() again, until the call stack overflows. The isChecking
    // flag is a second line of defence for registries that batch or defer
    // the state update.
    var wasSaving = false;
    var isChecking = false;
    wp.data.subscribe(function () {
        var editor = wp.data.select('core/editor');
        if (!editor || typeof editor.isSavingPost !== 'function') {
            return;
        }

        var saving       = editor.isSavingPost() && !editor.isAutosavingPost();
        var justFinished = wasSaving && !saving;

        wasSaving = saving;

        if (!justFinished || isChecking) {
            return;
        }

        isChecking = true;
        try {
            check();
        } finally {
            isChecking = false;
        }
    });
})(window.wp);
