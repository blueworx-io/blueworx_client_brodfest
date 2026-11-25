<?php
/**
 * Test site only: makes a switch of the live site go wrong on purpose, and
 * watches what a reader on another database connection can see meanwhile.
 *
 * tests/helpers/switch.js copies this into the test site's mu-plugins folder for
 * the length of a test, and says which fault to make in switch-fault.json. It is
 * never part of the plugin.
 *
 * @package Brodfest
 */

const BRODFEST_FAULT_FILE  = WP_CONTENT_DIR . '/switch-fault.json';
const BRODFEST_REPORT_FILE = WP_CONTENT_DIR . '/switch-report.json';

/** One part of the fault asked for, or null. */
function brodfest_fault( $key ) {
	static $fault = null;

	if ( null === $fault ) {
		$fault = file_exists( BRODFEST_FAULT_FILE ) ? (array) json_decode( (string) file_get_contents( BRODFEST_FAULT_FILE ), true ) : array();
	}

	return $fault[ $key ] ?? null;
}

/** Keeps something for the test to read. */
function brodfest_report( $key, $value ) {
	$report         = file_exists( BRODFEST_REPORT_FILE ) ? (array) json_decode( (string) file_get_contents( BRODFEST_REPORT_FILE ), true ) : array();
	$report[ $key ] = array_merge( $report[ $key ] ?? array(), array( $value ) );
	file_put_contents( BRODFEST_REPORT_FILE, json_encode( $report ) );
}

/** A second connection to the same database: another visitor's view of it. */
function brodfest_second_connection() {
	static $pdo = null;

	if ( null === $pdo ) {
		$pdo = new PDO( 'sqlite:' . FQDB );
		$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	}

	return $pdo;
}

