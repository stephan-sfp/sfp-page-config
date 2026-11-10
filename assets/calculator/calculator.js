/**
 * SFP Page Config - Incompanycalculator op de pagina.
 *
 * Opbouw als de zelftest (besluit 10-11-2026): één actie per scherm; een
 * enkele keuze gaat zonder knop door; een keuze met meerdere antwoorden of
 * een invulveld heeft een knop Volgende. De begroting staat pas naast de
 * vragen als de duur gekozen is. Rekenen doet motor.js; bij de stap met de
 * gegevens slaat de server de begroting op met een eigen berekening.
 *
 * Ingebed (op een incompany-pagina): kop, aantal mensen en een knop naar de
 * calculatorpagina met dat aantal.
 *
 * Geen naam of e-mail in de dataLayer: alleen calculator_start en
 * calculator_afgerond met de soort en de totaalklasse.
 */
(function () {
	'use strict';

	function start() {
		var wortel = document.getElementById('sfp-calculator');
		var C = window.sfpCalculatorConfig;
		var M = window.SFPCalculatorMotor;
		if (!wortel || !C || !M) return;
		var app = wortel.querySelector('.sfp-calc-app');
		var formVak = wortel.querySelector('.sfp-calc-formulier');
		var N = C.norm;
		var TX = C.t || {};

		/* ---------- Hulpjes ---------- */

		function t(s, v) {
			var tekst = Object.prototype.hasOwnProperty.call(TX, s) ? TX[s] : s;
			Object.keys(v || {}).forEach(function (k) { tekst = tekst.split('{' + k + '}').join(String(v[k])); });
			return tekst;
		}
		function esc(s) {
			return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
		}
		var teken = { EUR: '€', GBP: '£', USD: '$' };
		function bedrag(b) {
			var n = Math.abs(Math.round(b));
			var s = C.taal === 'en' ? n.toLocaleString('en-GB') : String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
			return (b < 0 ? '- ' : '') + (teken[N.valuta] || N.valuta) + ' ' + s;
		}
		function geheel(w, min, max) {
			var n = parseInt(w, 10);
			if (isNaN(n)) return 0;
			return Math.max(min, Math.min(max, n));
		}
		function $(sel) { return app.querySelector(sel); }
		function alle(sel) { return Array.prototype.slice.call(app.querySelectorAll(sel)); }

		/* ---------- Stand ---------- */

		var params = (function () { try { return new URL(window.location.href).searchParams; } catch (e) { return new URLSearchParams(''); } })();
		var S = {
			i: 0,
			doel: '',
			fase: '',
			mensen: { soort: 'getal', aantal: '', van: '', tot: '' },
			doelgroep: '', doelgroepAnders: '',
			vorm: 0, begeleiding: '',
			extras: [],
			coach: { deelnemers: '', uren: 0 },
			lotj: { deelnemers: '', uren: 0 },
			acteur: 0, terugkom: '', scan: '',
			groepsvoordeel: '',
			locatie: '', locatieExtra: [],
			situatie: { doel: '', waarom: [], criteria: '' },
			eerder: '', beviel: '', vorig: '',
			beslist: [], budget: '',
			inkoop: { aan: '', po: '', kostenplaats: '', referentie: '', factuuradres: '' },
			sprekers: '', soort: '',
			antwoord: null, bezig: false, fout: ''
		};
		var vanBuiten = false;
		if (!C.ingebed && params.get('n')) {
			var n0 = geheel(params.get('n'), 1, N.max_deelnemers);
			if (n0) { S.doel = 'training'; S.mensen.aantal = String(n0); vanBuiten = true; }
		}

		/* ---------- Berekening ---------- */

		function keuzes() {
			var ex = {};
			if (S.extras.indexOf('coachuren') > -1) ex.coachuren = { deelnemers: S.coach.deelnemers, uren: S.coach.uren };
			if (S.extras.indexOf('lotj') > -1) ex.lotj = { deelnemers: S.lotj.deelnemers, uren: S.lotj.uren };
			if (S.extras.indexOf('clinic') > -1) ex.clinic = true;
			if (S.extras.indexOf('boek') > -1) ex.boek = true;
			if (S.extras.indexOf('acteur') > -1) ex.acteur = S.acteur;
			if (S.extras.indexOf('terugkom') > -1) ex.terugkom = S.terugkom;
			if (S.extras.indexOf('scan') > -1) ex.scan = S.scan;
			return {
				route: S.doel === 'evenement' ? 'B' : 'A',
				mensen: { soort: S.mensen.soort, aantal: S.mensen.aantal, van: S.mensen.van, tot: S.mensen.tot },
				vorm: S.vorm,
				begeleiding: S.begeleiding === 'ja',
				extras: ex,
				groepsvoordeel: S.groepsvoordeel || 'korting',
				sprekers: S.sprekers,
				doelgroep: S.doelgroep,
				doelgroep_anders: S.doelgroepAnders,
				soort: S.soort,
				fase: S.fase,
				uitvraag: S.fase === 'procedure',
				locatie: S.locatie === 'anders' ? S.locatieExtra.slice() : []
			};
		}
		function reken(vorm) {
			var k = keuzes();
			if (vorm) k.vorm = vorm;
			return M.reken(k, N);
		}
		function deelnemers() {
			var u = reken(S.vorm || 1).uitkomst;
			if (!u) return N.groep_norm;
			return S.mensen.soort === 'onbekend' ? N.groep_norm : (u.deelnemers || N.groep_norm);
		}
		function groepen() {
			var u = reken(S.vorm || 1).uitkomst;
			return u && u.groepen ? u.groepen : 1;
		}
		function mensenKlaar() {
			var m = S.mensen;
			if (m.soort === 'onbekend') return true;
			if (m.soort === 'marge') return geheel(m.van, 1, N.max_deelnemers) > 0 && geheel(m.tot, 1, N.max_deelnemers) > 0;
			return geheel(m.aantal, 1, N.max_deelnemers) > 0;
		}

		/* ---------- Het pad: welke schermen, in welke volgorde ---------- */

		var extraVolgorde = ['coachuren', 'clinic', 'boek', 'acteur', 'terugkom', 'lotj', 'scan'];
		function extraGratis(e) {
			if (e === 'clinic') return Number(S.vorm) >= N.clinic_gratis_vanaf;
			if (e === 'boek') return Number(S.vorm) >= N.boek_gratis_vanaf;
			return false;
		}
		function extrasTeKiezen() {
			return extraVolgorde.filter(function (e) { return N.extras_aan && N.extras_aan[e] && !extraGratis(e); });
		}
		function pad() {
			var p = ['start'];
			if (!vanBuiten || S.doel !== 'training') {
				if (N.routes_aan && N.routes_aan.B) p.push('doel');
			}
			p.push('uitvraag');
			if (S.fase === 'procedure') p.push('criteria');
			if (S.doel === 'evenement') {
				p.push('sprekers', 'soort', 'situatie');
			} else {
				p.push('mensen', 'doelgroep');
				if (S.doelgroep === 'anders') p.push('doelgroep_anders');
				p.push('vorm');
				if (S.vorm && N.vormen[String(S.vorm)] && N.vormen[String(S.vorm)].begeleiding === 'keuze') p.push('begeleiding');
				if (extrasTeKiezen().length) p.push('extras');
				S.extras.forEach(function (e) {
					if (e === 'coachuren') p.push('coach_n', 'coach_u');
					if (e === 'lotj') p.push('lotj_n', 'lotj_u');
					if (e === 'acteur') p.push('acteur');
					if (e === 'terugkom') p.push('terugkom');
					if (e === 'scan') p.push('scan');
				});
				if (Number(S.vorm) >= 2 && groepen() >= N.voordeel_vanaf_groep) p.push('voordeel');
				p.push('locatie');
				if (S.locatie === 'anders') p.push('locatie_extra');
				p.push('situatie', 'waarom', 'eerder');
				if (S.eerder === 'ja') p.push('beviel', 'vorig');
			}
			p.push('beslist', 'budget', 'inkoop');
			if (S.inkoop.aan === 'ja') p.push('inkoop_gegevens');
			p.push('gegevens', 'klaar');
			return p;
		}
		function huidig() { var p = pad(); return p[Math.min(S.i, p.length - 1)]; }

		/* ---------- Meten ---------- */

		var gemeld = {};
		function meld(naam, extra) {
			if (gemeld[naam]) return;
			gemeld[naam] = true;
			try {
				window.dataLayer = window.dataLayer || [];
				var e = { event: naam, calculator_soort: S.doel || '' };
				Object.keys(extra || {}).forEach(function (k) { e[k] = extra[k]; });
				window.dataLayer.push(e);
			} catch (x) { /* meten mag de calculator nooit breken */ }
		}

		/* ---------- Bouwstenen van een scherm ---------- */

		function kop(vraag, onder) {
			var p = pad();
			var tel = p.length - 2;
			var n = Math.max(1, S.i);
			return '<div class="sfp-calc-vraagkop"><span class="sfp-calc-lab">' + esc(t('lab')) + '</span><span class="sfp-calc-tel">' + esc(t('voortgang', { n: n, t: tel })) + '</span></div>'
				+ '<div class="sfp-calc-voortgang" role="progressbar" aria-valuemin="0" aria-valuemax="' + tel + '" aria-valuenow="' + n + '"><b style="width:' + Math.round(n / tel * 100) + '%"></b></div>'
				+ '<p class="sfp-calc-vraag" id="sfp-calc-vraagtekst" tabindex="-1">' + esc(vraag) + (onder ? '<small>' + esc(onder) + '</small>' : '') + '</p>';
		}
		function optie(w, label, zin, aan, meer) {
			return '<button type="button" class="sfp-calc-optie' + (meer ? ' vink' : '') + (aan ? ' aan' : '') + '" data-w="' + esc(w) + '" aria-pressed="' + (aan ? 'true' : 'false') + '">'
				+ '<span class="sfp-calc-bol" aria-hidden="true"></span><span class="sfp-calc-otekst"><b>' + esc(label) + '</b>' + (zin ? '<span>' + esc(zin) + '</span>' : '') + '</span></button>';
		}
		function voet(metVolgende, overslaan) {
			return '<p class="sfp-calc-foutmelding" id="sfp-calc-fout" hidden role="alert"></p>'
				+ '<div class="sfp-calc-vraagvoet"><button type="button" class="sfp-calc-terug" id="sfp-calc-vorige">' + esc(t('terug')) + '</button>'
				+ '<span class="sfp-calc-voetrechts">' + (overslaan ? '<button type="button" class="sfp-calc-cta2" id="sfp-calc-over">' + esc(overslaan) + '</button>' : '')
				+ (metVolgende ? '<button type="button" class="sfp-calc-knop" id="sfp-calc-volgende">' + esc(t('verder')) + '</button>' : '') + '</span></div>';
		}
		function fout(tekst) {
			var f = $('#sfp-calc-fout');
			if (!f) return;
			f.textContent = tekst;
			f.hidden = !tekst;
		}
		function verder() { S.i = Math.min(S.i + 1, pad().length - 1); tekenAlles(); }
		function vorige() {
			if (S.i <= 0) return;
			S.i--;
			if (vanBuiten && pad()[S.i] === 'start') S.i = 0;
			tekenAlles();
		}

		/** Een enkele keuze: klikken = kiezen en door. */
		function enkel(vraag, onder, opties, huidigW, zet) {
			var html = kop(vraag, onder) + '<div class="sfp-calc-opties">'
				+ opties.map(function (o) { return optie(o.w, o.label, o.zin, String(huidigW) === String(o.w), false); }).join('')
				+ '</div>' + voet(false, '');
			return {
				html: html,
				na: function () {
					alle('.sfp-calc-optie').forEach(function (b) {
						b.addEventListener('click', function () {
							if (S.bezig) return;
							S.bezig = true;
							alle('.sfp-calc-optie').forEach(function (o) { o.classList.toggle('aan', o === b); o.setAttribute('aria-pressed', String(o === b)); });
							zet(b.getAttribute('data-w'));
							setTimeout(function () { S.bezig = false; verder(); }, 220);
						});
					});
				}
			};
		}
		/** Meerdere antwoorden: vinkjes en Volgende. */
		function meer(vraag, onder, opties, lijst, verplicht, overslaan) {
			var html = kop(vraag, onder || t('meerdere')) + '<div class="sfp-calc-opties' + (opties.length > 4 ? ' twee' : '') + '">'
				+ opties.map(function (o) { return optie(o.w, o.label, o.zin, lijst.indexOf(o.w) > -1, true); }).join('')
				+ '</div>' + voet(true, overslaan || '');
			return {
				html: html,
				na: function () {
					alle('.sfp-calc-optie').forEach(function (b) {
						b.addEventListener('click', function () {
							var w = b.getAttribute('data-w');
							var k = lijst.indexOf(w);
							if (k > -1) lijst.splice(k, 1); else lijst.push(w);
							b.classList.toggle('aan', k < 0);
							b.setAttribute('aria-pressed', String(k < 0));
							fout('');
						});
					});
					$('#sfp-calc-volgende').addEventListener('click', function () {
						if (verplicht && !lijst.length) { fout(t('kies_een')); return; }
						verder();
					});
					if ($('#sfp-calc-over')) $('#sfp-calc-over').addEventListener('click', function () { lijst.length = 0; verder(); });
				}
			};
		}
		/** Een tekstvak met Volgende; optioneel met Sla over. */
		function tekst(vraag, onder, waarde, zet, optioneel) {
			var html = kop(vraag, onder) + '<label class="sfp-calc-sr" for="sfp-calc-tekst">' + esc(vraag) + '</label>'
				+ '<textarea id="sfp-calc-tekst" rows="5">' + esc(waarde) + '</textarea>' + voet(true, optioneel ? t('overslaan') : '');
			return {
				html: html,
				na: function () {
					var v = $('#sfp-calc-tekst');
					v.addEventListener('input', function () { zet(v.value); fout(''); });
					$('#sfp-calc-volgende').addEventListener('click', function () {
						if (!optioneel && !v.value.trim()) { fout(t('vul_in')); return; }
						verder();
					});
					if ($('#sfp-calc-over')) $('#sfp-calc-over').addEventListener('click', function () { zet(''); verder(); });
				}
			};
		}
		/** Een getal met Volgende. */
		function getal(vraag, onder, label, waarde, max, zet, extraHtml) {
			var html = kop(vraag, onder) + '<div class="sfp-calc-veld"><label for="sfp-calc-getal">' + esc(label) + '</label>'
				+ '<input id="sfp-calc-getal" type="number" inputmode="numeric" min="1" max="' + max + '" step="1" value="' + esc(waarde) + '"></div>'
				+ (extraHtml || '') + voet(true, '');
			return {
				html: html,
				na: function () {
					var v = $('#sfp-calc-getal');
					v.addEventListener('input', function () { zet(v.value); fout(''); });
					v.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); $('#sfp-calc-volgende').click(); } });
					$('#sfp-calc-volgende').addEventListener('click', function () {
						if (!geheel(v.value, 1, max)) { fout(t('vul_in')); return; }
						zet(String(geheel(v.value, 1, max)));
						verder();
					});
				}
			};
		}
		function janee(vraag, onder, waarde, zet, jaLabel, neeLabel) {
			return enkel(vraag, onder, [{ w: 'ja', label: jaLabel || t('ja') }, { w: 'nee', label: neeLabel || t('nee') }], waarde, zet);
		}

		/* ---------- De schermen ---------- */

		var schermen = {};

		schermen.start = function () {
			var punten = ['aanbod_1', 'aanbod_2', 'aanbod_3'].concat(N.routes_aan && N.routes_aan.B ? ['aanbod_4'] : []);
			var c = C.contact || {};
			var bel = c.telefoon ? '<a class="sfp-calc-cta2" href="tel:' + esc(c.telefoon.replace(/[^+0-9]/g, '')) + '">' + esc(t('bel', { n: c.telefoon })) + '</a>' : '';
			var mail = c.email ? '<a class="sfp-calc-cta2" href="mailto:' + esc(c.email) + '">' + esc(t('mail', { n: c.email })) + '</a>' : '';
			return {
				html: '<div class="sfp-calc-start">'
					+ '<div class="sfp-calc-startkop"><span class="sfp-calc-lab">' + esc(t('lab')) + '</span><h1 class="sfp-calc-titel">' + esc(t('titel')) + '</h1>'
					+ '<p class="sfp-calc-intro">' + esc(t('intro')) + '</p></div>'
					+ '<div class="sfp-calc-aanbod"><p class="sfp-calc-lijstkop">' + esc(t('aanbod_kop')) + '</p><ul class="sfp-calc-lijst">'
					+ punten.map(function (k) { return '<li>' + esc(t(k)) + '</li>'; }).join('') + '</ul></div>'
					+ '<div class="sfp-calc-startknop"><button type="button" class="sfp-calc-knop" id="sfp-calc-start">' + esc(t('start_knop')) + '</button><p class="sfp-calc-duur">' + esc(t('start_duur')) + '</p></div>'
					+ ((bel || mail) ? '<div class="sfp-calc-anders"><p><b>' + esc(t('anders_kop')) + '</b> ' + esc(t('anders_zin')) + '</p><p class="sfp-calc-contactregel">' + bel + mail + '</p></div>' : '')
					+ '</div>',
				na: function () {
					$('#sfp-calc-start').addEventListener('click', function () { meld('calculator_start'); verder(); });
				}
			};
		};
		schermen.doel = function () {
			return enkel(t('doel_vraag'), '', [
				{ w: 'training', label: t('doel_training'), zin: t('doel_training_zin') },
				{ w: 'evenement', label: t('doel_evenement'), zin: t('doel_evenement_zin') }
			], S.doel, function (w) { S.doel = w; });
		};
		schermen.uitvraag = function () {
			if (!S.doel) S.doel = 'training';
			return enkel(t('fase_vraag'), '', ['verkennen', 'intern', 'plannen', 'procedure'].map(function (w) {
				return { w: w, label: t('fase_' + w), zin: t('fase_' + w + '_zin') };
			}), S.fase, function (w) { S.fase = w; });
		};
		schermen.criteria = function () {
			return tekst(t('criteria_vraag'), t('criteria_hulp'), S.situatie.criteria, function (w) { S.situatie.criteria = w; }, true);
		};

		schermen.mensen = function () {
			var m = S.mensen;
			var veld;
			if (m.soort === 'marge') {
				veld = '<div class="sfp-calc-twee"><div class="sfp-calc-veld"><label for="sfp-calc-van">' + esc(t('mensen_van')) + '</label><input id="sfp-calc-van" type="number" inputmode="numeric" min="1" max="' + N.max_deelnemers + '" value="' + esc(m.van) + '"></div>'
					+ '<div class="sfp-calc-veld"><label for="sfp-calc-tot">' + esc(t('mensen_tot')) + '</label><input id="sfp-calc-tot" type="number" inputmode="numeric" min="1" max="' + N.max_deelnemers + '" value="' + esc(m.tot) + '"></div></div>';
			} else {
				veld = '<div class="sfp-calc-veld"><label for="sfp-calc-aantal">' + esc(t('mensen_aantal')) + '</label><input id="sfp-calc-aantal" type="number" inputmode="numeric" min="1" max="' + N.max_deelnemers + '" value="' + esc(m.aantal) + '"></div>';
			}
			var links = '<p class="sfp-calc-links"><button type="button" class="sfp-calc-cta2 klein" id="sfp-calc-wissel">' + esc(m.soort === 'marge' ? t('mensen_precies') : t('mensen_marge')) + '</button>'
				+ '<button type="button" class="sfp-calc-cta2 klein" id="sfp-calc-onbekend">' + esc(t('mensen_onbekend')) + '</button></p>';
			return {
				html: kop(t('mensen_vraag')) + veld + links + '<div class="sfp-calc-meldingen" id="sfp-calc-meld" aria-live="polite"></div>' + voet(true, ''),
				na: function () {
					function toon() {
						var plek = $('#sfp-calc-meld');
						plek.innerHTML = '';
						if (!mensenKlaar() || m.soort === 'onbekend') return;
						var u = reken(1).uitkomst;
						if (!u || !u.groepen) return;
						var html = '';
						if (u.groepen > 1) html += '<p class="sfp-calc-regel">' + esc(t('mensen_groepen', { n: t('groepen', { n: u.groepen }), t: u.verdeling.join(', ') })) + '</p>';
						u.meldingen.forEach(function (k) {
							if (k === 'klein') {
								var ml = [];
								if (C.coaching) ml.push('<a href="' + esc(C.coaching) + '">' + esc(t('link_coaching')) + '</a>');
								if (C.open) ml.push('<a href="' + esc(C.open) + '">' + esc(t('link_open')) + '</a>');
								html += '<p class="sfp-calc-regel">' + esc(t('melding_klein'))
									+ (ml.length ? '<span class="sfp-calc-meldlinks">' + ml.join('<span aria-hidden="true"> \u2022 </span>') + '</span>' : '') + '</p>';
							} else if (k === 'maatwerk') {
								html += '<p class="sfp-calc-regel">' + esc(t('melding_maatwerk')) + ' ' + esc(t('melding_groot')) + '</p>';
							} else if (k === 'negen_tien') {
								html += '<p class="sfp-calc-regel">' + esc(t('melding_negen_tien')) + '</p>';
							}
						});
						plek.innerHTML = html;
					}
					['#sfp-calc-aantal', '#sfp-calc-van', '#sfp-calc-tot'].forEach(function (id) {
						var v = $(id);
						if (!v) return;
						v.addEventListener('input', function () {
							if (id === '#sfp-calc-aantal') m.aantal = v.value;
							if (id === '#sfp-calc-van') m.van = v.value;
							if (id === '#sfp-calc-tot') m.tot = v.value;
							fout('');
							toon();
						});
						v.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); $('#sfp-calc-volgende').click(); } });
					});
					$('#sfp-calc-wissel').addEventListener('click', function () { m.soort = m.soort === 'marge' ? 'getal' : 'marge'; tekenAlles(); });
					$('#sfp-calc-onbekend').addEventListener('click', function () { m.soort = 'onbekend'; verder(); });
					$('#sfp-calc-volgende').addEventListener('click', function () {
						if (m.soort === 'onbekend') m.soort = 'getal';
						if (!mensenKlaar()) { fout(t('vul_in')); return; }
						verder();
					});
					if (m.soort === 'onbekend') m.soort = 'getal';
					toon();
				}
			};
		};
		schermen.doelgroep = function () {
			var o = ['starters', 'ervaren', 'senioren', 'leidinggevend', 'gemengd', 'anders'].map(function (d) { return { w: d, label: t('doelgroep_' + d) }; });
			var s = enkel(t('doelgroep_vraag'), '', o, S.doelgroep, function (w) { S.doelgroep = w; });
			s.html = s.html.replace('<span class="sfp-calc-voetrechts">', '<span class="sfp-calc-voetrechts"><button type="button" class="sfp-calc-cta2" id="sfp-calc-over">' + esc(t('overslaan')) + '</button>');
			var na = s.na;
			s.na = function () { na(); $('#sfp-calc-over').addEventListener('click', function () { S.doelgroep = ''; verder(); }); };
			return s;
		};
		schermen.doelgroep_anders = function () {
			return tekst(t('doelgroep_anders_vraag'), '', S.doelgroepAnders, function (w) { S.doelgroepAnders = w; }, true);
		};

		schermen.vorm = function () {
			var g = groepen();
			var kaarten = ['1', '2', '3'].filter(function (v) { return N.vormen[v]; }).map(function (v) {
				var vn = N.vormen[v];
				var punten = [];
				if (v === '1') punten.push(t('punt_dag'));
				if (v !== '1') punten.push(t('punt_oefenen'));
				if (vn.begeleiding === 'keuze') punten.push(t('punt_begeleiding_keuze'));
				if (vn.begeleiding === 'inbegrepen') punten.push(t('punt_begeleiding_in'));
				if (N.extras_aan.clinic && Number(v) >= N.clinic_gratis_vanaf) punten.push(t('punt_clinic_gratis'));
				if (N.extras_aan.boek && Number(v) >= N.boek_gratis_vanaf) punten.push(t('punt_boek_gratis'));
				if (vn.begeleiding === 'inbegrepen' && N.vormen['1']) {
					var los = N.vormen['1'].prijs * Number(v) + N.begeleiding_prijs - vn.prijs;
					if (los > 0) punten.push(t('punt_voordelig', { prijs: bedrag(los) }));
				}
				punten.push(t('punt_calls'));
				var u = reken(Number(v)).uitkomst;
				var basis = u ? u.groepen * vn.prijs : vn.prijs;
				return '<button type="button" class="sfp-calc-kaart' + (String(S.vorm) === v ? ' aan' : '') + '" data-w="' + v + '" aria-pressed="' + (String(S.vorm) === v) + '">'
					+ '<span class="sfp-calc-kaartlab">' + esc(t('vorm_' + v)) + '</span>'
					+ '<span class="sfp-calc-kaartprijs">' + esc(bedrag(vn.prijs)) + '</span><span class="sfp-calc-kaartper">' + esc(t('per_groep') + ', ' + t('excl')) + '</span>'
					+ '<span class="sfp-calc-kaartpunten">' + punten.map(function (p) { return '<span>' + esc(p) + '</span>'; }).join('') + '</span>'
					+ (g > 1 ? '<span class="sfp-calc-kaarttotaal">' + esc(t('vorm_totaal', { n: t('groepen', { n: g }), prijs: bedrag(basis) })) + '</span>' : '')
					+ '<span class="sfp-calc-kaartknop">' + esc(t('vorm_kies', { n: t('vorm_' + v) })) + '</span></button>';
			}).join('');
			return {
				html: kop(t('vorm_vraag')) + '<div class="sfp-calc-kaarten">' + kaarten + '</div>' + voet(false, ''),
				na: function () {
					alle('.sfp-calc-kaart').forEach(function (b) {
						b.addEventListener('click', function () {
							if (S.bezig) return;
							S.bezig = true;
							alle('.sfp-calc-kaart').forEach(function (o) { o.classList.toggle('aan', o === b); });
							S.vorm = Number(b.getAttribute('data-w'));
							if (N.vormen[String(S.vorm)].begeleiding !== 'keuze') S.begeleiding = '';
							S.extras = S.extras.filter(function (e) { return !extraGratis(e); });
							setTimeout(function () { S.bezig = false; verder(); }, 220);
						});
					});
				}
			};
		};
		schermen.begeleiding = function () {
			return enkel(t('begeleiding_vraag'), t('begeleiding_zin'), [
				{ w: 'ja', label: t('begeleiding_ja'), zin: t('begeleiding_ja_zin', { prijs: bedrag(N.begeleiding_prijs) }) },
				{ w: 'nee', label: t('begeleiding_nee') }
			], S.begeleiding, function (w) { S.begeleiding = w; });
		};
		schermen.extras = function () {
			var keuze = extrasTeKiezen();
			var al = ['clinic', 'boek'].filter(function (e) { return N.extras_aan[e] && extraGratis(e); }).map(function (e) { return t('extra_' + e); });
			var o = keuze.map(function (e) { return { w: e, label: t('extra_' + e), zin: (C.uitleg && C.uitleg[e]) || t('extra_' + e + '_kort') }; });
			var s = meer(t('extras_vraag'), al.length ? t('extras_al', { n: al.join(', ') }) + ' ' + t('meerdere') : t('meerdere'), o, S.extras, false, t('extras_geen'));
			return s;
		};
		function urenScherm(sleutel, vraagN) {
			var pp = deelnemers();
			return {
				n: function () {
					return getal(t(vraagN), t('van_de', { n: pp }), t('mensen_aantal'), S[sleutel].deelnemers, pp, function (w) { S[sleutel].deelnemers = w; });
				},
				u: function () {
					var n = geheel(S[sleutel].deelnemers, 1, pp) || 1;
					var o = [4, 8, 12].map(function (uren) {
						var per = uren / N.blok_uren * N.blok_prijs;
						return { w: uren, label: uren === 12 ? t('uren_optie_meer') : t('uren_optie', { n: uren }), zin: t('uren_zin', { prijs: bedrag(per), totaal: bedrag(per * n), n: t('deelnemers_n', { n: n }) }) + (uren >= N.abonnement_uren ? '. ' + t('melding_abonnement') : '') };
					});
					return enkel(t('uren_vraag'), '', o, S[sleutel].uren, function (w) { S[sleutel].uren = Number(w); });
				}
			};
		}
		schermen.coach_n = function () { return urenScherm('coach', 'coach_n_vraag').n(); };
		schermen.coach_u = function () { return urenScherm('coach', 'coach_n_vraag').u(); };
		schermen.lotj_n = function () { return urenScherm('lotj', 'lotj_n_vraag').n(); };
		schermen.lotj_u = function () { return urenScherm('lotj', 'lotj_n_vraag').u(); };
		schermen.acteur = function () {
			var max = Number(S.vorm) * groepen();
			var o = [];
			for (var d = 1; d <= Math.min(max, 4); d++) o.push({ w: d, label: d === 1 ? t('acteur_dag') : t('acteur_dagen', { n: d }), zin: bedrag(d * N.acteur_prijs) });
			if (max > 4) o.push({ w: max, label: t('acteur_alle', { n: max }), zin: bedrag(max * N.acteur_prijs) });
			return enkel(t('acteur_vraag'), '', o, S.acteur, function (w) { S.acteur = Number(w); });
		};
		schermen.terugkom = function () {
			var g = groepen();
			return enkel(t('terugkom_vraag'), '', ['dagdeel', 'online'].map(function (w) {
				return { w: w, label: t('terugkom_' + w), zin: t('prijs_per_groep_totaal', { prijs: bedrag(N.terugkom[w]), totaal: bedrag(N.terugkom[w] * g) }) };
			}), S.terugkom, function (w) { S.terugkom = w; });
		};
		schermen.scan = function () {
			var g = groepen();
			var pp = deelnemers();
			return enkel(t('scan_vraag'), '', [
				{ w: 'individueel', label: t('scan_individueel'), zin: t('prijs_pp_totaal', { prijs: bedrag(N.scan_individueel), totaal: bedrag(N.scan_individueel * pp) }) },
				{ w: 'team', label: t('scan_team'), zin: t('prijs_per_groep_totaal', { prijs: bedrag(N.scan_team), totaal: bedrag(N.scan_team * g) }) }
			], S.scan, function (w) { S.scan = w; });
		};
		schermen.voordeel = function () {
			return enkel(t('voordeel_vraag'), '', [
				{ w: 'korting', label: t('voordeel_korting', { prijs: bedrag(N.voordeel_bedrag) }) },
				{ w: 'coachuren', label: t('voordeel_coachuren') }
			], S.groepsvoordeel, function (w) { S.groepsvoordeel = w; });
		};
		schermen.locatie = function () {
			return enkel(t('locatie_vraag'), '', [
				{ w: 'klant', label: t('locatie_klant'), zin: t('locatie_klant_zin') },
				{ w: 'anders', label: t('locatie_anders'), zin: t('locatie_anders_zin') }
			], S.locatie, function (w) { S.locatie = w; if (w !== 'anders') S.locatieExtra = []; });
		};
		schermen.locatie_extra = function () {
			return meer(t('locatie_extra_vraag'), t('locatie_extra_hulp'), ['extern', 'lunch', 'borrel', 'teamdag'].map(function (w) { return { w: w, label: t('locatie_' + w) }; }), S.locatieExtra, true);
		};
		schermen.situatie = function () {
			return tekst(t('situatie_vraag'), t('situatie_hulp'), S.situatie.doel, function (w) { S.situatie.doel = w; }, true);
		};
		schermen.waarom = function () {
			return meer(t('waarom_vraag'), '', ['nieuwe_rol', 'groei', 'moment', 'feedback', 'beleid', 'anders'].map(function (w) { return { w: w, label: t('waarom_' + w) }; }), S.situatie.waarom, false, t('overslaan'));
		};
		schermen.eerder = function () {
			return janee(t('eerder_vraag'), '', S.eerder, function (w) { S.eerder = w; if (w !== 'ja') { S.beviel = ''; S.vorig = ''; } });
		};
		schermen.beviel = function () {
			return enkel(t('beviel_vraag'), '', ['goed', 'wisselend', 'slecht'].map(function (w) { return { w: w, label: t('beviel_' + w) }; }), S.beviel, function (w) { S.beviel = w; });
		};
		schermen.vorig = function () {
			return tekst(t('vorig_vraag'), '', S.vorig, function (w) { S.vorig = w; }, true);
		};
		schermen.beslist = function () {
			return meer(t('beslist_vraag'), '', ['zelf', 'leidinggevende', 'hr', 'inkoop', 'directie', 'anders'].map(function (w) { return { w: w, label: t('beslist_' + w) }; }), S.beslist, false, t('overslaan'));
		};
		schermen.budget = function () {
			var o = [
				{ w: 'tot5', label: t('budget_tot5', { prijs: bedrag(5000) }) },
				{ w: '5_10', label: t('budget_5_10', { van: bedrag(5000), tot: bedrag(10000) }) },
				{ w: '10_25', label: t('budget_10_25', { van: bedrag(10000), tot: bedrag(25000) }) },
				{ w: 'meer', label: t('budget_meer', { prijs: bedrag(25000) }) },
				{ w: 'onbekend', label: t('budget_onbekend') },
				{ w: 'geen', label: t('budget_geen') }
			];
			return enkel(t('budget_vraag'), t('budget_hulp'), o, S.budget, function (w) { S.budget = w; });
		};
		schermen.inkoop = function () {
			return janee(t('inkoop_vraag'), t('inkoop_zin'), S.inkoop.aan, function (w) { S.inkoop.aan = w; });
		};
		schermen.inkoop_gegevens = function () {
			var p = S.inkoop;
			var velden = ['po', 'kostenplaats', 'referentie'].map(function (k) {
				return '<div class="sfp-calc-veld"><label for="sfp-calc-ink-' + k + '">' + esc(t('inkoop_' + k)) + '</label><input id="sfp-calc-ink-' + k + '" type="text" value="' + esc(p[k]) + '"></div>';
			}).join('') + '<div class="sfp-calc-veld"><label for="sfp-calc-ink-factuuradres">' + esc(t('inkoop_factuuradres')) + '</label><textarea id="sfp-calc-ink-factuuradres" rows="3">' + esc(p.factuuradres) + '</textarea></div>';
			return {
				html: kop(t('inkoop_gegevens_vraag'), t('inkoop_gegevens_hulp')) + '<div class="sfp-calc-velden zichtbaar">' + velden + '</div>' + voet(true, ''),
				na: function () {
					['po', 'kostenplaats', 'referentie', 'factuuradres'].forEach(function (k) {
						var v = $('#sfp-calc-ink-' + k);
						v.addEventListener('input', function () { p[k] = v.value; fout(''); });
					});
					$('#sfp-calc-volgende').addEventListener('click', function () {
						var iets = ['po', 'kostenplaats', 'referentie', 'factuuradres'].some(function (k) { return String(p[k]).trim(); });
						if (!iets) { fout(t('inkoop_mis')); return; }
						verder();
					});
				}
			};
		};
		schermen.sprekers = function () {
			return getal(t('sprekers_vraag'), '', t('sprekers_aantal'), S.sprekers, 500, function (w) { S.sprekers = w; },
				'<p class="sfp-calc-regel">' + esc(t('evenement_prijs', { prijs: bedrag(N.evenement_prijs) })) + '</p>');
		};
		schermen.soort = function () {
			return enkel(t('soort_vraag'), '', ['keynote', 'meerdere', 'pitch', 'anders'].map(function (w) { return { w: w, label: t('soort_' + w) }; }), S.soort, function (w) { S.soort = w; });
		};

		schermen.gegevens = function () {
			var leeg = !formVak || !C.formulier;
			return {
				html: kop(t('gegevens_vraag'), t('gegevens_hulp'))
					+ (S.fout ? '<p class="sfp-calc-foutmelding" role="alert">' + esc(S.fout) + '</p>' : '')
					+ (leeg && C.beheerder ? '<p class="sfp-calc-foutmelding">' + esc(t('geen_formulier')) + '</p>' : '')
					+ '<div class="sfp-calc-formplek"></div>'
					+ '<div class="sfp-calc-vraagvoet"><button type="button" class="sfp-calc-terug" id="sfp-calc-vorige">' + esc(t('terug')) + '</button></div>',
				na: function () {
					if (formVak && C.formulier) {
						$('.sfp-calc-formplek').appendChild(formVak);
						formVak.hidden = !S.antwoord;
						if (!S.antwoord) {
							$('.sfp-calc-formplek').insertAdjacentHTML('beforeend', '<p class="sfp-calc-bezig">' + esc(t('bezig')) + '</p>');
							bewaarBegroting();
						}
					}
				}
			};
		};

		schermen.klaar = function () {
			var a = S.antwoord || {};
			var b = a.berekening || {};
			var o = b.uitkomst;
			var c = C.contact || {};
			var boek = C.boek ? C.boek + (C.boek.indexOf('?') > -1 ? '&' : '?') + 'kenmerk=' + encodeURIComponent(a.kenmerk || '') : '';
			var wat = [];
			var totaal = '';
			if (S.doel === 'evenement') {
				totaal = '<p class="sfp-calc-groot">' + esc(bedrag(N.evenement_prijs)) + '</p><p class="sfp-calc-klein">' + esc(t('evenement_prijs', { prijs: bedrag(N.evenement_prijs) })) + '</p>';
			} else if (o) {
				wat.push(t('vorm_' + S.vorm) + ', ' + (o.groepen === 1 ? t('groep') : t('groepen', { n: o.groepen })));
				o.regels.forEach(function (r) {
					if (r.code.indexOf('vorm_') === 0) return;
					wat.push(t('regel_' + r.code) + (r.gratis ? ' (' + t('inbegrepen') + ')' : ''));
				});
				wat.push(t('punt_calls'));
				totaal = '<p class="sfp-calc-lab">' + esc(b.richtprijs ? t('richtprijs') : t('totaal')) + '</p><p class="sfp-calc-groot">' + esc(bedrag(o.totaal_excl)) + ' <span>' + esc(t('excl')) + '</span></p>'
					+ (b.bandbreedte ? '<p class="sfp-calc-klein">' + esc(t('bandbreedte', { van: b.mensen.van, tot: b.mensen.tot, prijs: bedrag(b.bandbreedte.van) + ' - ' + bedrag(b.bandbreedte.tot) })) + '</p>' : '');
			}
			var geldig = '';
			if (a.geldig_tot) {
				try { geldig = new Date(a.geldig_tot + 'T12:00:00').toLocaleDateString(C.taal === 'en' ? 'en-GB' : 'nl-NL', { day: 'numeric', month: 'long', year: 'numeric' }); } catch (e) { geldig = a.geldig_tot; }
			}
			return {
				html: '<div class="sfp-calc-klaar">'
					+ '<div><span class="sfp-calc-lab">' + esc(t('klaar_lab')) + '</span><h2 class="sfp-calc-titel">' + esc(t('klaar_kop')) + '</h2>'
					+ '<p class="sfp-calc-intro">' + esc(t('klaar_zin', { kenmerk: a.kenmerk || '' })) + '</p></div>'
					+ '<div class="sfp-calc-bon">' + totaal
					+ (wat.length ? '<p class="sfp-calc-lijstkop">' + esc(t('klaar_wat')) + '</p><ul class="sfp-calc-lijst">' + wat.map(function (w) { return '<li>' + esc(w) + '</li>'; }).join('') + '</ul>' : '')
					+ (geldig ? '<p class="sfp-calc-klein">' + esc(t('klaar_geldig', { datum: geldig })) + '</p>' : '')
					+ (a.pdf_url ? '<p><a class="sfp-calc-cta2" href="' + esc(a.pdf_url) + '" target="_blank" rel="noopener">' + esc(t('knop_pdf')) + '</a></p>' : '')
					+ '</div>'
					+ '<div class="sfp-calc-stap">'
					+ (c.foto ? '<img class="sfp-calc-foto" src="' + esc(c.foto) + '" alt="" width="88" height="88">' : '')
					+ '<div class="sfp-calc-stapin"><p class="sfp-calc-stapkop">' + esc(t('klaar_stap_kop')) + '</p><p>' + esc(t('klaar_stap_zin')) + '</p>'
					+ (boek ? '<a class="sfp-calc-knop" href="' + esc(boek) + '">' + esc(t('knop_gesprek')) + '</a>' : '')
					+ (c.naam ? '<p class="sfp-calc-klein">' + esc(t('klaar_contact')) + ': <b>' + esc(c.naam) + '</b>' + (c.functie ? ', ' + esc(c.functie) : '') + (c.telefoon ? ' | ' + esc(c.telefoon) : '') + '</p>' : '')
					+ '</div></div>'
					+ '</div>',
				na: function () {}
			};
		};

		/* ---------- De begroting naast de vragen ---------- */

		function begrotingHtml() {
			if (S.doel === 'evenement' || !S.vorm) return '';
			var p = pad();
			if (p.indexOf(huidig()) <= p.indexOf('vorm') || huidig() === 'klaar') return '';
			var u = reken();
			var o = u.uitkomst;
			if (!o || !o.groepen) return '';
			var regels = o.regels.map(function (r) {
				return '<li><span>' + esc(t('regel_' + r.code)) + (r.aantal > 1 ? ' <i>x' + r.aantal + '</i>' : '') + '</span><span>' + esc(r.gratis ? t('inbegrepen') : bedrag(r.bedrag)) + '</span></li>';
			}).join('');
			var groepen = (o.groepen === 1 ? t('groep') : t('groepen', { n: o.groepen })) + (o.verdeling.length > 1 ? ' (' + o.verdeling.join(', ') + ')' : '') + ', ' + t('vorm_' + S.vorm);
			var tot = '<p class="sfp-calc-btotaal"><b>' + esc(bedrag(o.totaal_excl)) + '</b> <span>' + esc(t('excl')) + '</span></p>';
			var breed = !window.matchMedia || window.matchMedia('(min-width: 1001px)').matches;
			return '<details class="sfp-calc-begroting"' + (breed ? ' open' : '') + '><summary><span class="sfp-calc-lab">' + esc(u.richtprijs ? t('richtprijs') : t('begroting')) + '</span>' + tot + '</summary>'
				+ '<p class="sfp-calc-bgroepen">' + esc(groepen) + '</p><ul class="sfp-calc-regels">' + regels + '</ul>'
				+ (u.bandbreedte ? '<p class="sfp-calc-klein">' + esc(t('bandbreedte', { van: u.mensen.van, tot: u.mensen.tot, prijs: bedrag(u.bandbreedte.van) + ' - ' + bedrag(u.bandbreedte.tot) })) + '</p>' : '')
				+ (o.meldingen.indexOf('abonnement') > -1 ? '<p class="sfp-calc-klein">' + esc(t('melding_abonnement')) + '</p>' : '')
				+ '</details>';
		}

		/* ---------- Opslaan en formulier ---------- */

		function post(padNaam, gegevens) {
			var kopjes = { 'Content-Type': 'application/json' };
			if (C.nonce) kopjes['X-WP-Nonce'] = C.nonce;
			return fetch(C.rest + padNaam, { method: 'POST', credentials: C.nonce ? 'same-origin' : 'omit', headers: kopjes, body: JSON.stringify(gegevens) })
				.then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { if (!r.ok) throw new Error((j && j.message) || t('fout')); return j; }); });
		}
		function bron() {
			var utm = {};
			['source', 'medium', 'campaign'].forEach(function (k) {
				var w = params.get('utm_' + k) || '';
				try { if (w) sessionStorage.setItem('sfp_calc_utm_' + k, w); else w = sessionStorage.getItem('sfp_calc_utm_' + k) || ''; } catch (e) { /* geen opslag */ }
				utm[k] = w;
			});
			var ref = window.location.origin + window.location.pathname;
			try {
				var r = document.referrer ? new URL(document.referrer) : null;
				if (r && r.host === window.location.host && r.pathname !== window.location.pathname) ref = r.origin + r.pathname;
			} catch (e) { /* eigen pagina */ }
			return { pagina_url: ref, utm: utm, gclid: params.get('gclid') || '' };
		}
		function labelVan(naam) {
			var deel = String(naam || '').split('-lbl-')[1];
			if (!deel) return '';
			deel = deel.split('-')[0];
			while (deel.length % 4) deel += '=';
			try { return decodeURIComponent(escape(window.atob(deel))).trim().toLowerCase(); } catch (e) { return ''; }
		}
		function vulFormulier(velden) {
			if (!formVak) return;
			Array.prototype.forEach.call(formVak.querySelectorAll('input[name*="-lbl-"],textarea[name*="-lbl-"]'), function (inp) {
				var label = labelVan(inp.getAttribute('name'));
				if (Object.prototype.hasOwnProperty.call(velden, label)) {
					inp.value = velden[label];
					inp.dispatchEvent(new Event('input', { bubbles: true }));
					inp.dispatchEvent(new Event('change', { bubbles: true }));
				}
			});
		}
		function bewaarBegroting() {
			if (S.bezig) return;
			S.bezig = true;
			S.fout = '';
			var k = keuzes();
			var b = bron();
			post('begroting', {
				keuzes: k,
				situatie: S.situatie,
				intern: { beslist: S.beslist, budget: S.budget, eerder: S.eerder, beviel: S.beviel, vorig: S.vorig },
				inkoop: S.inkoop.aan === 'ja' ? { aan: true, po: S.inkoop.po, kostenplaats: S.inkoop.kostenplaats, referentie: S.inkoop.referentie, factuuradres: S.inkoop.factuuradres } : { aan: false },
				pagina_url: b.pagina_url, utm: b.utm, gclid: b.gclid, website: ''
			}).then(function (a) {
				S.antwoord = a;
				S.bezig = false;
				vulFormulier(a.velden || {});
				tekenAlles();
			}).catch(function (e) {
				S.bezig = false;
				S.fout = e && e.message ? e.message : t('fout');
				tekenAlles();
			});
		}
		document.addEventListener('srfm_form_submission_success', function (e) {
			var id = e && e.detail ? String(e.detail.formId || '') : '';
			if (!C.formulier || (id !== 'srfm-form-' + C.formulier && id !== String(C.formulier))) return;
			if (huidig() !== 'gegevens') return;
			var b = S.antwoord && S.antwoord.berekening;
			var tot = b && b.uitkomst ? b.uitkomst.totaal_excl : 0;
			meld('calculator_afgerond', { calculator_totaalklasse: tot >= 20000 ? '20000+' : tot >= 10000 ? '10000-20000' : tot >= 5000 ? '5000-10000' : tot > 0 ? '0-5000' : '' });
			if (formVak) { formVak.hidden = true; wortel.appendChild(formVak); }
			S.i = pad().indexOf('klaar');
			tekenAlles();
		});

		/* ---------- Tekenen ---------- */

		function naarBoven() {
			var top = wortel.getBoundingClientRect().top + window.pageYOffset - 90;
			if (window.pageYOffset > top) window.scrollTo(0, Math.max(0, top));
		}
		function tekenAlles() {
			if (formVak && formVak.parentNode !== wortel && huidig() !== 'gegevens') { formVak.hidden = true; wortel.appendChild(formVak); }
			var id = huidig();
			var s = (schermen[id] || schermen.start)();
			var zij = begrotingHtml();
			app.innerHTML = '<div class="sfp-calc-raster' + (zij ? ' met-zij' : '') + '"><div class="sfp-calc-scherm sfp-calc-s-' + esc(id) + '">' + s.html + '</div>'
				+ (zij ? '<aside class="sfp-calc-zij" aria-label="' + esc(t('begroting')) + '">' + zij + '</aside>' : '') + '</div>';
			s.na();
			if ($('#sfp-calc-vorige')) $('#sfp-calc-vorige').addEventListener('click', vorige);
			if (id !== 'start' || S.i > 0) naarBoven();
			var v = $('#sfp-calc-vraagtekst');
			if (v) v.focus({ preventScroll: true });
		}

		/* ---------- Ingebed ---------- */

		if (C.ingebed) {
			app.innerHTML = '<div class="sfp-calc-ingebed"><h2 class="sfp-calc-ikop">' + esc(t('ingebed_kop')) + '</h2><p class="sfp-calc-intro">' + esc(t('ingebed_zin')) + '</p>'
				+ '<div class="sfp-calc-irij"><div class="sfp-calc-veld"><label for="sfp-calc-iaantal">' + esc(t('mensen_aantal')) + '</label><input id="sfp-calc-iaantal" type="number" inputmode="numeric" min="1" max="' + N.max_deelnemers + '"></div>'
				+ '<a class="sfp-calc-knop" id="sfp-calc-naar" href="' + esc(C.pagina || '#') + '">' + esc(t('ingebed_knop')) + '</a></div></div>';
			$('#sfp-calc-naar').addEventListener('click', function (e) {
				if (!C.pagina) { e.preventDefault(); return; }
				var n = geheel($('#sfp-calc-iaantal').value, 1, N.max_deelnemers);
				meld('calculator_start');
				if (n) { e.preventDefault(); window.location.href = C.pagina + (C.pagina.indexOf('?') > -1 ? '&' : '?') + 'n=' + n; }
			});
			return;
		}

		if (vanBuiten) S.i = pad().indexOf('uitvraag');
		var sluit = document.querySelector('.sfp-calc-sluit');
		if (sluit) {
			sluit.addEventListener('click', function (e) {
				try {
					var r = document.referrer ? new URL(document.referrer) : null;
					if (r && r.host === window.location.host && window.history.length > 1) { e.preventDefault(); window.history.back(); }
				} catch (x) { /* gewone link */ }
			});
		}
		tekenAlles();
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
	else start();
})();
