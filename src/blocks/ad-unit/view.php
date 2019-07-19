<?php
/**
 * Server-side rendering of the `newspack-ads/ad-unit` block.
 *
 * @package WordPress
 */

/**
 * Renders the `newspack-ads/ad-unit` block on server.
 *
 * @param array $attributes The block attributes.
 *
 * @return string Returns the post content with latest posts added.
 */
function newspack_ads_render_block_ad_unit( $attributes ) {
	$active_ad = isset( $attributes['activeAd'] ) ? (int) $attributes['activeAd'] : 0;
	if ( 1 > $active_ad ) {
		return '';
	}

	$classes = Newspack_Ads_Blocks::block_classes( 'wp-block-newspack-ads-blocks-ad-unit', $attributes );

	$ad_unit = Newspack_Ads_Model::get_ad_unit( $active_ad );
	$content = sprintf(
		'<div class="%s">%s</div>',
		esc_attr( $classes ),
		$ad_unit['code'] /* TODO: escape with wp_kses() */
	);

	// Detect a GAM ad and add to header code.
	$header_code = newspack_generate_gam_header_code_for_ad_unit( $ad_unit['code'] );
	if ( $header_code ) {
		$content .= '<!-- ' . $header_code . ' -->';
	}

	Newspack_Ads_Blocks::enqueue_view_assets( 'ad-unit' );

	return $content;
}

/**
 * Registers the `newspack-ads/ad-unit` block on server.
 */
function newspack_ads_register_ad_unit() {
	register_block_type(
		'newspack-ads/ad-unit',
		array(
			'attributes'      => array(
				'activeAd' => array(
					'type' => 'integer',
				),
			),
			'render_callback' => 'newspack_ads_render_block_ad_unit',
		)
	);
}
add_action( 'init', 'newspack_ads_register_ad_unit' );

/**
 * Generates the ad-specific head code.
 *
 * @param  string $ad_code The specific ad unit code.
 * @return string          The HEAD code.
 */
function newspack_generate_gam_header_code_for_ad_unit( $ad_code ) {
	$ad_unit_name       = '';
	$ad_unit_dimensions = '';
	$ad_unit_gpt_id     = '';

	// Find the ad unit name.
	preg_match(
		'/--\s(\/[0-9]+\/[a-zA-Z_-]+)\s--/',
		$ad_code,
		$ad_unit_name
	);
	if ( ! isset( $ad_unit_name[1] ) ) {
		return false;
	}

	// Find the ad unit dimensions.
	preg_match(
		'/width: ([0-9]+)px; height: ([0-9]+)px/',
		$ad_code,
		$ad_unit_dimensions
	);
	if ( ! isset( $ad_unit_dimensions[1] ) ) {
		return false;
	}

	// Find the ad unit GPT thingy.
	preg_match(
		'/div-gpt-ad-([0-9]+-[0-9]+)/',
		$ad_code,
		$ad_unit_gpt_id
	);
	if ( ! isset( $ad_unit_gpt_id[1] ) ) {
		return false;
	}

	// Construct the ad-specific header code.
	$header_code = sprintf(
		"googletag.defineSlot('%s', [%d, %d], 'div-gpt-ad-%s').addService(googletag.pubads());",
		$ad_unit_name[1], // Ad unit code, as defined in GAM.
		$ad_unit_dimensions[1], // Width.
		$ad_unit_dimensions[2], // Height.
		$ad_unit_gpt_id[1] // The GPT ID.
	);

	return $header_code;

}
