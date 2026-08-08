<?php
/**
 * Regenerate languages/elementor-taxonomy-loop.pot from the plugin sources.
 *
 * Dependency-free so it runs anywhere PHP does:
 *
 *     php bin/make-pot.php
 *
 * @package Beenacle\ElementorTaxonomyLoop
 */

declare(strict_types=1);

const TEXT_DOMAIN = 'elementor-taxonomy-loop';
const PACKAGE     = 'Elementor Taxonomy Loop Widget';

/**
 * Gettext callees, mapped to the argument positions that matter.
 *
 * Singular, plural and context are zero-indexed argument positions; `domain` is
 * the position that must match the plugin's text domain.
 */
const CALLEES = array(
	'__'         => array(
		'singular' => 0,
		'domain'   => 1,
	),
	'_e'         => array(
		'singular' => 0,
		'domain'   => 1,
	),
	'esc_html__' => array(
		'singular' => 0,
		'domain'   => 1,
	),
	'esc_html_e' => array(
		'singular' => 0,
		'domain'   => 1,
	),
	'esc_attr__' => array(
		'singular' => 0,
		'domain'   => 1,
	),
	'esc_attr_e' => array(
		'singular' => 0,
		'domain'   => 1,
	),
	'_x'         => array(
		'singular' => 0,
		'context'  => 1,
		'domain'   => 2,
	),
	'esc_html_x' => array(
		'singular' => 0,
		'context'  => 1,
		'domain'   => 2,
	),
	'esc_attr_x' => array(
		'singular' => 0,
		'context'  => 1,
		'domain'   => 2,
	),
	'_n'         => array(
		'singular' => 0,
		'plural'   => 1,
		'domain'   => 3,
	),
	'_nx'        => array(
		'singular' => 0,
		'plural'   => 1,
		'context'  => 3,
		'domain'   => 4,
	),
);

$root = dirname( __DIR__ );
$out  = $root . '/languages/' . TEXT_DOMAIN . '.pot';

$files = array();
$walk  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, RecursiveDirectoryIterator::SKIP_DOTS ) );

foreach ( $walk as $file ) {
	$path = str_replace( '\\', '/', $file->getPathname() );

	if ( 'php' !== strtolower( $file->getExtension() ) ) {
		continue;
	}

	if ( preg_match( '#/(vendor|node_modules|bin)/#', $path ) ) {
		continue;
	}

	$files[] = $path;
}

sort( $files );

$entries = array();

foreach ( $files as $path ) {
	$relative = ltrim( substr( $path, strlen( $root ) ), '/' );

	foreach ( extract_strings( (string) file_get_contents( $path ) ) as $entry ) {
		$key = ( $entry['context'] ?? '' ) . "\4" . $entry['singular'] . "\4" . ( $entry['plural'] ?? '' );

		if ( ! isset( $entries[ $key ] ) ) {
			$entries[ $key ] = $entry + array(
				'references' => array(),
				'comments'   => array(),
			);
		}

		$entries[ $key ]['references'][] = $relative . ':' . $entry['line'];

		if ( '' !== ( $entry['comment'] ?? '' ) ) {
			$entries[ $key ]['comments'][ $entry['comment'] ] = true;
		}
	}
}

ksort( $entries );

file_put_contents( $out, render_pot( $entries ) );

printf( 'Wrote %d strings to %s%s', count( $entries ), str_replace( $root . '/', '', $out ), PHP_EOL );

/**
 * Pull translatable strings out of one PHP source file.
 *
 * @param string $source File contents.
 * @return array<int,array<string,mixed>>
 */
function extract_strings( string $source ): array {
	$tokens  = token_get_all( $source );
	$count   = count( $tokens );
	$found   = array();
	$comment = '';

	for ( $i = 0; $i < $count; $i++ ) {
		$token = $tokens[ $i ];

		if ( is_array( $token ) && in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
			if ( false !== stripos( $token[1], 'translators:' ) ) {
				$comment = normalize_comment( $token[1] );
			}
			continue;
		}

		if ( ! is_array( $token ) || T_STRING !== $token[0] || ! isset( CALLEES[ $token[1] ] ) ) {
			continue;
		}

		// A method or property access is not the global gettext function.
		$previous = previous_significant( $tokens, $i );
		if ( is_array( $previous ) && in_array( $previous[0], array( T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION ), true ) ) {
			continue;
		}

		$open = next_significant_index( $tokens, $i );
		if ( null === $open || '(' !== $tokens[ $open ] ) {
			continue;
		}

		$args = read_arguments( $tokens, $open );
		$spec = CALLEES[ $token[1] ];

		if ( ( $args[ $spec['domain'] ] ?? null ) !== TEXT_DOMAIN ) {
			$comment = '';
			continue;
		}

		$entry = array(
			'singular' => (string) ( $args[ $spec['singular'] ] ?? '' ),
			'line'     => $token[2],
			'comment'  => $comment,
		);

		if ( isset( $spec['plural'] ) && isset( $args[ $spec['plural'] ] ) ) {
			$entry['plural'] = (string) $args[ $spec['plural'] ];
		}

		if ( isset( $spec['context'] ) && isset( $args[ $spec['context'] ] ) ) {
			$entry['context'] = (string) $args[ $spec['context'] ];
		}

		if ( '' !== $entry['singular'] ) {
			$found[] = $entry;
		}

		$comment = '';
	}

	return $found;
}

