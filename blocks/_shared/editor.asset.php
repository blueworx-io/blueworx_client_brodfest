<?php
/**
 * What editor.js needs loaded first. Hand-written because there is no build
 * step for the blocks' editor scripts.
 *
 * @package Brodfest
 */

return array(
	'dependencies' => array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n' ),
	'version'      => '1.0.0',
);
