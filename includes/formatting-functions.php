<?php
/**
 * GeoDirectory Formatting
 *
 * Functions for formatting data.
 *
 * @author 		AyeCode
 * @category 	Core
 * @package 	GeoDirectory/Functions
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


/**
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 * @param string|array $var
 * @return string|array
 */
function geodir_clean( $var ) {
	if ( is_array( $var ) ) {
		return array_map( 'geodir_clean', $var );
	} else {
		return is_scalar( $var ) ? geodir_sanitize_text_field( $var ) : $var;
	}
}

/**
 * Emulate the WP native sanitize_text_field function in a %%variable%% safe way.
 *
 * @see   https://core.trac.wordpress.org/browser/trunk/src/wp-includes/formatting.php for the original
 *
 * Sanitize a string from user input or from the db.
 *
 * - Check for invalid UTF-8,
 * - Convert single < characters to entity,
 * - Strip all tags,
 * - Remove line breaks, tabs and extra white space,
 * - Strip octets - BUT DO NOT REMOVE (part of) VARIABLES WHICH WILL BE REPLACED.
 *
 * @static
 *
 * @since 2.0.0
 *
 * @param string $value String value to sanitize.
 *
 * @return string
 */
function geodir_sanitize_text_field( $value ) {
	$filtered = wp_check_invalid_utf8( $value );

	if ( strpos( $filtered, '<' ) !== false ) {
		$filtered = wp_pre_kses_less_than( $filtered );
		// This will strip extra whitespace for us.
		$filtered = wp_strip_all_tags( $filtered, true );
	}
	else {
		$filtered = trim( preg_replace( '`[\r\n\t ]+`', ' ', $filtered ) );
	}

	$found = false;
	while ( preg_match( '`[^%](%[a-f0-9]{2})`i', $filtered, $match ) ) {
		$filtered = str_replace( $match[1], '', $filtered );
		$found    = true;
	}
	unset( $match );

	if ( $found ) {
		// Strip out the whitespace that may now exist after removing the octets.
		$filtered = trim( preg_replace( '` +`', ' ', $filtered ) );
	}

	/**
	 * Filter a sanitized text field string.
	 *
	 * @since WP 2.9.0
	 *
	 * @param string $filtered The sanitized string.
	 * @param string $str      The string prior to being sanitized.
	 */

	return apply_filters( 'sanitize_text_field', $filtered, $value );
}

/**
 * Clean variables.
 *
 * This function is used to create posttype, posts, taxonomy and terms slug.
 *
 * @since   1.0.0
 * @package GeoDirectory
 *
 * @param string $string The variable to clean.
 *
 * @return string Cleaned variable.
 */
function geodir_clean_slug( $string ) {

	$string = trim( strip_tags( stripslashes( $string ) ) );
	$string = str_replace( " ", "-", $string ); // Replaces all spaces with hyphens.
	$string = preg_replace( '/[^A-Za-z0-9\-\_]/', '', $string ); // Removes special chars.
	$string = preg_replace( '/-+/', '-', $string ); // Replaces multiple hyphens with single one.

	return $string;
}

/**
 * Sanitize a string destined to be a tooltip.
 *
 * @since 2.0.0 Tooltips are encoded with htmlspecialchars to prevent XSS. Should not be used in conjunction with esc_attr()
 * @param string $var
 * @return string
 */
function geodir_sanitize_tooltip( $var ) {
	return htmlspecialchars( wp_kses( html_entity_decode( $var ), array(
		'br'     => array(),
		'em'     => array(),
		'strong' => array(),
		'small'  => array(),
		'span'   => array(),
		'ul'     => array(),
		'li'     => array(),
		'ol'     => array(),
		'p'      => array(),
	) ) );
}

/**
 * Return the formatted date.
 *
 * Return a formatted date from a date/time string according to WordPress date format. $date must be in format : 'Y-m-d
 * H:i:s'.
 *
 * @since   1.0.0
 * @package GeoDirectory
 *
 * @param string $date must be in format: 'Y-m-d H:i:s'.
 *
 * @return bool|int|string the formatted date.
 */
function geodir_get_formated_date( $date ) {
	return mysql2date( get_option( 'date_format' ), $date );
}

/**
 * Return the formatted time.
 *
 * Return a formatted time from a date/time string according to WordPress time format. $time must be in format : 'Y-m-d
 * H:i:s'.
 *
 * @since   1.0.0
 * @package GeoDirectory
 *
 * @param string $time must be in format: 'Y-m-d H:i:s'.
 *
 * @return bool|int|string the formatted time.
 */
function geodir_get_formated_time( $time ) {
	return mysql2date( get_option( 'time_format' ), $time, $translate = true );
}

/**
 * GeoDirectory Date Format.
 *
 * @since 2.0.0
 *
 * @return string
 */
function geodir_date_format() {
	$date_format = get_option( 'date_format' );
	if ( empty( $date_format ) ) {
		$date_format = 'F j, Y';
	} 
	return apply_filters( 'geodir_date_format', $date_format );
}

/**
 * GeoDirectory Time Format.
 *
 * @since 2.0.0
 *
 * @return string
 */
function geodir_time_format() {
	$time_format = get_option( 'time_format' );
	if ( empty( $time_format ) ) {
		$time_format = 'g:i a';
	}
	return apply_filters( 'geodir_time_format', $time_format );
}

/**
 * GeoDirectory Date Time Format.
 *
 * @since 2.0.0
 *
 * @return string
 */
