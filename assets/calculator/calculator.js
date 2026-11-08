/**
 * SFP Page Config - Incompanycalculator op de pagina (sinds 3.36.0).
 *
 * Eén vraag per scherm. Route A: route, situatie, aantal mensen (met
 * doelgroep), vorm, extra's, locatie, intern proces (met inkoopvakje),
 * gegevens (SureForms), eindscherm. Route B: evenement. Route C: uitvraag.
 * De begroting rekent vanaf het aantal mensen live mee (motor.js); bij
 * stap 8 slaat de server hem op met een eigen berekening en geeft het
 * kenmerk en de waarden van de verborgen velden.
 *
 * Ingebed (op een incompany-pagina): alleen route en aantal met een eerste
 * begroting; Verder opent de calculatorpagina met die keuzes.
 *
 * Geen naam of e-mail in de dataLayer: alleen calculator_start en
 * calculator_afgerond met route en totaalklasse.
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
		var T = C.t || {};
		var N = C.norm;

		function t(s, v) {
			var tekst = Object.prototype.hasOwnProperty.call(T, s) ? T[s] : s;
			if (v) Object.keys(v).forEach(function (k) { tekst = tekst.split('{' + k + '}').join(String(v[k])); });
			return tekst;
		}
		function el(tag, attrs, kids) {
			var n = document.createElement(tag);
			Object.keys(attrs || {}).forEach(function (k) {
				var w = attrs[k];
				if (w === null || w === undefined || w === false) return;
				if (k === 'text') n.textContent = w;
				else if (k === 'klik') n.addEventListener('click', w);
				else if (k === 'bij') n.addEventListener('input', w);
				else if (k === 'wissel') n.addEventListener('change', w);
				else if (k === 'class') n.className = w;
				else if (k === 'checked' || k === 'value') n[k] = w;
				else n.setAttribute(k, w === true ? '' : w);
			});
			[].concat(kids || []).forEach(function (c) {
				if (c === null || c === undefined || c === false) return;
				n.appendChild(typeof c === 'string' ? document.createTextNode(c) : c);
			});
			return n;
		}
		var valuta = { EUR: '€', GBP: '£', USD: '$' };
		function centen(c) {
			var heel = Math.floor(Math.abs(c) / 100);
			var rest = String(Math.abs(c) % 100);
			return bedrag(heel) + (C.taal === 'en' ? '.' : ',') + (rest.length < 2 ? '0' + rest : rest);
		}
		function bedrag(b) {
			var n = Math.abs(Math.round(b));
			var s = C.taal === 'en' ? n.toLocaleString('en-GB') : String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
			return (b < 0 ? '- ' : '') + (valuta[N.valuta] || N.valuta) + ' ' + s;
		}

		/* ---------- Stand ---------- */

		var params = new URLSearchParams(window.location.search);
		var S = {
			stap: 0,
			route: '',
			keuzes: {
				mensen: { soort: 'getal', aantal: '', van: '', tot: '' },
				doelgroep: '', doelgroep_anders: '',
				vorm: 0, begeleiding: false,
				extras: { coachuren: 0, clinic: false, boek: false, acteur: 0, terugkom: '', lotj: 0, scan: '' },
				groepsvoordeel: 'korting',
				locatieAanvraag: false, locatie: [],
				sprekers: '', soort: ''
			},
			situatie: { doel: '', waarom: [], eerder: '', criteria: '' },
			intern: { beslist: '', budget: '', vorig: '' },
			inkoop: { aan: false, po: '', kostenplaats: '', referentie: '', factuuradres: '' },
			antwoord: null,
			bezig: false,
			fout: ''
		};
		/* Keuzes uit de ingebedde versie. */
		var pr = params.get('route');
		if (pr === 'A' || pr === 'B' || pr === 'C') S.route = pr;
		if (params.get('n')) { S.keuzes.mensen.aantal = String(parseInt(params.get('n'), 10) || ''); }
		if (params.get('van') && params.get('tot')) { S.keuzes.mensen = { soort: 'marge', aantal: '', van: params.get('van'), tot: params.get('tot') }; }
		if (params.get('onbekend') === '1') S.keuzes.mensen.soort = 'onbekend';

		var gemeld = {};
		function meld(naam, extra) {
			if (gemeld[naam]) return;
			gemeld[naam] = true;
			try {
				window.dataLayer = window.dataLayer || [];
				var e = { event: naam, calculator_route: S.route || '' };
				Object.keys(extra || {}).forEach(function (k) { e[k] = extra[k]; });
				window.dataLayer.push(e);
			} catch (x) { /* meten mag de calculator nooit breken */ }
		}

		function routes() {
			return ['A', 'B', 'C'].filter(function (r) { return N.routes_aan && N.routes_aan[r]; });
		}
		function stappen() {
			if (C.ingebed) return ['ingebed'];
			if (S.route === 'B') return ['route', 'evenement', 'gegevens', 'klaar'];
			if (S.route === 'C') return ['route', 'uitvraag', 'gegevens', 'klaar'];
			return ['route', 'situatie', 'mensen', 'vorm', 'extras', 'locatie', 'intern', 'gegevens', 'klaar'];
		}
		function huidig() { return stappen()[S.stap] || 'route'; }

		function reken() {
			var k = JSON.parse(JSON.stringify(S.keuzes));
			k.route = S.route || 'A';
			if (!k.vorm) k.vorm = C.ingebed || huidig() === 'mensen' ? 2 : 0;
			return M.reken(k, N);
		}
		function mensenKlaar() {
			var m = S.keuzes.mensen;
			if (m.soort === 'onbekend') return true;
			if (m.soort === 'marge') return parseInt(m.van, 10) > 0 && parseInt(m.tot, 10) > 0;
			return parseInt(m.aantal, 10) > 0;
		}
		function magVerder() {
			var s = huidig();
			if (s === 'route') return !!S.route;
			if (s === 'mensen') return mensenKlaar();
			if (s === 'vorm') return !!S.keuzes.vorm;
			if (s === 'evenement') return parseInt(S.keuzes.sprekers, 10) > 0;
			return true;
		}

		/* ---------- Onderdelen ---------- */

		function info(sleutel) {
			var tekst = (C.uitleg || {})[sleutel];
			if (!tekst) return null;
			var knop = el('button', { type: 'button', class: 'sfp-calc-i', 'aria-label': t('uitleg'), 'aria-expanded': 'false', text: 'i' });
			var vak = el('span', { class: 'sfp-calc-i-tekst', hidden: true, role: 'note', text: tekst });
			knop.addEventListener('click', function () {
				var open = knop.getAttribute('aria-expanded') !== 'true';
				knop.setAttribute('aria-expanded', open ? 'true' : 'false');
				vak.hidden = !open;
			});
			return el('span', { class: 'sfp-calc-i-wrap' }, [knop, vak]);
		}
		function keuze(naam, waarde, gekozen, label, zin, bij, type) {
			var id = 'sfp-calc-' + naam + '-' + waarde;
			var invoer = el('input', { type: type || 'radio', name: 'sfp-calc-' + naam, id: id, value: waarde, checked: !!gekozen });
			invoer.addEventListener('change', function () { bij(invoer.checked, waarde); });
			return el('label', { class: 'sfp-calc-optie' + (gekozen ? ' is-gekozen' : ''), for: id }, [
				invoer,
				el('span', { class: 'sfp-calc-optie-tekst' }, [el('b', { text: label }), zin ? el('span', { text: zin }) : null])
			]);
		}
		function veld(label, invoer, hulp) {
			return el('div', { class: 'sfp-calc-veld' }, [el('label', { text: label }, []), invoer, hulp ? el('span', { class: 'sfp-calc-hulp', text: hulp }) : null]);
		}
		function tekstvak(waarde, bij, lang, type) {
			var n = lang ? el('textarea', { rows: '4' }) : el('input', { type: type || 'text' });
			n.value = waarde || '';
			n.addEventListener('input', function () { bij(n.value); knopStand(); });
			return n;
		}
		function getal(waarde, bij, min, max) {
			var n = el('input', { type: 'number', inputmode: 'numeric', min: String(min), max: String(max), step: '1' });
			n.value = waarde === 0 || waarde ? String(waarde) : '';
			n.addEventListener('input', function () { bij(n.value); knopStand(); });
			return n;
		}
		function melding(tekst, links) {
			return el('p', { class: 'sfp-calc-melding' }, [tekst].concat(links || []));
		}

		/* De begroting naast of onder de vragen. */
		function begroting(compact) {
			if (S.route !== 'A' && !C.ingebed) return null;
			if (C.ingebed && S.route && S.route !== 'A') return null;
			if (!mensenKlaar()) return null;
			var u = reken();
			var o = u.uitkomst;
			if (!o || !o.groepen) return null;
			var regels = [];
			if (!compact) {
				o.regels.forEach(function (r) {
					regels.push(el('li', null, [
						el('span', { text: t('regel_' + r.code) + ' x' + r.aantal }),
						el('span', { text: r.gratis ? t('inbegrepen') : bedrag(r.bedrag) })
					]));
				});
			}
			var mel = [];
			o.meldingen.forEach(function (m) {
				if (m === 'klein') {
					var links = [];
					if (C.coaching) links.push(' ', el('a', { href: C.coaching, text: t('link_coaching') }));
					if (C.open) links.push(' ', el('a', { href: C.open, text: t('link_open') }));
					mel.push(melding(t('melding_klein'), links));
				} else if (m === 'maatwerk') {
					mel.push(melding(t('melding_maatwerk')));
					mel.push(melding(t('melding_groot')));
				} else {
					mel.push(melding(t('melding_' + m)));
				}
			});
			if (S.keuzes.mensen.soort === 'onbekend') mel.push(melding(t('melding_onbekend')));
			var groepen = o.groepen === 1 ? t('groep') : t('groepen', { n: o.groepen });
			return el('aside', { class: 'sfp-calc-begroting', 'aria-label': t('begroting') }, [
				el('span', { class: 'sfp-calc-lab', text: C.ingebed ? t('ingebed_vanaf') : (u.richtprijs ? t('richtprijs') : t('begroting')) }),
				el('p', { class: 'sfp-calc-groepen', text: groepen + (o.verdeling.length > 1 ? ' (' + o.verdeling.join(', ') + ')' : '') + (S.keuzes.vorm ? ', ' + t('vorm_' + S.keuzes.vorm) : ', ' + t('vorm_2')) }),
				regels.length ? el('ul', { class: 'sfp-calc-regels' }, regels) : null,
				el('p', { class: 'sfp-calc-totaal' }, [el('b', { text: bedrag(o.totaal_excl) }), ' ', el('span', { text: t('excl') })]),
				u.bandbreedte ? el('p', { class: 'sfp-calc-hulp', text: t('bandbreedte', { van: u.mensen.van, tot: u.mensen.tot, prijs: bedrag(u.bandbreedte.van) + ' - ' + bedrag(u.bandbreedte.tot) }) }) : null,
				compact ? null : el('p', { class: 'sfp-calc-hulp', text: t('altijd_inbegrepen') })
			].concat(mel));
		}

		/* ---------- Schermen ---------- */

		function schermRoute() {
			var lijst = routes().map(function (r) {
				return keuze('route', r, S.route === r, t('route_' + r), t('route_' + r + '_zin'), function () { S.route = r; S.stap = 0; teken(); });
			});
			return [
				el('h2', { class: 'sfp-calc-vraag', text: t('route_vraag') }),
				el('div', { class: 'sfp-calc-opties' }, lijst),
				el('p', { class: 'sfp-calc-hulp', text: t('afwijkend') })
			];
		}
		function schermSituatie() {
			var waarom = ['nieuwe_rol', 'groei', 'moment', 'feedback', 'beleid', 'anders'].map(function (w) {
				return keuze('waarom', w, S.situatie.waarom.indexOf(w) > -1, t('waarom_' + w), '', function (aan) {
					S.situatie.waarom = S.situatie.waarom.filter(function (x) { return x !== w; });
					if (aan) S.situatie.waarom.push(w);
				}, 'checkbox');
			});
			return [
				el('h2', { class: 'sfp-calc-vraag', text: t('situatie_vraag') }),
				veld(t('situatie_vraag'), tekstvak(S.situatie.doel, function (w) { S.situatie.doel = w; }, true), t('situatie_hulp')),
				el('h3', { text: t('waarom_vraag') }),
				el('div', { class: 'sfp-calc-opties is-klein' }, waarom),
				veld(t('eerder_vraag') + ' (' + t('optioneel') + ')', tekstvak(S.situatie.eerder, function (w) { S.situatie.eerder = w; }, true), t('eerder_hulp'))
			];
		}
		function schermMensen() {
			var m = S.keuzes.mensen;
			var soorten = ['getal', 'marge', 'onbekend'].map(function (s) {
				return keuze('mensen', s, m.soort === s, t('mensen_' + s), '', function () { m.soort = s; teken(); });
			});
			var invoer = null;
			if (m.soort === 'getal') invoer = veld(t('mensen_aantal'), getal(m.aantal, function (w) { m.aantal = w; tekenBegroting(); }, 1, N.max_deelnemers));
			if (m.soort === 'marge') invoer = el('div', { class: 'sfp-calc-twee' }, [
				veld(t('mensen_van'), getal(m.van, function (w) { m.van = w; tekenBegroting(); }, 1, N.max_deelnemers)),
				veld(t('mensen_tot'), getal(m.tot, function (w) { m.tot = w; tekenBegroting(); }, 1, N.max_deelnemers))
			]);
			var delen = [
				el('h2', { class: 'sfp-calc-vraag', text: t('mensen_vraag') }),
				el('div', { class: 'sfp-calc-opties is-rij' }, soorten),
				invoer
			];
			if (!C.ingebed) {
				var dg = el('select');
				dg.appendChild(el('option', { value: '', text: '' }));
				['starters', 'ervaren', 'senioren', 'leidinggevend', 'gemengd', 'anders'].forEach(function (d) {
					dg.appendChild(el('option', { value: d, text: t('doelgroep_' + d) }));
				});
				dg.value = S.keuzes.doelgroep;
				dg.addEventListener('change', function () { S.keuzes.doelgroep = dg.value; teken(); });
				delen.push(veld(t('doelgroep_vraag') + ' (' + t('optioneel') + ')', dg));
				if (S.keuzes.doelgroep === 'anders') {
					delen.push(veld(t('doelgroep_anders'), tekstvak(S.keuzes.doelgroep_anders, function (w) { S.keuzes.doelgroep_anders = w; })));
				}
			}
			return delen;
		}
		function schermVorm() {
			var kaarten = ['1', '2', '3'].filter(function (v) { return N.vormen[v]; }).map(function (v) {
				var k = JSON.parse(JSON.stringify(S.keuzes));
				k.route = 'A'; k.vorm = Number(v); k.extras = {}; k.begeleiding = false;
				var u = M.reken(k, N).uitkomst;
				var perGroep = N.vormen[v].prijs;
				return keuze('vorm', v, String(S.keuzes.vorm) === v, t('vorm_' + v) + ': ' + bedrag(perGroep) + ' ' + t('per_groep'),
					t('vorm_' + v + '_zin') + ' ' + t('vorm_inleveren', { n: v }) + (u && u.groepen > 1 ? ' ' + t('totaal') + ': ' + bedrag(u.totaal_excl) + ' ' + t('excl') + '.' : ''),
					function () { S.keuzes.vorm = Number(v); teken(); });
			});
			var delen = [
				el('h2', { class: 'sfp-calc-vraag', text: t('vorm_vraag') }),
				el('div', { class: 'sfp-calc-opties is-kaarten' }, kaarten),
				el('p', { class: 'sfp-calc-hulp', text: t('altijd_inbegrepen') })
			];
			if (S.keuzes.vorm && N.vormen[String(S.keuzes.vorm)].begeleiding === 'keuze') {
				delen.push(el('div', { class: 'sfp-calc-extra' }, [
					keuze('begeleiding', '1', S.keuzes.begeleiding, t('begeleiding') + ' (+ ' + bedrag(N.begeleiding_prijs) + ' ' + t('per_groep') + ')', t('begeleiding_zin'), function (aan) { S.keuzes.begeleiding = aan; tekenBegroting(); }, 'checkbox'),
					info('begeleiding')
				]));
			}
			return delen;
		}
		function schermExtras() {
			var ex = S.keuzes.extras;
			var aan = N.extras_aan || {};
			var vorm = Number(S.keuzes.vorm);
			var rijen = [];
			function rij(sleutel, inhoud) {
				rijen.push(el('div', { class: 'sfp-calc-extra' }, [inhoud, info(sleutel)]));
			}
			if (aan.coachuren) rij('coachuren', veld(t('extra_coachuren') + ': ' + bedrag(N.blok_prijs) + ' / ' + t('extra_coachuren_eenheid'), getal(ex.coachuren, function (w) { ex.coachuren = w; tekenBegroting(); }, 0, 50)));
			if (aan.clinic) rij('clinic', keuze('clinic', '1', ex.clinic, t('extra_clinic') + ': ' + (vorm >= N.clinic_gratis_vanaf ? t('gratis') : bedrag(N.clinic_prijs) + ' ' + t('extra_clinic_eenheid')), '', function (a) { ex.clinic = a; tekenBegroting(); }, 'checkbox'));
			if (aan.boek) rij('boek', keuze('boek', '1', ex.boek, t('extra_boek') + ': ' + (vorm >= N.boek_gratis_vanaf ? t('gratis') : centen(N.boek_centen) + ' ' + t('extra_boek_eenheid')), '', function (a) { ex.boek = a; tekenBegroting(); }, 'checkbox'));
			if (aan.acteur) rij('acteur', veld(t('extra_acteur') + ': ' + bedrag(N.acteur_prijs) + ' / ' + t('extra_acteur_eenheid'), getal(ex.acteur, function (w) { ex.acteur = w; tekenBegroting(); }, 0, 200)));
			if (aan.terugkom) {
				var tk = el('select');
				[['', t('extra_terugkom_geen')], ['dagdeel', t('extra_terugkom_dagdeel') + ', ' + bedrag(N.terugkom.dagdeel)], ['online', t('extra_terugkom_online') + ', ' + bedrag(N.terugkom.online)]].forEach(function (o) { tk.appendChild(el('option', { value: o[0], text: o[1] })); });
				tk.value = ex.terugkom;
				tk.addEventListener('change', function () { ex.terugkom = tk.value; tekenBegroting(); });
				rij('terugkom', veld(t('extra_terugkom'), tk));
			}
			if (aan.lotj) rij('lotj', veld(t('extra_lotj') + ': ' + bedrag(N.blok_prijs) + ' / ' + t('extra_lotj_eenheid'), getal(ex.lotj, function (w) { ex.lotj = w; tekenBegroting(); }, 0, 50)));
			if (aan.scan) {
				var sc = el('select');
				[['', t('extra_scan_geen')], ['individueel', t('extra_scan_individueel') + ', ' + bedrag(N.scan_individueel)], ['team', t('extra_scan_team') + ', ' + bedrag(N.scan_team)]].forEach(function (o) { sc.appendChild(el('option', { value: o[0], text: o[1] })); });
				sc.value = ex.scan;
				sc.addEventListener('change', function () { ex.scan = sc.value; tekenBegroting(); });
				rij('scan', veld(t('extra_scan'), sc));
			}
			var delen = [
				el('h2', { class: 'sfp-calc-vraag', text: t('extras_vraag') }),
				el('p', { class: 'sfp-calc-hulp', text: t('extras_hulp') })
			].concat(rijen);
			var u = reken().uitkomst;
			if (u && vorm >= 2 && u.groepen >= N.voordeel_vanaf_groep) {
				delen.push(el('h3', { text: t('voordeel_vraag') }));
				delen.push(el('div', { class: 'sfp-calc-opties is-klein' }, ['korting', 'coachuren'].map(function (v) {
					return keuze('voordeel', v, S.keuzes.groepsvoordeel === v, v === 'korting' ? t('voordeel_korting').replace(/€ ?1\.000/, bedrag(N.voordeel_bedrag)) : t('voordeel_coachuren'), '', function () { S.keuzes.groepsvoordeel = v; tekenBegroting(); });
				})));
			}
			return delen;
		}
		function schermLocatie() {
			var k = S.keuzes;
			var delen = [
				el('h2', { class: 'sfp-calc-vraag', text: t('locatie_vraag') }),
				el('div', { class: 'sfp-calc-opties is-klein' }, [
					keuze('locatie', 'klant', !k.locatieAanvraag, t('locatie_klant'), '', function () { k.locatieAanvraag = false; k.locatie = []; teken(); }),
					keuze('locatie', 'aanvraag', k.locatieAanvraag, t('locatie_aanvraag'), '', function () { k.locatieAanvraag = true; teken(); })
				])
			];
			if (k.locatieAanvraag) {
				delen.push(el('div', { class: 'sfp-calc-opties is-klein' }, ['extern', 'lunch', 'borrel', 'teamdag'].map(function (l) {
					return keuze('loc', l, k.locatie.indexOf(l) > -1, t('locatie_' + l), '', function (aan) {
						k.locatie = k.locatie.filter(function (x) { return x !== l; });
						if (aan) k.locatie.push(l);
					}, 'checkbox');
				})));
			}
			return delen;
		}
		function schermIntern() {
			var p = S.inkoop;
			var delen = [
				el('h2', { class: 'sfp-calc-vraag', text: t('intern_vraag') }),
				el('p', { class: 'sfp-calc-hulp', text: t('intern_hulp') }),
				veld(t('intern_beslist'), tekstvak(S.intern.beslist, function (w) { S.intern.beslist = w; })),
				veld(t('intern_budget'), tekstvak(S.intern.budget, function (w) { S.intern.budget = w; })),
				veld(t('intern_vorig'), tekstvak(S.intern.vorig, function (w) { S.intern.vorig = w; }, true)),
				keuze('inkoop', '1', p.aan, t('inkoop_vakje'), '', function (aan) { p.aan = aan; teken(); }, 'checkbox')
			];
			if (p.aan) {
				delen.push(el('div', { class: 'sfp-calc-twee' }, [
					veld(t('inkoop_po') + ' (' + t('optioneel') + ')', tekstvak(p.po, function (w) { p.po = w; })),
					veld(t('inkoop_kostenplaats') + ' (' + t('optioneel') + ')', tekstvak(p.kostenplaats, function (w) { p.kostenplaats = w; }))
				]));
				delen.push(veld(t('inkoop_referentie') + ' (' + t('optioneel') + ')', tekstvak(p.referentie, function (w) { p.referentie = w; })));
				delen.push(veld(t('inkoop_factuuradres') + ' (' + t('optioneel') + ')', tekstvak(p.factuuradres, function (w) { p.factuuradres = w; }, true)));
			}
			return delen;
		}
		function schermEvenement() {
			var k = S.keuzes;
			return [
				el('h2', { class: 'sfp-calc-vraag', text: t('sprekers_vraag') }),
				veld(t('sprekers_vraag'), getal(k.sprekers, function (w) { k.sprekers = w; }, 1, 500)),
				el('h3', { text: t('soort_vraag') }),
				el('div', { class: 'sfp-calc-opties is-klein' }, ['keynote', 'meerdere', 'pitch', 'anders'].map(function (s) {
					return keuze('soort', s, k.soort === s, t('soort_' + s), '', function () { k.soort = s; });
				})),
				veld(t('situatie_vraag'), tekstvak(S.situatie.doel, function (w) { S.situatie.doel = w; }, true), t('situatie_hulp')),
				el('p', { class: 'sfp-calc-begroting is-los' }, [el('span', { class: 'sfp-calc-lab', text: t('richtprijs') }), ' ', t('evenement_prijs', { prijs: bedrag(N.evenement_prijs) })])
			];
		}
		function schermUitvraag() {
			return [
				el('h2', { class: 'sfp-calc-vraag', text: t('criteria_vraag') }),
				veld(t('criteria_vraag'), tekstvak(S.situatie.criteria, function (w) { S.situatie.criteria = w; }, true), t('criteria_hulp')),
				veld(t('situatie_vraag') + ' (' + t('optioneel') + ')', tekstvak(S.situatie.doel, function (w) { S.situatie.doel = w; }, true))
			];
		}
		function schermGegevens() {
			var delen = [
				el('h2', { class: 'sfp-calc-vraag', text: t('gegevens_vraag') }),
				el('p', { class: 'sfp-calc-hulp', text: t('gegevens_hulp') })
			];
			if (S.fout) delen.push(el('p', { class: 'sfp-calc-fout', role: 'alert', text: S.fout }));
			if (!formVak || !C.formulier) {
				if (C.beheerder) delen.push(el('p', { class: 'sfp-calc-fout', text: t('geen_formulier') }));
			}
			return delen;
		}
		function schermKlaar() {
			var a = S.antwoord || {};
			var knoppen = [];
			if (C.boek) knoppen.push(el('a', { class: 'sfp-calc-knop', href: C.boek + (C.boek.indexOf('?') > -1 ? '&' : '?') + 'kenmerk=' + encodeURIComponent(a.kenmerk || ''), text: t('knop_gesprek') }));
			if (S.route !== 'C' && C.offerte) knoppen.push(el('a', { class: 'sfp-calc-knop is-stil', href: C.offerte + (C.offerte.indexOf('?') > -1 ? '&' : '?') + 'kenmerk=' + encodeURIComponent(a.kenmerk || ''), text: t('knop_offerte') }));
			if (a.pdf_url) knoppen.push(el('a', { class: 'sfp-calc-link', href: a.pdf_url, target: '_blank', rel: 'noopener', text: t('knop_pdf') }));
			var b = S.route === 'A' ? begroting(false) : null;
			return [
				el('h2', { class: 'sfp-calc-vraag', text: S.route === 'C' ? t('klaar_c_kop') : t('klaar_kop') }),
				el('p', { text: S.route === 'C' ? t('klaar_c_zin') : t('klaar_zin', { kenmerk: a.kenmerk || '' }) }),
				b,
				el('div', { class: 'sfp-calc-knoppen' }, knoppen)
			];
		}
		function schermIngebed() {
			var delen = [
				el('h2', { class: 'sfp-calc-vraag', text: t('ingebed_kop') }),
				el('p', { class: 'sfp-calc-hulp', text: t('ingebed_zin') }),
				el('div', { class: 'sfp-calc-opties is-klein' }, routes().map(function (r) {
					return keuze('route', r, S.route === r, t('route_' + r), '', function () { S.route = r; teken(); });
				}))
			];
			if (S.route === 'A' || !S.route) delen = delen.concat(schermMensen());
			return delen;
		}

		/* ---------- Server en formulier ---------- */

		function post(pad, gegevens) {
			var kop = { 'Content-Type': 'application/json' };
			if (C.nonce) kop['X-WP-Nonce'] = C.nonce;
			return fetch(C.rest + pad, {
				method: 'POST',
				credentials: C.nonce ? 'same-origin' : 'omit',
				headers: kop,
				body: JSON.stringify(gegevens)
			}).then(function (r) {
				return r.json().then(function (j) { if (!r.ok) throw new Error((j && j.message) || t('fout')); return j; });
			});
		}
		function bron() {
			var utm = {};
			['source', 'medium', 'campaign'].forEach(function (k) {
				var w = params.get('utm_' + k);
				try { if (w) sessionStorage.setItem('sfp_calc_utm_' + k, w); else w = sessionStorage.getItem('sfp_calc_utm_' + k) || ''; } catch (e) { w = w || ''; }
				utm[k] = w;
			});
			var ref = '';
			try {
				var r = document.referrer ? new URL(document.referrer) : null;
				ref = r && r.host === window.location.host && r.pathname !== window.location.pathname ? r.origin + r.pathname : window.location.origin + window.location.pathname;
			} catch (e) { ref = window.location.origin + window.location.pathname; }
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
			[].forEach.call(formVak.querySelectorAll('input[name*="-lbl-"],textarea[name*="-lbl-"]'), function (inp) {
				var label = labelVan(inp.getAttribute('name'));
				if (Object.prototype.hasOwnProperty.call(velden, label)) {
					inp.value = velden[label];
					inp.dispatchEvent(new Event('input', { bubbles: true }));
					inp.dispatchEvent(new Event('change', { bubbles: true }));
				}
			});
		}
		function naarGegevens() {
			S.bezig = true; S.fout = '';
			var k = JSON.parse(JSON.stringify(S.keuzes));
			k.route = S.route;
			if (!k.locatieAanvraag) k.locatie = [];
			var bronnen = bron();
			return post('begroting', {
				keuzes: k, situatie: S.situatie, intern: S.intern, inkoop: S.inkoop,
				pagina_url: bronnen.pagina_url, utm: bronnen.utm, gclid: bronnen.gclid, website: ''
			}).then(function (a) {
				S.antwoord = a;
				vulFormulier(a.velden || {});
				S.bezig = false;
				return true;
			}).catch(function (e) {
				S.bezig = false;
				S.fout = e && e.message ? e.message : t('fout');
				return false;
			});
		}
		document.addEventListener('srfm_form_submission_success', function (e) {
			var id = e && e.detail ? String(e.detail.formId || '') : '';
			if (!C.formulier || (id !== 'srfm-form-' + C.formulier && id !== String(C.formulier))) return;
			if (huidig() !== 'gegevens') return;
			var tot = S.antwoord && S.antwoord.berekening && S.antwoord.berekening.uitkomst ? S.antwoord.berekening.uitkomst.totaal_excl : 0;
			meld('calculator_afgerond', { calculator_totaalklasse: tot >= 20000 ? '20000+' : tot >= 10000 ? '10000-20000' : tot >= 5000 ? '5000-10000' : tot > 0 ? '0-5000' : '' });
			S.stap = stappen().indexOf('klaar');
			teken();
		});

		/* ---------- Tekenen ---------- */

		var begrotingPlek = null;
		function tekenBegroting() {
			if (!begrotingPlek) return;
			var b = begroting(C.ingebed);
			begrotingPlek.innerHTML = '';
			if (b) begrotingPlek.appendChild(b);
			if (verderKnop) verderKnop.disabled = !magVerder();
		}
		var verderKnop = null;
		function knopStand() { if (verderKnop) verderKnop.disabled = !magVerder(); }

		function teken() {
			var s = huidig();
			var maker = {
				route: schermRoute, situatie: schermSituatie, mensen: schermMensen, vorm: schermVorm, extras: schermExtras,
				locatie: schermLocatie, intern: schermIntern, evenement: schermEvenement, uitvraag: schermUitvraag,
				gegevens: schermGegevens, klaar: schermKlaar, ingebed: schermIngebed
			}[s];
			var lijst = stappen();
			var nr = S.stap + 1;
			var totaalStappen = lijst.length - 1;
			var kop = !C.ingebed && s !== 'klaar' ? el('div', { class: 'sfp-calc-kop' }, [
				S.stap === 0 ? el('h1', { class: 'sfp-calc-titel', text: t('titel') }) : null,
				S.stap === 0 ? el('p', { class: 'sfp-calc-intro', text: t('intro') }) : null,
				el('span', { class: 'sfp-calc-lab', text: t('stap', { n: nr }) + ' / ' + totaalStappen }),
				el('div', { class: 'sfp-calc-balk', role: 'progressbar', 'aria-valuemin': '1', 'aria-valuemax': String(totaalStappen), 'aria-valuenow': String(nr) }, [el('span', { style: 'width:' + Math.round((nr / totaalStappen) * 100) + '%' })])
			]) : null;

			verderKnop = null;
			var knoppen = [];
			if (C.ingebed) {
				if (C.pagina) {
					/* Het adres pas bij de klik: het aantal verandert zonder nieuw scherm. */
					var naar = function () {
						var q = new URLSearchParams();
						if (S.route) q.set('route', S.route);
						var m = S.keuzes.mensen;
						var a = S.route === 'A' || !S.route;
						if (a && m.soort === 'getal' && parseInt(m.aantal, 10) > 0) q.set('n', String(parseInt(m.aantal, 10)));
						if (a && m.soort === 'marge' && parseInt(m.van, 10) > 0 && parseInt(m.tot, 10) > 0) { q.set('van', String(parseInt(m.van, 10))); q.set('tot', String(parseInt(m.tot, 10))); }
						if (a && m.soort === 'onbekend') q.set('onbekend', '1');
						return C.pagina + (C.pagina.indexOf('?') > -1 ? '&' : '?') + q.toString();
					};
					knoppen.push(el('a', { class: 'sfp-calc-knop', href: C.pagina, text: t('verder'), klik: function (e) { e.preventDefault(); meld('calculator_start'); window.location.href = naar(); } }));
				}
			} else if (s !== 'klaar' && s !== 'gegevens') {
				if (S.stap > 0) knoppen.push(el('button', { type: 'button', class: 'sfp-calc-knop is-stil', text: t('terug'), klik: function () { S.stap--; teken(); } }));
				verderKnop = el('button', { type: 'button', class: 'sfp-calc-knop', text: t('verder'), disabled: !magVerder(), 'aria-busy': S.bezig ? 'true' : null, klik: function () {
					if (!magVerder() || S.bezig) return;
					if (S.stap === 0) meld('calculator_start');
					var volgende = stappen()[S.stap + 1];
					if (volgende === 'gegevens') {
						verderKnop.setAttribute('aria-busy', 'true');
						naarGegevens().then(function (ok) { if (ok) S.stap++; teken(); });
						return;
					}
					S.stap++;
					teken();
				} });
				knoppen.push(verderKnop);
			} else if (s === 'gegevens') {
				knoppen.push(el('button', { type: 'button', class: 'sfp-calc-knop is-stil', text: t('terug'), klik: function () { S.stap--; teken(); } }));
			}

			var vragen = el('div', { class: 'sfp-calc-scherm sfp-calc-' + s }, [kop].concat(maker()).concat([el('div', { class: 'sfp-calc-knoppen' }, knoppen)]));
			begrotingPlek = el('div', { class: 'sfp-calc-zij' });
			app.innerHTML = '';
			var raster = el('div', { class: 'sfp-calc-raster' + (s === 'klaar' || s === 'gegevens' && !mensenKlaar() ? ' is-enkel' : '') }, [vragen, begrotingPlek]);
			app.appendChild(raster);
			var toonBegroting = C.ingebed || ['mensen', 'vorm', 'extras', 'locatie', 'intern', 'gegevens'].indexOf(s) > -1;
			if (toonBegroting) tekenBegroting();
			if (formVak) {
				formVak.hidden = s !== 'gegevens';
				if (s === 'gegevens') vragen.insertBefore(formVak, vragen.lastChild);
			}
			if (!C.ingebed && S.stap > 0) {
				var top = wortel.getBoundingClientRect().top + window.pageYOffset - 80;
				if (window.pageYOffset > top) window.scrollTo(0, Math.max(0, top));
			}
		}

		/* Van de ingebedde versie: de route is al gekozen, verder met de volgende vraag. */
		if (!C.ingebed && S.route) S.stap = 1;
		var sluit = document.querySelector('.sfp-calc-sluit');
		if (sluit) {
			sluit.addEventListener('click', function (e) {
				try {
					var r = document.referrer ? new URL(document.referrer) : null;
					if (r && r.host === window.location.host && window.history.length > 1) { e.preventDefault(); window.history.back(); }
				} catch (x) { /* gewone link */ }
			});
		}
		teken();
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
	else start();
})();
