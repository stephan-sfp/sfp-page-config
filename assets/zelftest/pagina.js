/*
 * SFP Page Config - Zelftest: het kruisje op de testpagina (sinds 3.5.0)
 *
 * Terug naar de pagina op deze site waar de bezoeker vandaan kwam, anders
 * naar de homepage. Midden in de test (scherm "vraag") eerst de vraag of de
 * bezoeker wil stoppen.
 */
(function () {
	'use strict';

	function start() {
		var knop = document.getElementById('sfp-zt-sluit');
		if (!knop) return;
		var home = knop.getAttribute('data-home') || '/';
		var sleutel = 'sfp-zt-terug:' + location.pathname;

		/* De vorige pagina op deze site onthouden; een herlaadbeurt of een stap binnen de test overschrijft hem niet. */
		var terug = '';
		try {
			var ref = document.referrer ? new URL(document.referrer) : null;
			if (ref && ref.origin === location.origin && ref.pathname !== location.pathname) {
				terug = ref.href;
				sessionStorage.setItem(sleutel, terug);
			} else {
				terug = sessionStorage.getItem(sleutel) || '';
			}
		} catch (e) { terug = ''; }
		var doel = terug || home;

		var dialoog = document.getElementById('sfp-zt-stop');
		var weg = function () { window.location.href = doel; };
		var bezig = function () {
			var test = document.getElementById('sfp-zelftest');
			return !!(test && (test.getAttribute('data-scherm') === 'vraag' || test.getAttribute('data-scherm') === 'overzicht'));
		};

		knop.addEventListener('click', function () {
			if (bezig() && dialoog && typeof dialoog.showModal === 'function') {
				dialoog.showModal();
				var nee = document.getElementById('sfp-zt-stop-nee');
				if (nee) nee.focus();
				return;
			}
			weg();
		});
		var ja = document.getElementById('sfp-zt-stop-ja');
		var nee = document.getElementById('sfp-zt-stop-nee');
		if (ja) ja.addEventListener('click', weg);
		if (nee && dialoog) nee.addEventListener('click', function () { dialoog.close(); knop.focus(); });
		if (dialoog) dialoog.addEventListener('click', function (e) { if (e.target === dialoog) dialoog.close(); });
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
