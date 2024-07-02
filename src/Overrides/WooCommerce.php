<?php

/**
 * The file that is an WooCommerce class.
 *
 * @package YouBetchaCannabisTheme\Overrides;
 */

declare(strict_types=1);

namespace YouBetchaCannabisTheme\Overrides;

use YouBetchaCannabisThemeVendor\EightshiftLibs\Services\ServiceInterface;

/**
 * WooCommerce class.
 */
class WooCommerce implements ServiceInterface
{
	
	/**
	 * Register all the hooks
	 *
	 * @return void
	 */
	public function register(): void
	{
		\add_filter('woocommerce_register_post_type_product', [$this, 'restored_reset_product_template']);
		\add_filter('use_block_editor_for_post_type', [$this, 'restored_activate_gutenberg_product'], 10, 2);
		\add_filter('woocommerce_taxonomy_args_product_cat', [$this, 'restored_enable_taxonomy_rest']);
	}
	/**
	 * Disable new WooCommerce product template (from Version 7.7.0)
	 *
	 * @param array $post_type_args Post type arguments.
	 *
	 * @return array
	 */
	public function restored_reset_product_template($post_type_args): array
	{
		if (\array_key_exists('template', $post_type_args)) {
			unset($post_type_args['template']);
		}

		return $post_type_args;
	}

	/**
	 * Enable Gutenberg editor for WooCommerce
	 *
	 * @param bool $can_edit Can edit.
	 * @param string $post_type Post type.
	 *
	 * @return bool
	 */
	public function restored_activate_gutenberg_product($can_edit, $post_type): bool
	{
		if ($post_type === 'product') {
			$can_edit = true;
		}

		return $can_edit;
	}

	/**
	 * Enable taxonomy fields for woocommerce with gutenberg on
	 *
	 * @param array $args Arguments.
	 *
	 * @return array
	 */
	public function restored_enable_taxonomy_rest($args): array
	{
		$args['show_in_rest'] = true;

		return $args;
	}
}
