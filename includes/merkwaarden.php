<?php
/**
 * SFP Page Config - Merkwaarden (sinds 3.23.0)
 *
 * Het blok sfp/merkwaarden en de code [sfp_merkwaarden] tonen de tekst over
 * de merkwaarden voor bezoekers. De tekst staat niet in de plugin maar in de
 * merkbron (repo sfp-brand-source, branch main):
 *
 *   nl  branding.md,    ## Merkwaarden, ### Voor bezoekers
 *   en  merkwaarden.md, ### For visitors
 *   pt  merkwaarden.md, ### Para visitantes
 *
 * De lezer leest op de vaste labels in vet (Titel, Kernzin, Inleiding, Vraag,
 * Grens en hun vertalingen) en op de koppen (#### per waarde, #### In
 * samenhang; de oude kop #### Samen blijft herkend),
 * niet op regelnummers. Besluit Stephan 1-11-2026.
 *
 * Gedrag:
 * - ophalen op de server met wp_remote_get; het resultaat staat 12 uur in een
 *   transient per taal;
 * - de laatst gelukte versie staat apart in een option. Lukt ophalen niet of
 *   is de opbouw niet te herkennen, dan toont het blok die versie en komt er
 *   een melding in het log van dit onderdeel. Is er nog nooit iets gelukt,
 *   dan valt het blok terug op de kopie die met de plugin meekomt
 *   (assets/merkwaarden/terugval.json). Het blok is nooit leeg;
 * - verversen: een knop in SFP Beheer (Onderdelen, Merkwaarden) en de
 *   ability sfp-merkwaarden/ververs. Verversen leest via de GitHub-API, zodat
 *   een wijziging op main meteen zichtbaar is (de raw-adressen hebben een
 *   cache van enkele minuten); lukt dat niet, dan via het raw-adres;
 * - taal: die van de site, te overschrijven met taal="nl|en|pt";
 * - slot: per site en per taal een slotzin en een knop (tekst en pagina of
 *   adres). Zonder slot ziet een beheerder een melding, een bezoeker niets.
 *
 * Kleuren alleen via de variabelen --sfp-b-* (Astra), fonts via Astra.
 *
 * @package SFP_Page_Config
 * @since   3.23.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Transient per taal: sfp_pc_merkwaarden_<taal>. */
const SFP_PC_MW_TRANSIENT = 'sfp_pc_merkwaarden_';

/** De laatst gelukte versie per taal. */
const SFP_PC_MW_LAATST = 'sfp_page_config_merkwaarden_laatst';

/** Meldingen van dit onderdeel (de nieuwste achteraan, hooguit 30). */
const SFP_PC_MW_LOG = 'sfp_page_config_merkwaarden_log';

/* =========================================================================
 * Bron
 * ====================================================================== */

/**
 * Waar de tekst per taal staat. Te wijzigen met het filter
 * sfp_page_config_merkwaarden_bron, bijvoorbeeld voor een test.
 *
 * @return array<string, array{raw: string, api: string, bestand: string, sectie: string, sub: string}>
 */
function sfp_page_config_merkwaarden_bronnen() {
    $repo = 'stephan-sfp/sfp-brand-source';
    $raw  = 'https://raw.githubusercontent.com/' . $repo . '/main/';
    $api  = 'https://api.github.com/repos/' . $repo . '/contents/';
    $bron = array(
        'nl' => array(
            'bestand' => 'branding.md',
            'sectie'  => '## Merkwaarden',
            'sub'     => '### Voor bezoekers',
        ),
        'en' => array(
            'bestand' => 'merkwaarden.md',
            'sectie'  => '',
            'sub'     => '### For visitors',
        ),
        'pt' => array(
            'bestand' => 'merkwaarden.md',
            'sectie'  => '',
            'sub'     => '### Para visitantes',
        ),
    );
    foreach ( $bron as $taal => $b ) {
        $bron[ $taal ]['raw'] = $raw . $b['bestand'];
        $bron[ $taal ]['api'] = $api . $b['bestand'] . '?ref=main';
    }
    return (array) apply_filters( 'sfp_page_config_merkwaarden_bron', $bron );
}

/**
 * De vaste labels per taal. De sleutel is wat de plugin ermee doet.
 *
 * @return array<string, array<string, string>>
 */
function sfp_page_config_merkwaarden_labels() {
    return array(
        'nl' => array(
            'titel'     => 'Titel',
            'kernzin'   => 'Kernzin',
            'inleiding' => 'Inleiding',
            'vraag'     => 'Vraag',
            'grens'     => 'Grens',
            'samen'     => 'In samenhang',
            'samen_oud' => 'Samen',
        ),
        'en' => array(
            'titel'     => 'Title',
            'kernzin'   => 'Key sentence',
            'inleiding' => 'Introduction',
            'vraag'     => 'Question',
            'grens'     => 'Boundary',
            'samen'     => 'As a whole',
            'samen_oud' => 'Together',
        ),
        'pt' => array(
            'titel'     => 'Título',
            'kernzin'   => 'Frase-chave',
            'inleiding' => 'Introdução',
            'vraag'     => 'Pergunta',
            'grens'     => 'Limite',
            'samen'     => 'Em conjunto',
            'samen_oud' => 'Juntos',
        ),
    );
}

