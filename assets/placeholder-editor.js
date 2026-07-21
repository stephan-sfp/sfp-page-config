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

        var bracket = /\[([A-Za-z][A-Za-z0-9_-]*)[^\]\[]*\]/g;
        var m;
        while ((m = bracket.exec(content)) !== null) {
            if (known.indexOf(m[1]) !== -1) {
                continue;
            }
            var frag = m[0].trim();
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
    var wasSaving = false;
    wp.data.subscribe(function () {
        var editor = wp.data.select('core/editor');
        if (!editor || typeof editor.isSavingPost !== 'function') {
            return;
        }
        var saving = editor.isSavingPost() && !editor.isAutosavingPost();
        if (wasSaving && !saving) {
            check();
        }
        wasSaving = saving;
    });
})(window.wp);
