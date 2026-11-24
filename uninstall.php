<?php
/**
 * Removes this plugin's own options. Nothing else.
 *
 * The pages this plugin created, and the held-back pages that keep the other
 * site's blocks, are left alone: they are content a site owner can see, and
 * deleting content silently on uninstall is not this plugin's decision to make.
 *
 * @package Brodfest
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'brodfest_page_ids' );
delete_option( 'brodfest_version' );
delete_option( 'brodfest_settings' );
delete_option( 'brodfest_guests_carried_over' );
delete_option( 'brodfest_synced_site' );
delete_transient( 'brodfest_sync_lock' );
delete_transient( 'brodfest_upgrade_lock' );