/**
 * Een geldige taal, of '' als de waarde geen taal is.
 *
 * @param  string $taal Waarde.
 * @return string
 */
function sfp_page_config_merkwaarden_taal( $taal ) {
    $taal = strtolower( trim( (string) $taal ) );
    return in_array( $taal, array( 'nl', 'en', 'pt' ), true ) ? $taal : '';
}

/**
 * Het deel van een markdownbestand onder een kop, tot de volgende kop van
 * hetzelfde of een hoger niveau.
 *
 * @param  string $tekst Markdown.
 * @param  string $kop   De kopregel, zoals '## Merkwaarden'.
 * @return string|null   null als de kop er niet (precies één keer) staat.
 */
function sfp_page_config_merkwaarden_deel( $tekst, $kop ) {
    $niveau = strspn( $kop, '#' );
    $regels = preg_split( '/\r\n|\r|\n/', (string) $tekst );
    $start  = null;
    $aantal = 0;
    foreach ( $regels as $i => $regel ) {
        if ( rtrim( $regel ) === $kop ) {
            $aantal++;
            $start = $i;
        }
    }
    if ( 1 !== $aantal ) {
        return null;
    }
    $uit = array();
    for ( $i = $start + 1, $n = count( $regels ); $i < $n; $i++ ) {
        if ( preg_match( '/^(#{1,6})\s/', $regels[ $i ], $m ) && strlen( $m[1] ) <= $niveau ) {
            break;
        }
        $uit[] = $regels[ $i ];
    }
    return implode( "\n", $uit );
}

/**
 * Lees de bezoekerstekst uit de markdown van de bron.
 *
 * @param  string $markdown Hele bestand.
 * @param  string $taal     nl, en of pt.
 * @return array|WP_Error   De tekst, of een fout met wat er niet klopt.
 */
function sfp_page_config_merkwaarden_lees( $markdown, $taal ) {
    $bronnen = sfp_page_config_merkwaarden_bronnen();
    $labels  = sfp_page_config_merkwaarden_labels();
    if ( ! isset( $bronnen[ $taal ], $labels[ $taal ] ) ) {
        return new WP_Error( 'sfp_mw_taal', 'Onbekende taal.' );
    }
    $b = $bronnen[ $taal ];
    $l = $labels[ $taal ];

    $deel = (string) $markdown;
    if ( '' !== $b['sectie'] ) {
        $deel = sfp_page_config_merkwaarden_deel( $deel, $b['sectie'] );
        if ( null === $deel ) {
            return new WP_Error( 'sfp_mw_opbouw', 'De kop "' . $b['sectie'] . '" staat niet (precies één keer) in ' . $b['bestand'] . '.' );
        }
    }
    $deel = sfp_page_config_merkwaarden_deel( $deel, $b['sub'] );
    if ( null === $deel ) {
        return new WP_Error( 'sfp_mw_opbouw', 'De kop "' . $b['sub'] . '" staat niet (precies één keer) in ' . $b['bestand'] . '.' );
    }

    $uit    = array(
        'taal'      => $taal,
        'titel'     => '',
        'kernzin'   => '',
        'inleiding' => '',
        'waarden'   => array(),
        'samen'     => array(
            'kop'   => '',
            'tekst' => array(),
        ),
    );
    $huidig = null; // null: voor de eerste ####; 'samen'; of de index van een waarde.

    // Alinea's: blokken tekst tussen lege regels, of een kopregel.
    $blokken = array();
    $alinea  = array();
    foreach ( preg_split( '/\r\n|\r|\n/', $deel ) as $regel ) {
        $regel = rtrim( $regel );
        if ( preg_match( '/^#{1,6}\s/', $regel ) ) {
            if ( $alinea ) {
                $blokken[] = implode( ' ', $alinea );
                $alinea    = array();
            }
            $blokken[] = $regel;
            continue;
        }
        if ( '' === trim( $regel ) ) {
            if ( $alinea ) {
                $blokken[] = implode( ' ', $alinea );
                $alinea    = array();
            }
            continue;
        }
        $alinea[] = trim( $regel );
    }
    if ( $alinea ) {
        $blokken[] = implode( ' ', $alinea );
    }

    $label = function ( $blok, $naam ) {
        $patroon = '/^\*\*' . preg_quote( $naam, '/' ) . ':\*\*\s*(.+)$/u';
        return preg_match( $patroon, $blok, $m ) ? trim( $m[1] ) : null;
    };

    foreach ( $blokken as $blok ) {
        if ( preg_match( '/^####\s+(.+)$/u', $blok, $m ) ) {
            $naam = trim( $m[1] );
            // Het slot heet sinds 1-11-2026 In samenhang (As a whole, Em
            // conjunto); de oude kop blijft herkend.
            if ( 0 === strcasecmp( $naam, $l['samen'] ) || 0 === strcasecmp( $naam, $l['samen_oud'] ) ) {
                $huidig              = 'samen';
                $uit['samen']['kop'] = $naam;
            } else {
                $uit['waarden'][] = array(
                    'naam'  => $naam,
                    'vraag' => '',
                    'tekst' => array(),
                    'grens' => '',
                );
                $huidig           = count( $uit['waarden'] ) - 1;
            }
            continue;
        }
        if ( preg_match( '/^#{1,6}\s/', $blok ) ) {
            continue;
        }
        if ( null === $huidig ) {
            foreach ( array( 'titel', 'kernzin', 'inleiding' ) as $veld ) {
                $waarde = $label( $blok, $l[ $veld ] );
                if ( null !== $waarde ) {
                    $uit[ $veld ] = $waarde;
                }
            }
            // Andere tekst voor de eerste waarde is uitleg voor de lezer van de bron.
            continue;
        }
        if ( 'samen' === $huidig ) {
            $uit['samen']['tekst'][] = $blok;
            continue;
        }
        $vraag = $label( $blok, $l['vraag'] );
        $grens = $label( $blok, $l['grens'] );
        if ( null !== $vraag ) {
            $uit['waarden'][ $huidig ]['vraag'] = $vraag;
        } elseif ( null !== $grens ) {
            $uit['waarden'][ $huidig ]['grens'] = $grens;
        } elseif ( ! preg_match( '/^\*\*[^*]+:\*\*/u', $blok ) ) {
            $uit['waarden'][ $huidig ]['tekst'][] = $blok;
        }
    }

    // Controle: is alles er dat het blok toont?
    $mist = array();
    foreach ( array( 'titel', 'kernzin', 'inleiding' ) as $veld ) {
        if ( '' === $uit[ $veld ] ) {
            $mist[] = $l[ $veld ];
        }
    }
    if ( 3 !== count( $uit['waarden'] ) ) {
        $mist[] = 'drie waarden (gevonden: ' . count( $uit['waarden'] ) . ')';
    }
    foreach ( $uit['waarden'] as $w ) {
        if ( '' === $w['vraag'] ) {
            $mist[] = $l['vraag'] . ' bij ' . $w['naam'];
        }
        if ( ! $w['tekst'] ) {
            $mist[] = 'tekst bij ' . $w['naam'];
        }
        if ( '' === $w['grens'] ) {
            $mist[] = $l['grens'] . ' bij ' . $w['naam'];
        }
    }
    if ( ! $uit['samen']['tekst'] ) {
        $mist[] = '#### ' . $l['samen'];
    }
    if ( $mist ) {
        return new WP_Error( 'sfp_mw_opbouw', 'Niet te herkennen in ' . $b['bestand'] . ' (' . $taal . '): ontbreekt ' . implode( ', ', $mist ) . '.' );
    }
    return $uit;
}

