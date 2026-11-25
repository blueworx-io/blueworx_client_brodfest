<?php
/**
 * One group of recommendations (beach clubs, restaurants…), drawn on the
 * server: its name and a row of places. Without places, only the name shows.
 *
 * @package Brodfest
 *
 * @var array $attributes The block's attributes.
 */

defined( 'ABSPATH' ) || exit;

$brodfest_name  = brodfest_attr( $attributes, 'name' );
$brodfest_level = max( 2, min( 5, (int) ( $attributes['headingLevel'] ?? 2 ) ) );

if ( '' === $brodfest_name ) {
	return;
}
?>
<section class="bf-category">
	<h<?php echo (int) $brodfest_level; ?> class="bf-area__name"><?php echo esc_html( $brodfest_name ); ?></h<?php echo (int) $brodfest_level; ?>>
	<?php
	brodfest_print_cards(
		brodfest_list( $attributes, 'places' ),
		array(
			'label' => $brodfest_name,
			'level' => $brodfest_level + 1,
			'book'  => brodfest_attr( $attributes, 'bookLabel' ),
		)
	);
	?>
</section>
