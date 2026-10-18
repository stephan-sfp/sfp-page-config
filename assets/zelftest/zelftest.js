/**
 * SFP Page Config - Zelftest (de test op de pagina)
 *
 * Schermen: start (voornaam, e-mail, nieuwsbrief, lang of kort) -> vragen ->
 * eindscherm (het resultaat van het label, de rest wazig, de melding dat het
 * volledige resultaat in de mailbox staat).
 *
 * Alle teksten komen uit het pakket (window.sfpZelftestConfig.inhoud). De
 * rekenregel is window.sfpZelftestMotor (motor.js van het label), het label
 * levert CRM-velden, notitie en het resultaat (window.sfpZelftestLabel).
 *
 * Twee verzendingen naar de site: bij de start en bij het afronden. De site
 * stuurt door naar het CRM; geen webhookadres in deze pagina.
 *
 * Knoppen werken op klik en Enter, nooit via het versturen van een formulier.
 */
(function () {
	'use strict';

	function start() {
		var C = window.sfpZelftestConfig, M = window.sfpZelftestMotor, L = window.sfpZelftestLabel;
		var wortel = document.getElementById('sfp-zelftest');
		if (!C || !M || !L || !wortel || typeof M.bereken !== 'function') {
			return;
		}
		var I = C.inhoud;
		var binnen = wortel.querySelector('.sfp-zt-wrap') || wortel;

		/* ---------- Hulpfuncties ---------- */
		var T = function (o) { return (o && typeof o === 'object' ? (o[C.taal] != null ? o[C.taal] : '') : (typeof o === 'string' ? o : '')) || ''; };
		var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
		/* Tekst tussen rechte haken (een plaatshouder) blijft zichtbaar als label. */
		var ph = function (s) { return esc(s).replace(/\[([^\]]+)\]/g, '<span class="sfp-zt-ph">[$1]</span>'); };
		var $ = function (sel) { return wortel.querySelector(sel); };
		var okMail = function (m) { return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(m); };
		var vraag = function (nr) { return I.vragen.filter(function (v) { return v.nr === nr; })[0]; };

		/* ---------- Stand ---------- */
		var params = (function () { try { return new URL(window.location.href).searchParams; } catch (e) { return null; } })();
		var S = {
			scherm: 'start',
			lengte: 'long',
			kortEerst: !!(params && params.get('lengte') === 'kort' && C.lengtes.short),
			A: {},
			volgorde: [],
			i: 0,
			voornaam: '', email: '', nieuwsbrief: false,
			sessie: '', code: '', resultUrl: '',
			R: null,
			bezig: false,
			fout: ''
		};
		var meta = { utm: {}, dienst: '', bron: '' };
		if (params) {
			['utm_source', 'utm_medium', 'utm_campaign', 'gclid'].forEach(function (k) { var v = params.get(k); if (v) meta.utm[k] = v.slice(0, 200); });
			meta.dienst = (params.get('dienst') || '').slice(0, 20);
		}
		/* De pagina waar de bezoeker vandaan kwam (salespagina), anders de testpagina zelf. */
		try {
			var ref = document.referrer ? new URL(document.referrer) : null;
			meta.bron = ref && ref.host === window.location.host && ref.pathname !== window.location.pathname ? ref.origin + ref.pathname : window.location.origin + window.location.pathname;
		} catch (e) { meta.bron = window.location.origin + window.location.pathname; }

		function post(pad, gegevens) {
			return fetch(C.rest + pad, {
				method: 'POST',
				credentials: 'omit',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(gegevens)
			}).then(function (r) {
				return r.json().catch(function () { return {}; }).then(function (j) {
					if (!r.ok) { var e = new Error(j && j.message ? j.message : T(I.module.fout_versturen)); e.status = r.status; throw e; }
					return j;
				});
			}, function () {
				/* Geen verbinding: de tekst uit het pakket, niet de melding van de browser. */
				throw new Error(T(I.module.fout_versturen));
			});
		}

		function naarBoven() {
			var top = wortel.getBoundingClientRect().top + window.pageYOffset - 24;
			if (Math.abs(window.pageYOffset - top) > 40) window.scrollTo({ top: Math.max(0, top) });
		}

		/* ---------- Start ---------- */
		function schermStart() {
			var St = I.start;
			var lang = '<div class="sfp-zt-kaartje' + (S.kortEerst ? '' : ' hoofd') + '"><span class="sfp-zt-lab">' + esc(T(St.p3_kop)) + '</span><p class="sfp-zt-uitleg2">' + esc(T(St.p3_uitleg)) + ' ' + ph(T(St.p3_tijd)) + '</p><button type="button" class="sfp-zt-knop' + (S.kortEerst ? ' stil' : '') + '" data-lengte="long">' + esc(T(St.p3_knop)) + '</button></div>';
			var kort = C.lengtes.short ? '<div class="sfp-zt-kaartje' + (S.kortEerst ? ' hoofd' : '') + '"><span class="sfp-zt-lab">' + esc(T(St.p4_kop)) + '</span><p class="sfp-zt-uitleg2"><b>' + ph(T(St.p4_tekst)) + '</b></p>' + (T(St.p4_uitleg) ? '<p class="sfp-zt-uitleg2">' + esc(T(St.p4_uitleg)) + '</p>' : '') + '<button type="button" class="sfp-zt-knop' + (S.kortEerst ? '' : ' stil') + '" data-lengte="short">' + esc(T(St.p4_knop)) + '</button></div>' : '';
			var privacy = St.p9_url || C.privacy;
			binnen.innerHTML = '<div class="sfp-zt-scherm sfp-zt-start">'
				+ '<div><span class="sfp-zt-lab licht">' + esc(T(I.algemeen.testnaam)) + '</span><h2 class="sfp-zt-h1">' + esc(T(St.kop)) + '</h2></div>'
				+ (T(St.intro) ? '<div class="sfp-zt-intro"><p>' + esc(T(St.intro)) + '</p>' + (T(St.introLijstkop) ? '<p class="sfp-zt-lijstkop">' + ph(T(St.introLijstkop)) + '</p>' : '') + (St.introPunten && St.introPunten.length ? '<ul class="sfp-zt-lijst">' + St.introPunten.map(function (a) { return '<li>' + esc(T(a)) + '</li>'; }).join('') + '</ul>' : '') + '</div>' : '')
				+ (T(St.instructie) ? '<p class="sfp-zt-instructie">' + esc(T(St.instructie)) + '</p>' : '')
				+ '<div class="sfp-zt-formulier">'
				+ '<div class="sfp-zt-veld" data-veld="naam"><label for="sfp-zt-voornaam">' + esc(T(St.p6_voornaam)) + '</label><input type="text" id="sfp-zt-voornaam" placeholder="' + esc(T(St.p6_voornaam)) + '" autocomplete="given-name" maxlength="100" value="' + esc(S.voornaam) + '"><span class="sfp-zt-fout">' + esc(T(St.fout_naam)) + '</span></div>'
				+ '<div class="sfp-zt-veld" data-veld="email"><label for="sfp-zt-email">' + esc(T(St.p6_email)) + '</label><input type="email" id="sfp-zt-email" placeholder="' + esc(T(St.p6_email)) + '" autocomplete="email" inputmode="email" maxlength="190" value="' + esc(S.email) + '"><span class="sfp-zt-fout">' + esc(T(St.fout_email)) + '</span></div>'
				+ '<div class="sfp-zt-lok" aria-hidden="true"><label for="sfp-zt-website">Website</label><input type="text" id="sfp-zt-website" tabindex="-1" autocomplete="off"></div>'
				+ '<label class="sfp-zt-vink"><input type="checkbox" id="sfp-zt-nieuwsbrief"' + (S.nieuwsbrief ? ' checked' : '') + '><span>' + ph(T(St.p8_nieuwsbrief)) + '</span></label>'
				+ '<div class="sfp-zt-keuzes">' + (S.kortEerst ? kort + lang : lang + kort) + '</div>'
				+ '<p class="sfp-zt-foutmelding" id="sfp-zt-startfout"' + (S.fout ? '' : ' hidden') + ' role="alert">' + esc(S.fout) + '</p>'
				+ '<p class="sfp-zt-avg">' + esc(T(St.p9_avg)) + (privacy ? ' <a href="' + esc(privacy) + '" target="_blank" rel="noopener">' + esc(T(St.p9_link)) + '</a>.' : '') + '</p>'
				+ '</div></div>';

			var ga = function (lengte, knop) {
				if (S.bezig) return;
				var naam = $('#sfp-zt-voornaam').value.trim(), mail = $('#sfp-zt-email').value.trim();
				$('[data-veld="naam"]').classList.toggle('mis', !naam);
				$('[data-veld="email"]').classList.toggle('mis', !okMail(mail));
				if (!naam || !okMail(mail)) { (!naam ? $('#sfp-zt-voornaam') : $('#sfp-zt-email')).focus(); return; }
				var zelfde = S.sessie && S.lengte === lengte && S.voornaam === naam && S.email === mail && S.nieuwsbrief === $('#sfp-zt-nieuwsbrief').checked;
				S.voornaam = naam; S.email = mail; S.nieuwsbrief = $('#sfp-zt-nieuwsbrief').checked; S.fout = '';
				if (zelfde) {
					/* Terug naar het begin en dezelfde keuze: verder met dezelfde rij. */
					S.i = 0; S.scherm = 'vraag'; teken();
					return;
				}
				S.bezig = true;
				if (knop) knop.setAttribute('aria-busy', 'true');
				post('start', {
					voornaam: naam, email: mail, lengte: lengte, nieuwsbrief: S.nieuwsbrief,
					lead_source_url: meta.bron, utm_source: meta.utm.utm_source || '', utm_medium: meta.utm.utm_medium || '',
					utm_campaign: meta.utm.utm_campaign || '', gclid: meta.utm.gclid || '', dienst: meta.dienst,
					website: $('#sfp-zt-website').value
				}).then(function (j) {
					S.bezig = false; S.sessie = j.sessie || '';
					S.lengte = lengte;
					S.volgorde = (C.lengtes[lengte] && C.lengtes[lengte].vragen ? C.lengtes[lengte].vragen : []).slice().sort(function (a, b) { return a - b; });
					S.A = {}; S.i = 0; S.scherm = 'vraag';
					teken();
				}, function (e) {
					S.bezig = false; S.fout = e.message || T(I.module.fout_versturen);
					teken();
				});
			};
			Array.prototype.forEach.call(binnen.querySelectorAll('[data-lengte]'), function (b) {
				b.addEventListener('click', function () { ga(b.getAttribute('data-lengte'), b); });
			});
			['#sfp-zt-voornaam', '#sfp-zt-email'].forEach(function (id) {
				$(id).addEventListener('input', function () { $(id === '#sfp-zt-voornaam' ? '[data-veld="naam"]' : '[data-veld="email"]').classList.remove('mis'); });
				$(id).addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); ga(S.kortEerst ? 'short' : 'long'); } });
			});
		}

		/* ---------- Vragen ---------- */
		function schermVraag() {
			var V = I.vragenscherm, nr = S.volgorde[S.i], v = vraag(nr), tx = v[C.taal] || {}, n = S.i + 1, t = S.volgorde.length;
			var meer = !!v.meerdere, gekozen = S.A[nr], laatste = S.i === t - 1;
			var opties = (tx.o || []).map(function (o, k) {
				var l = 'abcdefghi'[k], aan = meer ? (Array.isArray(gekozen) && gekozen.indexOf(l) >= 0) : gekozen === l;
				return '<button type="button" class="sfp-zt-optie' + (meer ? ' vink' : '') + (aan ? ' aan' : '') + '" data-l="' + l + '" aria-pressed="' + aan + '"><span class="sfp-zt-bol" aria-hidden="true"></span><span>' + esc(o) + '</span></button>';
			}).join('');
			var blok = V.blokken && V.blokken[v.blok] ? T(V.blokken[v.blok]) : '';
			binnen.innerHTML = '<div class="sfp-zt-scherm">'
				+ '<div class="sfp-zt-vraagkop"><span class="sfp-zt-lab">' + esc(blok) + '</span><span class="sfp-zt-tel">' + esc(T(V.voortgang).replace('{n}', n).replace('{t}', t)) + '</span></div>'
				+ '<div class="sfp-zt-voortgang" role="progressbar" aria-valuemin="0" aria-valuemax="' + t + '" aria-valuenow="' + n + '"><b style="width:' + Math.round(n / t * 100) + '%"></b></div>'
				+ '<p class="sfp-zt-vraag" id="sfp-zt-vraagtekst" tabindex="-1">' + esc(tx.v) + (meer ? '<small>' + esc(T(V.meerdere)) + '</small>' : '') + '</p>'
				+ '<div class="sfp-zt-opties">' + opties + '</div>'
				+ '<p class="sfp-zt-foutmelding" id="sfp-zt-vraagfout" hidden role="alert">' + esc(T(V.kiesEen)) + '</p>'
				+ '<div class="sfp-zt-vraagvoet"><button type="button" class="sfp-zt-terug" id="sfp-zt-vorige">' + esc(T(V.vorige)) + '</button>'
				+ (meer || laatste ? '<button type="button" class="sfp-zt-knop" id="sfp-zt-verder">' + esc(laatste ? T(V.naarResultaat) : T(V.volgende)) + '</button>' : '') + '</div>'
				+ '</div>';
			$('#sfp-zt-vorige').addEventListener('click', function () {
				if (S.i > 0) { S.i--; teken(); } else { S.scherm = 'start'; teken(); }
			});
			Array.prototype.forEach.call(binnen.querySelectorAll('.sfp-zt-optie'), function (b) {
				b.addEventListener('click', function () {
					var l = b.getAttribute('data-l');
					if (meer) {
						var cur = Array.isArray(S.A[nr]) ? S.A[nr].slice() : [], k = cur.indexOf(l);
						if (k >= 0) cur.splice(k, 1); else cur.push(l);
						S.A[nr] = cur; b.classList.toggle('aan'); b.setAttribute('aria-pressed', String(b.classList.contains('aan')));
						$('#sfp-zt-vraagfout').hidden = true;
						return;
					}
					if (S.bezig) return;
					S.bezig = true; S.A[nr] = l;
					Array.prototype.forEach.call(binnen.querySelectorAll('.sfp-zt-optie'), function (o) { o.classList.toggle('aan', o === b); o.setAttribute('aria-pressed', String(o === b)); });
					setTimeout(function () { S.bezig = false; verder(); }, 220);
				});
			});
			if ($('#sfp-zt-verder')) {
				$('#sfp-zt-verder').addEventListener('click', function () {
					if (S.bezig) return;
					var leeg = meer ? !(Array.isArray(S.A[nr]) && S.A[nr].length) : !S.A[nr];
					if (leeg) { $('#sfp-zt-vraagfout').hidden = false; return; }
					verder();
				});
			}
			naarBoven();
			$('#sfp-zt-vraagtekst').focus({ preventScroll: true });
		}

		function verder() {
			if (S.i < S.volgorde.length - 1) { S.i++; teken(); return; }
			afronden();
		}

		/* ---------- Afronden ---------- */
		function antwoorden() {
			var uit = {};
			S.volgorde.forEach(function (nr) { if (S.A[nr] != null) uit[nr] = S.A[nr]; });
			return uit;
		}

		function afronden() {
			if (S.scherm === 'einde') return;
			S.R = M.bereken(S.A, S.lengte);
			S.scherm = 'einde'; S.fout = '';
			teken();
			verstuurAfgerond();
		}

		function verstuurAfgerond() {
			var R = S.R;
			post('afgerond', {
				sessie: S.sessie,
				antwoorden: antwoorden(),
				uitkomst: {
					crm: L.crm(S.A, R),
					notitie: L.notitie(S.A, R, T, I)
				}
			}).then(function (j) {
				S.code = j.code || ''; S.resultUrl = j.result_url || '';
				var knop = $('#sfp-zt-open-pdf');
				if (knop) { knop.removeAttribute('aria-disabled'); if (S.resultUrl) knop.setAttribute('href', S.resultUrl); }
			}, function (e) {
				S.fout = e.message || T(I.module.fout_versturen);
				var plek = $('#sfp-zt-eindfout');
				if (plek) { plek.hidden = false; plek.querySelector('span').textContent = S.fout; }
			});
		}

		/* ---------- Eindscherm ---------- */
		function schermEinde() {
			var Re = I.resultaat, St = I.start;
			var delen = L.resultaat({ A: S.A, R: S.R, naam: S.voornaam, T: T, esc: esc, inhoud: I, motor: M });
			var melding = esc(T(Re.p10)).replace('[e-mail]', '<span class="sfp-zt-mail">' + esc(S.email) + '</span>');
			binnen.innerHTML = '<div class="sfp-zt-scherm sfp-zt-res">'
				+ (delen.boven || '')
				+ '<div class="sfp-zt-teaser">'
				+ '<div class="sfp-zt-wazig" aria-hidden="true">' + (delen.wazig || '') + '</div>'
				+ '<section class="sfp-zt-t3blok sfp-zt-overlay">'
				+ '<div class="sfp-zt-mailkop"><span class="sfp-zt-mailicoon" aria-hidden="true"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg></span><p class="sfp-zt-inmail">' + esc(T(Re.inMailbox)) + '</p></div>'
				+ '<p class="sfp-zt-p10" id="sfp-zt-melding" role="status">' + melding + '</p>'
				+ '<p class="sfp-zt-spamregel">' + esc(T(Re.p10_spam)) + '</p>'
				+ '<p class="sfp-zt-foutmelding" id="sfp-zt-eindfout"' + (S.fout ? '' : ' hidden') + ' role="alert"><span>' + esc(S.fout) + '</span> <button type="button" class="sfp-zt-cta2 klein" id="sfp-zt-opnieuw">' + esc(T(Re.p7_knop)) + '</button></p>'
				+ '<div class="sfp-zt-hulp">'
				+ '<p class="sfp-zt-hulpregel"><span>' + esc(T(Re.p10_corrigeer)) + '</span> <button type="button" class="sfp-zt-cta2 klein" id="sfp-zt-corrigeer">' + esc(T(Re.p10_corrigeerKnop)) + '</button></p>'
				+ '<div class="sfp-zt-formulier" id="sfp-zt-cform" hidden>'
				+ '<div class="sfp-zt-veld" data-veld="email2"><label for="sfp-zt-email2">' + esc(T(St.p6_email)) + '</label><input type="email" id="sfp-zt-email2" placeholder="' + esc(T(St.p6_email)) + '" autocomplete="email" inputmode="email" maxlength="190" value="' + esc(S.email) + '"><span class="sfp-zt-fout">' + esc(T(St.fout_email)) + '</span></div>'
				+ '<button type="button" class="sfp-zt-knop" id="sfp-zt-stuur">' + esc(T(Re.p7_knop)) + '</button>'
				+ '</div>'
				+ '<p class="sfp-zt-hulpregel"><span>' + esc(T(Re.openHier)) + '</span> <a class="sfp-zt-cta2 klein" id="sfp-zt-open-pdf" target="_blank" rel="noopener"' + (S.resultUrl ? ' href="' + esc(S.resultUrl) + '"' : ' aria-disabled="true"') + '>' + esc(T(Re.openHierKnop)) + '</a></p>'
				+ '</div>'
				+ '</section></div></div>';

			$('#sfp-zt-corrigeer').addEventListener('click', function () {
				var f = $('#sfp-zt-cform'); f.hidden = !f.hidden; if (!f.hidden) $('#sfp-zt-email2').focus();
			});
			var opnieuwSturen = function () {
				var mail = $('#sfp-zt-email2').value.trim();
				$('[data-veld="email2"]').classList.toggle('mis', !okMail(mail));
				if (!okMail(mail)) { $('#sfp-zt-email2').focus(); return; }
				if (S.bezig) return;
				if (!S.code) {
					var p0 = $('#sfp-zt-eindfout'); p0.hidden = false; p0.querySelector('span').textContent = S.fout || T(I.module.fout_versturen);
					return;
				}
				S.bezig = true;
				post('opnieuw-sturen', { sessie: S.sessie, email: mail }).then(function () {
					S.bezig = false; S.email = mail; $('#sfp-zt-cform').hidden = true;
					$('#sfp-zt-melding').innerHTML = esc(T(Re.p10)).replace('[e-mail]', '<span class="sfp-zt-mail">' + esc(S.email) + '</span>');
				}, function (e) {
					S.bezig = false;
					var plek = $('#sfp-zt-eindfout'); plek.hidden = false; plek.querySelector('span').textContent = e.message || T(I.module.fout_versturen);
					/* De herhaalknop hoort bij het afronden; na een mislukte wijziging probeert de bezoeker het met Stuur opnieuw. */
					$('#sfp-zt-opnieuw').hidden = !!S.code;
				});
			};
			$('#sfp-zt-stuur').addEventListener('click', opnieuwSturen);
			$('#sfp-zt-email2').addEventListener('input', function () { $('[data-veld="email2"]').classList.remove('mis'); });
			$('#sfp-zt-email2').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); opnieuwSturen(); } });
			$('#sfp-zt-opnieuw').addEventListener('click', function () { $('#sfp-zt-eindfout').hidden = true; S.fout = ''; if (!S.code) verstuurAfgerond(); });
			$('#sfp-zt-open-pdf').addEventListener('click', function (e) { if (!S.resultUrl) e.preventDefault(); });
			$('#sfp-zt-open-pdf').addEventListener('keydown', function (e) { if (e.key === ' ' && S.resultUrl) { e.preventDefault(); window.open(S.resultUrl, '_blank', 'noopener'); } });
			naarBoven();
		}

		function teken() {
			/* Het scherm staat op de wortel, zodat het kruisje van de testpagina weet of iemand midden in de test zit. */
			wortel.setAttribute('data-scherm', S.scherm);
			if (S.scherm === 'vraag') schermVraag();
			else if (S.scherm === 'einde') schermEinde();
			else schermStart();
		}

		teken();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