/* =========================================================================
 * Ophalen, bewaren, terugvallen
 * ====================================================================== */

/**
 * Schrijf een melding in het log van dit onderdeel.
 *
 * @param string $taal  Taal.
 * @param string $tekst Melding.
 * @param string $soort fout of ververst.
 */
function sfp_page_config_merkwaarden_log( $taal, $tekst, $soort = 'fout' ) {
    $log   = get_option( SFP_PC_MW_LOG, array() );
    $log   = is_array( $log ) ? $log : array();
    $log[] = array(
        'tijd'  => time(),
        'taal'  => (string) $taal,
        'soort' => (string) $soort,
        'tekst' => (string) $tekst,
    );
    update_option( SFP_PC_MW_LOG, array_slice( $log, -30 ), false );
}

/**
 * Haal de markdown van één bestand op.
 *
 * @param  array $bron   Bron uit sfp_page_config_merkwaarden_bronnen().
 * @param  bool  $vers   Via de GitHub-API (geen cache), met het raw-adres als terugval.
 * @return string|WP_Error
 */
function sfp_page_config_merkwaarden_haal( array $bron, $vers = false ) {
    $pogingen = $vers ? array( 'api', 'raw' ) : array( 'raw' );
    $fout     = null;
    foreach ( $pogingen as $soort ) {
        if ( empty( $bron[ $soort ] ) ) {
            continue;
        }
        $kop = array( 'User-Agent' => 'SFP-Page-Config/' . ( defined( 'SFP_PAGE_CONFIG_VERSION' ) ? SFP_PAGE_CONFIG_VERSION : '0' ) . '; ' . home_url( '/' ) );
        if ( 'api' === $soort ) {
            $kop['Accept'] = 'application/vnd.github.raw';
        }
        $antwoord = wp_remote_get(
            $bron[ $soort ],
            array(
                'timeout' => 8,
                'headers' => $kop,
            )
        );
        if ( is_wp_error( $antwoord ) ) {
            $fout = new WP_Error( 'sfp_mw_ophalen', $bron['bestand'] . ' niet bereikbaar (' . $soort . '): ' . $antwoord->get_error_message() );
            continue;
        }
        $code = (int) wp_remote_retrieve_response_code( $antwoord );
        $body = (string) wp_remote_retrieve_body( $antwoord );
        if ( 200 !== $code || '' === trim( $body ) ) {
            $fout = new WP_Error( 'sfp_mw_ophalen', $bron['bestand'] . ' gaf ' . $code . ' (' . $soort . ').' );
            continue;
        }
        return $body;
    }
    return $fout ? $fout : new WP_Error( 'sfp_mw_ophalen', 'Geen bron ingesteld.' );
}

