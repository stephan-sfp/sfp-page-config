<?php
/**
 * De HTML van het reviewblok.
 *
 * Overgenomen uit SFP Google Reviews 2.1.9; de opbouw en de classes zijn
 * gelijk gebleven. Anders: de vaste teksten ("Uitstekend", "Op basis van",
 * "Lees verder", de labels voor schermlezers) volgen de taal van de site
 * (includes/teksten.php).
 *
 * @package SFP_Page_Config
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

class SFP_Reviews_Renderer {

	/**
	 * Een tekst in de taal van de site.
	 *
	 * @param string $sleutel Sleutel in includes/teksten.php.
	 * @param mixed  ...$args Waarden voor de invultekens.
	 */
	private function t( string $sleutel, ...$args ): string {
		return $args ? sfp_page_config_tekst_f( $sleutel, ...$args ) : sfp_page_config_tekst( $sleutel );
	}

	/**
	 * Een tekst die veilig in een sprintf-patroon en in een attribuut past.
	 *
	 * @param string $sleutel Sleutel in includes/teksten.php.
	 */
	private function fmt( string $sleutel ): string {
		return str_replace( '%', '%%', esc_attr( $this->t( $sleutel ) ) );
	}

	/**
	 * Het oordeel bij een gemiddeld cijfer.
	 */
	private function oordeel( float $rating ): string {
		if ( $rating >= 4.5 ) {
			return $this->t( 'reviews_uitstekend' );
		}
		if ( $rating >= 4.0 ) {
			return $this->t( 'reviews_zeer_goed' );
		}
		if ( $rating >= 3.5 ) {
			return $this->t( 'reviews_goed' );
		}
		if ( $rating >= 3.0 ) {
			return $this->t( 'reviews_gemiddeld' );
		}
		return $this->t( 'reviews_matig' );
	}

	/**
	 * Alleen het cijfer met het oordeel en het aantal reviews (weergave
	 * "cijfer", nieuw in 3.0.0).
	 */
	public function samenvatting( array $meta ): string {
		return $this->summary( $meta );
	}

	/**
	 * Het oordeel en de regel "Op basis van n reviews" als tekst, voor het
	 * voorbeeld in het beheer.
	 *
	 * @return array{oordeel: string, basis: string}
	 */
	public function samenvatting_teksten( array $meta ): array {
		$total = (int) ( $meta['total'] ?? 0 );
		return array(
			'oordeel' => $this->oordeel( (float) ( $meta['rating'] ?? 0 ) ),
			'basis'   => $this->t( 1 === $total ? 'reviews_basis_een' : 'reviews_basis', number_format_i18n( $total ) ),
		);
	}

	public function render( array $reviews, array $atts, array $meta = [], bool $show_summary = false ): string {
		$layout  = sanitize_key( $atts['layout'] );
		$summary = $show_summary ? $this->summary( $meta ) : '';

		if ( $layout === 'banner' ) {
			return $summary . $this->banner_slider( $reviews, $atts );
		}

		$widget = $layout === 'grid'
			? $this->grid( $reviews )
			: $this->slider( $reviews, $atts );

		return $summary . $widget;
	}

	// ─────────────────────────────────────────────
	// SAMENVATTING
	// ─────────────────────────────────────────────

	private function summary( array $meta ): string {
		$rating = $meta['rating'] ?? 0;
		$total  = $meta['total']  ?? 0;

		if ( ! $rating ) return '';

		$label = $this->oordeel( (float) $rating );

		$stars = $this->stars( (int) round( $rating ) );

		return sprintf(
			'<div class="sfp-reviews-summary">
				<span class="sfp-reviews-summary__label">%s</span>
				<div class="sfp-reviews-summary__stars">%s</div>
				<span class="sfp-reviews-summary__total">%s</span>
			</div>',
			esc_html( $label ),
			$stars,
			esc_html( $this->t( 1 === (int) $total ? 'reviews_basis_een' : 'reviews_basis', number_format_i18n( $total ) ) )
		);
	}

	// ─────────────────────────────────────────────
	// SLIDER
	// ─────────────────────────────────────────────

	private function slider( array $reviews, array $atts ): string {
		$auto         = filter_var( $atts['auto'], FILTER_VALIDATE_BOOLEAN );
		$interval     = max( 2000, (int) $atts['interval'] );
		$cols_mobile  = max( 1, (int) ( $atts['cols_mobile']  ?? 1 ) );
		$cols_desktop = max( 1, (int) ( $atts['cols_desktop'] ?? 1 ) );
		$count        = count( $reviews );

		$slides = '';
		foreach ( $reviews as $r ) {
			$slides .= $this->card( $r );
		}

		// Aantal dots = aantal navigeerbare posities (rekening houdend met max cols)
		$max_cols = max( $cols_mobile, $cols_desktop );
		$dot_count = max( 1, $count - $max_cols + 1 );
		$dots = '';
		for ( $i = 0; $i < $dot_count; $i++ ) {
			$active = $i === 0 ? ' sfp-reviews__dot--active' : '';
			$dots  .= '<button class="sfp-reviews__dot' . $active . '" aria-label="Review ' . ( $i + 1 ) . '" data-index="' . $i . '"></button>';
		}

		return sprintf(
			'<div class="sfp-reviews sfp-reviews--slider" role="region" aria-label="Reviews" data-auto="%s" data-interval="%d" data-cols-mobile="%d" data-cols-desktop="%d">
				<div class="sfp-reviews__stage">
					<button class="sfp-reviews__btn sfp-reviews__btn--prev" aria-label="' . $this->fmt( 'reviews_vorige' ) . '">&#8250;</button>
					<div class="sfp-reviews__viewport">
						<div class="sfp-reviews__track">%s</div>
					</div>
					<button class="sfp-reviews__btn sfp-reviews__btn--next" aria-label="' . $this->fmt( 'reviews_volgende' ) . '">&#8250;</button>
				</div>
				<div class="sfp-reviews__dots">%s</div>
			</div>',
			$auto ? 'true' : 'false',
			$interval,
			$cols_mobile,
			$cols_desktop,
			$slides,
			$dots
		);
	}

	// ─────────────────────────────────────────────
	// BANNER SLIDER
	// ─────────────────────────────────────────────

	private function banner_slider( array $reviews, array $atts ): string {
		$auto         = filter_var( $atts['auto'], FILTER_VALIDATE_BOOLEAN );
		$interval     = max( 2000, (int) $atts['interval'] );
		$cols_mobile  = max( 1, (int) ( $atts['cols_mobile']  ?? 2 ) );
		$cols_desktop = max( 1, (int) ( $atts['cols_desktop'] ?? 4 ) );
		$count        = count( $reviews );

		$slides = '';
		foreach ( $reviews as $r ) {
			$photo = ! empty( $r['photo'] )
				? sprintf(
					'<img class="sfp-banner__photo" src="%s" alt="%s" width="40" height="40" loading="lazy">',
					esc_url( function_exists( 'sfp_page_config_reviews_foto' ) ? sfp_page_config_reviews_foto( $r['photo'] ) : $r['photo'] ),
					esc_attr( $r['author'] )
				)
				: '<span class="sfp-banner__photo sfp-banner__photo--fallback" aria-hidden="true">'
					. esc_html( mb_substr( $r['author'], 0, 1 ) )
					. '</span>';

			$stars   = $this->stars( $r['rating'] );
			$slides .= sprintf(
				'<div class="sfp-banner__item">
					%s
					<div class="sfp-banner__meta">
						<span class="sfp-banner__author">%s</span>
						<div class="sfp-banner__stars" aria-label="%s">%s</div>
					</div>
				</div>',
				$photo,
				esc_html( $r['author'] ),
				esc_attr( $this->t( 'reviews_sterren', (int) $r['rating'] ) ),
				$stars
			);
		}

		$max_cols  = max( $cols_mobile, $cols_desktop );
		$dot_count = max( 1, $count - $max_cols + 1 );
		$dots      = '';
		for ( $i = 0; $i < $dot_count; $i++ ) {
			$active = $i === 0 ? ' sfp-reviews__dot--active' : '';
			$dots  .= '<button class="sfp-reviews__dot' . $active . '" aria-label="Review ' . ( $i + 1 ) . '" data-index="' . $i . '"></button>';
		}

		return sprintf(
			'<div class="sfp-reviews sfp-reviews--banner-slider" role="region" aria-label="Reviews" data-auto="%s" data-interval="%d" data-cols-mobile="%d" data-cols-desktop="%d">
				<div class="sfp-reviews__stage">
					<button class="sfp-reviews__btn sfp-reviews__btn--prev" aria-label="' . $this->fmt( 'reviews_vorige' ) . '">&#8250;</button>
					<div class="sfp-reviews__viewport">
						<div class="sfp-reviews__track">%s</div>
					</div>
					<button class="sfp-reviews__btn sfp-reviews__btn--next" aria-label="' . $this->fmt( 'reviews_volgende' ) . '">&#8250;</button>
				</div>
			</div>',
			$auto ? 'true' : 'false',
			$interval,
			$cols_mobile,
			$cols_desktop,
			$slides
		);
	}

	// ─────────────────────────────────────────────
	// BANNER (statisch, legacy)
	// ─────────────────────────────────────────────

	private function banner( array $reviews ): string {
		$items = '';
		foreach ( $reviews as $r ) {
			$photo = ! empty( $r['photo'] )
				? sprintf(
					'<img class="sfp-banner__photo" src="%s" alt="%s" width="36" height="36" loading="lazy">',
					esc_url( function_exists( 'sfp_page_config_reviews_foto' ) ? sfp_page_config_reviews_foto( $r['photo'] ) : $r['photo'] ),
					esc_attr( $r['author'] )
				)
				: '<span class="sfp-banner__photo sfp-banner__photo--fallback" aria-hidden="true">'
					. esc_html( mb_substr( $r['author'], 0, 1 ) )
					. '</span>';

			$stars = $this->stars( $r['rating'] );

			$items .= sprintf(
				'<div class="sfp-banner__item">
					%s
					<div class="sfp-banner__meta">
						<span class="sfp-banner__author">%s</span>
						<div class="sfp-banner__stars" aria-label="%s">%s</div>
					</div>
				</div>',
				$photo,
				esc_html( $r['author'] ),
				esc_attr( $this->t( 'reviews_sterren', (int) $r['rating'] ) ),
				$stars
			);
		}

		return sprintf(
			'<div class="sfp-reviews sfp-reviews--banner" role="region" aria-label="Reviews">%s</div>',
			$items
		);
	}

	// ─────────────────────────────────────────────
	// GRID
	// ─────────────────────────────────────────────

	private function grid( array $reviews ): string {
		$cards = implode( '', array_map( [ $this, 'card' ], $reviews ) );
		return sprintf(
			'<div class="sfp-reviews sfp-reviews--grid" role="region" aria-label="Reviews">%s</div>',
			$cards
		);
	}

	// ─────────────────────────────────────────────
	// REVIEW CARD
	// ─────────────────────────────────────────────

	private function card( array $r ): string {
		$stars = $this->stars( $r['rating'] );
		$photo = ! empty( $r['photo'] )
			? sprintf(
				'<img class="sfp-review__photo" src="%s" alt="%s" width="40" height="40" loading="lazy">',
				esc_url( function_exists( 'sfp_page_config_reviews_foto' ) ? sfp_page_config_reviews_foto( $r['photo'] ) : $r['photo'] ),
				esc_attr( $r['author'] )
			)
			: '<span class="sfp-review__photo sfp-review__photo--fallback" aria-hidden="true">'
				. esc_html( mb_substr( $r['author'], 0, 1 ) )
				. '</span>';

		return sprintf(
			'<div class="sfp-review">
				<div class="sfp-review__header">
					%s
					<div class="sfp-review__meta">
						<span class="sfp-review__author">%s</span>
						<span class="sfp-review__time">%s</span>
					</div>
				</div>
				<div class="sfp-review__stars" aria-label="%s">%s</div>
				<div class="sfp-review__body">
					<p class="sfp-review__text">%s</p>
					<button class="sfp-review__more" aria-expanded="false">%s</button>
				</div>%s
			</div>',
			$photo,
			esc_html( $r['author'] ),
			esc_html( $r['time'] ),
			esc_attr( $this->t( 'reviews_sterren', (int) $r['rating'] ) ),
			$stars,
			esc_html( $r['text'] ),
			esc_html( $this->t( 'reviews_lees_verder' ) ),
			// Sinds 3.14.0: de vermelding van een eigen review, onder de tekst.
			! empty( $r['vermelding'] ) ? '<p class="sfp-review__vermelding">' . esc_html( $r['vermelding'] ) . '</p>' : ''
		);
	}

	// ─────────────────────────────────────────────
	// STERREN SVG
	// ─────────────────────────────────────────────

	private function stars( int $rating ): string {
		$out = '';
		for ( $i = 1; $i <= 5; $i++ ) {
			$filled = $i <= $rating ? 'sfp-review__star--filled' : 'sfp-review__star--empty';
			$out   .= '<svg class="sfp-review__star ' . $filled . '" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">'
					. '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>'
					. '</svg>';
		}
		return $out;
	}
}