/**
 * Read the literal arguments of a call, keyed by argument position.
 *
 * Non-literal arguments are recorded as null so positions stay aligned.
 *
 * @param array $tokens Token stream.
 * @param int   $open   Index of the opening parenthesis.
 * @return array<int,string|null>
 */
function read_arguments( array $tokens, int $open ): array {
	$depth    = 0;
	$position = 0;
	$args     = array();
	$count    = count( $tokens );

	for ( $i = $open; $i < $count; $i++ ) {
		$token = $tokens[ $i ];

		if ( '(' === $token || '[' === $token ) {
			++$depth;
			continue;
		}

		if ( ')' === $token || ']' === $token ) {
			--$depth;
			if ( 0 === $depth ) {
				break;
			}
			continue;
		}

		if ( ',' === $token && 1 === $depth ) {
			++$position;
			continue;
		}

		if ( 1 !== $depth || ! is_array( $token ) ) {
			continue;
		}

		if ( T_CONSTANT_ENCAPSED_STRING === $token[0] ) {
			$args[ $position ] = isset( $args[ $position ] )
				? $args[ $position ] . unquote( $token[1] )   // Handles 'a' . 'b'.
				: unquote( $token[1] );
			continue;
		}

		if ( in_array( $token[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
			continue;
		}

		if ( ! isset( $args[ $position ] ) ) {
			$args[ $position ] = null;
		}
	}

	return $args;
}

/**
 * Turn a PHP string literal into its value.
 *
 * @param string $literal Raw literal including quotes.
 * @return string
 */
function unquote( string $literal ): string {
	$quote = $literal[0];
	$body  = substr( $literal, 1, -1 );

	if ( "'" === $quote ) {
		return str_replace( array( "\\'", '\\\\' ), array( "'", '\\' ), $body );
	}

	return stripcslashes( $body );
}

/**
 * Strip comment markers and collapse a translator note onto one line.
 *
 * @param string $comment Raw comment token.
 * @return string
 */
function normalize_comment( string $comment ): string {
	$comment = preg_replace( '#^/\*+|\*+/$|^//#', '', trim( $comment ) ) ?? $comment;
	$comment = preg_replace( '#^\s*\*\s?#m', '', $comment ) ?? $comment;

	return trim( (string) preg_replace( '/\s+/', ' ', $comment ) );
}

/**
 * The token before $index, skipping whitespace and comments.
 *
 * @param array $tokens Token stream.
 * @param int   $index  Current position.
 * @return array|string|null
 */
function previous_significant( array $tokens, int $index ) {
	for ( $i = $index - 1; $i >= 0; $i-- ) {
		if ( is_array( $tokens[ $i ] ) && in_array( $tokens[ $i ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
			continue;
		}

		return $tokens[ $i ];
	}

	return null;
}

/**
 * Index of the token after $index, skipping whitespace and comments.
 *
 * @param array $tokens Token stream.
 * @param int   $index  Current position.
 * @return int|null
 */
function next_significant_index( array $tokens, int $index ): ?int {
	$count = count( $tokens );

	for ( $i = $index + 1; $i < $count; $i++ ) {
		if ( is_array( $tokens[ $i ] ) && in_array( $tokens[ $i ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
			continue;
		}

		return $i;
	}

	return null;
}

/**
 * Serialise the collected entries as a POT file.
 *
 * @param array $entries Collected entries.
 * @return string
 */
function render_pot( array $entries ): string {
	$pot  = "# Copyright (C) Beenacle\n";
	$pot .= '# This file is distributed under the same license as the ' . PACKAGE . " plugin.\n";
	$pot .= "msgid \"\"\nmsgstr \"\"\n";
	$pot .= '"Project-Id-Version: ' . PACKAGE . '\n"' . "\n";
	$pot .= '"Report-Msgid-Bugs-To: https://github.com/beenacle/elementor-taxonomy-loop/issues\n"' . "\n";
	$pot .= '"MIME-Version: 1.0\n"' . "\n";
	$pot .= '"Content-Type: text/plain; charset=UTF-8\n"' . "\n";
	$pot .= '"Content-Transfer-Encoding: 8bit\n"' . "\n";
	$pot .= '"Plural-Forms: nplurals=2; plural=(n != 1);\n"' . "\n";
	$pot .= '"X-Domain: ' . TEXT_DOMAIN . '\n"' . "\n";

	foreach ( $entries as $entry ) {
		$pot .= "\n";

		foreach ( array_keys( $entry['comments'] ) as $comment ) {
			$pot .= '#. ' . $comment . "\n";
		}

		$pot .= '#: ' . implode( ' ', array_unique( $entry['references'] ) ) . "\n";

		if ( isset( $entry['context'] ) ) {
			$pot .= 'msgctxt ' . escape_po( $entry['context'] ) . "\n";
		}

		$pot .= 'msgid ' . escape_po( $entry['singular'] ) . "\n";

		if ( isset( $entry['plural'] ) ) {
			$pot .= 'msgid_plural ' . escape_po( $entry['plural'] ) . "\n";
			$pot .= "msgstr[0] \"\"\nmsgstr[1] \"\"\n";
			continue;
		}

		$pot .= "msgstr \"\"\n";
	}

	return $pot;
}

/**
 * Quote a string for a PO file.
 *
 * @param string $value Raw value.
 * @return string
 */
function escape_po( string $value ): string {
	return '"' . str_replace(
		array( '\\', '"', "\n", "\t" ),
		array( '\\\\', '\"', '\n', '\t' ),
		$value
	) . '"';
}