/**
 * De kopie die met de plugin meekomt, voor een site waar nog nooit iets is
 * opgehaald.
 *
 * @param  string $taal Taal.
 * @return array|null
 */
function sfp_page_config_merkwaarden_meegeleverd( $taal ) {
    $bestand = dirname( __DIR__ ) . '/assets/merkwaarden/terugval.json';
    if ( ! is_readable( $bestand ) ) {
        return null;
    }
    $alle = json_decode( (string) file_get_contents( $bestand ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- eigen bestand in de plugin.
    return is_array( $alle ) && isset( $alle[ $taal ] ) && is_array( $alle[ $taal ] ) ? $alle[ $taal ] : null;
}

/**
 * Haal de tekst van één taal op en bewaar hem als het lukt.
 *
 * @param  string $taal Taal.
 * @param  bool   $vers Verversen: via de API, buiten de cache van de raw-adressen.
 * @return array|WP_Error De tekst, of de fout.
 */
function sfp_page_config_merkwaarden_ververs_taal( $taal, $vers = false ) {
    $bronnen = sfp_page_config_merkwaarden_bronnen();
    if ( ! isset( $bronnen[ $taal ] ) ) {
        return new WP_Error( 'sfp_mw_taal', 'Onbekende taal.' );
    }
    $markdown = sfp_page_config_merkwaarden_haal( $bronnen[ $taal ], $vers );
    $tekst    = is_wp_error( $markdown ) ? $markdown : sfp_page_config_merkwaarden_lees( $markdown, $taal );
    if ( is_wp_error( $tekst ) ) {
        sfp_page_config_merkwaarden_log( $taal, $tekst->get_error_message() );
        return $tekst;
    }
    $tekst['opgehaald'] = time();
    $tekst['hash']      = substr( md5( wp_json_encode( array( $tekst['titel'], $tekst['kernzin'], $tekst['inleiding'], $tekst['waarden'], $tekst['samen'] ) ) ), 0, 10 );
    set_transient( SFP_PC_MW_TRANSIENT . $taal, $tekst, 12 * HOUR_IN_SECONDS );
    $laatst          = get_option( SFP_PC_MW_LAATST, array() );
    $laatst          = is_array( $laatst ) ? $laatst : array();
    $laatst[ $taal ] = $tekst;
    update_option( SFP_PC_MW_LAATST, $laatst, false );
    return $tekst;
}

/**
 * De tekst voor het blok: uit de transient, vers opgehaald, de laatst
 * gelukte versie of de meegeleverde kopie. Nooit leeg.
 *
 * @param  string $taal Taal.
 * @return array{tekst: array, herkomst: string}
 */
function sfp_page_config_merkwaarden_tekst( $taal ) {
    static $geheugen = array();
    if ( isset( $geheugen[ $taal ] ) ) {
        return $geheugen[ $taal ];
    }
    $bewaard = get_transient( SFP_PC_MW_TRANSIENT . $taal );
    if ( is_array( $bewaard ) && ! empty( $bewaard['waarden'] ) ) {
        $geheugen[ $taal ] = array(
            'tekst'    => $bewaard,
            'herkomst' => ! empty( $bewaard['terugval'] ) ? 'laatst' : 'bron',
        );
        return $geheugen[ $taal ];
    }
    $vers = sfp_page_config_merkwaarden_ververs_taal( $taal );
    if ( ! is_wp_error( $vers ) ) {
        $geheugen[ $taal ] = array(
            'tekst'    => $vers,
            'herkomst' => 'bron',
        );
        return $geheugen[ $taal ];
    }
    $laatst   = get_option( SFP_PC_MW_LAATST, array() );
    $tekst    = is_array( $laatst ) && isset( $laatst[ $taal ] ) && is_array( $laatst[ $taal ] ) ? $laatst[ $taal ] : null;
    $herkomst = 'laatst';
    if ( null === $tekst ) {
        $tekst    = sfp_page_config_merkwaarden_meegeleverd( $taal );
        $herkomst = 'meegeleverd';
    }
    if ( null === $tekst ) {
        $tekst    = array();
        $herkomst = 'geen';
    }
    // Niet bij elke bezoeker opnieuw proberen: een kwartier de terugval bewaren.
    if ( $tekst ) {
        $tekst['terugval'] = 1;
        set_transient( SFP_PC_MW_TRANSIENT . $taal, $tekst, 15 * MINUTE_IN_SECONDS );
    }
    $geheugen[ $taal ] = array(
        'tekst'    => $tekst,
        'herkomst' => $herkomst,
    );
    return $geheugen[ $taal ];
}

/**
 * Ververs alle drie de talen (knop in SFP Beheer, ability).
 *
 * @return array{talen: array, stand: array}
 */
function sfp_page_config_merkwaarden_ververs() {
    $uit = array();
    foreach ( array( 'nl', 'en', 'pt' ) as $taal ) {
        delete_transient( SFP_PC_MW_TRANSIENT . $taal );
        $r            = sfp_page_config_merkwaarden_ververs_taal( $taal, true );
        $uit[ $taal ] = is_wp_error( $r )
            ? array(
                'gelukt' => false,
                'tekst'  => $r->get_error_message(),
            )
            : array(
                'gelukt' => true,
                'titel'  => $r['titel'],
                'hash'   => $r['hash'],
            );
        if ( ! is_wp_error( $r ) ) {
            sfp_page_config_merkwaarden_log( $taal, 'Ververst: ' . $r['titel'] . ' (' . $r['hash'] . ').', 'ververst' );
        }
    }
    return array(
        'talen' => $uit,
        'stand' => sfp_page_config_merkwaarden_stand(),
    );
}

/* =========================================================================
 * Slot per taal
 * ====================================================================== */

/**
 * Het slot van deze site in een taal.
 *
 * @param  string $taal Taal.
 * @return array{zin: string, knop: string, pagina: int, link: string, href: string}
 */
function sfp_page_config_merkwaarden_slot( $taal ) {
    $alle  = sfp_page_config_instellingen();
    $eigen = isset( $alle['merkwaarden']['slot'][ $taal ] ) && is_array( $alle['merkwaarden']['slot'][ $taal ] ) ? $alle['merkwaarden']['slot'][ $taal ] : array();
    $slot  = array(
        'zin'    => isset( $eigen['zin'] ) ? (string) $eigen['zin'] : '',
        'knop'   => isset( $eigen['knop'] ) ? (string) $eigen['knop'] : '',
        'pagina' => isset( $eigen['pagina'] ) ? (int) $eigen['pagina'] : 0,
        'link'   => isset( $eigen['link'] ) ? (string) $eigen['link'] : '',
        'href'   => '',
    );
    if ( $slot['pagina'] && 'publish' === get_post_status( $slot['pagina'] ) ) {
        $slot['href'] = (string) get_permalink( $slot['pagina'] );
    } elseif ( '' !== $slot['link'] ) {
        $slot['href'] = $slot['link'];
    }
    return $slot;
}

/**
 * Schoon het slot uit het beheer.
 *
 * @param  mixed $ruw Invoer: per taal zin, knop, pagina, link.
 * @return array
 */
function sfp_page_config_merkwaarden_slot_schoon( $ruw ) {
    $uit = array();
    if ( ! is_array( $ruw ) ) {
        return $uit;
    }
    foreach ( array( 'nl', 'en', 'pt' ) as $taal ) {
        if ( ! isset( $ruw[ $taal ] ) || ! is_array( $ruw[ $taal ] ) ) {
            continue;
        }
        $in    = $ruw[ $taal ];
        $regel = array(
            'zin'    => sanitize_text_field( (string) ( $in['zin'] ?? '' ) ),
            'knop'   => sanitize_text_field( (string) ( $in['knop'] ?? '' ) ),
            'pagina' => absint( $in['pagina'] ?? 0 ),
            'link'   => esc_url_raw( trim( (string) ( $in['link'] ?? '' ) ) ),
        );
        if ( '' === $regel['zin'] && '' === $regel['knop'] && ! $regel['pagina'] && '' === $regel['link'] ) {
            continue;
        }
        $uit[ $taal ] = $regel;
    }
    return $uit;
}

/* =========================================================================
 * Stand voor SFP Beheer en de ability
 * ====================================================================== */

/**
 * Wat het beheer over dit onderdeel toont.
 *
 * @return array
 */
function sfp_page_config_merkwaarden_stand() {
    $laatst = get_option( SFP_PC_MW_LAATST, array() );
    $laatst = is_array( $laatst ) ? $laatst : array();
    $bron   = sfp_page_config_merkwaarden_bronnen();
    $talen  = array();
    foreach ( array( 'nl', 'en', 'pt' ) as $taal ) {
        $t              = isset( $laatst[ $taal ] ) && is_array( $laatst[ $taal ] ) ? $laatst[ $taal ] : null;
        $slot           = sfp_page_config_merkwaarden_slot( $taal );
        $talen[ $taal ] = array(
            'bron'      => isset( $bron[ $taal ]['raw'] ) ? $bron[ $taal ]['raw'] : '',
            'titel'     => $t ? (string) $t['titel'] : '',
            'opgehaald' => $t && ! empty( $t['opgehaald'] ) ? wp_date( 'j-n-Y H:i', (int) $t['opgehaald'] ) : '',
            'hash'      => $t && ! empty( $t['hash'] ) ? (string) $t['hash'] : '',
            'slot'      => array(
                'zin'    => $slot['zin'],
                'knop'   => $slot['knop'],
                'pagina' => $slot['pagina'],
                'link'   => $slot['link'],
                'klaar'  => '' !== $slot['zin'] && '' !== $slot['knop'] && '' !== $slot['href'],
            ),
        );
    }
    $log = get_option( SFP_PC_MW_LOG, array() );
    $log = is_array( $log ) ? array_reverse( $log ) : array();
    foreach ( $log as $i => $regel ) {
        $log[ $i ]['wanneer'] = wp_date( 'j-n-Y H:i', (int) ( $regel['tijd'] ?? 0 ) );
    }
    return array(
        'sitetaal' => function_exists( 'sfp_page_config_taal' ) ? sfp_page_config_taal() : 'nl',
        'talen'    => $talen,
        'log'      => array_slice( $log, 0, 10 ),
    );
}

/* =========================================================================
 * Weergave
 * ====================================================================== */

/**
 * Inline opmaak uit de bron: vet. De rest is gewone tekst.
 *
 * @param  string $tekst Markdown van één alinea.
 * @return string        Veilige HTML.
 */
function sfp_page_config_merkwaarden_inline( $tekst ) {
    $html = esc_html( (string) $tekst );
    $html = preg_replace( '/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $html );
    return str_replace( '`', '', (string) $html );
}

/**
 * Een vaste tekst van het blok in een taal: de eigen tekst van de site als
 * de taal die van de site is, anders de standaard in die taal.
 *
 * @param  string $sleutel Sleutel uit het tekstregister.
 * @param  string $taal    Taal.
 * @return string
 */
function sfp_page_config_merkwaarden_vast( $sleutel, $taal ) {
    if ( function_exists( 'sfp_page_config_taal' ) && sfp_page_config_taal() === $taal && function_exists( 'sfp_page_config_tekst' ) ) {
        return sfp_page_config_tekst( $sleutel );
    }
    return function_exists( 'sfp_page_config_tekst_standaard' ) ? sfp_page_config_tekst_standaard( $sleutel, $taal ) : '';
}

/**
 * Een id voor een anker uit de naam van een waarde.
 *
 * @param  string $naam Naam.
 * @return string
 */
function sfp_page_config_merkwaarden_anker( $naam ) {
    $slug = sanitize_title( $naam );
    return 'sfp-mw-' . ( '' !== $slug ? $slug : md5( $naam ) );
}

/**
 * Het hele blok.
 *
 * @param  array $attrs Attributen: taal, align.
 * @return string
 */
function sfp_page_config_merkwaarden_render( $attrs = array() ) {
    $attrs = is_array( $attrs ) ? $attrs : array();
    $taal  = sfp_page_config_merkwaarden_taal( isset( $attrs['taal'] ) ? $attrs['taal'] : '' );
    if ( '' === $taal ) {
        $taal = function_exists( 'sfp_page_config_taal' ) ? sfp_page_config_taal() : 'nl';
    }
    $data  = sfp_page_config_merkwaarden_tekst( $taal );
    $tekst = $data['tekst'];
    if ( ! $tekst || empty( $tekst['waarden'] ) ) {
        // Alleen als zelfs de meegeleverde kopie ontbreekt (een kapotte installatie).
        return current_user_can( 'edit_posts' ) ? '<p class="sfp-mw-melding">' . esc_html( sfp_page_config_merkwaarden_vast( 'merkwaarden_geen_bron', $taal ) ) . '</p>' : '';
    }

    $pijl_omlaag = '<svg aria-hidden="true" focusable="false" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M6 13l6 6 6-6"/></svg>';
    $pijl_rechts = '<svg aria-hidden="true" focusable="false" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';

    $klassen = array( 'sfp-mw' );
    $align   = isset( $attrs['align'] ) ? (string) $attrs['align'] : '';
    if ( in_array( $align, array( 'full', 'wide' ), true ) ) {
        $klassen[] = 'align' . $align;
    }
    if ( ! empty( $attrs['className'] ) ) {
        $klassen[] = sanitize_html_class( (string) $attrs['className'] );
    }

    $h  = '<div class="' . esc_attr( implode( ' ', $klassen ) ) . '" lang="' . esc_attr( 'pt' === $taal ? 'pt-BR' : $taal ) . '" data-sfp-mw="' . esc_attr( $taal . '-' . ( $tekst['hash'] ?? '' ) ) . '">';
    $h .= '<div class="sfp-mw__held"><div class="sfp-mw__binnen">';
    $h .= '<h1 class="sfp-mw__titel">' . sfp_page_config_merkwaarden_inline( $tekst['titel'] ) . '</h1>';
    $h .= '<p class="sfp-mw__kernzin">' . sfp_page_config_merkwaarden_inline( $tekst['kernzin'] ) . '</p>';
    if ( '' !== (string) $tekst['inleiding'] ) {
        $h .= '<p class="sfp-mw__inleiding">' . sfp_page_config_merkwaarden_inline( $tekst['inleiding'] ) . '</p>';
    }
    $h .= '</div></div>';

    $h .= '<div class="sfp-mw__binnen sfp-mw__inhoud">';
    $h .= '<ul class="sfp-mw__kaarten" role="list" aria-label="' . esc_attr( sfp_page_config_merkwaarden_vast( 'merkwaarden_kaarten', $taal ) ) . '">';
    foreach ( $tekst['waarden'] as $w ) {
        $meer = sprintf( sfp_page_config_merkwaarden_vast( 'merkwaarden_meer', $taal ), function_exists( 'mb_strtolower' ) ? mb_strtolower( $w['naam'] ) : strtolower( $w['naam'] ) );
        $h   .= '<li><a class="sfp-mw__kaart" href="#' . esc_attr( sfp_page_config_merkwaarden_anker( $w['naam'] ) ) . '">'
            . '<span class="sfp-mw__kaarttitel">' . esc_html( $w['naam'] ) . '</span>'
            . '<span class="sfp-mw__vraag">' . sfp_page_config_merkwaarden_inline( $w['vraag'] ) . '</span>'
            . '<span class="sfp-mw__link">' . esc_html( $meer ) . $pijl_omlaag . '</span>'
            . '</a></li>';
    }
    $h .= '</ul>';

    foreach ( $tekst['waarden'] as $w ) {
        $anker = sfp_page_config_merkwaarden_anker( $w['naam'] );
        $h    .= '<section class="sfp-mw__waarde" id="' . esc_attr( $anker ) . '" aria-labelledby="' . esc_attr( $anker . '-kop' ) . '">';
        $h    .= '<h2 class="sfp-mw__kop" id="' . esc_attr( $anker . '-kop' ) . '">' . esc_html( $w['naam'] ) . '</h2>';
        foreach ( (array) $w['tekst'] as $alinea ) {
            $h .= '<p>' . sfp_page_config_merkwaarden_inline( $alinea ) . '</p>';
        }
        $h .= '<p class="sfp-mw__grens"><strong>' . esc_html( sfp_page_config_merkwaarden_vast( 'merkwaarden_grens', $taal ) ) . ':</strong> ' . sfp_page_config_merkwaarden_inline( $w['grens'] ) . '</p>';
        $h .= '</section>';
    }

    $h .= '</div>';

    // In samenhang is geen vierde merkwaarde (Stephan 1-11-2026): geen kaart
    // met bovenrand, maar een eigen band over de breedte met het slot erin.
    $samen_kop = '' !== (string) ( $tekst['samen']['kop'] ?? '' ) ? $tekst['samen']['kop'] : sfp_page_config_merkwaarden_labels()[ $taal ]['samen'];
    $h        .= '<section class="sfp-mw__samen" aria-labelledby="sfp-mw-samen-kop"><div class="sfp-mw__binnen">';
    $h        .= '<h2 class="sfp-mw__kop" id="sfp-mw-samen-kop">' . esc_html( $samen_kop ) . '</h2>';
    foreach ( (array) $tekst['samen']['tekst'] as $alinea ) {
        $h .= '<p>' . sfp_page_config_merkwaarden_inline( $alinea ) . '</p>';
    }

    $slot = sfp_page_config_merkwaarden_slot( $taal );
    if ( '' !== $slot['zin'] && '' !== $slot['knop'] && '' !== $slot['href'] ) {
        $h .= '<div class="sfp-mw__slot">'
            . '<p class="sfp-mw__slotzin">' . esc_html( $slot['zin'] ) . '</p>'
            . '<a class="sfp-mw__knop" href="' . esc_url( $slot['href'] ) . '">' . esc_html( $slot['knop'] ) . $pijl_rechts . '</a>'
            . '</div>';
    } elseif ( current_user_can( 'edit_posts' ) ) {
        $h .= '<p class="sfp-mw-melding">' . esc_html( sprintf( sfp_page_config_merkwaarden_vast( 'merkwaarden_geen_slot', $taal ), strtoupper( $taal ) ) ) . '</p>';
    }
    if ( 'bron' !== $data['herkomst'] && current_user_can( 'edit_posts' ) ) {
        $h .= '<p class="sfp-mw-melding">' . esc_html( sfp_page_config_merkwaarden_vast( 'merkwaarden_terugval', $taal ) ) . '</p>';
    }
    $h .= '</div></section></div>';

    $css = function_exists( 'sfp_page_config_blokken_css_vangnet' ) ? sfp_page_config_blokken_css_vangnet( 'sfp/merkwaarden' ) : '';
    return $css . $h;
}

/* =========================================================================
 * Blok, code tussen haken, onderdeel
 * ====================================================================== */

add_filter( 'sfp_page_config_blokken_extra', 'sfp_page_config_merkwaarden_blok' );

/**
 * Het blok Merkwaarden.
 *
 * @param  array $blokken Blokken.
 * @return array
 */
function sfp_page_config_merkwaarden_blok( $blokken ) {
    $blokken['sfp/merkwaarden'] = array(
        'title'        => 'Merkwaarden',
        'description'  => 'De merkwaarden voor bezoekers, live uit de merkbron. Met de slotzin en knop uit SFP Beheer.',
        'icon'         => 'heart',
        'css'          => 'merkwaarden',
        'zonder_basis' => true,
        'inserter'     => ! function_exists( 'sfp_page_config_onderdeel_aan' ) || sfp_page_config_onderdeel_aan( 'merkwaarden' ),
        'align'        => array( 'wide', 'full' ),
        'attributes'   => array(
            'taal'  => array(
                'type'    => 'string',
                'default' => '',
                'enum'    => array( '', 'nl', 'en', 'pt' ),
            ),
            'align' => array(
                'type'    => 'string',
                'default' => 'full',
            ),
        ),
        'render'       => 'sfp_page_config_merkwaarden_blok_render',
    );
    return $blokken;
}

/**
 * Render van het blok.
 *
 * @param  array $attrs Attributen.
 * @return string
 */
function sfp_page_config_merkwaarden_blok_render( $attrs ) {
    if ( function_exists( 'sfp_page_config_onderdeel_aan' ) && ! sfp_page_config_onderdeel_aan( 'merkwaarden' ) ) {
        return '';
    }
    return sfp_page_config_merkwaarden_render( $attrs );
}

add_action( 'init', 'sfp_page_config_merkwaarden_code' );

/**
 * De code [sfp_merkwaarden taal="nl|en|pt"].
 */
function sfp_page_config_merkwaarden_code() {
    if ( shortcode_exists( 'sfp_merkwaarden' ) ) {
        return;
    }
    add_shortcode(
        'sfp_merkwaarden',
        function ( $atts ) {
            $atts = shortcode_atts( array( 'taal' => '' ), $atts, 'sfp_merkwaarden' );
            return sfp_page_config_merkwaarden_blok_render( array( 'taal' => $atts['taal'] ) );
        }
    );
}

/* =========================================================================
 * Ability voor de MCP-koppeling
 * ====================================================================== */

add_action( 'wp_abilities_api_categories_init', 'sfp_page_config_merkwaarden_ability_groep' );
add_action( 'wp_abilities_api_init', 'sfp_page_config_merkwaarden_abilities' );

/**
 * De groep sfp-merkwaarden.
 */
function sfp_page_config_merkwaarden_ability_groep() {
    if ( ! function_exists( 'wp_register_ability_category' ) ) {
        return;
    }
    wp_register_ability_category(
        'sfp-merkwaarden',
        array(
            'label'       => 'SFP merkwaarden',
            'description' => 'De merkwaarden voor bezoekers opnieuw ophalen uit de merkbron en de stand lezen.',
        )
    );
}

/**
 * De abilities sfp-merkwaarden/ververs en sfp-merkwaarden/stand.
 */
function sfp_page_config_merkwaarden_abilities() {
    if ( ! function_exists( 'wp_register_ability' ) ) {
        return;
    }
    $mag = function () {
        return is_user_logged_in() && current_user_can( 'manage_options' );
    };
    wp_register_ability(
        'sfp-merkwaarden/ververs',
        array(
            'label'               => 'Merkwaarden verversen',
            'description'         => 'Haal de merkwaarden in het Nederlands, Engels en Portugees opnieuw op uit de merkbron (repo sfp-brand-source, main). Geeft per taal of het lukte, en de stand. Leegt de caches van WP Rocket, SiteGround en Cloudflare niet.',
            'category'            => 'sfp-merkwaarden',
            'input_schema'        => array(
                'type'                 => 'object',
                'additionalProperties' => false,
            ),
            'execute_callback'    => function () {
                return sfp_page_config_merkwaarden_ververs();
            },
            'permission_callback' => $mag,
            'meta'                => array(
                'mcp'         => array(
                    'public' => true,
                    'type'   => 'tool',
                ),
                'annotations' => array(
                    'readonly'      => false,
                    'destructive'   => false,
                    'idempotent'    => true,
                    'openWorldHint' => true,
                ),
            ),
        )
    );
    wp_register_ability(
        'sfp-merkwaarden/stand',
        array(
            'label'               => 'Stand van de merkwaarden',
            'description'         => 'Lees per taal wanneer de merkwaarden het laatst zijn opgehaald, of het slot is ingevuld, en de laatste meldingen.',
            'category'            => 'sfp-merkwaarden',
            'input_schema'        => array(
                'type'                 => 'object',
                'additionalProperties' => false,
            ),
            'execute_callback'    => function () {
                return sfp_page_config_merkwaarden_stand();
            },
            'permission_callback' => $mag,
            'meta'                => array(
                'mcp'         => array(
                    'public' => true,
                    'type'   => 'tool',
                ),
                'annotations' => array(
                    'readonly'      => true,
                    'destructive'   => false,
                    'idempotent'    => true,
                    'openWorldHint' => false,
                ),
            ),
        )
    );
}
