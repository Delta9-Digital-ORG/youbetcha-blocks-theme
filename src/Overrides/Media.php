<?php

/**
 * Media overrides — disable hard cropping on auto-generated thumbnails.
 *
 * @package YouBetchaCannabisTheme\Overrides;
 */

declare(strict_types=1);

namespace YouBetchaCannabisTheme\Overrides;

use YouBetchaCannabisThemeVendor\EightshiftLibs\Services\ServiceInterface;

/**
 * Media class.
 */
class Media implements ServiceInterface
{
	/**
	 * Register all the hooks
	 *
	 * @return void
	 */
	public function register(): void
	{
		// WP core: force the 150x150 `thumbnail` size to scale proportionally
		// instead of hard-cropping to a square. Filters the stored option so
		// no DB write is needed.
		\add_filter('pre_option_thumbnail_crop', [$this, 'disable_thumbnail_crop']);

		// Belt-and-braces: strip the `crop` flag from the `thumbnail` size at
		// generation time, in case anything else has registered a hard crop.
		\add_filter('intermediate_image_sizes_advanced', [$this, 'soften_thumbnail_size']);

		// WooCommerce: keep the product thumbnail uncropped (matches the
		// "Uncropped" choice in Customizer → WooCommerce → Product Images).
		\add_filter('woocommerce_get_image_size_thumbnail', [$this, 'soften_woocommerce_thumbnail']);
	}

	/**
	 * Return 0 for the `thumbnail_crop` option so WP scales rather than crops.
	 *
	 * @return int
	 */
	public function disable_thumbnail_crop(): int
	{
		return 0;
	}

	/**
	 * Force `crop => false` on the `thumbnail` size for newly uploaded images.
	 *
	 * @param array $sizes Image sizes about to be generated.
	 *
	 * @return array
	 */
	public function soften_thumbnail_size($sizes): array
	{
		if (isset($sizes['thumbnail'])) {
			$sizes['thumbnail']['crop'] = false;
		}

		return $sizes;
	}

	/**
	 * Force WooCommerce's `thumbnail` image size to be uncropped.
	 *
	 * @param array $size Size config (width/height/crop).
	 *
	 * @return array
	 */
	public function soften_woocommerce_thumbnail($size): array
	{
		$size['crop'] = 0;

		return $size;
	}
}
