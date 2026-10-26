<?php
/**
 * Reviews ophalen bij Google, een dag bewaard.
 *
 * Overgenomen uit SFP Google Reviews 2.1.9. De naam van de class, de
 * constructor, get() en de naam van de transient zijn een afspraak: het
 * snippet "LatePoint: boekkaart in merkrollen" en de code [sfp_reviews]
 * lezen ze.
 *
 * Anders dan in 2.1.9: de taal waarin Google de reviews teruggeeft volgt de
 * taal van de site (was altijd Nederlands).
 *
 * @package SFP_Page_Config
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

class SFP_Reviews_Fetcher {

	private string $api_key;
	private int    $ttl;

	public function __construct( string $api_key, int $ttl = DAY_IN_SECONDS ) {
		$this->api_key = $api_key;
		$this->ttl     = $ttl;
	}

	/**
	 * Haal reviews en het cijfer op voor een Place ID, bewaard in een transient.
	 *
	 * @return array{reviews: array, meta: array}|WP_Error
	 */
	public function get( string $place_id ): array|WP_Error {
		$cache_key = 'sfp_reviews_' . md5( $place_id );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		// Na een mislukte aanvraag tien minuten niet opnieuw proberen: anders
		// wacht elke pagina met reviews op een antwoord dat niet komt.
		$fout = get_transient( $cache_key . '_fout' );
		if ( is_string( $fout ) && '' !== $fout ) {
			return new WP_Error( 'sfp_reviews_geen_gegevens', $fout );
		}

		$talen = array(
			'nl' => 'nl',
			'en' => 'en',
			'pt' => 'pt-BR',
		);
		$taal  = function_exists( 'sfp_page_config_taal' ) ? sfp_page_config_taal() : 'nl';

		$url = add_query_arg(
			array(
				'place_id' => rawurlencode( $place_id ),
				'fields'   => 'name,rating,user_ratings_total,reviews',
				'key'      => $this->api_key,
				'language' => isset( $talen[ $taal ] ) ? $talen[ $taal ] : 'en',
			),
			'https://maps.googleapis.com/maps/api/place/details/json'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) ) {
			set_transient( $cache_key . '_fout', $response->get_error_message(), 10 * MINUTE_IN_SECONDS );
			return $response;
		}

		$body   = json_decode( wp_remote_retrieve_body( $response ), true );
		$result = is_array( $body ) && isset( $body['result'] ) && is_array( $body['result'] ) ? $body['result'] : array();

		// Een antwoord zonder gegevens (verkeerde sleutel, storing bij Google)
		// wordt niet een dag bewaard: de volgende aanvraag probeert het opnieuw.
		if ( ! $result ) {
			$status = is_array( $body ) && isset( $body['status'] ) ? (string) $body['status'] : 'geen antwoord';
			$melding = 'Google gaf geen gegevens terug (' . $status . ').';
			set_transient( $cache_key . '_fout', $melding, 10 * MINUTE_IN_SECONDS );
			return new WP_Error( 'sfp_reviews_geen_gegevens', $melding );
		}

		$meta = array(
			'rating' => (float) ( $result['rating'] ?? 0 ),
			'total'  => (int) ( $result['user_ratings_total'] ?? 0 ),
		);

		$reviews = array();
		foreach ( $result['reviews'] ?? array() as $r ) {
			$reviews[] = array(
				'author' => $r['author_name'] ?? '',
				'photo'  => function_exists( 'sfp_page_config_reviews_foto' ) ? sfp_page_config_reviews_foto( $r['profile_photo_url'] ?? '' ) : ( $r['profile_photo_url'] ?? '' ),
				'rating' => (int) ( $r['rating'] ?? 0 ),
				'text'   => $r['text'] ?? '',
				'time'   => $r['relative_time_description'] ?? '',
			);
		}

		$data = array(
			'reviews' => $reviews,
			'meta'    => $meta,
		);
		set_transient( $cache_key, $data, $this->ttl );
		delete_transient( $cache_key . '_fout' );

		return $data;
	}

	/** Verwijder de bewaarde gegevens van een Place ID. */
	public function flush( string $place_id ): void {
		delete_transient( 'sfp_reviews_' . md5( $place_id ) );
		delete_transient( 'sfp_reviews_' . md5( $place_id ) . '_fout' );
	}
}
