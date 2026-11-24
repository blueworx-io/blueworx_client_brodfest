<?php
/**
 * What edit.js needs loaded first. Hand-written: the blocks' editor scripts
 * have no build step.
 *
 * @package Brodfest
 */

return array(
	'dependencies' => array( 'brodfest-block-editor', 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n' ),
	'version'      => '1.0.0',
);
