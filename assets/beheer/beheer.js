/**
 * SFP Beheer - het beheer van SFP Page Config
 *
 * Bouwt de schermen Overzicht, Pagina's, Onderdelen, Teksten, Onderhoud en
 * Cursusdata op uit window.sfpBeheer en bewaart via /sfp/v1/beheer. Geen
 * bouwstap en geen bibliotheek: gewone DOM-code, zodat het bestand leesbaar
 * blijft in de repo.
 *
 * Opbouw:
 *   1. hulpfuncties (el, api, flits, velden)
 *   2. kop en navigatie
 *   3. de schermen, elk een functie die een element teruggeeft
 *   4. start
 *
 * Elke aanroep naar de server geeft de volledige stand terug; die vervangt
 * de stand in S, waarna het scherm opnieuw wordt opgebouwd.
 */
(function () {
	'use strict';

	var S = window.sfpBeheer;
	var wortel = document.getElementById('sfp-beheer');
	if (!S || !wortel || !window.wp || !wp.apiFetch) {
		return;
	}

	/* =====================================================================
	 * 1. Hulpfuncties
	 * =================================================================== */

	/** Wat het scherm onthoudt tussen twee keer opbouwen. */
	var ui = {
		scherm: 'overzicht',
		detail: '',
		filter: 'alles',
		lade: 0,
		knopSoort: 'coaching',
		artTab: 'kop',
		rvTab: 'weergave',
		rvBewerk: null,
		vvKeuze: 0,
		footer: null,
		cdOpen: 0,
		cdData: null,
		flits: null,
		open: {},
		koppeling: false,
		update: null
	};

	function el(tag, attrs, kids) {
		var n = document.createElement(tag);
		if (attrs) {
			Object.keys(attrs).forEach(function (k) {
				var v = attrs[k];
				if (v === null || v === undefined || v === false) return;
				if (k === 'text') n.textContent = v;
				else if (k === 'html') n.innerHTML = v;
				else if (k === 'klik') n.addEventListener('click', v);
				else if (k.indexOf('on') === 0 && typeof v === 'function') n.addEventListener(k.slice(2), v);
				else if (k === 'value') n.value = v;
				else if (k === 'checked' || k === 'disabled' || k === 'hidden' || k === 'open' || k === 'selected') n[k] = !!v;
				else n.setAttribute(k, v === true ? '' : v);
			});
		}
		(kids || []).forEach(function (k) {
			if (k === null || k === undefined || k === false) return;
			n.appendChild(typeof k === 'string' || typeof k === 'number' ? document.createTextNode(String(k)) : k);
		});
		return n;
	}

	function api(pad, methode, gegevens) {
		return wp.apiFetch({ path: '/sfp/v1/beheer' + pad, method: methode || 'GET', data: gegevens });
	}

	function foutTekst(e) {
		return (e && e.message) ? e.message : 'Dat is niet gelukt. Probeer het opnieuw.';
	}

	/** Een plek naast een knop waar "Opgeslagen" of een fout verschijnt. */
	function flitsPlek(sleutel) {
		var s = el('span', { class: 'sfpb-bewaard', role: 'status', 'data-flits': sleutel, hidden: true });
		if (ui.flits && ui.flits.sleutel === sleutel && Date.now() - ui.flits.tijd < 2500) {
			toonFlits(s, ui.flits.tekst, ui.flits.fout);
		}
		return s;
	}

	function toonFlits(plek, tekst, fout) {
		plek.textContent = tekst;
		plek.className = fout ? 'sfpb-mis' : 'sfpb-bewaard';
		plek.hidden = false;
		if (!fout) {
			setTimeout(function () { plek.hidden = true; }, 2200);
		}
	}

	/**
	 * Voer een aanroep uit vanuit een knop: knop even uit, stand bijwerken,
	 * scherm opnieuw opbouwen en naast de knop melden hoe het ging.
	 */
	function doe(knop, sleutel, belofte, klaarTekst, na) {
		if (knop) knop.disabled = true;
		return belofte.then(function (antwoord) {
			if (antwoord && antwoord.stand) S = antwoord.stand;
			else if (antwoord && antwoord.onderdelen) S = antwoord;
			ui.flits = { sleutel: sleutel, tekst: klaarTekst || 'Opgeslagen', tijd: Date.now(), fout: false };
			if (na) na(antwoord);
			teken();
			return antwoord;
		}, function (e) {
			if (knop) {
				knop.disabled = false;
				// Een schakelaar die niet is opgeslagen, springt terug.
				if (knop.getAttribute('role') === 'switch') knop.setAttribute('aria-checked', knop.getAttribute('aria-checked') === 'true' ? 'false' : 'true');
			}
			var plek = wortel.querySelector('[data-flits="' + sleutel + '"]');
			if (plek) toonFlits(plek, foutTekst(e), true);
			else window.alert(foutTekst(e));
		});
	}

	function bewaarInstellingen(knop, sleutel, wijzigingen, klaarTekst, na) {
		return doe(knop, sleutel, api('/instellingen', 'POST', wijzigingen), klaarTekst, na);
	}

	function kop(titel, intro, terug) {
		return el('div', { class: 'sfpb-kop' }, [
			el('h1', { text: titel }),
			intro ? el('p', { text: intro }) : null
		]);
	}

	function terugKnop() {
		return el('button', { class: 'sfpb-terug', type: 'button', text: 'Terug naar Onderdelen', klik: function () { ga('onderdelen'); } });
	}

	function veld(label, invoer, hulp, id) {
		if (id) invoer.id = id;
		return el('div', { class: 'sfpb-veld' }, [
			el('label', id ? { for: id, text: label } : { text: label }),
			invoer,
			hulp ? el('span', { class: 'sfpb-hulp', text: hulp }) : null
		]);
	}

	function tekstInvoer(waarde, placeholder, type) {
		return el('input', { type: type || 'text', value: waarde || '', placeholder: placeholder || '' });
	}

	function schakel(aan, tekst, bijWissel, uit) {
		var b = el('button', { class: 'sfpb-schakel', type: 'button', role: 'switch', 'aria-checked': aan ? 'true' : 'false', disabled: uit }, [
			el('span', { class: 'sfpb-spoor' }),
			el('span', { text: tekst })
		]);
		b.addEventListener('click', function () {
			var nu = b.getAttribute('aria-checked') !== 'true';
			b.setAttribute('aria-checked', nu ? 'true' : 'false');
			if (bijWissel) bijWissel(nu, b);
		});
		return b;
	}

	function pillen(opties, gekozen, bijKeuze, label) {
		var g = el('div', { class: 'sfpb-pillen', role: 'group', 'aria-label': label || '' });
		opties.forEach(function (o) {
			g.appendChild(el('button', { type: 'button', 'aria-pressed': o[0] === gekozen ? 'true' : 'false', text: o[1], klik: function () { bijKeuze(o[0]); } }));
		});
		return g;
	}

	/** Keuzelijst met de pagina's van de site. */
	function paginaKeuze(waarde, leeg, extra) {
		var s = el('select');
		if (leeg !== null && leeg !== undefined) s.appendChild(el('option', { value: '', text: leeg }));
		(extra || []).forEach(function (o) { s.appendChild(el('option', { value: o[0], text: o[1] })); });
		S.paginakeuze.forEach(function (p) { s.appendChild(el('option', { value: String(p.id), text: p.titel })); });
		// Een bewaarde pagina die niet meer in de lijst staat (concept, weg):
		// laat zien dat ze er niet meer is, in plaats van stil een andere te kiezen.
		if (waarde && !S.paginakeuze.some(function (p) { return p.id === Number(waarde); }) && !(extra || []).some(function (o) { return String(o[0]) === String(waarde); })) {
			s.appendChild(el('option', { value: String(waarde), text: 'Deze pagina is niet meer gepubliceerd' }));
		}
		s.value = waarde ? String(waarde) : '';
		if (s.selectedIndex < 0) s.selectedIndex = 0;
		return s;
	}

	function paginaTitel(id) {
		var p = S.paginakeuze.filter(function (x) { return x.id === Number(id); })[0];
		return p ? p.titel : '';
	}

	function status(goed, tekst) {
		return el('span', { class: 'sfpb-status' }, [el('span', { class: 'sfpb-stip ' + (goed === true ? 'sfpb-goed' : goed === false ? 'sfpb-fout' : '') }), tekst]);
	}

	function voorbeeld(inhoud) {
		return el('div', { class: 'sfpb-voorb' }, [el('span', { class: 'sfpb-label', text: 'Zo ziet het eruit' })].concat(inhoud));
	}

	function schets(n) {
		var uit = [];
		for (var i = 0; i < n; i++) uit.push(el('span', { class: 'sfpb-schets' + (i === n - 1 ? ' sfpb-kort' : '') }));
		return uit;
	}

	function onderdeel(id) {
		return S.onderdelen.filter(function (o) { return o.id === id; })[0] || {};
	}

	function datumTekst(d) {
		if (!d) return '';
		var dt = new Date(d + 'T12:00:00');
		if (isNaN(dt.getTime())) return d;
		var dagen = ['zo', 'ma', 'di', 'wo', 'do', 'vr', 'za'];
		var maanden = ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];
		return dagen[dt.getDay()] + ' ' + dt.getDate() + ' ' + maanden[dt.getMonth()] + ' ' + dt.getFullYear();
	}

	function meervoud(n, een, meer) {
		return n + ' ' + (n === 1 ? een : meer);
	}

	/* =====================================================================
	 * 2. Kop en navigatie
	 * =================================================================== */

	function ga(scherm, detail) {
		ui.scherm = scherm;
		ui.detail = detail || '';
		ui.lade = 0;
		var hash = '#' + scherm + (detail ? '/' + detail : '');
		if (location.hash !== hash) {
			history.pushState(null, '', hash);
		}
		teken();
		window.scrollTo(0, 0);
	}

	function leesHash() {
		var h = (location.hash || '').replace('#', '').split('/');
		var bekend = ['overzicht', 'paginas', 'onderdelen', 'teksten', 'onderhoud', 'cursusdata'];
		ui.scherm = bekend.indexOf(h[0]) !== -1 ? h[0] : 'overzicht';
		ui.detail = ui.scherm === 'onderdelen' && h[1] ? h[1] : '';
	}

	function kopbalk() {
		var schermen = [['overzicht', 'Overzicht'], ['paginas', 'Pagina\'s'], ['onderdelen', 'Onderdelen'], ['teksten', 'Teksten'], ['onderhoud', 'Onderhoud']];
		if (onderdeel('cursusdata').aan) schermen.splice(3, 0, ['cursusdata', 'Cursusdata']);
		var nav = el('nav', { class: 'sfpb-nav', 'aria-label': 'Schermen' });
		schermen.forEach(function (s) {
			var b = el('button', { type: 'button', text: s[1], 'aria-current': ui.scherm === s[0] ? 'page' : null, klik: function () { ga(s[0]); } });
			var dingen = S.controle.paginas.reduce(function (n, p) { return n + p.meldingen.length; }, 0);
			if (s[0] === 'overzicht' && dingen > 0) {
				b.appendChild(el('span', { class: 'sfpb-tel', text: String(dingen), 'aria-label': dingen + ' vragen aandacht' }));
			}
			nav.appendChild(b);
		});
		return el('header', { class: 'sfpb-top' }, [
			el('div', { class: 'sfpb-binnen' }, [
				el('div', { class: 'sfpb-rij1' }, [
					el('span', { class: 'sfpb-merknaam', text: 'SFP Beheer' }),
					el('span', { class: 'sfpb-site', text: S.site.host })
				]),
				nav
			])
		]);
	}

	/* =====================================================================
	 * 3. Schermen
	 * =================================================================== */

	/* ---------- Overzicht ---------- */

	function schermOverzicht() {
		var aandacht = S.controle.paginas.filter(function (p) { return p.meldingen.length; });
		var lijst = el('div', { class: 'sfpb-lijst' });
		aandacht.forEach(function (p) {
			p.meldingen.forEach(function (m) {
				lijst.appendChild(el('div', { class: 'sfpb-regel' }, [
					el('span', { class: 'sfpb-stip sfpb-fout' }),
					el('span', { class: 'sfpb-groei' }, [el('b', { text: p.titel }), ': ' + m.kort.charAt(0).toLowerCase() + m.kort.slice(1), el('br'), el('span', { class: 'sfpb-hulp', text: m.tekst })]),
					el('button', { class: 'sfpb-knop', type: 'button', text: m.actie === 'negeer' ? 'Bekijken' : 'Oplossen', klik: function () { ui.lade = p.id; ga2('paginas', p.id); } })
				]));
			});
		});
		var aantal = aandacht.reduce(function (n, p) { return n + p.meldingen.length; }, 0);
		var aandachtKaart = el('div', { class: 'sfpb-kaart' }, [
			el('h2', { text: aantal ? (aantal === 1 ? '1 ding vraagt je aandacht' : aantal + ' dingen vragen je aandacht') : 'Niets vraagt je aandacht' }),
			aantal ? lijst : status(true, 'Alle pagina\'s zijn in orde.')
		]);

		var aan = S.onderdelen.filter(function (o) { return o.aan; }).length;
		var oud = S.onderdelen.filter(function (o) { return o.oud; });
		var afw = S.afwijkingen;
		var normInhoud = el('div', { class: 'sfpb-stapel sfpb-krap' });
		if (!afw.length) {
			normInhoud.appendChild(status(true, 'Alles volgt de norm'));
		}
		afw.forEach(function (a, i) {
			normInhoud.appendChild(el('span', null, [a.label + ': ', el('b', { text: a.waarde }), el('br'), el('span', { class: 'sfpb-hulp', text: 'Norm: ' + (a.norm || '(leeg)') })]));
			normInhoud.appendChild(el('div', { class: 'sfpb-rij' }, [
				el('button', { class: 'sfpb-knop sfpb-klein', type: 'button', text: 'Terug naar de norm', klik: function () { doe(this, 'norm-' + a.pad, api('/norm', 'POST', { pad: a.pad }), 'Teruggezet', function () { ui.flits.sleutel = 'norm-klaar'; }); } }),
				flitsPlek('norm-' + a.pad)
			]));
		});
		normInhoud.appendChild(flitsPlek('norm-klaar'));

		return el('section', { class: 'sfpb-scherm' }, [
			kop('Overzicht', 'Wat op deze site je aandacht vraagt.'),
			aandachtKaart,
			el('div', { class: 'sfpb-raster sfpb-boven' }, [
				el('div', { class: 'sfpb-kaart' }, [
					el('span', { class: 'sfpb-hulp', text: 'Onderdelen' }),
					el('h2', { text: aan + ' aan, ' + (S.onderdelen.length - aan) + ' uit' }),
					oud.length ? el('span', { class: 'sfpb-hulp', text: oud.map(function (o) { return o.naam; }).join(' en ') + (oud.length === 1 ? ' draait' : ' draaien') + ' nog via de oude plugin.' }) : null,
					el('button', { class: 'sfpb-knop sfpb-tekst', type: 'button', style: 'align-self:flex-start', text: 'Bekijk de onderdelen', klik: function () { ga('onderdelen'); } })
				]),
				el('div', { class: 'sfpb-kaart' }, [
					el('span', { class: 'sfpb-hulp', text: 'Netwerknorm' }),
					el('h2', { text: afw.length ? (afw.length === 1 ? '1 instelling wijkt af' : afw.length + ' instellingen wijken af') : 'Geen afwijkingen' }),
					normInhoud
				]),
				el('div', { class: 'sfpb-kaart' }, [
					el('span', { class: 'sfpb-hulp', text: 'Versie' }),
					el('h2', { text: S.versie }),
					S.onderhoud.kanaal === 'proef' ? el('span', { class: 'sfpb-hulp', text: 'Deze site krijgt ook proefversies.' }) : null,
					el('button', { class: 'sfpb-knop sfpb-tekst', type: 'button', style: 'align-self:flex-start', text: 'Naar Onderhoud', klik: function () { ui.open.versie = true; ga('onderhoud'); } })
				])
			])
		]);
	}

	function ga2(scherm, lade) {
		ui.scherm = scherm;
		ui.detail = '';
		ui.lade = lade || 0;
		history.pushState(null, '', '#' + scherm);
		teken();
		window.scrollTo(0, 0);
	}

	/* ---------- Pagina's ---------- */

	function schermPaginas() {
		var alle = S.controle.paginas;
		var aandacht = alle.filter(function (p) { return p.meldingen.length; });
		var toon = ui.filter === 'let' ? aandacht : alle;
		var lijst = el('div', { class: 'sfpb-lijst' });
		toon.forEach(function (p) {
			var meta = p.soortnaam + (p.knoptekst ? ' · knop onderaan: ' + p.knoptekst : '');
			var rij = el('div', { class: 'sfpb-regel sfpb-klik', tabindex: '0', role: 'button' }, [
				el('span', { class: 'sfpb-groei' }, [el('b', { text: p.titel }), el('br'), el('span', { class: 'sfpb-hulp', text: meta })]),
				p.meldingen.length ? status(false, p.meldingen[0].kort + (p.meldingen.length > 1 ? ' en nog ' + (p.meldingen.length - 1) : '')) : status(true, 'In orde')
			]);
			var open = function () { ui.lade = p.id; teken(); };
			rij.addEventListener('click', open);
			rij.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); } });
			lijst.appendChild(rij);
		});
		if (!toon.length) {
			lijst.appendChild(el('div', { class: 'sfpb-regel' }, [status(true, ui.filter === 'let' ? 'Niets vraagt aandacht' : 'Deze site heeft nog geen pagina\'s.')]));
		}
		var scherm = el('section', { class: 'sfpb-scherm' }, [
			kop('Pagina\'s', 'Wat er per pagina is ingesteld, en wat er nog ontbreekt.'),
			el('div', { class: 'sfpb-rij sfpb-tussen' }, [
				pillen([['alles', 'Alles (' + alle.length + ')'], ['let', 'Vraagt aandacht (' + aandacht.length + ')']], ui.filter, function (f) { ui.filter = f; teken(); }, 'Filter'),
				el('div', { class: 'sfpb-rij' }, [
					flitsPlek('opnieuw'),
					el('button', { class: 'sfpb-knop sfpb-klein', type: 'button', text: 'Controleer opnieuw', klik: function () {
						doe(this, 'opnieuw', api('/paginas').then(function (c) { S.controle = c; return null; }), 'Gecontroleerd');
					} })
				])
			]),
			el('div', { class: 'sfpb-kaart' }, [lijst]),
			S.controle.overig_in_orde ? el('p', { class: 'sfpb-hulp', text: meervoud(S.controle.overig_in_orde, 'bericht of begrip is', 'berichten en begrippen zijn') + ' in orde en ' + (S.controle.overig_in_orde === 1 ? 'staat' : 'staan') + ' niet in deze lijst.' }) : null
		]);
		if (ui.lade) {
			var pagina = alle.filter(function (p) { return p.id === ui.lade; })[0];
			if (pagina) scherm.appendChild(lade(pagina));
		}
		return scherm;
	}

	function lade(p) {
		var soortKeuze = el('select', { id: 'sfpb-lade-soort' });
		Object.keys(S.soorten).forEach(function (k) { soortKeuze.appendChild(el('option', { value: k, text: S.soorten[k].naam })); });
		soortKeuze.value = p.soort;
		var uitleg = el('span', { class: 'sfpb-hulp', text: S.soorten[p.soort] ? S.soorten[p.soort].uitleg : '' });

		var tekst = tekstInvoer(p.eigen.tekst, p.soort && S.knop[p.soort] ? (S.knop[p.soort].text || S.knop[p.soort].norm) : '');
		var doel = el('select', { id: 'sfpb-lade-doel' }, [
			el('option', { value: 'form', text: 'Het formulier op de pagina' }),
			el('option', { value: 'pagina', text: 'Een pagina van de site' }),
			el('option', { value: 'link', text: 'Een andere link' })
		]);
		doel.value = p.eigen.doel;
		var doelPagina = paginaKeuze(p.eigen.pagina, null);
		var doelLink = tekstInvoer(p.eigen.doel === 'link' ? p.eigen.link : '', 'Plak de link', 'url');
		var knopVak = el('div', { class: 'sfpb-stapel' }, [
			veld('Tekst op de knop onderaan', tekst, 'Leeg: de tekst van deze site voor deze soort pagina.', 'sfpb-lade-tekst'),
			el('div', { class: 'sfpb-veld' }, [el('label', { for: 'sfpb-lade-doel', text: 'De knop gaat naar' }), doel, doelPagina, doelLink, el('span', { class: 'sfpb-hulp', text: 'Het formulier vindt de plugin zelf.' })])
		]);
		function toon() {
			var dienst = soortKeuze.value !== '' && p.type === 'page';
			uitleg.textContent = S.soorten[soortKeuze.value] ? S.soorten[soortKeuze.value].uitleg : '';
			knopVak.hidden = !dienst || !onderdeel('knop').aan;
			doelPagina.hidden = doel.value !== 'pagina';
			doelLink.hidden = doel.value !== 'link';
			tekst.placeholder = dienst && S.knop[soortKeuze.value] ? (S.knop[soortKeuze.value].text || S.knop[soortKeuze.value].norm) : '';
		}
		soortKeuze.addEventListener('change', toon);
		doel.addEventListener('change', toon);
		toon();

		var meldingen = p.meldingen.map(function (m, i) {
			var knoppen = el('div', { class: 'sfpb-rij' });
			if (m.actie === 'bestemming') {
				knoppen.appendChild(el('span', { class: 'sfpb-hulp', text: p.type === 'page' ? 'Kies hieronder een andere bestemming, of zet een formulier op de pagina.' : 'Zet een formulier in de tekst, of kies in de editor een andere bestemming.' }));
			} else if (m.actie === 'cursusdata') {
				knoppen.appendChild(el('button', { class: 'sfpb-knop sfpb-klein', type: 'button', text: 'Naar Cursusdata', klik: function () { ui.cdOpen = p.id; ui.cdData = null; ga('cursusdata'); } }));
			} else if (m.actie === 'editor') {
				knoppen.appendChild(el('a', { class: 'sfpb-knop sfpb-klein', href: p.bewerk, text: 'Open in de editor' }));
			}
			knoppen.appendChild(el('button', { class: 'sfpb-knop sfpb-klein', type: 'button', text: 'Dit is bewust, niet meer melden', klik: function () {
				doe(this, 'lade', api('/pagina/' + p.id, 'POST', { negeer: m.code }), 'Niet meer gemeld');
			} }));
			return el('div', { class: 'sfpb-let' }, [el('span', { text: m.tekst }), knoppen]);
		});

		return el('aside', { class: 'sfpb-lade', 'aria-label': 'Instellingen van de pagina' }, [
			el('div', { class: 'sfpb-rij sfpb-tussen' }, [
				el('h1', { style: 'font-size:22px', text: p.titel }),
				el('button', { class: 'sfpb-knop', type: 'button', text: 'Sluiten', klik: function () { ui.lade = 0; teken(); } })
			])
		].concat(meldingen, [
			p.type === 'page'
				? el('div', { class: 'sfpb-veld' }, [el('label', { for: 'sfpb-lade-soort', text: 'Soort pagina' }), soortKeuze, uitleg])
				: el('p', { class: 'sfpb-hulp', text: p.soortnaam + '. De soort van een bericht of begrip ligt vast.' }),
			knopVak,
			p.genegeerd ? el('div', { class: 'sfpb-rij' }, [
				el('span', { class: 'sfpb-hulp', text: p.genegeerd === 1 ? 'Voor deze pagina is één melding weggeklikt.' : 'Voor deze pagina zijn ' + p.genegeerd + ' meldingen weggeklikt.' }),
				el('button', { class: 'sfpb-knop sfpb-klein', type: 'button', text: 'Meld weer', klik: function () { doe(this, 'lade', api('/pagina/' + p.id, 'POST', { meld_weer: true }), 'Wordt weer gemeld'); } })
			]) : null,
			el('div', { class: 'sfpb-rij' }, [
				p.type === 'page' ? el('button', { class: 'sfpb-knop sfpb-hoofd', type: 'button', text: 'Opslaan', klik: function () {
					var gegevens = { soort: soortKeuze.value };
					if (!knopVak.hidden) {
						gegevens.tekst = tekst.value;
						gegevens.doel = doel.value;
						gegevens.pagina = doelPagina.value;
						gegevens.link = doelLink.value;
					}
					doe(this, 'lade', api('/pagina/' + p.id, 'POST', gegevens));
				} }) : null,
				flitsPlek('lade')
			]),
			el('div', { class: 'sfpb-rij' }, [
				el('a', { class: 'sfpb-knop', href: p.bewerk, text: 'Open in de editor' }),
				el('a', { class: 'sfpb-knop', href: p.link, target: '_blank', rel: 'noopener', text: 'Bekijk de pagina' })
			])
		]));
	}

	/* ---------- Onderdelen ---------- */

	function schermOnderdelen() {
		var raster = el('div', { class: 'sfpb-raster' });
		S.onderdelen.forEach(function (o) {
			var sw = schakel(o.aan, o.aan ? 'Aan' : 'Uit', function (nu, knop) {
				var w = {}; w[o.id] = nu;
				bewaarInstellingen(knop, 'od-' + o.id, { onderdelen: w });
			});
			sw.setAttribute('aria-label', o.naam + ' aan of uit');
			raster.appendChild(el('div', { class: 'sfpb-kaart' }, [
				el('h2', { text: o.naam }),
				el('p', { class: 'sfpb-zacht sfpb-groei', text: o.uitleg }),
				o.oud ? el('p', { class: 'sfpb-hulp', text: 'Draait nog via de plugin ' + o.oud + '. Dit onderdeel neemt het over zodra die plugin uit staat.' }) : null,
				el('div', { class: 'sfpb-rij sfpb-tussen' }, [
					el('div', { class: 'sfpb-rij' }, [sw, flitsPlek('od-' + o.id)]),
					o.detail ? el('button', { class: 'sfpb-knop', type: 'button', text: 'Instellen', klik: function () { o.id === 'cursusdata' ? ga('cursusdata') : ga('onderdelen', o.id); } }) : null
				])
			]));
		});
		return el('section', { class: 'sfpb-scherm' }, [
			kop('Onderdelen', 'Wat de plugin op deze site doet. Zet een onderdeel aan of uit, of stel het in.'),
			raster
		]);
	}

	/* ---------- Onderdeel: knop onderaan ---------- */

	function detailKnop() {
		var soort = ui.knopSoort;
		var k = S.knop[soort];
		var naam = S.soorten[soort].naam;
		var tekst = tekstInvoer(k.text, k.norm);
		var plak = el('div', { class: 'sfpb-plak', text: k.text || k.norm });
		tekst.addEventListener('input', function () { plak.textContent = tekst.value.trim() || k.norm; });

		var doelPagina = paginaKeuze(k.pagina, null);
		var doelLink = tekstInvoer(k.doel === 'link' ? k.link : '', 'Plak de link', 'url');
		var subPagina = el('div', { class: 'sfpb-sub sfpb-veld' }, [doelPagina]);
		var subLink = el('div', { class: 'sfpb-sub sfpb-veld' }, [doelLink]);
		doelPagina.setAttribute('aria-label', 'Kies een pagina');
		doelLink.setAttribute('aria-label', 'Plak de link');
		function radio(waarde, titel, hulp) {
			var r = el('input', { type: 'radio', name: 'sfpb-doel', value: waarde, checked: k.doel === waarde });
			r.addEventListener('change', toon);
			return el('label', null, [r, el('span', null, [el('b', { text: titel }), hulp ? el('br') : null, hulp ? el('span', { class: 'sfpb-hulp', text: hulp }) : null])]);
		}
		var keuzes = el('div', { class: 'sfpb-keuzes' }, [
			radio('form', 'Het formulier op de pagina', 'De plugin vindt het formulier zelf.'),
			radio('pagina', 'Een pagina van de site'), subPagina,
			radio('link', 'Een andere link'), subLink
		]);
		function gekozen() {
			var r = keuzes.querySelector('input[type=radio]:checked');
			return r ? r.value : 'form';
		}
		function toon() {
			subPagina.hidden = gekozen() !== 'pagina';
			subLink.hidden = gekozen() !== 'link';
		}
		toon();

		var voorbeeldPagina = S.controle.paginas.filter(function (p) { return p.soort === soort; })[0];

		return el('section', { class: 'sfpb-scherm' }, [
			terugKnop(),
			kop('Knop onderaan', 'Op een telefoon blijft op een dienstpagina één knop in beeld. Hij verdwijnt zodra het formulier in beeld is.'),
			el('div', { class: 'sfpb-twee' }, [
				el('div', { class: 'sfpb-stapel sfpb-ruim' }, [
					pillen(Object.keys(S.knop).map(function (t) { return [t, S.soorten[t].naam]; }), soort, function (t) { ui.knopSoort = t; teken(); }, 'Soort pagina'),
					veld('Tekst op de knop', tekst, 'Leeg: de standaardtekst van het netwerk.', 'sfpb-knop-tekst'),
					el('div', { class: 'sfpb-veld' }, [el('span', { class: 'sfpb-label', text: 'De knop gaat naar' }), keuzes]),
					el('p', { class: 'sfpb-hulp', text: 'Wil je het voor één pagina anders, dan doe je dat bij die pagina zelf, op het scherm Pagina\'s.' }),
					el('div', { class: 'sfpb-rij' }, [
						el('button', { class: 'sfpb-knop sfpb-hoofd', type: 'button', text: 'Opslaan', klik: function () {
							var w = { sticky_cta: {} };
							w.sticky_cta[soort] = { text: tekst.value, doel: gekozen(), pagina: doelPagina.value, link: doelLink.value };
							bewaarInstellingen(this, 'knop', w);
						} }),
						flitsPlek('knop')
					])
				]),
				voorbeeld([
					el('div', { class: 'sfpb-telefoon sfpb-m' }, [el('div', { class: 'sfpb-scherm2' }, [
						el('div', { class: 'sfpb-pag' }, [el('span', { class: 'sfpb-h1', text: voorbeeldPagina ? voorbeeldPagina.titel.replace(' (startpagina)', '') : naam })].concat(schets(5))),
						plak
					])])
				])
			])
		]);
	}

	/* ---------- Onderdeel: artikelen ---------- */

	/** Eén promokaart: de kaart van de site (artikel_promo) of de tweede kaart (artikel_promo2). */
	function promoKaart(p, basis) {
			var beeld = { id: p.beeld, url: p.beeld_url };
			var titel = tekstInvoer(p.titel);
			var label = tekstInvoer(p.label);
			var tekst = el('textarea', { value: p.tekst });
			var linktekst = tekstInvoer(p.linktekst);
			var doel = paginaKeuze(p.pagina || ((!p.pagina && p.url) ? 'link' : ''), 'Geen bestemming', [['link', 'Een andere link']]);
			var doelLink = tekstInvoer(p.url, 'Plak de link', 'url');
			var vorm = el('select', { id: 'sfpb-promo-vorm-' + basis });
			[['', 'Omslag (zoals een boek)'], ['rond', 'Ronde foto']].forEach(function (o) { vorm.appendChild(el('option', { value: o[0], text: o[1] })); });
			vorm.value = p.vorm || '';
			var vBeeld = el('img', { class: 'sfpb-omslag', alt: '' });
			var vKaart = el('div', { class: 'sfpb-mvak sfpb-promo' });
			var vLabel = el('span', { class: 'sfpb-op' });
			var vTitel = el('span', { class: 'sfpb-h3' });
			var vTekst = el('span');
			var vLink = el('span', { class: 'sfpb-pijl' });
			var klein = el('div', { class: 'sfpb-beeld', style: 'width:120px;background-size:contain;background-repeat:no-repeat' });
			var kiesKnop = el('button', { class: 'sfpb-knop', type: 'button' });
			var wegKnop = el('button', { class: 'sfpb-knop sfpb-tekst', type: 'button', text: 'Geen afbeelding', klik: function () { beeld.id = 0; beeld.url = ''; ververs(); } });
			function ververs() {
				vLabel.textContent = label.value; vLabel.hidden = !label.value;
				vTitel.textContent = titel.value || 'Titel van de kaart';
				vTekst.textContent = tekst.value; vTekst.hidden = !tekst.value;
				vLink.textContent = (linktekst.value || titel.value || 'Tekst van de link') + ' →';
				klein.style.backgroundImage = beeld.url ? 'url("' + beeld.url + '")' : '';
				if (beeld.url) vBeeld.src = beeld.url; else vBeeld.removeAttribute('src');
				vBeeld.hidden = !beeld.url;
				// Zelfde opbouw als de kaart op de site: een klein omslag met de tekst ernaast.
				vKaart.classList.toggle('sfpb-promo--zonder', !beeld.url);
				vKaart.classList.toggle('sfpb-promo--rond', vorm.value === 'rond');
				kiesKnop.textContent = beeld.url ? 'Kies een andere afbeelding' : 'Kies een afbeelding';
				wegKnop.hidden = !beeld.url;
				doelLink.hidden = doel.value !== 'link';
			}
			[titel, label, tekst, linktekst].forEach(function (n) { n.addEventListener('input', ververs); });
			doel.addEventListener('change', ververs);
			vorm.addEventListener('change', ververs);
			kiesKnop.addEventListener('click', function () {
				if (!wp.media) return;
				var kader = wp.media({ title: 'Kies een afbeelding', library: { type: 'image' }, multiple: false, button: { text: 'Gebruik deze afbeelding' } });
				kader.on('select', function () {
					var keuze = kader.state().get('selection').first().toJSON();
					beeld.id = keuze.id;
					beeld.url = (keuze.sizes && keuze.sizes.medium ? keuze.sizes.medium.url : keuze.url);
					ververs();
				});
				kader.open();
			});
			ververs();
			return el('div', { class: 'sfpb-twee' }, [
				el('div', { class: 'sfpb-stapel sfpb-ruim' }, [
					el('div', { class: 'sfpb-rij' }, [el('div', { class: 'sfpb-m' }, [klein]), kiesKnop, wegKnop]),
					veld('Vorm van de afbeelding', vorm, null, 'sfpb-promo-vorm-' + basis),
					veld('Label boven de titel', label, 'Leeg: geen label.', 'sfpb-promo-label'),
					veld('Titel', titel, null, 'sfpb-promo-titel'),
					veld('Tekst', tekst, null, 'sfpb-promo-tekst'),
					veld('Tekst van de link', linktekst, 'Leeg: de titel.', 'sfpb-promo-linktekst'),
					el('div', { class: 'sfpb-veld' }, [el('label', { for: 'sfpb-promo-doel', text: 'De kaart gaat naar' }), (doel.id = 'sfpb-promo-doel', doel), doelLink, el('span', { class: 'sfpb-hulp', text: 'Zonder titel of bestemming is er geen kaart.' })]),
					el('div', { class: 'sfpb-rij' }, [
						el('button', { class: 'sfpb-knop sfpb-hoofd', type: 'button', text: 'Opslaan', klik: function () {
							var w = {};
							w[basis + '_beeld'] = beeld.id;
							w[basis + '_label'] = label.value;
							w[basis + '_titel'] = titel.value;
							w[basis + '_tekst'] = tekst.value;
							w[basis + '_linktekst'] = linktekst.value;
							w[basis + '_pagina'] = doel.value === 'link' ? '' : doel.value;
							w[basis + '_url'] = doel.value === 'link' ? doelLink.value : '';
							w[basis + '_vorm'] = vorm.value;
							bewaarInstellingen(this, 'art-promo', w);
						} }),
						flitsPlek('art-promo')
					])
				]),
				voorbeeld([el('div', { class: 'sfpb-m' }, [(vKaart.appendChild(vBeeld), vKaart.appendChild(el('div', { class: 'sfpb-promo-tekst' }, [vLabel, vTitel, vTekst, vLink])), vKaart)])])
			]);
	}

	/** Welke kaart elke vorm toont. Een pijler met een zelftest toont eerst die test. */
	function promoPerVorm(a) {
		var keuzes = [['site', 'De kaart van de site'], ['tweede', 'De tweede kaart'], ['geen', 'Geen kaart']];
		var rijen = [['artikel', 'Artikel', null], ['pijler', 'Pijler', 'Een pijler toont de zelftest van de site (onderdeel Zelftest) op de plek van het boek. Deze keuze geldt alleen als er geen zelftest is of de pijler er geen wil.'], ['begrip', 'Begrip', null], ['tool', 'Tool', 'Een tool heeft standaard geen kaart.']];
		var lijsten = rijen.map(function (r) {
			var s = el('select', { id: 'sfpb-promo-per-' + r[0] });
			keuzes.forEach(function (k) { s.appendChild(el('option', { value: k[0], text: k[1] })); });
			var nu = a.promoVorm && a.promoVorm[r[0]] ? a.promoVorm[r[0]] : (r[0] === 'tool' ? 'geen' : 'site');
			s.value = nu;
			s.dataset.vorm = r[0];
			return veld(r[1], s, r[2], 'sfpb-promo-per-' + r[0]);
		});
		return el('div', { class: 'sfpb-stapel sfpb-ruim' }, lijsten.concat([
			el('div', { class: 'sfpb-rij' }, [
				el('button', { class: 'sfpb-knop sfpb-hoofd', type: 'button', text: 'Opslaan', klik: function () {
					var w = {};
					lijsten.forEach(function (vd) { var s = vd.querySelector('select'); w['promo_vorm_' + s.dataset.vorm] = s.value; });
					bewaarInstellingen(this, 'art-promo-vorm', w);
				} }),
				flitsPlek('art-promo-vorm')
			])
		]));
	}

	function detailArtikel() {
		var a = S.artikel;
		var tabs = pillen([['kop', 'Kopkaart'], ['promo', 'Promokaart'], ['vervolg', 'Vervolg per pijler'], ['aff', 'Affiliatelinks']], ui.artTab, function (t) { ui.artTab = t; teken(); }, 'Onderwerp');
		var paneel;

		if (ui.artTab === 'kop') {
			var bron = { aan: a.voorkeursbron, leestekens: a.leestekens };
			var linkVeld = function (sleutel, label, hulp) {
				var huidig = a.links[sleutel];
				var extra = (!huidig.pagina && huidig.url) ? [['url', 'Huidige link: ' + huidig.url]] : [];
				var s = paginaKeuze(huidig.pagina || (extra.length ? 'url' : ''), 'Geen pagina gekozen', extra);
				s.dataset.sleutel = sleutel;
				return veld(label, s, hulp, 'sfpb-art-' + sleutel);
			};
			var velden = [
				linkVeld('factcheck', 'Pagina over factchecken', 'Leeg: de redactionele verantwoording.'),
				linkVeld('redactie', 'Redactionele verantwoording', 'Leeg: geen link onder het artikel.'),
				linkVeld('bibliografie', 'Bibliografie', 'Leeg: geen link onder het artikel.')
			];
			var v = a.voorbeeld;
			paneel = el('div', { class: 'sfpb-twee' }, [
				el('div', { class: 'sfpb-stapel sfpb-ruim' }, [
					schakel(a.voorkeursbron, 'Toon de actie Voorkeursbron bij Google', function (nu) { bron.aan = nu; }),
					schakel(a.leestekens, 'Trek leestekens in de titel naar het woord ernaast', function (nu) { bron.leestekens = nu; }),
					el('span', { class: 'sfpb-hulp', text: 'Voor een titelletter waarin elk teken even breed is, zoals op DGA, DST, SLB, SwS en FL. Dubbele punt, komma, punt en koppelteken staan dan niet meer los.' })
				].concat(velden, [
					el('div', { class: 'sfpb-rij' }, [
						el('button', { class: 'sfpb-knop sfpb-hoofd', type: 'button', text: 'Opslaan', klik: function () {
							var w = { artikel_voorkeursbron: bron.aan, kop_leestekens: bron.leestekens };
							velden.forEach(function (vd) {
								var s = vd.querySelector('select');
								if (s.value === 'url') return;
								w['artikel_' + s.dataset.sleutel + '_pagina'] = s.value;
								// Een gekozen pagina of "geen" vervangt de oude link.
								w['artikel_' + s.dataset.sleutel + '_url'] = '';
							});
							bewaarInstellingen(this, 'art-kop', w);
						} }),
						flitsPlek('art-kop')
					])
				])),
				voorbeeld([el('div', { class: 'sfpb-m sfpb-stapel sfpb-krap' }, v.titel ? [
					v.pijler ? el('span', { class: 'sfpb-op', text: v.pijler }) : null,
					el('span', { class: 'sfpb-h1', text: v.titel }),
					v.auteur ? el('span', { text: v.auteur }) : null,
					el('span', { style: 'font-size:14px' }, [v.meta + ' · ', el('b', { style: 'color:var(--sfp-b-primair)', text: v.gecheckt })])
				] : [el('span', { class: 'sfpb-hulp', text: 'Nog geen artikel om te tonen.' })].concat(schets(3)))])
			]);
		} else if (ui.artTab === 'promo') {
			var promoTab = ui.promoTab || 'site';
			paneel = el('div', { class: 'sfpb-stapel sfpb-ruim' }, [
				pillen([['site', 'Kaart van de site'], ['tweede', 'Tweede kaart'], ['vorm', 'Per vorm']], promoTab, function (t) { ui.promoTab = t; teken(); }, 'Kaart'),
				promoTab === 'vorm' ? promoPerVorm(a) : promoKaart(promoTab === 'tweede' ? a.promo2 : a.promo, promoTab === 'tweede' ? 'artikel_promo2' : 'artikel_promo')
			]);
		} else if (ui.artTab === 'vervolg') {
			paneel = paneelVervolg();
		} else {
			var patronen = a.patronen.slice();
			var chips = el('div', { class: 'sfpb-rij' });
			var nieuw = tekstInvoer('', 'Een stukje dat in de link voorkomt');
			nieuw.style.maxWidth = '300px';
			nieuw.id = 'sfpb-aff-nieuw';
			var bewaarPatronen = function (knop, lijst) { bewaarInstellingen(knop, 'art-aff', { affiliate_patronen: lijst }); };
			patronen.forEach(function (pt) {
				chips.appendChild(el('span', { class: 'sfpb-chip' }, [pt, el('button', { type: 'button', 'aria-label': pt + ' weghalen', text: '×', klik: function () { bewaarPatronen(this, patronen.filter(function (x) { return x !== pt; })); } })]));
			});
			if (!patronen.length) chips.appendChild(el('span', { class: 'sfpb-hulp', text: 'Nog geen herkenning ingesteld. Er komt dan nooit een melding.' }));
			// Een stukje dat op geen enkel artikel een link vindt, is waarschijnlijk verkeerd getypt.
			var treffers = a.treffers || {};
			var zonder = patronen.filter(function (pt) { return treffers[pt] === 0; });
			var voeg = function () {
				var w = nieuw.value.trim();
				if (!w) { nieuw.focus(); return; }
				bewaarPatronen(this, patronen.indexOf(w) === -1 ? patronen.concat([w]) : patronen);
			};
			nieuw.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); voeg.call(null); } });
			paneel = el('div', { class: 'sfpb-twee' }, [
				el('div', { class: 'sfpb-stapel sfpb-ruim' }, [
					el('div', { class: 'sfpb-veld' }, [
						el('label', { for: 'sfpb-aff-nieuw', text: 'Een affiliatelink herken je aan' }),
						chips,
						el('div', { class: 'sfpb-rij' }, [nieuw, el('button', { class: 'sfpb-knop', type: 'button', text: 'Toevoegen', klik: voeg }), flitsPlek('art-aff')]),
						el('span', { class: 'sfpb-hulp', text: 'Staat zo\'n link in een artikel of een tooltip, dan komt er vanzelf een melding bij. De tekst van de melding staat bij Teksten.' })
					]),
					zonder.length ? el('div', { class: 'sfpb-let' }, [el('span', { text: (zonder.length === 1 ? zonder[0] + ' vindt' : zonder.join(', ') + ' vinden') + ' op geen enkel gepubliceerd artikel een link. Klopt het stukje? Een sterretje staat voor een willekeurig stuk van de link, bijvoorbeeld managementboek.nl*affiliate.' })]) : null,
					(!a.affiliate.regel || !a.affiliate.uitleg) ? el('div', { class: 'sfpb-let' }, [el('span', { text: 'Voor de taal van deze site is nog geen tekst voor de melding ingevuld. Zonder tekst komt er geen melding.' }), el('div', { class: 'sfpb-rij' }, [el('button', { class: 'sfpb-knop sfpb-klein', type: 'button', text: 'Naar Teksten', klik: function () { ui.open['tk-Artikelen'] = true; ga('teksten'); } })])]) : null
				]),
				voorbeeld([el('div', { class: 'sfpb-m sfpb-stapel sfpb-krap' }, [el('span', { style: 'font-size:14px' }, [(a.voorbeeld.meta ? a.voorbeeld.meta + ' · ' : '') + a.voorbeeld.gecheckt + ' · ', el('b', { style: 'color:var(--sfp-b-primair)', text: a.affiliate.regel || '(geen tekst)' })])])])
			]);
		}

		return el('section', { class: 'sfpb-scherm' }, [
			terugKnop(),
			kop('Artikelen', 'Wat er vanzelf om elk artikel heen staat.'),
			tabs,
			paneel
		]);
	}

	function paneelVervolg() {
		var lijst = el('div', { class: 'sfpb-lijst' });
		var metVoorbeeld = S.vervolg.filter(function (r) { return r.vervolg && r.vervolg.html; });
		var gekozen = metVoorbeeld.filter(function (r) { return r.id === ui.vvKeuze; })[0] || metVoorbeeld[0];
		S.vervolg.forEach(function (r) {
			var hulp, knop;
			if (r.vervolg) {
				var v = r.vervolg;
				hulp = v.vlakken ? (v.vlakken === 1 ? 'Eén vlak' : (v.vlakken === 2 ? 'Twee vlakken' : v.vlakken + ' vlakken')) + (v.labels.length ? ': ' + v.labels.join(' en ') : '') : 'Nog leeg.';
				if (v.status !== 'publish') hulp += ' Concept: nog niet zichtbaar op de site.';
				knop = el('a', { class: 'sfpb-knop', href: v.bewerk, text: 'Bewerken' });
			} else if (r.astra) {
				hulp = 'Staat nu in de Astra-layout "' + r.astra.titel + '". Neem het over om het hier te beheren; de layout wordt dan een concept.';
				knop = el('button', { class: 'sfpb-knop', type: 'button', text: 'Overnemen', klik: function () {
					doe(this, 'vv-' + r.id, api('/vervolg/overnemen', 'POST', { layout: r.astra.id, pijler: r.id }), 'Overgenomen', function () { ui.vvKeuze = r.id; });
				} });
			} else {
				hulp = 'Nog geen vervolg. Artikelen in deze pijler krijgen het vervolg van de pijler erboven, of eindigen zonder.';
				knop = el('button', { class: 'sfpb-knop', type: 'button', text: 'Vervolg maken', klik: function () {
					var b = this; b.disabled = true;
					api('/vervolg', 'POST', { pijler: r.id }).then(function (antw) { window.location.href = antw.bewerk; }, function (e) { b.disabled = false; window.alert(foutTekst(e)); });
				} });
			}
			var rij = el('div', { class: 'sfpb-regel' + (r.niveau ? ' sfpb-inspring-' + Math.min(2, r.niveau) : '') }, [
				el('span', { class: 'sfpb-groei' }, [el('b', { text: r.naam }), ' ', el('span', { class: 'sfpb-hulp', text: '(' + meervoud(r.artikelen, 'artikel', 'artikelen') + ')' }), el('br'), el('span', { class: 'sfpb-hulp', text: hulp })]),
				flitsPlek('vv-' + r.id),
				(r.vervolg && r.vervolg.html && gekozen && gekozen.id !== r.id) ? el('button', { class: 'sfpb-knop sfpb-tekst', type: 'button', text: 'Toon voorbeeld', klik: function () { ui.vvKeuze = r.id; teken(); } }) : null,
				knop
			]);
			lijst.appendChild(rij);
		});
		if (!S.vervolg.length) {
			lijst.appendChild(el('div', { class: 'sfpb-regel' }, [el('span', { class: 'sfpb-hulp', text: 'Deze site heeft nog geen pijlers. Een pijler is een categorie van artikelen.' })]));
		}
		var voorb = gekozen
			? [el('span', { class: 'sfpb-hulp', text: 'Vervolg van ' + gekozen.naam }), el('div', { class: 'sfpb-m' }, [el('style', { text: S.vervolg_css }), el('div', { html: gekozen.vervolg.html })])]
			: [el('div', { class: 'sfpb-m' }, [el('div', { class: 'sfpb-donker' }, schets(3))]), el('span', { class: 'sfpb-hulp', text: 'Hier verschijnt het vervolg zodra een pijler er een heeft.' })];
		return el('div', { class: 'sfpb-twee' }, [
			el('div', { class: 'sfpb-stapel' }, [
				el('p', { class: 'sfpb-hulp', text: 'Onder elk artikel staat het vervolg van zijn pijler: wat de lezer hierna kan doen. Je bewerkt een vervolg in de editor, met het blok Vervolg.' }),
				(S.vervolg_algemeen || []).length ? el('div', { class: 'sfpb-let' }, [el('span', { text: 'De Astra-layout "' + S.vervolg_algemeen.join('", "') + '" zet een vervolg onder alle artikelen. Zolang die layout gepubliceerd is, toont de plugin zelf geen vervolg, zodat het er niet twee keer staat.' })]) : null,
				lijst
			]),
			voorbeeld(voorb)
		]);
	}

	/* ---------- Onderdeel: reviews ---------- */

	function detailReviews() {
		var r = S.reviews;
		var tabs = pillen([['weergave', 'Weergave'], ['eigen', 'Eigen reviews'], ['google', 'Koppeling met Google']], ui.rvTab, function (t) { ui.rvTab = t; ui.rvBewerk = null; teken(); }, 'Onderwerp');
		var links;
		var keuze = { layout: r.opties.layout, summary: r.opties.summary };

		var vKop = el('div', { class: 'sfpb-stapel', style: 'gap:2px' });
		var vKaarten = el('div', { style: 'display:grid;gap:10px' });
		function sterren(n) { return '★★★★★'.slice(0, n) + '☆☆☆☆☆'.slice(0, 5 - n); }
		function tekenVoorbeeld() {
			vKop.innerHTML = ''; vKaarten.innerHTML = '';
			if (r.stand.verbonden && (keuze.summary || keuze.layout === 'cijfer')) {
				vKop.appendChild(el('span', { class: 'sfpb-h3', text: r.stand.oordeel }));
				vKop.appendChild(el('span', null, [el('b', { style: 'color:var(--sfp-b-primair)', text: r.stand.cijfer_tekst }), ' ', el('span', { class: 'sfpb-ster', 'aria-hidden': 'true', text: sterren(Math.round(r.stand.cijfer)) })]));
				vKop.appendChild(el('span', { style: 'font-size:14px', text: r.stand.basis }));
			}
			if (keuze.layout === 'cijfer') return;
			var n = keuze.layout === 'grid' ? 3 : 1;
			vKaarten.style.gridTemplateColumns = keuze.layout === 'grid' ? 'repeat(auto-fit,minmax(130px,1fr))' : '1fr';
			var lijst = r.voorbeeld.slice(0, n);
			if (!lijst.length) {
				vKaarten.appendChild(el('span', { class: 'sfpb-hulp', text: 'Nog geen reviews om te tonen.' }));
			}
			lijst.forEach(function (rv) {
				vKaarten.appendChild(el('div', { class: 'sfpb-mvak' }, keuze.layout === 'banner'
					? [el('b', { text: rv.author }), el('span', { class: 'sfpb-ster', 'aria-hidden': 'true', text: sterren(rv.rating) })]
					: [el('span', { class: 'sfpb-ster', 'aria-hidden': 'true', text: sterren(rv.rating) }), el('span', { text: rv.text }), el('span', { style: 'font-size:14px', text: rv.author + (rv.time ? ' · ' + rv.time : '') })]));
			});
		}

		if (ui.rvTab === 'weergave') {
			var vormen = el('div', { class: 'sfpb-vormen' });
			var opties = [['slider', 'Eén tegelijk', '<i></i>', ''], ['grid', 'Naast elkaar', '<i></i><i></i><i></i>', ''], ['banner', 'Namen met sterren', '<i style="flex:.5"></i><i style="flex:.5"></i>', '']];
			if (!r.oud) opties.push(['cijfer', 'Alleen het cijfer', '<i style="flex:.45"></i>', 'sfpb-onder']);
			opties.forEach(function (o) {
				var b = el('button', { type: 'button', 'aria-pressed': keuze.layout === o[0] ? 'true' : 'false' }, [el('span', { class: 'sfpb-mini ' + o[3], html: o[2] }), el('span', { text: o[1] })]);
				b.addEventListener('click', function () {
					keuze.layout = o[0];
					Array.prototype.forEach.call(vormen.children, function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
					tekenVoorbeeld();
				});
				vormen.appendChild(b);
			});
			var min = el('select', null, [5, 4, 3, 2, 1].map(function (n) { return el('option', { value: String(n), text: n === 1 ? '1 ster' : n + ' sterren' }); }));
			min.value = String(r.opties.min_stars);
			links = el('div', { class: 'sfpb-stapel sfpb-ruim' }, [
				vormen,
				veld('Toon reviews vanaf', min, null, 'sfpb-rv-min'),
				schakel(r.opties.summary, 'Toon het cijfer boven de reviews', function (nu) { keuze.summary = nu; tekenVoorbeeld(); }),
				el('p', { class: 'sfpb-hulp', text: 'Je plaatst reviews met de code [google_reviews] in een pagina. Bestaande codes blijven werken.' }),
				el('div', { class: 'sfpb-rij' }, [
					el('button', { class: 'sfpb-knop sfpb-hoofd', type: 'button', text: 'Opslaan', klik: function () {
						doe(this, 'rv-weergave', api('/reviews', 'POST', { layout: keuze.layout, min_stars: min.value, summary: keuze.summary }));
					} }),
					flitsPlek('rv-weergave')
				])
			]);
		} else if (ui.rvTab === 'eigen') {
			var lijst = el('div', { class: 'sfpb-lijst' });
			r.eigen.forEach(function (rv) {
				lijst.appendChild(el('div', { class: 'sfpb-regel' }, [
					el('span', { class: 'sfpb-groei' }, [el('b', { text: rv.author }), ' · ' + (rv.rating === 1 ? '1 ster' : rv.rating + ' sterren'), el('br'), el('span', { class: 'sfpb-hulp', text: (rv.datum ? datumTekst(rv.datum) : (rv.time || 'zonder datum')) + ' · ' + (rv.text || '').slice(0, 90) + ((rv.text || '').length > 90 ? '…' : '') })]),
					r.oud ? null : el('button', { class: 'sfpb-knop', type: 'button', text: 'Bewerken', klik: function () { ui.rvBewerk = rv; teken(); } })
				]));
			});
			if (!r.eigen.length) lijst.appendChild(el('div', { class: 'sfpb-regel' }, [el('span', { class: 'sfpb-hulp', text: 'Nog geen eigen reviews.' })]));
			var formulier = null;
			if (ui.rvBewerk && !r.oud) {
				var b = ui.rvBewerk;
				var naam = tekstInvoer(b.author);
				var ster = el('select', null, [5, 4, 3, 2, 1].map(function (n) { return el('option', { value: String(n), text: n === 1 ? '1 ster' : n + ' sterren' }); }));
				ster.value = String(b.rating || 5);
				var datum = el('input', { type: 'date', value: b.datum || '' });
				var rtekst = el('textarea', { value: b.text || '' });
				var vermelding = tekstInvoer(b.vermelding || '', 'bijvoorbeeld: Vertaald uit het Nederlands.');
				formulier = el('div', { class: 'sfpb-groepvak' }, [
					el('h2', { text: b.id ? 'Review bewerken' : 'Review toevoegen' }),
					veld('Naam', naam, null, 'sfpb-rv-naam'),
					veld('Sterren', ster, null, 'sfpb-rv-ster'),
					veld('Datum van de review', datum, b.id && !b.datum && b.time ? 'Nu staat er: ' + b.time + '. Met een datum blijft de ouderdom vanzelf kloppen.' : 'De site toont hoe lang geleden dat is.', 'sfpb-rv-datum'),
					veld('Tekst', rtekst, null, 'sfpb-rv-tekst'),
					veld('Vermelding', vermelding, 'Komt klein onder de naam, bijvoorbeeld dat de review vertaald is. Leeg: geen vermelding.', 'sfpb-rv-vermelding'),
					el('div', { class: 'sfpb-rij' }, [
						el('button', { class: 'sfpb-knop sfpb-hoofd', type: 'button', text: b.id ? 'Opslaan' : 'Toevoegen', klik: function () {
							doe(this, 'rv-eigen', api('/reviews/eigen', 'POST', { id: b.id || '', author: naam.value, rating: ster.value, datum: datum.value, text: rtekst.value, vermelding: vermelding.value, time: b.time || '' }), null, function () { ui.rvBewerk = null; });
						} }),
						el('button', { class: 'sfpb-knop', type: 'button', text: 'Annuleren', klik: function () { ui.rvBewerk = null; teken(); } }),
						b.id ? el('button', { class: 'sfpb-knop sfpb-weg', type: 'button', text: 'Weghalen', klik: function () {
							if (!window.confirm('Deze review weghalen?')) return;
							doe(this, 'rv-eigen', api('/reviews/eigen/weg', 'POST', { id: b.id }), 'Weggehaald', function () { ui.rvBewerk = null; });
						} }) : null,
						flitsPlek('rv-eigen')
					])
				]);
			}
			links = el('div', { class: 'sfpb-stapel' }, [
				r.oud ? el('div', { class: 'sfpb-weet' }, [el('span', { text: 'Eigen reviews bewerk je hier zodra de plugin SFP Google Reviews uit staat. Tot die tijd beheer je ze in die plugin.' })]) : null,
				lijst,
				formulier,
				(!formulier && !r.oud) ? el('div', { class: 'sfpb-rij' }, [el('button', { class: 'sfpb-knop', type: 'button', text: 'Review toevoegen', klik: function () { ui.rvBewerk = { rating: 5, datum: new Date().toISOString().slice(0, 10) }; teken(); } }), flitsPlek('rv-eigen')]) : null
			]);
		} else {
			var st = r.stand;
			links = el('div', { class: 'sfpb-stapel' }, [
				status(st.verbonden ? true : (st.ingesteld ? null : false), st.verbonden ? 'Verbonden met Google' : (st.ingesteld ? 'Ingesteld, nog niets opgehaald' : 'Nog niet verbonden')),
				st.verbonden ? el('p', { text: st.cijfer_tekst + ' op basis van ' + st.aantal + (st.aantal === 1 ? ' review.' : ' reviews.') }) : null,
				st.ingesteld ? el('div', { class: 'sfpb-rij' }, [
					el('button', { class: 'sfpb-knop', type: 'button', text: 'Nu ophalen', klik: function () { doe(this, 'rv-haal', api('/reviews/ophalen', 'POST', {}), 'Opgehaald'); } }),
					flitsPlek('rv-haal')
				]) : null,
				el('p', { class: 'sfpb-hulp', text: 'De koppeling zelf is eenmalig ingesteld en staat bij Onderhoud.' })
			]);
		}

		tekenVoorbeeld();
		return el('section', { class: 'sfpb-scherm' }, [
			terugKnop(),
			kop('Reviews', 'Reviews van Google en je eigen reviews.'),
			r.oud ? el('div', { class: 'sfpb-weet' }, [el('span', { text: 'Reviews draaien nog via de plugin SFP Google Reviews. Wat je bij Weergave en bij de koppeling instelt, geldt nu al. De nieuwe opmaak en teksten per taal gaan in zodra die plugin uit staat.' })]) : null,
			tabs,
			el('div', { class: 'sfpb-twee' }, [links, voorbeeld([el('div', { class: 'sfpb-m sfpb-stapel', style: 'gap:12px' }, [vKop, vKaarten])])])
		]);
	}

	/* ---------- Onderdeel: tooltips ---------- */

	function detailTooltips() {
		var voorbeeldTekst = { nl: ['De meeste sprekers overschatten het ', 'spotlighteffect', '.', 'Het gevoel dat iedereen je fouten ziet. Dat gebeurt zelden.'], en: ['Most speakers overestimate the ', 'spotlight effect', '.', 'The feeling that everyone notices your mistakes. They rarely do.'], pt: ['A maioria dos oradores superestima o ', 'efeito holofote', '.', 'A sensação de que todos notam seus erros. Raramente notam.'] }[S.site.taal] || [];
		return el('section', { class: 'sfpb-scherm' }, [
			terugKnop(),
			kop('Tooltips', 'Uitleg bij een woord, die verschijnt als de lezer het aanwijst of aantikt.'),
			S.tooltips.oud ? el('div', { class: 'sfpb-weet' }, [el('span', { text: 'Tooltips draaien nog via de plugin SFP Tooltip. Dit onderdeel neemt het over zodra die plugin uit staat. Bestaande tooltips blijven dan werken.' })]) : null,
			el('div', { class: 'sfpb-twee' }, [
				el('div', { class: 'sfpb-stapel' }, [
					el('h2', { text: 'Hier valt niets in te stellen' }),
					el('p', { text: 'Tooltips volgen het merk van de site. Je maakt er een in de editor: selecteer een woord en klik op Tooltip.' })
				]),
				voorbeeld([el('div', { class: 'sfpb-m sfpb-stapel', style: 'gap:12px' }, [
					el('span', null, [voorbeeldTekst[0], el('span', { class: 'sfpb-tt-term', text: voorbeeldTekst[1] }), voorbeeldTekst[2]]),
					el('div', { class: 'sfpb-tt-vlak', text: voorbeeldTekst[3] })
				])])
			])
		]);
	}

	/* ---------- Onderdeel: WhatsApp ---------- */

	function detailWhatsapp() {
		var w = S.whatsapp;
		var nummer = tekstInvoer(w.nummer, '+' + w.norm, 'tel');
		var bericht = tekstInvoer(w.bericht, w.standaard);
		return el('section', { class: 'sfpb-scherm' }, [
			terugKnop(),
			kop('WhatsApp-knop', 'Een zwevende knop op de pagina\'s waar je hem aanzet.'),
			el('div', { class: 'sfpb-twee' }, [
				el('div', { class: 'sfpb-stapel sfpb-ruim' }, [
					veld('Telefoonnummer', nummer, 'Leeg: het nummer van het netwerk.', 'sfpb-wa-nr'),
					veld('Eerste bericht van de bezoeker', bericht, 'Leeg: de standaardtekst in de taal van de site.', 'sfpb-wa-tekst'),
					el('p', { class: 'sfpb-hulp', text: 'Staat op een dienstpagina de knop onderaan in beeld, dan schuift de WhatsApp-knop erboven.' }),
					el('div', { class: 'sfpb-rij' }, [
						el('button', { class: 'sfpb-knop sfpb-hoofd', type: 'button', text: 'Opslaan', klik: function () {
							bewaarInstellingen(this, 'wa', { whatsapp_number: nummer.value, teksten: { whatsapp_bericht: bericht.value } });
						} }),
						flitsPlek('wa')
					])
				]),
				voorbeeld([el('div', { class: 'sfpb-telefoon sfpb-m' }, [el('div', { class: 'sfpb-scherm2' }, [
					el('div', { class: 'sfpb-pag' }, schets(4).concat([el('span', { class: 'sfpb-wa', html: '<svg viewBox="0 0 32 32" aria-hidden="true"><path fill="#fff" d="M26.6 5.4C23.8 2.6 20 1 16 1 7.7 1 1 7.7 1 16c0 2.6.7 5.2 2 7.5L1 31l7.7-2c2.2 1.2 4.7 1.9 7.3 1.9 8.3 0 15-6.7 15-15 0-4-1.6-7.8-4.4-10.5zM16 28.4c-2.3 0-4.6-.6-6.6-1.8l-.5-.3-4.6 1.2 1.2-4.5-.3-.5C4 20.4 3.3 18.2 3.3 16c0-7 5.7-12.7 12.7-12.7 3.4 0 6.6 1.3 9 3.7 2.4 2.4 3.7 5.6 3.7 9C28.7 22.7 23 28.4 16 28.4zm7-9.5c-.4-.2-2.3-1.1-2.6-1.2-.3-.1-.6-.2-.9.2-.3.4-1 1.2-1.3 1.5-.2.2-.5.3-.8.1-.4-.2-1.7-.6-3.2-2-1.2-1.1-2-2.4-2.2-2.8-.2-.4 0-.6.2-.8.2-.2.4-.4.6-.7.2-.2.2-.4.3-.6.1-.2 0-.5 0-.7-.1-.2-.9-2.1-1.2-2.9-.3-.8-.6-.7-.9-.7h-.7c-.2 0-.6.1-1 .5s-1.3 1.3-1.3 3.2 1.3 3.7 1.5 3.9c.2.2 2.6 3.9 6.2 5.5.9.4 1.5.6 2.1.8.9.3 1.7.2 2.3.1.7-.1 2.3-.9 2.6-1.8.3-.9.3-1.7.2-1.8 0-.2-.3-.3-.7-.5z"/></svg>' })]))
				])])])
			])
		]);
	}

	/* ---------- Onderdeel: kleurrollen (sinds 3.15.0) ---------- */

	function detailKleurrollen() {
		var K = S.kleurrollen || { rollen: [], palet: [], bewaard: false, snippet: false };
		var keuzes = {};
		var rijen = K.rollen.map(function (r) {
			var s = el('select', { id: 'sfpb-rol-' + r.rol });
			s.appendChild(el('option', { value: '', text: 'Niet gevoerd' }));
			K.palet.forEach(function (kleur, i) {
				s.appendChild(el('option', { value: String(i), text: 'Paletkleur ' + (i + 1) + (kleur ? ' (' + kleur + ')' : ' (leeg)') }));
			});
			s.value = r.slot === null || r.slot === undefined ? '' : String(r.slot);
			keuzes[r.rol] = s;
			var staal = el('span', { class: 'sfpb-staal', 'aria-hidden': 'true' });
			function kleurStaal() { var k = s.value === '' ? '' : K.palet[Number(s.value)]; staal.style.background = k || 'transparent'; staal.style.borderStyle = k ? 'solid' : 'dashed'; }
			s.addEventListener('change', kleurStaal);
			kleurStaal();
			return el('div', { class: 'sfpb-rij' }, [staal, veld(r.naam, s)]);
		});
		var uitleg = K.bewaard
			? 'Deze toewijzing staat in de plugin. Het snippet "Kleuren: rollen van het palet" is niet meer nodig.'
			: (K.snippet ? 'Nu nog uit het snippet "Kleuren: rollen van het palet". Zet het onderdeel aan: de plugin neemt deze toewijzing dan over.' : 'Nog geen toewijzing. Kies per rol de kleur uit het palet van Astra.');
		return el('section', { class: 'sfpb-scherm' }, [
			terugKnop(),
			kop('Kleurrollen', 'Welke kleur van het palet in Astra elke merkrol draagt. De kleuren zelf stel je in Astra in; hier kies je alleen welke kleur bij welke rol hoort.'),
			el('div', { class: 'sfpb-stapel sfpb-ruim' }, [
				el('p', { class: 'sfpb-hulp', text: uitleg }),
				el('div', { class: 'sfpb-stapel' }, rijen),
				el('p', { class: 'sfpb-hulp', text: 'Een rol die het merk niet voert, staat op Niet gevoerd. De merkbron zegt welke rollen een merk voert. De caches leegt de plugin niet: doe dat na opslaan.' }),
				el('div', { class: 'sfpb-rij' }, [
					el('button', { class: 'sfpb-knop sfpb-hoofd', type: 'button', text: 'Opslaan', klik: function () {
						var w = {};
						Object.keys(keuzes).forEach(function (rol) { if (keuzes[rol].value !== '') w[rol] = Number(keuzes[rol].value); });
						bewaarInstellingen(this, 'rollen', { kleurrollen: w });
					} }),
					flitsPlek('rollen')
				])
			])
		]);
	}

	/* ---------- Onderdeel: tools (sinds 3.8.0) ---------- */

	function detailTools() {
		var T = S.tools || { bestaat: false, tools: [], adres: '', beheer: '', log: [] };
		var adres = tekstInvoer(T.adres, T.beheer, 'email');
		var rijen = T.tools.map(function (t) {
			var stand = ! t.motor ? 'geen beschrijving' : (t.fouten ? 'beschrijving klopt niet' : (t.soort || 'onbekend'));
			return el('tr', {}, [
				el('td', {}, [el('a', { href: t.bewerk, text: t.titel }), t.status !== 'publish' ? el('span', { class: 'sfpb-tag', text: t.status }) : null]),
				el('td', { text: stand, class: t.fouten || ! t.motor ? 'sfpb-fout' : '' }),
				el('td', { text: t.meldingen ? String(t.meldingen) : '' }),
				el('td', { text: '\uD83D\uDC4D ' + t.omhoog + '   \uD83D\uDC4E ' + t.omlaag })
			]);
		});
		var tabel = el('table', { class: 'sfpb-tabel' }, [
			el('thead', {}, [el('tr', {}, [el('th', { text: 'Tool' }), el('th', { text: 'Motor' }), el('th', { text: 'Meldingen' }), el('th', { text: 'Duimpjes' })])]),
			el('tbody', {}, rijen.length ? rijen : [el('tr', {}, [el('td', { text: T.bestaat ? 'Nog geen tools.' : 'Het berichttype tool bestaat niet op deze site.', colspan: '4' })])])
		]);
		var log = T.log.length ? el('ul', { class: 'sfpb-lijst' }, T.log.map(function (r) {
			return el('li', { text: r.tijd + ' · ' + r.tool + ' · duim ' + r.stem + ': ' + r.tekst + (r.email ? ' (' + r.email + ')' : '') });
		})) : el('p', { class: 'sfpb-hulp', text: 'Nog geen tekstfeedback.' });
		return el('section', { class: 'sfpb-scherm' }, [
			terugKnop(),
			kop('Tools', 'Lichte tools voor bezoekers. Elke tool is een bericht van het type tool met een beschrijving; de motor rekent in de browser en bewaart niets op de server.'),
			el('div', { class: 'sfpb-stapel sfpb-ruim' }, [
				veld('Adres voor tekstfeedback', adres, 'Waar de tekst bij een duimpje heen gaat. Leeg: het beheerdersadres van de site.', 'sfpb-tool-adres'),
				el('div', { class: 'sfpb-rij' }, [
					el('button', { class: 'sfpb-knop sfpb-hoofd', type: 'button', text: 'Opslaan', klik: function () {
						bewaarInstellingen(this, 'tools', { tool_feedback_mail: adres.value });
					} }),
					flitsPlek('tools')
				]),
				el('h2', { text: 'Tools op deze site' }),
				tabel,
				el('p', { class: 'sfpb-hulp', text: 'De duimpjes tellen door op de tellingen van de oude snippets. De beschrijving van een tool bewerk je in de editor, paneel Deze pagina.' }),
				el('h2', { text: 'Laatste tekstfeedback' }),
				log
			])
		]);
	}

	/* ---------- Onderdeel: zelftest ---------- */

	/** Aanroep naar /sfp/v1/zelftest; het antwoord vervangt S.zelftest. */
	function zt(knop, sleutel, pad, gegevens, klaarTekst, extra) {
		var opties = { path: '/sfp/v1/zelftest' + pad, method: 'POST' };
		if (gegevens instanceof window.FormData) opties.body = gegevens; else opties.data = gegevens;
		return doe(knop, sleutel, wp.apiFetch(opties).then(function (a) {
			var stand = a && a.stand ? a.stand : a;
			if (stand && stand.tests) S.zelftest = stand;
			if (extra) extra(a);
			return null;
		}), klaarTekst);
	}

	function detailZelftest() {
		var Z = S.zelftest || { tests: [], cloudflare: {}, log: [] };
		var delen = [];

		// Pakket inlezen (nieuw of een nieuwe versie).
		var naamInvoer = tekstInvoer('', 'Bijvoorbeeld dps');
		var bestand = el('input', { type: 'file', accept: '.zip,application/zip' });
		var inlezen = el('div', { class: 'sfpb-stapel sfpb-ruim' }, [
			el('h2', { text: Z.tests.length ? 'Nieuwe versie of een andere test inlezen' : 'Een testpakket inlezen' }),
			el('p', { text: 'Een testpakket is een zip met manifest.json, inhoud.json, motor.js, label.js en blad.html. De vorige versie blijft bewaard.' }),
			Z.zip ? null : el('div', { class: 'sfpb-let' }, [el('span', { text: 'Deze server kan geen zip openen. Lees het pakket in via REST.' })]),
			veld('Naam van de test', naamInvoer, 'Zoals in het manifest: kleine letters, bijvoorbeeld dps.', 'sfpb-zt-naam'),
			veld('Zip met het pakket', bestand, null, 'sfpb-zt-zip'),
			el('div', { class: 'sfpb-rij' }, [
				el('button', { class: 'sfpb-knop sfpb-hoofd', type: 'button', text: 'Inlezen', klik: function () {
					if (!bestand.files || !bestand.files[0] || !naamInvoer.value.trim()) { window.alert('Kies een zip en vul de naam van de test in.'); return; }
					var fd = new window.FormData();
					fd.append('pakket', bestand.files[0]);
					zt(this, 'zt-inlezen', '/' + encodeURIComponent(naamInvoer.value.trim()) + '/pakket', fd, 'Ingelezen', function (a) {
						if (a && a.let_op && a.let_op.length) window.alert('Ingelezen. Let op:\n- ' + a.let_op.join('\n- '));
					}).catch(function () {});
				} }),
				flitsPlek('zt-inlezen')
			])
		]);

		Z.tests.forEach(function (t) {
			var I = t.instellingen;
			var pagina = paginaKeuze(I.testpagina, 'Kies de testpagina');
			var pdfPad = tekstInvoer(I.pdf_pad, 'presentatietest/resultaat');
			/* Sinds 3.11.0: per extra taal van het pakket een eigen testpagina en pdf-adres. */
			var extraTalen = (t.talen || []).slice(1).map(function (taal) {
				var eigen = (I.talen || {})[taal] || {};
				return { taal: taal, pagina: paginaKeuze(eigen.testpagina || 0, 'Kies de testpagina (' + taal + ')'), pdf: tekstInvoer(eigen.pdf_pad || '', 'bijvoorbeeld en/test/result') };
			});
			var logo = tekstInvoer(I.logo ? String(I.logo) : '', 'Media-ID');
			var whStart = tekstInvoer('', I.webhook_start ? 'Ingesteld; laat leeg om te houden' : 'https://...', 'password');
			var whAf = tekstInvoer('', I.webhook_afgerond ? 'Ingesteld; laat leeg om te houden' : 'https://...', 'password');
			[whStart, whAf].forEach(function (w) { w.setAttribute('autocomplete', 'off'); });
			var proef = { aan: I.proefmodus };
			var hussel = { aan: I.husselen !== false };
			var ctaKop = tekstInvoer(I.cta_kop || '', t.naam || 'Naam van de test');
			var ctaZin = tekstInvoer(I.cta_zin || '', 'Eén zin: wat levert de test op?');
			var ctaKnop = tekstInvoer(I.cta_knop || '', 'Vaste tekst: Doe de zelftest');
			var c = t.tellingen;
			var waarschuwing = [];
			if (!t.blad) waarschuwing.push('Dit pakket heeft geen pdf-blad: er komt geen pdf.');
			if (!I.pdf_pad) waarschuwing.push('Het adres van de pdf-link ontbreekt: de link in mail 1 werkt nog niet.');
			if (!I.proefmodus && (!I.webhook_start || !I.webhook_afgerond)) waarschuwing.push('Proefmodus staat uit, maar niet beide webhookadressen zijn ingesteld.');
			if (c.crm_mislukt) waarschuwing.push(c.crm_mislukt + ' verzending(en) naar het CRM zijn mislukt. Zie het logboek.');
			if (c.pdf_mislukt) waarschuwing.push(c.pdf_mislukt + ' pdf(s) zijn niet gemaakt. Zie het logboek.');

			delen.push(el('div', { class: 'sfpb-stapel sfpb-ruim' }, [
				el('h2', { text: (t.naam || t.test) + ' (' + t.test + ')' }),
				el('p', { class: 'sfpb-hulp', text: 'Pakket ' + t.versie + ', ingelezen ' + t.ingelezen + '. ' + t.vragen + ' vragen; lang ' + (t.lengtes.long || 0) + (t.lengtes.short ? ', kort ' + t.lengtes.short : '') + '.' }),
				waarschuwing.length ? el('div', { class: 'sfpb-let' }, waarschuwing.map(function (w) { return el('span', { text: w }); })) : null,
				t.let_op.length ? el('div', { class: 'sfpb-weet' }, t.let_op.map(function (w) { return el('span', { text: w }); })) : null,
				el('p', { text: 'Gestart: ' + c.gestart + ' (laatste 30 dagen ' + c.gestart_30 + '). Afgerond: ' + c.afgerond + ' (laatste 30 dagen ' + c.afgerond_30 + '). Pdf klaar: ' + c.pdf_klaar + '. In de wachtrij: CRM ' + c.crm_wacht + ', pdf ' + c.pdf_wacht + '.' }),
				veld('Testpagina', pagina, 'De pagina met het blok Zelftest. Knoppen "Doe de test" wijzen hierheen.', 'sfpb-zt-pag-' + t.test),
				veld('Logo op de testpagina', logo, 'Media-ID van het logo voor een witte achtergrond (versie origineel). Leeg: het logo van de site.', 'sfpb-zt-logo-' + t.test),
				veld('Adres van de pdf-link', pdfPad, 'Zonder domein, bijvoorbeeld presentatietest/resultaat. Geen bestaande pagina.', 'sfpb-zt-pdf-' + t.test),
				extraTalen.length ? el('h3', { text: 'Andere talen van deze test' }) : null,
				extraTalen.length ? el('p', { class: 'sfpb-hulp', text: 'De eerste taal (' + (t.talen || [])[0] + ') gebruikt de testpagina en het adres hierboven. Leeg: ook voor deze taal de velden hierboven.' }) : null
			].concat(extraTalen.reduce(function (l, x) {
				return l.concat([
					veld('Testpagina ' + x.taal, x.pagina, 'De pagina met het blok Zelftest in deze taal.', 'sfpb-zt-pag-' + t.test + '-' + x.taal),
					veld('Adres van de pdf-link ' + x.taal, x.pdf, 'Zonder domein. Elk adres een eigen uitzondering in Cloudflare.', 'sfpb-zt-pdf-' + t.test + '-' + x.taal)
				]);
			}, [])).concat([
				veld('Webhook bij de start (SureContact)', whStart, 'Wordt nooit getoond. Typ - om te wissen.', 'sfpb-zt-whs-' + t.test),
				veld('Webhook bij het afronden (SureContact)', whAf, 'Wordt nooit getoond. Typ - om te wissen.', 'sfpb-zt-wha-' + t.test),
				schakel(I.proefmodus, 'Proefmodus: niets naar het CRM, de gegevens komen in het logboek', function (nu) { proef.aan = nu; }),
				schakel(I.husselen !== false, 'Vragen en antwoorden husselen: de kernvragen en hun antwoorden in willekeurige volgorde, zonder bloknaam (netwerknorm: aan)', function (nu) { hussel.aan = nu; }),
				el('h3', { text: 'Oproep om de test te doen' }),
				el('p', { class: 'sfpb-hulp', text: 'Eén tekst voor het blok Zelftest-oproep, de code [sfp_zelftest_cta] en de promokaart en kopkaart van een pijler.' + (Z.site_test === t.test ? ' Dit is de test van de site.' : (Z.site_test ? '' : ' Er is nog geen test van de site: die heeft een gepubliceerde testpagina nodig.')) }),
				veld('Kop', ctaKop, 'Leeg: de naam van de test.', 'sfpb-zt-ctak-' + t.test),
				veld('Zin', ctaZin, 'Hoogstens 220 tekens. Leeg: geen zin.', 'sfpb-zt-ctaz-' + t.test),
				veld('Knoptekst', ctaKnop, 'Leeg: de vaste tekst in de taal van de site.', 'sfpb-zt-ctab-' + t.test),
				el('div', { class: 'sfpb-rij' }, [
					el('button', { class: 'sfpb-knop sfpb-hoofd', type: 'button', text: 'Opslaan', klik: function () {
						var talen = {}; extraTalen.forEach(function (x) { talen[x.taal] = { testpagina: x.pagina.value, pdf_pad: x.pdf.value }; });
						zt(this, 'zt-' + t.test, '/' + t.test + '/instellingen', { testpagina: pagina.value, logo: logo.value, pdf_pad: pdfPad.value, webhook_start: whStart.value, webhook_afgerond: whAf.value, proefmodus: proef.aan, husselen: hussel.aan, cta_kop: ctaKop.value, cta_zin: ctaZin.value, cta_knop: ctaKnop.value, talen: talen });
					} }),
					t.vorige ? el('button', { class: 'sfpb-knop', type: 'button', text: 'Vorige pakketversie terugzetten', klik: function () {
						if (window.confirm('De vorige versie van het pakket terugzetten?')) zt(this, 'zt-' + t.test, '/' + t.test + '/pakket/terug', {}, 'Teruggezet');
					} }) : null,
					flitsPlek('zt-' + t.test)
				])
			])));
		});

		// Cloudflare.
		var cf = Z.cloudflare || {};
		var account = tekstInvoer('', cf.account ? 'Ingesteld; laat leeg om te houden' : '32 tekens');
		var token = tekstInvoer('', cf.token ? 'Ingesteld; laat leeg om te houden' : 'Token met Browser Run - Edit', 'password');
		token.setAttribute('autocomplete', 'off');
		var cloudflare = el('div', { class: 'sfpb-stapel sfpb-ruim' }, [
			el('h2', { text: 'Pdf maken: Cloudflare Browser Run' }),
			el('p', { text: (cf.account && cf.token ? 'Ingesteld.' : 'Nog niet ingesteld: de site maakt nog geen pdf.') + (cf.pauze ? ' Cloudflare vroeg om te wachten tot ' + cf.pauze + '.' : '') }),
			veld('Account-ID', account, 'Wordt nooit getoond. Typ - om te wissen.', 'sfpb-zt-cfa'),
			veld('Token', token, 'Wordt nooit getoond. Typ - om te wissen.', 'sfpb-zt-cft'),
			el('div', { class: 'sfpb-rij' }, [
				el('button', { class: 'sfpb-knop sfpb-hoofd', type: 'button', text: 'Opslaan', klik: function () {
					zt(this, 'zt-cf', '/cloudflare', { account: account.value, token: token.value });
				} }),
				flitsPlek('zt-cf')
			])
		]);

		// Gegevens van een bezoeker wissen.
		var wisMail = tekstInvoer('', 'naam@voorbeeld.nl', 'email');
		var wissen = el('div', { class: 'sfpb-stapel sfpb-ruim' }, [
			el('h2', { text: 'Gegevens van een deelnemer wissen' }),
			el('p', { text: 'Voor een verwijderverzoek: wist alle tests, pdf\'s en deelafbeeldingen van dit e-mailadres op deze site. Het contact in het CRM blijft staan.' }),
			veld('E-mailadres', wisMail, null, 'sfpb-zt-wis'),
			el('div', { class: 'sfpb-rij' }, [
				el('button', { class: 'sfpb-knop', type: 'button', text: 'Wissen', klik: function () {
					var m = wisMail.value.trim();
					if (!m || !window.confirm('Alles van ' + m + ' definitief wissen?')) return;
					zt(this, 'zt-wis', '/wissen', { email: m }, 'Gewist', function (a) { window.alert(a.gewist + ' test(s) gewist.'); });
				} }),
				flitsPlek('zt-wis')
			])
		]);

		// Logboek.
		var log = (Z.log || []).map(function (r) {
			var kop = r.tijd + ' UTC · ' + r.test + ' · ' + r.tekst;
			return r.gegevens && Object.keys(r.gegevens).length
				? el('details', { class: 'sfpb-vouw' }, [el('summary', { text: kop }), el('pre', { style: 'white-space:pre-wrap;overflow-wrap:anywhere;font-size:12px', text: JSON.stringify(r.gegevens, null, 2) })])
				: el('p', { class: r.soort === 'fout' ? 'sfpb-mis' : 'sfpb-hulp', text: kop });
		});

		return el('section', { class: 'sfpb-scherm' }, [
			terugKnop(),
			kop('Zelftest', 'Een zelftest voor bezoekers. De inhoud per label is een testpakket; het resultaat gaat als pdf per e-mail via het CRM.'),
			Z.aan ? null : el('div', { class: 'sfpb-let' }, [el('span', { text: 'Het onderdeel staat uit. Zet het aan bij Onderdelen.' })]),
			el('div', { class: 'sfpb-stapel', style: 'gap:40px' }, delen.concat([inlezen, cloudflare, wissen,
				el('div', { class: 'sfpb-stapel' }, [el('h2', { text: 'Logboek (laatste 20)' })].concat(log.length ? log : [el('p', { class: 'sfpb-hulp', text: 'Nog niets.' })]))
			]))
		]);
	}

	/* ---------- Onderdeel: footerlinks ---------- */

	function detailFooter() {
		if (!ui.footer) {
			ui.footer = JSON.parse(JSON.stringify(S.footer.groepen || []));
		}
		var groepen = ui.footer;
		var blokken = el('div', { class: 'sfpb-stapel' });
		var vb = el('div', { class: 'sfpb-footvoorb' });

		function linkNaam(l) {
			return l.label || (l.soort === 'pagina' ? paginaTitel(l.id) : '') || '(pagina bestaat niet meer)';
		}
		function tekenVb() {
			vb.innerHTML = '';
			groepen.forEach(function (g) {
				vb.appendChild(el('div', null, [el('div', { class: 'sfpb-fkop', text: g.naam || 'Naam van de groep' }), el('div', { class: 'sfpb-flinks', text: g.links.map(linkNaam).join(' • ') })]));
			});
			if (!groepen.length) vb.appendChild(el('span', { class: 'sfpb-hulp', text: 'Nog geen groepen.' }));
		}
		groepen.forEach(function (g, gi) {
			var naam = tekstInvoer(g.naam);
			naam.addEventListener('input', function () { g.naam = naam.value; tekenVb(); });
			var lijst = el('div', { class: 'sfpb-lijst' });
			g.links.forEach(function (l, li) {
				lijst.appendChild(el('div', { class: 'sfpb-regel' }, [
					el('span', { class: 'sfpb-groei' }, [el('b', { text: linkNaam(l) }), l.soort === 'link' ? el('br') : null, l.soort === 'link' ? el('span', { class: 'sfpb-hulp', text: l.url }) : null]),
					el('button', { class: 'sfpb-knop sfpb-klein', type: 'button', 'aria-label': linkNaam(l) + ' omhoog', text: 'Omhoog', disabled: li === 0, klik: function () { g.links.splice(li - 1, 0, g.links.splice(li, 1)[0]); teken(); } }),
					el('button', { class: 'sfpb-knop sfpb-klein sfpb-weg', type: 'button', 'aria-label': linkNaam(l) + ' weghalen', text: 'Weghalen', klik: function () { g.links.splice(li, 1); teken(); } })
				]));
			});
			var kies = paginaKeuze('', null);
			kies.setAttribute('aria-label', 'Kies een pagina');
			var lTekst = tekstInvoer('', 'Tekst van de link');
			var lUrl = tekstInvoer('', 'Plak de link', 'url');
			lTekst.style.maxWidth = lUrl.style.maxWidth = '200px';
			blokken.appendChild(el('div', { class: 'sfpb-groepvak' }, [
				veld('Naam van de groep', naam, null, 'sfpb-ft-naam-' + gi),
				lijst,
				el('div', { class: 'sfpb-rij' }, [kies, el('button', { class: 'sfpb-knop', type: 'button', text: 'Pagina toevoegen', klik: function () {
					var id = Number(kies.value);
					if (g.links.length >= 20) { window.alert('Een groep heeft hoogstens 20 links.'); return; }
					if (id && !g.links.some(function (x) { return x.soort === 'pagina' && Number(x.id) === id; })) { g.links.push({ soort: 'pagina', id: id, label: '' }); teken(); }
				} })]),
				el('div', { class: 'sfpb-rij' }, [lTekst, lUrl, el('button', { class: 'sfpb-knop', type: 'button', text: 'Andere link toevoegen', klik: function () {
					if (!lTekst.value.trim() || !lUrl.value.trim()) { (lTekst.value.trim() ? lUrl : lTekst).focus(); return; }
					if (g.links.length >= 20) { window.alert('Een groep heeft hoogstens 20 links.'); return; }
					g.links.push({ soort: 'link', url: lUrl.value.trim(), label: lTekst.value.trim() }); teken();
				} })]),
				el('button', { class: 'sfpb-knop sfpb-tekst sfpb-weg', type: 'button', style: 'align-self:flex-start', text: 'Deze groep weghalen', klik: function () { groepen.splice(gi, 1); teken(); } })
			]));
		});
		tekenVb();

		return el('section', { class: 'sfpb-scherm' }, [
			terugKnop(),
			kop('Footerlinks', 'De groepen met links onderaan de site.'),
			!S.footer.vlak ? el('div', { class: 'sfpb-let' }, [el('span', { text: 'Het footervlak voor de links (Footer Widget 4 in Astra) is leeg of ontbreekt. De lijst komt op de plek van dat vlak en is nu dus niet te zien. Zet er in Astra een widget in; de lijst vervangt hem.' })]) : null,
			(S.footer.widget && !groepen.length) ? el('div', { class: 'sfpb-weet' }, [
				el('span', { text: 'De links staan nu als HTML in een widget in de footer. Neem ze over om ze hier als lijst te beheren. Er verandert pas iets op de site als je daarna opslaat.' }),
				el('div', { class: 'sfpb-rij' }, [el('button', { class: 'sfpb-knop', type: 'button', text: 'Neem de links over', klik: function () {
					var b = this; b.disabled = true;
					api('/footer/widget').then(function (antw) { ui.footer = antw.groepen || []; teken(); }, function (e) { b.disabled = false; window.alert(foutTekst(e)); });
				} })])
			]) : null,
			el('div', { class: 'sfpb-twee' }, [
				el('div', { class: 'sfpb-stapel sfpb-ruim' }, [
					blokken,
					el('button', { class: 'sfpb-knop', type: 'button', style: 'align-self:flex-start', text: 'Groep toevoegen', disabled: groepen.length >= 6, klik: function () { groepen.push({ naam: '', links: [] }); teken(); } }),
					el('p', { class: 'sfpb-hulp', text: 'Een groep zonder naam of zonder links wordt niet bewaard. De lijst komt op de plek van de widget in de footer: laat die widget staan. Hoogstens 6 groepen. Zonder groepen toont de site weer de widget zelf.' }),
					el('div', { class: 'sfpb-rij' }, [
						el('button', { class: 'sfpb-knop sfpb-hoofd', type: 'button', text: 'Opslaan', klik: function () {
							bewaarInstellingen(this, 'footer', { footer: { groepen: groepen } }, null, function () { ui.footer = null; });
						} }),
						flitsPlek('footer')
					])
				]),
				voorbeeld([el('div', { class: 'sfpb-m' }, [vb])])
			])
		]);
	}

	/* ---------- Cursusdata ---------- */

	function smTekst(sm) {
		var dagen = (sm.data || []).map(datumTekst).join(' | ');
		var tijd = sm.start && sm.eind ? ' (' + sm.start + ' - ' + sm.eind + ')' : (sm.start ? ' (vanaf ' + sm.start + ')' : (sm.eind ? ' (tot ' + sm.eind + ')' : ''));
		return dagen + tijd;
	}

	function schermCursusdata() {
		var c = S.cursusdata;
		var lijst = el('div', { class: 'sfpb-lijst' });
		c.paginas.forEach(function (p) {
			var open = ui.cdOpen === p.id;
			if (open && !ui.cdData) {
				ui.cdData = JSON.parse(JSON.stringify(p.data.length ? p.data : [{ data: [''] }]));
			}
			var samenvatting = p.data.length
				? el('span', { class: 'sfpb-hulp' }, p.data.map(function (sm, i) { return el('span', { style: 'display:block', text: (i + 1) + '. ' + smTekst(sm) }); }))
				: el('span', { class: 'sfpb-hulp', text: 'Geen startmomenten ingesteld.' });
			var rij = el('div', { class: 'sfpb-regel' }, [
				el('span', { class: 'sfpb-groei' }, [el('b', { text: p.titel }), el('br'), samenvatting]),
				flitsPlek('cd-' + p.id),
				open ? null : el('button', { class: 'sfpb-knop', type: 'button', text: 'Bewerken', klik: function () { ui.cdOpen = p.id; ui.cdData = null; teken(); } })
			]);
			lijst.appendChild(rij);
			if (open) {
				var data = ui.cdData;
				var bewerk = el('div', { class: 'sfpb-stapel', style: 'padding:0 0 20px' });
				data.forEach(function (sm, si) {
					var start = el('input', { type: 'time', value: sm.start || '', 'aria-label': 'Begintijd' });
					var eind = el('input', { type: 'time', value: sm.eind || '', 'aria-label': 'Eindtijd' });
					start.addEventListener('change', function () { sm.start = start.value; });
					eind.addEventListener('change', function () { sm.eind = eind.value; });
					var dagen = el('div', { class: 'sfpb-stapel sfpb-krap' });
					(sm.data || []).forEach(function (d, di) {
						var inp = el('input', { type: 'date', value: d || '', 'aria-label': 'Dag ' + (di + 1) });
						inp.addEventListener('change', function () { sm.data[di] = inp.value; });
						dagen.appendChild(el('div', { class: 'sfpb-rij' }, [
							el('span', { style: 'min-width:52px', text: 'Dag ' + (di + 1) }), inp,
							sm.data.length > 1 ? el('button', { class: 'sfpb-knop sfpb-klein sfpb-weg', type: 'button', text: 'Weghalen', 'aria-label': 'Dag ' + (di + 1) + ' weghalen', klik: function () { sm.data.splice(di, 1); teken(); } }) : null
						]));
					});
					bewerk.appendChild(el('div', { class: 'sfpb-sm' }, [
						el('div', { class: 'sfpb-rij sfpb-tussen' }, [el('h3', { text: 'Startmoment ' + (si + 1) }), el('button', { class: 'sfpb-knop sfpb-klein sfpb-weg', type: 'button', text: 'Startmoment weghalen', klik: function () { data.splice(si, 1); teken(); } })]),
						el('div', { class: 'sfpb-rij' }, [el('span', { style: 'min-width:52px', text: 'Lestijd' }), start, 'tot', eind]),
						dagen,
						(sm.data || []).length < 10 ? el('button', { class: 'sfpb-knop sfpb-klein', type: 'button', style: 'align-self:flex-start', text: 'Dag toevoegen', klik: function () { sm.data.push(''); teken(); } }) : null
					]));
				});
				bewerk.appendChild(el('div', { class: 'sfpb-rij' }, [
					el('button', { class: 'sfpb-knop', type: 'button', text: 'Startmoment toevoegen', klik: function () { data.push({ data: [''] }); teken(); } }),
					el('button', { class: 'sfpb-knop sfpb-hoofd', type: 'button', text: 'Opslaan', klik: function () {
						doe(this, 'cd-' + p.id, api('/cursusdata/' + p.id, 'POST', { data: data }), null, function () { ui.cdOpen = 0; ui.cdData = null; });
					} }),
					el('button', { class: 'sfpb-knop', type: 'button', text: 'Annuleren', klik: function () { ui.cdOpen = 0; ui.cdData = null; teken(); } })
				]));
				lijst.appendChild(bewerk);
			}
		});
		if (!c.paginas.length) {
			lijst.appendChild(el('div', { class: 'sfpb-regel' }, [el('span', { class: 'sfpb-hulp', text: 'Deze site heeft geen trainingen. Een pagina wordt een training als je bij Pagina\'s de soort Training kiest.' })]));
		}
		var start = el('input', { type: 'time', value: c.start, 'aria-label': 'Standaard begintijd' });
		var eind = el('input', { type: 'time', value: c.eind, 'aria-label': 'Standaard eindtijd' });
		var mail = tekstInvoer(S.onderhoud.cron_email, S.onderhoud.beheer_email, 'email');
		return el('section', { class: 'sfpb-scherm' }, [
			kop('Cursusdata', 'Startmomenten van trainingen. Ze staan op de trainingspagina en in de keuzelijst van het aanmeldformulier.'),
			!onderdeel('cursusdata').aan ? el('div', { class: 'sfpb-weet' }, [el('span', { text: 'Het onderdeel Cursusdata staat uit. Zet het aan bij Onderdelen om startmomenten te tonen.' })]) : null,
			el('div', { class: 'sfpb-kaart' }, [lijst]),
			el('div', { class: 'sfpb-kaart' }, [
				el('h2', { text: 'Voor alle trainingen' }),
				el('div', { class: 'sfpb-veld' }, [el('span', { class: 'sfpb-label', text: 'Lestijd als een startmoment geen eigen tijd heeft' }), el('div', { class: 'sfpb-rij' }, [start, 'tot', eind]), el('span', { class: 'sfpb-hulp', text: 'Leeg: de site toont dan geen lestijd.' })]),
				veld('Melding over ontbrekende of verlopen data gaat naar', mail, 'Leeg: het e-mailadres van de sitebeheerder.', 'sfpb-cd-mail'),
				el('div', { class: 'sfpb-rij' }, [
					el('button', { class: 'sfpb-knop sfpb-hoofd', type: 'button', text: 'Opslaan', klik: function () {
						bewaarInstellingen(this, 'cd-alg', { cursus_tijd_default_start: start.value, cursus_tijd_default_eind: eind.value, cron_email: mail.value });
					} }),
					flitsPlek('cd-alg')
				])
			])
		]);
	}

	/* ---------- Teksten ---------- */

	function schermTeksten() {
		var velden = [];
		var groepen = el('div');
		S.teksten.forEach(function (g, gi) {
			var inhoud = el('div', { class: 'sfpb-inhoud' });
			var eigen = 0;
			g.items.forEach(function (t) {
				var inp = t.lang ? el('textarea', { value: t.waarde, placeholder: t.standaard }) : tekstInvoer(t.waarde, t.standaard);
				inp.dataset.id = t.id;
				velden.push(inp);
				if (t.waarde) eigen++;
				inhoud.appendChild(veld(t.label, inp, t.standaard ? null : 'Voor de taal van deze site is er geen standaardtekst.', 'sfpb-tk-' + t.id));
			});
			var sleutel = 'tk-' + g.groep;
			var d = el('details', { class: 'sfpb-vouw', open: ui.open[sleutel] !== undefined ? ui.open[sleutel] : gi === 0 }, [
				el('summary', null, [g.groep, el('span', { class: 'sfpb-status', text: meervoud(g.items.length, 'tekst', 'teksten') + (eigen ? ', ' + eigen + ' aangepast' : '') })]),
				inhoud
			]);
			d.addEventListener('toggle', function () { ui.open[sleutel] = d.open; });
			groepen.appendChild(d);
		});
		var talen = { nl: 'Nederlands', en: 'Engels', pt: 'Braziliaans-Portugees' };
		return el('section', { class: 'sfpb-scherm' }, [
			kop('Teksten', 'Vaste teksten die bezoekers zien. In elk veld staat de standaardtekst in de taal van de site (' + (talen[S.site.taal] || S.site.taal) + '). Typ er alleen iets in als je het anders wilt.'),
			groepen,
			el('div', { class: 'sfpb-rij' }, [
				el('button', { class: 'sfpb-knop sfpb-hoofd', type: 'button', text: 'Opslaan', klik: function () {
					var t = {};
					velden.forEach(function (v) { t[v.dataset.id] = v.value; });
					bewaarInstellingen(this, 'teksten', { teksten: t });
				} }),
				flitsPlek('teksten')
			])
		]);
	}

	/* ---------- Onderhoud ---------- */

	function vouw(sleutel, titel, statusNode, inhoud) {
		var d = el('details', { class: 'sfpb-vouw', open: !!ui.open[sleutel] }, [
			el('summary', null, [titel, statusNode]),
			el('div', { class: 'sfpb-inhoud' }, inhoud)
		]);
		d.addEventListener('toggle', function () { ui.open[sleutel] = d.open; });
		return d;
	}

	function schermOnderhoud() {
		var o = S.onderhoud;

		// Versie en updates.
		var kanaal = el('select', null, [el('option', { value: '', text: 'Gewone versies' }), el('option', { value: 'proef', text: 'Ook proefversies' })]);
		kanaal.value = o.kanaal;
		var updateUit = el('p', { hidden: !ui.update, text: ui.update || '' });
		var versie = vouw('versie', 'Versie en updates', status(true, 'Versie ' + S.versie), [
			veld('Deze site krijgt', kanaal, 'Proefversies zijn bedoeld voor één site tegelijk.', 'sfpb-oh-kanaal'),
			el('div', { class: 'sfpb-rij' }, [
				el('button', { class: 'sfpb-knop sfpb-hoofd', type: 'button', text: 'Opslaan', klik: function () { bewaarInstellingen(this, 'oh-kanaal', { update_kanaal: kanaal.value }); } }),
				flitsPlek('oh-kanaal')
			]),
			el('div', { class: 'sfpb-rij' }, [
				el('button', { class: 'sfpb-knop', type: 'button', text: 'Kijk nu of er een nieuwe versie is', klik: function () {
					var b = this; b.disabled = true;
					wp.apiFetch({ path: '/sfp/v1/updater/ververs', method: 'POST' }).then(function (a) {
						b.disabled = false;
						ui.update = a.aangeboden ? 'Versie ' + a.aangeboden + ' staat klaar. Werk bij op het scherm Plugins.' : 'Je hebt de nieuwste versie (' + a.geinstalleerd + ').';
						updateUit.textContent = ui.update; updateUit.hidden = false;
					}, function (e) { b.disabled = false; updateUit.textContent = foutTekst(e); updateUit.hidden = false; });
				} })
			]),
			updateUit
		]);

		// Resten van oude plugins.
		var teveel = o.autoload_kb > 800;
		var resten = vouw('resten', 'Resten van oude plugins', status(!teveel, teveel ? 'Vraagt aandacht' : 'In orde'), [
			el('p', { text: 'Instellingen van plugins die niet meer actief zijn, maken elke pagina iets trager. Nu ' + o.autoload_kb + ' KB; boven 800 KB wordt het een probleem.' }),
			o.autoload_url ? el('a', { class: 'sfpb-knop', style: 'align-self:flex-start', href: o.autoload_url, text: 'Bekijk en ruim op' }) : null
		]);

		// Boekformulier sneller.
		var lean = { aan: o.lean_aan };
		var vinkjes = el('div', { class: 'sfpb-stapel sfpb-krap' });
		o.plugins.forEach(function (p) {
			var c = el('input', { type: 'checkbox', checked: p.over, value: p.map });
			vinkjes.appendChild(el('label', { class: 'sfpb-vink' }, [c, p.naam]));
		});
		var sneller = vouw('lean', 'Boekformulier sneller', status(o.lean_aan ? true : null, o.lean_aan ? 'Aan' : 'Uit'), [
			el('p', { text: 'Bij een boeking slaat de site de plugins over die het boeken niet nodig heeft. Vink aan wat overgeslagen mag worden.' }),
			schakel(o.lean_aan, 'Sla de aangevinkte plugins over bij een boeking', function (nu) { lean.aan = nu; }),
			vinkjes,
			o.lean_fout ? el('div', { class: 'sfpb-let' }, [el('span', { text: o.lean_fout })]) : null,
			el('p', { class: 'sfpb-hulp', text: 'Test na een wijziging één boeking van begin tot eind.' }),
			el('div', { class: 'sfpb-rij' }, [
				el('button', { class: 'sfpb-knop sfpb-hoofd', type: 'button', text: 'Opslaan', klik: function () {
					var over = Array.prototype.filter.call(vinkjes.querySelectorAll('input'), function (c) { return c.checked; }).map(function (c) { return c.value; });
					bewaarInstellingen(this, 'oh-lean', { ajax_lean_enabled: lean.aan, ajax_lean_skip: over });
				} }),
				flitsPlek('oh-lean')
			])
		]);

		// Beelden om te delen.
		var d = o.deelbeeld;
		var kan = d && d.gd && d.freetype && d.jpeg && d.kleurrollen && d.werkmap;
		var beelden = d ? vouw('beelden', 'Beelden om te delen', status(kan ? true : false, kan ? 'Werkt' : 'Werkt niet'), [
			el('p', { text: kan ? 'De site maakt zelf een beeld van de blokken Inzicht en Samenvatting.' : 'De site kan nu geen beelden maken; de blokken tonen dezelfde kaart als tekst.' }),
			d.laatste_fout ? el('p', { class: 'sfpb-hulp', text: 'Laatste melding: ' + d.laatste_fout }) : null
		]) : null;

		// Koppeling met Google.
		var st = S.reviews.stand;
		var sleutel = tekstInvoer('', S.reviews.opties.sleutel ? 'Laat leeg om de huidige sleutel te houden' : 'Plak de sleutel', 'password');
		sleutel.setAttribute('autocomplete', 'off');
		var plek = tekstInvoer(S.reviews.opties.place_id, 'Begint met ChIJ');
		var google = vouw('google', 'Koppeling met Google voor reviews', status(st.verbonden ? true : (st.ingesteld ? null : false), st.verbonden ? 'Verbonden' : (st.ingesteld ? 'Ingesteld' : 'Niet ingesteld')), [
			el('p', { text: 'Eenmalig in te stellen. De sleutel wordt hier nooit getoond.' }),
			veld('Sleutel van Google', sleutel, 'De API-sleutel van Google Places.', 'sfpb-oh-sleutel'),
			veld('Code van het bedrijf bij Google', plek, 'De Place ID van het bedrijfsprofiel.', 'sfpb-oh-plek'),
			el('div', { class: 'sfpb-rij' }, [
				el('button', { class: 'sfpb-knop sfpb-hoofd', type: 'button', text: 'Opslaan', klik: function () { doe(this, 'oh-google', api('/reviews', 'POST', { api_key: sleutel.value, place_id: plek.value })); } }),
				flitsPlek('oh-google')
			])
		]);

		return el('section', { class: 'sfpb-scherm' }, [
			kop('Onderhoud', 'Voor onderhoud aan de site. Om de site te beheren hoef je hier niets te doen.'),
			el('div', null, [versie, resten, sneller, beelden, google])
		]);
	}

	/* =====================================================================
	 * 4. Start
	 * =================================================================== */

	function teken() {
		var scherm;
		if (ui.scherm === 'paginas') scherm = schermPaginas();
		else if (ui.scherm === 'teksten') scherm = schermTeksten();
		else if (ui.scherm === 'onderhoud') scherm = schermOnderhoud();
		else if (ui.scherm === 'cursusdata') scherm = schermCursusdata();
		else if (ui.scherm === 'onderdelen') {
			var details = { knop: detailKnop, artikel: detailArtikel, reviews: detailReviews, tooltips: detailTooltips, whatsapp: detailWhatsapp, footer: detailFooter, zelftest: detailZelftest, tools: detailTools, kleurrollen: detailKleurrollen };
			scherm = Object.prototype.hasOwnProperty.call(details, ui.detail) ? details[ui.detail]() : schermOnderdelen();
		} else scherm = schermOverzicht();

		if (ui.detail !== 'footer') ui.footer = null;

		var y = window.pageYOffset;
		wortel.innerHTML = '';
		wortel.appendChild(kopbalk());
		wortel.appendChild(el('main', { class: 'sfpb-main' }, [scherm]));
		window.scrollTo(0, y);
	}

	window.addEventListener('popstate', function () { leesHash(); ui.lade = 0; teken(); });
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && ui.lade) { ui.lade = 0; teken(); } });

	leesHash();
	teken();
})();
