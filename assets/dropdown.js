/**
 * SFP Page Config - Groep Dropdown Populator
 * Version: 1.9.5
 *
 * Populates any dropdown with CSS class `sfp-startmoment-select`
 * from window.sfpCourseData. Handles SureForms/Tom Select timing,
 * including WP Rocket delay-JS which defers script execution until
 * first user interaction.
 *
 * Strategy:
 *   1. If no sfpCourseData: hide the dropdown and show a fallback message.
 *   2. Fill native <option> elements once (before SureForms runs).
 *   3. Listen for Tom Select initialisation via MutationObserver.
 *   4. Patch Tom Select render templates when it appears.
 *   5. Guard against re-entrant observer loops by tracking fill state.
 */
(function () {
    'use strict';

    var PLACEHOLDER = 'Kies een groep';
    var nativeOptionsFilled = false;  // True once native <option>s are set.
    var tomSelectPatched = false;
    var isPatching = false; // Guard flag: true during any DOM modification.
    var dropdownsHidden = false; // True once dropdowns are hidden (no data).

    function hideDropdowns() {
        if (dropdownsHidden) return;
        var ctaColor = (window.sfpDropdownConfig && window.sfpDropdownConfig.ctaColor) || '#ff5a06';
        var containers = document.querySelectorAll('.sfp-startmoment-select');
        containers.forEach(function (container) {
            // Find the SureForms field wrapper (parent of the dropdown).
            var wrapper = container.closest('.srfm-block');
            var target = wrapper || container;
            // Replace with a styled fallback message in the brand colour.
            var msg = document.createElement('p');
            msg.className = 'sfp-no-coursedata-notice';
            msg.style.cssText = 'font-style:italic;color:' + ctaColor + ';margin:1em 0;';
            msg.textContent = 'Neem contact op voor de eerstvolgende startdata.';
            target.parentNode.insertBefore(msg, target);
            target.style.display = 'none';
        });
        dropdownsHidden = true;
    }

    function buildOptions(data) {
        var options = [];
        for (var i = 0; i < data.length; i++) {
            var sm = data[i];
            var label = sm.label;
            if (!label || typeof label === 'undefined') {
                label = 'Groep ' + (sm.index || (i + 1));
                if (sm.dates && sm.dates.length) {
                    label += ': ' + sm.dates.join(' \u2022 ');
                }
            }
            options.push({
                value: sm.value || ('groep-' + (i + 1)),
                label: label
            });
        }
        return options;
    }

    function fillNativeSelect(select, options) {
        isPatching = true;
        select.innerHTML = '';
        var ph = document.createElement('option');
        ph.value = '';
        ph.textContent = PLACEHOLDER;
        ph.disabled = true;
        ph.selected = true;
        select.appendChild(ph);
        for (var i = 0; i < options.length; i++) {
            var opt = document.createElement('option');
            opt.value = options[i].value;
            opt.textContent = options[i].label;
            select.appendChild(opt);
        }
        isPatching = false;
    }

    function patchTomSelect(ts, options) {
        isPatching = true;

        // Override SureForms' render templates to not use e.icon.
        ts.settings.render.option = function (data, escape) {
            if (data.value === '') {
                return '<div style="display:none;"></div>';
            }
            return '<div><span>' + escape(data.text) + '</span></div>';
        };
        ts.settings.render.item = function (data, escape) {
            return '<div><span>' + escape(data.text) + '</span></div>';
        };

        ts.settings.placeholder = PLACEHOLDER;
        if (ts.control_input) {
            ts.control_input.setAttribute('placeholder', PLACEHOLDER);
        }

        ts.clear(true);
        ts.clearOptions();

        for (var i = 0; i < options.length; i++) {
            ts.addOption({ value: options[i].value, text: options[i].label, icon: '' });
        }
        ts.refreshOptions(false);

        isPatching = false;
    }

    function run() {
        var data = window.sfpCourseData;
        if (!data || !data.length) {
            hideDropdowns();
            return;
        }

        var options = buildOptions(data);
        var containers = document.querySelectorAll('.sfp-startmoment-select');
        if (!containers.length) return;

        containers.forEach(function (container) {
            var select = container.tagName === 'SELECT'
                ? container
                : container.querySelector('select');
            if (!select) return;

            // Fill native options ONCE. Skip if already done to prevent
            // MutationObserver re-entrance (observer callbacks fire async,
            // after isPatching is already reset to false).
            if (!nativeOptionsFilled) {
                fillNativeSelect(select, options);
            }

            var ts = select.tomselect;
            if (ts && !tomSelectPatched) {
                patchTomSelect(ts, options);
                tomSelectPatched = true;
            }
        });

        // Mark native options as filled after processing all containers.
        nativeOptionsFilled = true;
    }

    // Phase 1: Fill native <option>s as early as possible.
    // Listen to both standard and WP Rocket delayed events.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
    // WP Rocket delay-JS dispatches its own DOMContentLoaded after
    // delayed scripts have executed. Listen for it so we can patch
    // Tom Select once SureForms has initialised it.
    window.addEventListener('rocket-DOMContentLoaded', run);

    // MutationObserver: catch SureForms initialising Tom Select.
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function () {
            // Skip mutations caused by our own DOM changes.
            if (isPatching) return;

            // Once Tom Select is patched, only re-patch if SureForms
            // has reset it (options missing the icon property).
            if (tomSelectPatched) {
                var sel = document.querySelector('.sfp-startmoment-select select');
                if (sel && sel.tomselect) {
                    var ts = sel.tomselect;
                    var keys = Object.keys(ts.options);
                    var needsPatch = false;
                    if (keys.length === 0) {
                        needsPatch = true;
                    } else {
                        for (var k = 0; k < keys.length; k++) {
                            if (!('icon' in ts.options[keys[k]])) {
                                needsPatch = true;
                                break;
                            }
                        }
                    }
                    if (needsPatch) {
                        var data = window.sfpCourseData;
                        if (data && data.length) {
                            patchTomSelect(ts, buildOptions(data));
                        }
                    }
                }
            } else {
                // Native options already filled; only try to attach to
                // Tom Select if it has appeared since last check.
                var sel2 = document.querySelector('.sfp-startmoment-select select');
                if (sel2 && sel2.tomselect) {
                    var data2 = window.sfpCourseData;
                    if (data2 && data2.length) {
                        var opts = buildOptions(data2);
                        patchTomSelect(sel2.tomselect, opts);
                        tomSelectPatched = true;
                    }
                }
                // Do NOT call run() here. Native options are already filled
                // (nativeOptionsFilled is true). Calling run() would be a
                // no-op for native options but would still trigger the
                // observer check above, which is harmless. However, NOT
                // calling run() avoids any risk of re-entrant loops.
            }
        });

        // Narrow the observer scope to form containers.
        var formContainers = document.querySelectorAll('.srfm-section, .sureforms-form, form');
        if (formContainers.length) {
            formContainers.forEach(function(fc) {
                observer.observe(fc, { childList: true, subtree: true });
            });
        } else {
            // Fallback: observe body, childList only (no subtree).
            observer.observe(document.body || document.documentElement, { childList: true, subtree: false });
        }

        // Disconnect observer after 30 seconds.
        setTimeout(function() { observer.disconnect(); }, 30000);
    }
})();