function geodir_date_time_format() {
	$date_time_format = geodir_date_format() . ' ' . geodir_time_format();
	return apply_filters( 'geodir_date_time_format', $date_time_format, $sep );
}

/**
 * let_to_num function.
 *
 * This function transforms the php.ini notation for numbers (like '2M') to an integer.
 *
 * @since 2.0.0
 * @param $size
 * @return int
 */
function geodir_let_to_num( $size ) {
	$l   = substr( $size, -1 );
	$ret = substr( $size, 0, -1 );
	switch ( strtoupper( $l ) ) {
		case 'P':
			$ret *= 1024;
		case 'T':
			$ret *= 1024;
		case 'G':
			$ret *= 1024;
		case 'M':
			$ret *= 1024;
		case 'K':
			$ret *= 1024;
	}
	return $ret;
}

/**
 * Return the thousand separator for prices.
 * @since  2.0.0
 * @return string
 */
function geodir_get_price_thousand_separator() {
	$separator = apply_filters( 'geodir_get_price_thousand_separator', ',' );
	return stripslashes( $separator );
}

/**
 * Return the decimal separator for prices.
 * @since  2.0.0
 * @return string
 */
function geodir_get_price_decimal_separator() {
	$separator = apply_filters( 'geodir_get_price_decimal_separator', '.' );
	return $separator ? stripslashes( $separator ) : '.';
}

/**
 * Return the number of decimals after the decimal point.
 * @since  2.0.0
 * @return int
 */
function geodir_get_price_decimals() {
	$decimals = apply_filters( 'geodir_get_price_decimals', 2 );
	return absint( $decimals );
}

/**
 * Get rounding precision for internal GD calculations.
 * Will increase the precision of geodir_get_price_decimals by 2 decimals, unless GEODIR_ROUNDING_PRECISION is set to a higher number.
 *
 * @since 2.0.0
 * @return int
 */
function geodir_get_rounding_precision() {
	$precision = geodir_get_price_decimals() + 2;
	if ( absint( GEODIR_ROUNDING_PRECISION ) > $precision ) {
		$precision = absint( GEODIR_ROUNDING_PRECISION );
	}
	return $precision;
}

/**
 * Format decimal numbers ready for DB storage.
 *
 * Sanitize, remove decimals, and optionally round + trim off zeros.
 *
 * This function does not remove thousands - this should be done before passing a value to the function.
 *
 * @since 2.0.0
 *
 * @param  float|string $number Expects either a float or a string with a decimal separator only (no thousands)
 * @param  mixed $dp number of decimal points to use, blank to use geodir_get_price_decimals, or false to avoid all rounding.
 * @param  bool $trim_zeros from end of string
 * @return string
 */
function geodir_format_decimal( $number, $dp = false, $trim_zeros = false ) {
	$locale   = localeconv();
	$decimals = array( geodir_get_price_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point'] );

	// Remove locale from string.
	if ( ! is_float( $number ) ) {
		$number = str_replace( $decimals, '.', $number );
		$number = preg_replace( '/[^0-9\.,-]/', '', geodir_clean( $number ) );
	}

	if ( false !== $dp ) {
		$dp     = intval( '' == $dp ? geodir_get_price_decimals() : $dp );
		$number = number_format( floatval( $number ), $dp, '.', '' );

	// DP is false - don't use number format, just return a string in our format
	} elseif ( is_float( $number ) ) {
		// DP is false - don't use number format, just return a string using whatever is given. Remove scientific notation using sprintf.
		$number     = str_replace( $decimals, '.', sprintf( '%.' . geodir_get_rounding_precision() . 'f', $number ) );
		// We already had a float, so trailing zeros are not needed.
		$trim_zeros = true;
	}

	if ( $trim_zeros && strstr( $number, '.' ) ) {
		$number = rtrim( rtrim( $number, '0' ), '.' );
	}

	return $number;
}


/**
 * Retrieve the timezone string for a site until.
 *
 * @since 2.0.0
 * @return string PHP timezone string for the site
 */
function geodir_timezone_string() {

	// if site timezone string exists, return it
	if ( $timezone = get_option( 'timezone_string' ) ) {
		return $timezone;
	}

	// get UTC offset, if it isn't set then return UTC
	if ( 0 === ( $utc_offset = intval( get_option( 'gmt_offset', 0 ) ) ) ) {
		return 'UTC';
	}

	// adjust UTC offset from hours to seconds
	$utc_offset *= 3600;

	// attempt to guess the timezone string from the UTC offset
	if ( $timezone = timezone_name_from_abbr( '', $utc_offset ) ) {
		return $timezone;
	}

	// last try, guess timezone string manually
	foreach ( timezone_abbreviations_list() as $abbr ) {
		foreach ( $abbr as $city ) {
			if ( (bool) date( 'I' ) === (bool) $city['dst'] && $city['timezone_id'] && intval( $city['offset'] ) === $utc_offset ) {
				return $city['timezone_id'];
			}
		}
	}

	// fallback to UTC
	return 'UTC';
}

/**
 * Get timezone offset in seconds.
 *
 * @since  2.0.0
 * @return float
 */
function geodir_timezone_offset() {
	if ( $timezone = get_option( 'timezone_string' ) ) {
		$timezone_object = new DateTimeZone( $timezone );
		return $timezone_object->getOffset( new DateTime( 'now' ) );
	} else {
		return floatval( get_option( 'gmt_offset', 0 ) ) * HOUR_IN_SECONDS;
	}
}