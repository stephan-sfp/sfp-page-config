/**
 * SFP Page Config - Incompanycalculator: de rekenregel (sinds 3.36.0).
 *
 * Eén functie, reken(keuzes, norm), voor het scherm en de pdf. De server
 * rekent met includes/calculator-reken.php opnieuw; tests/calculator-reken.php
 * vergelijkt beide op dezelfde scenario's. Wijzig ze altijd samen.
 *
 * Bedragen in hele euro's (de boekprijs in centen in de norm). Geen teksten:
 * de uitkomst bevat codes, de teksten staan in includes/teksten.php.
 */
(function (wortel, maak) {
	if (typeof module === 'object' && module.exports) {
		module.exports = maak();
	} else {
		wortel.SFPCalculatorMotor = maak();
	}
}(typeof self !== 'undefined' ? self : this, function () {
	'use strict';

	function geheel(w, min, max) {
		var n = parseInt(w, 10);
		if (isNaN(n)) n = 0;
		if (n < min) n = min;
		if (n > max) n = max;
		return n;
	}

	/** Verdeel n deelnemers zo gelijk mogelijk over groepen (30 = 8, 8, 7, 7). */
	function verdeel(n, norm) {
		if (n <= 0) return [];
		var groot = norm.groep_norm;
		var ruimte = norm.groep_ruimte;
		if (n <= ruimte) return [n];
		var g = Math.ceil(n / groot);
		var basis = Math.floor(n / g);
		var rest = n % g;
		var uit = [];
		for (var i = 0; i < g; i++) uit.push(basis + (i < rest ? 1 : 0));
		return uit;
	}

	/** Euro's van aantal x prijs in centen, afgerond op hele euro's. */
	function euros(aantal, centen) {
		return Math.round((aantal * centen) / 100);
	}

	function regel(code, aantal, prijs, btw, gratis) {
		return {
			code: code,
			aantal: aantal,
			prijs: prijs,
			bedrag: gratis ? 0 : euros(aantal, prijs),
			btw: btw,
			gratis: !!gratis
		};
	}

	/**
	 * Uren per deelnemer voor een deel van de groep: { deelnemers, uren }.
	 * Uren in blokken van norm.blok_uren (4); deelnemers hoogstens de groep.
	 */
	function persoonUren(w, pp, norm) {
		if (!w || typeof w !== 'object') return null;
		var uren = geheel(w.uren, 0, 200);
		var blokken = Math.ceil(uren / norm.blok_uren);
		var n = geheel(w.deelnemers, 0, pp);
		if (!blokken || !n) return null;
		return { deelnemers: n, uren: blokken * norm.blok_uren, blokken: blokken };
	}

	/** De berekening voor één aantal deelnemers. */
	function rekenVoor(k, norm, deelnemers, onbekend) {
		var verdeling = onbekend ? [norm.groep_norm] : verdeel(deelnemers, norm);
		var groepen = verdeling.length;
		var pp = onbekend ? norm.groep_norm : deelnemers;
		var vorm = String(k.vorm);
		var v = norm.vormen[vorm];
		var ex = k.extras || {};
		var aan = norm.extras_aan || {};
		var regels = [];
		var meldingen = [];

		if (!v || groepen < 1) {
			return { groepen: 0, verdeling: [], deelnemers: 0, regels: [], meldingen: [] };
		}

		regels.push(regel('vorm_' + vorm, groepen, v.prijs * 100, norm.btw_hoog, false));

		// Persoonlijke begeleiding tijdens de cursus: keuze bij 2 dagen, inbegrepen bij 3.
		if (v.begeleiding === 'keuze' && k.begeleiding) {
			regels.push(regel('begeleiding', groepen, norm.begeleiding_prijs * 100, norm.btw_hoog, false));
		} else if (v.begeleiding === 'inbegrepen') {
			regels.push(regel('begeleiding', groepen, norm.begeleiding_prijs * 100, norm.btw_hoog, true));
		}

		// Coachuren en learning on the job: een deel van de groep, in uren per deelnemer (blokken van 4).
		var coach = persoonUren(ex.coachuren, pp, norm);
		if (aan.coachuren && coach) {
			regels.push(regel('coachuren', coach.deelnemers * coach.blokken, norm.blok_prijs * 100, norm.btw_hoog, false));
			if (coach.uren >= norm.abonnement_uren) meldingen.push('abonnement');
		}
		// Clinic en boek: een keuze, en vanzelf inbegrepen waar ze gratis zijn.
		var clinicGratis = Number(vorm) >= norm.clinic_gratis_vanaf;
		if (aan.clinic && (ex.clinic || clinicGratis)) {
			regels.push(regel('clinic', pp, norm.clinic_prijs * 100, norm.btw_hoog, clinicGratis));
		}
		var boekGratis = Number(vorm) >= norm.boek_gratis_vanaf;
		if (aan.boek && (ex.boek || boekGratis)) {
			regels.push(regel('boek', pp, norm.boek_centen, norm.btw_laag, boekGratis));
		}
		var acteur = geheel(ex.acteur, 0, Number(vorm) * groepen);
		if (aan.acteur && acteur > 0) {
			regels.push(regel('acteur', acteur, norm.acteur_prijs * 100, norm.btw_hoog, false));
		}
		if (aan.terugkom && (ex.terugkom === 'dagdeel' || ex.terugkom === 'online')) {
			regels.push(regel('terugkom_' + ex.terugkom, groepen, norm.terugkom[ex.terugkom] * 100, norm.btw_hoog, false));
		}
		var lotj = persoonUren(ex.lotj, pp, norm);
		if (aan.lotj && lotj) {
			regels.push(regel('lotj', lotj.deelnemers * lotj.blokken, norm.blok_prijs * 100, norm.btw_hoog, false));
		}
		if (aan.scan && ex.scan === 'individueel') {
			regels.push(regel('scan_individueel', pp, norm.scan_individueel * 100, norm.btw_hoog, false));
		} else if (aan.scan && ex.scan === 'team') {
			regels.push(regel('scan_team', groepen, norm.scan_team * 100, norm.btw_hoog, false));
		}

		// Groepsvoordeel vanaf de 3e groep, alleen bij 2 en 3 dagen.
		var voordeel = 0;
		var extraGroepen = groepen - (norm.voordeel_vanaf_groep - 1);
		if (Number(vorm) >= 2 && extraGroepen > 0) {
			voordeel = extraGroepen * norm.voordeel_bedrag;
			if (k.groepsvoordeel === 'coachuren') {
				regels.push(regel('voordeel_coachuren', extraGroepen, norm.blok_prijs * 100, norm.btw_hoog, true));
			} else {
				var r = regel('voordeel_korting', extraGroepen, -norm.voordeel_bedrag * 100, norm.btw_hoog, false);
				regels.push(r);
			}
		}

		if (!onbekend && deelnemers >= 1 && deelnemers < norm.klein_tot) meldingen.push('klein');
		if (!onbekend && groepen === 1 && deelnemers > norm.groep_norm) meldingen.push('negen_tien');
		if (groepen >= norm.maatwerk_groepen) meldingen.push('maatwerk');

		var excl = 0;
		var perTarief = {};
		regels.forEach(function (x) {
			excl += x.bedrag;
			perTarief[x.btw] = (perTarief[x.btw] || 0) + x.bedrag;
		});
		var btw = {};
		var btwTotaal = 0;
		Object.keys(perTarief).sort().forEach(function (t) {
			btw[t] = Math.round((perTarief[t] * Number(t)) / 100);
			btwTotaal += btw[t];
		});

		return {
			groepen: groepen,
			verdeling: verdeling,
			deelnemers: onbekend ? 0 : deelnemers,
			regels: regels,
			meldingen: meldingen,
			voordeel: voordeel,
			totaal_excl: excl,
			btw: btw,
			totaal_incl: excl + btwTotaal
		};
	}

	/**
	 * De begroting.
	 *
	 * @param {Object} k    Keuzes: route (A training, B evenement), mensen {soort, aantal, van, tot}, vorm, begeleiding,
	 *                      extras (coachuren en lotj als {deelnemers, uren}), groepsvoordeel, sprekers.
	 * @param {Object} norm Norm van de site (includes/calculator-norm.php).
	 * @return {Object}
	 */
	function reken(k, norm) {
		k = k || {};
		var route = k.route === 'B' ? 'B' : 'A';
		var uit = { route: route, maatwerk: false, richtprijs: false, bandbreedte: null };
		if (route === 'B') {
			uit.sprekers = geheel(k.sprekers, 0, 500);
			uit.evenement_prijs = norm.evenement_prijs;
			uit.richtprijs = true;
			uit.maatwerk = true;
			uit.uitkomst = null;
			return uit;
		}

		var m = k.mensen || {};
		var soort = m.soort === 'marge' || m.soort === 'onbekend' ? m.soort : 'getal';
		var max = norm.max_deelnemers;
		if (soort === 'onbekend') {
			uit.mensen = { soort: soort };
			uit.uitkomst = rekenVoor(k, norm, 0, true);
		} else if (soort === 'marge') {
			var van = geheel(m.van, 1, max);
			var tot = geheel(m.tot, 1, max);
			if (van > tot) { var t = van; van = tot; tot = t; }
			var midden = Math.round((van + tot) / 2);
			uit.mensen = { soort: soort, van: van, tot: tot, midden: midden };
			uit.uitkomst = rekenVoor(k, norm, midden, false);
			if (van !== tot) {
				var a = rekenVoor(k, norm, van, false);
				var b = rekenVoor(k, norm, tot, false);
				uit.bandbreedte = { van: a.totaal_excl, tot: b.totaal_excl, groepen_van: a.groepen, groepen_tot: b.groepen };
			}
		} else {
			var n = geheel(m.aantal, 0, max);
			uit.mensen = { soort: soort, aantal: n };
			uit.uitkomst = rekenVoor(k, norm, n, false);
		}
		if (uit.uitkomst && uit.uitkomst.groepen >= norm.maatwerk_groepen) {
			uit.maatwerk = true;
			uit.richtprijs = true;
		}
		return uit;
	}

	return { reken: reken, verdeel: verdeel };
}));
