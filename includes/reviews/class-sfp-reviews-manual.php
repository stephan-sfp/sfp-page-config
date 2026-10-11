<?php
/**
 * Eigen reviews, bewaard als JSON in de optie `sfp_reviews_manual`.
 *
 * Overgenomen uit SFP Google Reviews 2.1.9; de optie en de vorm van een
 * review zijn gelijk gebleven. Nieuw in 3.0.0: een review heeft een datum
 * (`datum`, Y-m-d) in plaats van een vrije tekst als "6 maanden geleden", en
 * is te bewerken. Een review van voor 3.0.0 houdt zijn tekst in `time` tot
 * hij een datum krijgt.
 *
 * @package SFP_Page_Config
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

class SFP_Reviews_Manual {

	const OPTION = 'sfp_reviews_manual';

	/** Alle eigen reviews, in de bewaarde volgorde. */
	public static function get_all(): array {
		$raw  = get_option( self::OPTION, '[]' );
		$data = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
		return is_array( $data ) ? array_values( $data ) : array();
	}

	/** Bewaar de volledige lijst. */
	public static function save_all( array $reviews ): void {
		update_option( self::OPTION, wp_json_encode( array_values( $reviews ), JSON_UNESCAPED_UNICODE ) );
	}

	/**
	 * Schoon één review op.
	 *
	 * @param array $review Ruwe review.
	 * @return array
	 */
	public static function schoon( array $review ): array {
		$datum = isset( $review['datum'] ) ? sanitize_text_field( (string) $review['datum'] ) : '';
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $datum ) || ! strtotime( $datum ) ) {
			$datum = '';
		}
		return array(
			'id'     => isset( $review['id'] ) && '' !== $review['id'] ? sanitize_text_field( (string) $review['id'] ) : uniqid( 'mr_', true ),
			'author' => sanitize_text_field( (string) ( $review['author'] ?? '' ) ),
			'rating' => min( 5, max( 1, (int) ( $review['rating'] ?? 5 ) ) ),
			'time'   => sanitize_text_field( (string) ( $review['time'] ?? '' ) ),
			'datum'  => $datum,
			'text'   => sanitize_textarea_field( (string) ( $review['text'] ?? '' ) ),
			'photo'  => '',
		);
	}

	/** Voeg een review toe en geef de nieuwe lijst terug. */
	public static function add( array $review ): array {
		$reviews      = self::get_all();
		$review['id'] = '';
		$reviews[]    = self::schoon( $review );
		self::save_all( $reviews );
		return $reviews;
	}

	/** Werk een review bij op ID. Geeft false als hij niet bestaat. */
	public static function update( string $id, array $review ): bool {
		$reviews = self::get_all();
		foreach ( $reviews as $i => $bestaand ) {
			if ( ( $bestaand['id'] ?? '' ) === $id ) {
				$review['id']  = $id;
				$reviews[ $i ] = self::schoon( $review );
				self::save_all( $reviews );
				return true;
			}
		}
		return false;
	}

	/** Verwijder een review op ID. */
	public static function delete( string $id ): void {
		$reviews = array_filter( self::get_all(), fn( $r ) => ( $r['id'] ?? '' ) !== $id );
		self::save_all( array_values( $reviews ) );
	}

	/**
	 * Eigen reviews vanaf een aantal sterren, in dezelfde vorm als de reviews
	 * van Google. Een datum wordt hier de ouderdom in de taal van de site.
	 */
	public static function get_filtered( int $min_stars = 4 ): array {
		$uit = array();
		foreach ( self::get_all() as $r ) {
			if ( (int) ( $r['rating'] ?? 0 ) < $min_stars ) {
				continue;
			}
			if ( ! empty( $r['datum'] ) && strtotime( $r['datum'] ) ) {
				$verschil = human_time_diff( strtotime( $r['datum'] . ' 12:00:00' ), time() );
				$r['time'] = function_exists( 'sfp_page_config_tekst_f' ) ? sfp_page_config_tekst_f( 'reviews_geleden', $verschil ) : $verschil;
			}
			$uit[] = $r;
		}
		return $uit;
	}
}
