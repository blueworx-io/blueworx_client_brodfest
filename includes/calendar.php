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
 * The event as an iCalendar file, starting at its local time. The start and
 * end are written in UTC so every calendar app places them right without a
 * time-zone table. Pure.
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
			'DTEND:' . brodfest_calendar_end( $event )->format( 'Ymd\THis\Z' ),
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
 * The event's start, in UTC. Pure.
 *
 * @param array $event From brodfest_event().
 * @return DateTimeImmutable
 */
function brodfest_calendar_start( $event ) {
	$local = new DateTimeImmutable( $event['date'] . ' ' . $event['time'], new DateTimeZone( $event['timezone'] ) );

	return $local->setTimezone( new DateTimeZone( 'UTC' ) );
}

/**
 * The event's end, in UTC. Pure.
 *
 * @param array $event From brodfest_event().
 * @return DateTimeImmutable
 */
function brodfest_calendar_end( $event ) {
	$local = new DateTimeImmutable( $event['end'], new DateTimeZone( $event['timezone'] ) );

	return $local->setTimezone( new DateTimeZone( 'UTC' ) );
}

/**
 * The calendars Add to Calendar offers (client, 6 Nov 2026): Apple, Google
 * and Outlook, each opening its calendar with the event filled in, nothing
 * downloaded (client, 8 Nov 2026). Apple is the calendar file at its plain
 * web address, which Safari hands to Calendar as one event to add. It is not
 * a webcal address: Calendar treats those as a calendar to subscribe to
 * (Luke, 9 Nov 2026). Google
 * and Outlook go through their sign-in, which a signed-in visitor passes
 * straight through: opened directly while signed out, both calendars dropped
 * the event and showed a marketing page instead (Mark, 8 Nov 2026). Outlook
 * is the Microsoft 365 address, which also takes personal accounts. Pure.
 *
 * @param array  $event From brodfest_event().
 * @param string $file  The calendar file's URL.
 * @return array<string, string> Calendar name => URL.
 */
function brodfest_calendar_links( $event, $file ) {
	$start = brodfest_calendar_start( $event );
	$end   = brodfest_calendar_end( $event );

	return array(
		'Apple'   => $file,
		'Google'  => 'https://accounts.google.com/ServiceLogin?' . http_build_query(
			array(
				'continue' => 'https://calendar.google.com/calendar/render?' . http_build_query(
					array(
						'action'   => 'TEMPLATE',
						'text'     => $event['title'],
						'dates'    => $start->format( 'Ymd\THis\Z' ) . '/' . $end->format( 'Ymd\THis\Z' ),
						'details'  => $event['description'],
						'location' => $event['location'],
					),
					'',
					'&',
					PHP_QUERY_RFC3986
				),
			),
			'',
			'&',
			PHP_QUERY_RFC3986
		),
		'Outlook' => 'https://outlook.office.com/calendar/0/deeplink/compose?' . http_build_query(
			array(
				'path'     => '/calendar/action/compose',
				'rru'      => 'addevent',
				'subject'  => $event['title'],
				'startdt'  => $start->format( 'Y-m-d\TH:i:s\Z' ),
				'enddt'    => $end->format( 'Y-m-d\TH:i:s\Z' ),
				'body'     => $event['description'],
				'location' => $event['location'],
			),
			'',
			'&',
			PHP_QUERY_RFC3986
		),
	);
}

/**
 * Serves the calendar file at ?brodfest-calendar=1. Inline, not as a
 * download: a browser that knows calendar files (Safari) then opens it in
 * Calendar rather than saving it (client, 8 Nov 2026).
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
	header( 'Content-Disposition: inline; filename="brodfest.ics"' );
	echo brodfest_calendar_ics( brodfest_event(), (string) wp_parse_url( home_url(), PHP_URL_HOST ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- A calendar file, not HTML; every value is escaped for iCalendar.
	exit;
}
add_action( 'template_redirect', 'brodfest_serve_calendar', 1 );

/**
 * Whether the home page shows the Add to Calendar button. Hidden for now
 * (Luke, 9 Nov 2026): the line under it moves up into its place. The calendar
 * file itself still works at its address. Turn the button back on here, and
 * un-skip its tests, when it is wanted again.
 *
 * @return bool
 */
function brodfest_show_calendar() {
	return (bool) apply_filters( 'brodfest_show_calendar', false );
}
