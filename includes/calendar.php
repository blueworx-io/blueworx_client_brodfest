<?php
/**
 * Add to Calendar: the event as a calendar file any calendar app opens.
 *
 * @package Brodfest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BRODFEST_CALENDAR_ARG = 'brodfest-calendar';

/**
 * The Add to Calendar link.
 *
 * @return string
 */
function brodfest_calendar_url() {
	return add_query_arg( BRODFEST_CALENDAR_ARG, '1', home_url( '/' ) );
}

/**
 * The event as an iCalendar file, starting at its local time. The start is
 * written in UTC so every calendar app places it right without a time-zone
 * table. No end time has been set, so none is given. Pure.
 *
 * @param array  $event From brodfest_event().
 * @param string $uid_host Host for the event's unique id.
 * @return string
 */
function brodfest_calendar_ics( $event, $uid_host ) {
	$escape = static function ( $text ) {
		return str_replace( array( '\\', ';', ',', "\n" ), array( '\\\\', '\;', '\,', '\n' ), (string) $text );
	};
	$local  = new DateTimeImmutable( $event['date'] . ' ' . $event['time'], new DateTimeZone( $event['timezone'] ) );
	$start  = $local->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Ymd\THis\Z' );

	return implode(
		"\r\n",
		array(
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//BlueWorx//Brodfest//EN',
			'CALSCALE:GREGORIAN',
			'BEGIN:VEVENT',
			'UID:brodfest-' . $local->format( 'Ymd' ) . '@' . $uid_host,
			'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
			'DTSTART:' . $start,
			'SUMMARY:' . $escape( $event['title'] ),
			'LOCATION:' . $escape( $event['location'] ),
			'DESCRIPTION:' . $escape( $event['description'] ),
			'END:VEVENT',
			'END:VCALENDAR',
			'',
		)
	);
}

/**
 * Serves the calendar file at ?brodfest-calendar=1.
 *
 * @return void
 */
function brodfest_serve_calendar() {
	// A public download that changes nothing, so no nonce.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( empty( $_GET[ BRODFEST_CALENDAR_ARG ] ) ) {
		return;
	}

	nocache_headers();
	header( 'Content-Type: text/calendar; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="brodfest.ics"' );
	echo brodfest_calendar_ics( brodfest_event(), (string) wp_parse_url( home_url(), PHP_URL_HOST ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- A calendar file, not HTML; every value is escaped for iCalendar.
	exit;
}
add_action( 'template_redirect', 'brodfest_serve_calendar', 1 );
