/* Reviews: schuiven, lees verder. Overgenomen uit SFP Google Reviews 2.1.9.
   Vanilla JS, IIFE, geen arrow functions */

(function () {
	'use strict';

	var DESKTOP_BREAKPOINT = 768;

	function getCols(wrapper) {
		var isMobile = window.innerWidth < DESKTOP_BREAKPOINT;
		return isMobile
			? parseInt(wrapper.getAttribute('data-cols-mobile'),  10) || 1
			: parseInt(wrapper.getAttribute('data-cols-desktop'), 10) || 1;
	}

	// "Lees verder" alleen bij een tekst die echt is ingekort.
	function toonLeesVerder() {
		var btns = document.querySelectorAll('.sfp-review__more');
		for (var i = 0; i < btns.length; i++) {
			var card = btns[i].closest('.sfp-review');
			var tekst = card ? card.querySelector('.sfp-review__text') : null;
			if (!tekst || card.classList.contains('sfp-review--expanded')) { continue; }
			if (!tekst.clientHeight) { continue; } // nog niet in beeld: niets beslissen
			btns[i].hidden = tekst.scrollHeight <= tekst.clientHeight + 1;
		}
	}

	function initReadMore() {
		var btns = document.querySelectorAll('.sfp-review__more');
		toonLeesVerder();
		window.addEventListener('resize', toonLeesVerder);
		if (document.fonts && document.fonts.ready) { document.fonts.ready.then(toonLeesVerder); }
		for (var i = 0; i < btns.length; i++) {
			btns[i].addEventListener('click', function () {
				var card     = this.closest('.sfp-review');
				var expanded = card.classList.toggle('sfp-review--expanded');
				var teksten = window.sfpReviewsTeksten || {};
				this.textContent = expanded ? (teksten.minder || 'Minder tonen') : (teksten.meer || 'Lees verder');
				this.setAttribute('aria-expanded', expanded ? 'true' : 'false');
			});
		}
	}

	function initSlider(wrapper) {
		var isBanner = wrapper.classList.contains('sfp-reviews--banner-slider');
		var track    = wrapper.querySelector('.sfp-reviews__track');
		var slides   = isBanner
			? wrapper.querySelectorAll('.sfp-banner__item')
			: wrapper.querySelectorAll('.sfp-review');
		var btnPrev  = wrapper.querySelector('.sfp-reviews__btn--prev');
		var btnNext  = wrapper.querySelector('.sfp-reviews__btn--next');
		var dots     = wrapper.querySelectorAll('.sfp-reviews__dot');
		var total    = slides.length;
		var current  = 0;
		var autoPlay = wrapper.getAttribute('data-auto') !== 'false';
		var interval = parseInt(wrapper.getAttribute('data-interval'), 10) || 5000;
		var timer    = null;
		var cols     = 1;

		function updateCols() {
			cols = getCols(wrapper);
			// Stel CSS-variabele in zodat kaartbreedte klopt
			wrapper.style.setProperty('--sfp-slider-cols', cols);
			// Zorg dat current niet buiten bereik valt
			var maxIndex = Math.max(0, total - cols);
			if (current > maxIndex) { current = maxIndex; }
			updateTrack();
			updateDots();
		}

		var slideWidth = 0;
		var gapWidth   = 0;

		function calcStep() {
			var viewport = wrapper.querySelector('.sfp-reviews__viewport');
			if ( !viewport ) return;
			var vw  = viewport.getBoundingClientRect().width;
			var gap = parseFloat( getComputedStyle( track ).gap ) || 0;
			gapWidth   = gap;
			slideWidth = ( vw - gap * ( cols - 1 ) ) / cols;
			// Zet pixelbreedte direct op de wrapper zodat CSS calc wegvalt
			wrapper.style.setProperty( '--sfp-slide-width', slideWidth + 'px' );
		}

		function measureSlide() { calcStep(); }

		function updateTrack() {
			if ( !slideWidth ) calcStep();
			track.style.transform = 'translateX(-' + ( ( slideWidth + gapWidth ) * current ) + 'px)';
		}

		function updateDots() {
			var maxIndex = Math.max(0, total - cols);
			for (var i = 0; i < dots.length; i++) {
				dots[i].classList.toggle(
					'sfp-reviews__dot--active',
					i === Math.min(current, maxIndex)
				);
			}
		}

		function goTo(index) {
			var maxIndex = Math.max(0, total - cols);
			// Infinite wrap
			if (index > maxIndex) { index = 0; }
			if (index < 0)        { index = maxIndex; }
			current = index;
			updateTrack();
			updateDots();
		}

		function next() { goTo(current + 1); }
		function prev() { goTo(current - 1); }

		function startAuto() {
			if (!autoPlay) return;
			// Nooit twee timers tegelijk: anders schuift de rij dubbel zo snel.
			if (timer) { clearInterval(timer); timer = null; }
			timer = setInterval(function () {
				var maxIndex = Math.max(0, total - cols);
				goTo(current >= maxIndex ? 0 : current + 1);
			}, interval);
		}

		function stopAuto()  { if (timer) { clearInterval(timer); timer = null; } }
		function resetAuto() { stopAuto(); startAuto(); }

		btnNext.addEventListener('click', function () { next(); resetAuto(); });
		btnPrev.addEventListener('click', function () { prev(); resetAuto(); });

		for (var d = 0; d < dots.length; d++) {
			(function (index) {
				dots[index].addEventListener('click', function () {
					goTo(index); resetAuto();
				});
			}(d));
		}

		wrapper.addEventListener('mouseenter', stopAuto);
		wrapper.addEventListener('mouseleave', startAuto);

		var touchStartX = 0;
		wrapper.addEventListener('touchstart', function (e) {
			touchStartX = e.changedTouches[0].clientX;
		}, { passive: true });

		wrapper.addEventListener('touchend', function (e) {
			var diff = touchStartX - e.changedTouches[0].clientX;
			if (Math.abs(diff) > 40) {
				if (diff > 0) { next(); } else { prev(); }
				resetAuto();
			}
		}, { passive: true });

		// Dubbel rAF: eerste frame zet de CSS-variabele,
		// tweede frame meet nadat de browser heeft herlayout
		requestAnimationFrame( function () {
			updateCols();
			requestAnimationFrame( function () {
				measureSlide();
				if ( total > cols ) { startAuto(); }
			} );
		} );

		var resizeTimer = null;
		window.addEventListener('resize', function () {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function () {
				stopAuto();
				updateCols();
				measureSlide();
				updateTrack();
				startAuto();
			}, 150);
		});

		if (total > 1) { /* gestart via rAF */ }
	}

	function init() {
		initReadMore();
		var sliders = document.querySelectorAll('.sfp-reviews--slider, .sfp-reviews--banner-slider');
		for (var i = 0; i < sliders.length; i++) {
			initSlider(sliders[i]);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

}());