/** Notes any published page that holds Invite words while the Invite is not live. */
function brodfest_watch( $when ) {
	global $wpdb;

	$pdo  = brodfest_second_connection();
	$live = $pdo->query( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'brodfest_synced_site'" )->fetchColumn();
	$seen = $pdo->query( "SELECT post_name FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_content LIKE '%Lorem ipsum%'" )->fetchAll( PDO::FETCH_COLUMN );

	brodfest_report( 'looks', substr( $when, 0, 60 ) );

	if ( 'invite' !== $live && $seen ) {
		brodfest_report( 'early', implode( ',', $seen ) . ' before ' . substr( $when, 0, 60 ) );
	}
}

/** Whether a switch's transaction is open: between its start and its end. */
function brodfest_in_switch( $query ) {
	static $open = false;

	if ( preg_match( '/^\s*START TRANSACTION/i', $query ) ) {
		$open = true;
	} elseif ( preg_match( '/^\s*(COMMIT|ROLLBACK)/i', $query ) ) {
		$open = false;
	}

	return $open;
}

add_filter( 'query', static function ( $query ) {
	brodfest_in_switch( $query );

	return $query;
}, 1 );

// What a page cache or an SEO plugin is told.
add_action(
	'save_post',
	static function ( $post_id, $post ) {
		if ( brodfest_fault( 'noteSaves' ) && in_array( $post->post_type, array( 'page', 'brodfest_held_page' ), true ) ) {
			brodfest_report( 'saves', (int) $post_id );
		}
	},
	10,
	2
);

add_filter(
	'query',
	static function ( $query ) {
		global $wpdb;
		static $busy   = false;
		static $waited = false;

		if ( $busy ) {
			return $query;
		}

		$busy  = true;
		$write = preg_match( '/^\s*(UPDATE|INSERT|DELETE|COMMIT|ROLLBACK)/i', $query );

		if ( brodfest_fault( 'watch' ) && $write ) {
			brodfest_watch( $query );
		}

		// A switch by someone else that finishes while this request waits for the lock.
		if ( brodfest_fault( 'switchedWhileWaiting' ) && ! $waited && false !== strpos( $query, 'brodfest_sync_lock' ) && preg_match( '/^\s*INSERT/i', $query ) ) {
			$waited = true;
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = meta_value + 1 WHERE meta_key = '_brodfest_swap_gen' AND post_id = %d", brodfest_fault( 'switchedWhileWaiting' ) ) );
		}

		// An autosave written over, for the other site, while this request waits for the lock.
		$replaced = brodfest_fault( 'autosaveReplaced' );

		if ( $replaced && ! $waited && false !== strpos( $query, 'brodfest_sync_lock' ) && preg_match( '/^\s*INSERT/i', $query ) ) {
			$waited = true;
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_content = %s WHERE ID = %d", $replaced['content'], $replaced['id'] ) );
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = '_brodfest_content_site' AND post_id = %d", $replaced['site'], $replaced['id'] ) );
		}

		// Somebody takes the lock over just as a slow switch checks it still has it.
		if ( brodfest_fault( 'lockTakenOver' ) && brodfest_in_switch( $query ) && false !== strpos( $query, 'brodfest_sync_lock' ) && preg_match( '/^\s*SELECT/i', $query ) ) {
			brodfest_report( 'lockRead', $query );
			$wpdb->query( "UPDATE {$wpdb->options} SET option_value = '1:somebody-else' WHERE option_name = 'brodfest_sync_lock'" );
		}

		// The request ends the moment the switch is committed, before anything else is done.
		if ( brodfest_fault( 'crashAfterCommit' ) && preg_match( '/^\s*COMMIT/i', $query ) ) {
			$wpdb->query( $query );
			exit;
		}

		$hit = brodfest_fault( 'onPostWrite' );

		if ( $hit && preg_match( '/^\s*UPDATE\s+`?' . $wpdb->posts . '`?/i', $query ) && false !== strpos( $query, $hit ) ) {
			if ( brodfest_fault( 'crash' ) ) {
				exit;
			}

			$query = '';
		}

		$busy = false;

		return $query;
	}
);

if ( brodfest_fault( 'noRetry' ) ) {
	// The retry delay has not passed.
	add_filter( 'pre_transient_brodfest_sync_retry', '__return_true' );
}

if ( brodfest_fault( 'rewriteOnSave' ) ) {
	// A plugin that tidies content as it is saved.
	add_filter(
		'wp_insert_post_data',
		static function ( $data ) {
			if ( in_array( $data['post_type'], array( 'page', 'brodfest_held_page' ), true ) ) {
				$data['post_content'] .= "\n";
			}

			return $data;
		}
	);
}

add_action(
	'wp_restore_post_revision',
	static function () {
		global $wpdb;

		brodfest_report( 'lockDuringRestore', (bool) $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'brodfest_sync_lock'" ) );
	}
);

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'brodfest-test/v1',
			'/do',
			array(
				'methods'             => 'POST',
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
				'callback'            => static function ( $request ) {
					global $wpdb;

					$value = $request['value'];

					switch ( $request['what'] ) {
						case 'expire':
							// As if the retry delay had passed.
							delete_transient( 'brodfest_sync_retry' );
							delete_transient( 'brodfest_upgrade_lock' );
							break;
						case 'site':
							update_option( 'brodfest_settings', array_merge( (array) get_option( 'brodfest_settings', array() ), array( 'site' => $value ) ) );
							break;
						case 'lock':
							// Somebody else's switch is running.
							$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES ('brodfest_sync_lock', %s, 'no')", time() . ':somebody-else' ) );
							break;
						case 'unlock':
							$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name = 'brodfest_sync_lock'" );
							break;
						case 'version':
							update_option( 'brodfest_version', $value );
							break;
						case 'neverSynced':
							delete_option( 'brodfest_synced_site' );
							break;
						case 'alterOnWrite':
							// The database stores something other than what it was given.
							$pdo = brodfest_second_connection();
							$pdo->exec( 'DROP TRIGGER IF EXISTS brodfest_alter' );

							if ( $value ) {
								$pdo->exec( "CREATE TRIGGER brodfest_alter AFTER UPDATE OF post_content ON {$wpdb->posts} WHEN NEW.ID = " . (int) $value . " AND NEW.post_content LIKE '%Lorem ipsum%' AND NEW.post_content NOT LIKE '%ALTERED%' BEGIN UPDATE {$wpdb->posts} SET post_content = NEW.post_content || 'ALTERED' WHERE ID = NEW.ID; END" );
							}
							break;
						case 'restoreNonce':
							return wp_create_nonce( 'restore-post_' . (int) $value );
					}

					// What is stored, whatever any cache says.
					return array(
						'posts'   => $wpdb->get_results( "SELECT ID, post_type, post_status, post_title, post_content FROM {$wpdb->posts} WHERE post_type IN ('page', 'brodfest_held_page') ORDER BY ID" ),
						'synced'  => $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'brodfest_synced_site'" ),
						'version' => $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'brodfest_version'" ),
						'lock'    => $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'brodfest_sync_lock'" ),
					);
				},
			)
		);
	}
);
