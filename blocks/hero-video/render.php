<?php
/**
 * The home video, drawn on the server: the video, its sound button and the
 * replay button. Figma: Media/Hero Video (Site × Size).
 *
 * On a first visit the opening's Enter button starts it with sound (no
 * browser allows sound without a click); on later visits it plays by itself
 * with sound where the browser allows, or else waits for the play button: it
 * never starts muted (client, 8 Nov 2026). Either way it plays here, in its
 * place on the page (intro.js). Once it ends, a play button offers a replay
 * with sound.
 *
 * @package Brodfest
 *
 * @var array $attributes The block's attributes.
 */

defined( 'ABSPATH' ) || exit;

$brodfest_videos = brodfest_hero_videos( brodfest_list( $attributes, 'videos' ) );
$brodfest_play   = brodfest_attr( $attributes, 'playLabel', __( 'Play the video', 'blueworx-client-brodfest' ) );
$brodfest_sound  = brodfest_attr( $attributes, 'soundLabel', __( 'Sound', 'blueworx-client-brodfest' ) );
?>
<section class="bf-hero" data-bf-hero>
	<?php if ( array() !== $brodfest_videos ) : ?>
		<video class="bf-hero__video" playsinline preload="auto" data-bf-video>
			<?php foreach ( $brodfest_videos as $brodfest_video ) : ?>
				<source src="<?php echo esc_url( $brodfest_video['src'] ); ?>" type="video/mp4"<?php echo '' !== $brodfest_video['media'] ? ' media="' . esc_attr( $brodfest_video['media'] ) . '"' : ''; ?> />
			<?php endforeach; ?>
		</video>
		<button class="bf-hero__play" type="button" hidden data-bf-play>
			<span class="sr-only"><?php echo esc_html( $brodfest_play ); ?></span>
		</button>
		<button class="bf-hero__sound" type="button" hidden aria-pressed="false" data-bf-sound>
			<span class="sr-only"><?php echo esc_html( $brodfest_sound ); ?></span>
			<svg class="bf-hero__sound-off" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5" /><line x1="22" x2="16" y1="9" y2="15" /><line x1="16" x2="22" y1="9" y2="15" /></svg>
			<svg class="bf-hero__sound-on" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5" /><path d="M15.54 8.46a5 5 0 0 1 0 7.07" /><path d="M19.07 4.93a10 10 0 0 1 0 14.14" /></svg>
		</button>
	<?php else : ?>
		<p class="bf-hero__placeholder"><?php echo esc_html( brodfest_attr( $attributes, 'placeholder', __( 'Video to come', 'blueworx-client-brodfest' ) ) ); ?></p>
	<?php endif; ?>
</section>
