<?php
/**
 * Home hero: the video, the first-visit intro and the replay button.
 *
 * Figma: Media/Hero Video (Site × Size). On a visitor's first arrival the intro
 * (loader.js) plays and ends on an Enter button — that click is what lets the
 * browser play the video with sound. After that the video never starts by
 * itself again; a play button offers a replay instead.
 *
 * @package Brodfest
 *
 * @var array $args {
 *     @type array $videos Sources from brodfest_hero_videos(), or none while
 *                         the video is still to come.
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$brodfest_videos = isset( $args['videos'] ) ? (array) $args['videos'] : array();
?>
<section class="bf-hero" data-bf-hero>
	<?php if ( array() !== $brodfest_videos ) : ?>
		<video class="bf-hero__video" playsinline preload="auto" data-bf-video>
			<?php foreach ( $brodfest_videos as $brodfest_video ) : ?>
				<source src="<?php echo esc_url( $brodfest_video['src'] ); ?>" type="video/mp4"<?php echo '' !== $brodfest_video['media'] ? ' media="' . esc_attr( $brodfest_video['media'] ) . '"' : ''; ?> />
			<?php endforeach; ?>
		</video>
		<button class="bf-hero__play" type="button" hidden data-bf-play>
			<span class="sr-only"><?php esc_html_e( 'Play the video', 'blueworx-client-brodfest' ); ?></span>
		</button>
	<?php else : ?>
		<p class="bf-hero__placeholder"><?php esc_html_e( 'Video to come', 'blueworx-client-brodfest' ); ?></p>
	<?php endif; ?>
</section>

<template data-bf-intro>
	<div class="bf-intro" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Welcome to Brodfest', 'blueworx-client-brodfest' ); ?>">
		<div class="bf-intro__mark">
			<span class="bf-intro__mountain"><?php brodfest_inline_svg( 'mountain' ); ?></span>
			<span class="bf-intro__logo"><?php brodfest_inline_svg( 'logo', 'Brodfest Ibiza' ); ?></span>
		</div>
		<button class="bf-intro__enter bf-pill" type="button" data-bf-enter><?php esc_html_e( 'ENTER', 'blueworx-client-brodfest' ); ?></button>
	</div>
</template>
