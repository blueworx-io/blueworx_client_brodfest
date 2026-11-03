<?php
/**
 * Removes this plugin's own options. Nothing else.
 *
 * The pages this plugin created are left alone: they are content a site owner
 * can see, and deleting content silently on uninstall is not this plugin's
 * decision to make.
 *
 * @package Brodfest
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'brodfest_page_ids' );
delete_option( 'brodfest_version' );
