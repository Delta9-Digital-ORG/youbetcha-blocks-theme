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
		\add_action('woocommerce_product_options_general_product_data', [$this, 'custom_product_description_text_field']);
		\add_action('woocommerce_admin_process_product_object', [$this, 'custom_product_description_text_field_save'], 10, 1);
		\add_action('woocommerce_product_options_general_product_data', [$this, 'custom_product_ingredients_text_field']);
		\add_action('woocommerce_admin_process_product_object', [$this, 'custom_product_ingredients_text_field_save'], 10, 1);
		\add_action('woocommerce_product_options_general_product_data', [$this, 'custom_product_other_ingredients_text_field']);
		\add_action('woocommerce_admin_process_product_object', [$this, 'custom_product_other_ingredients_text_field_save'], 10, 1);
		\add_action('woocommerce_product_options_general_product_data', [$this, 'custom_product_cannafacts_select_field']);
		\add_action('woocommerce_admin_process_product_object', [$this, 'custom_product_cannafacts_select_field_save'], 10, 1);
		\add_action('woocommerce_product_options_general_product_data', [$this, 'custom_product_per_serving_text_field']);
		\add_action('woocommerce_admin_process_product_object', [$this, 'custom_product_per_serving_size_text_field_save'], 10, 1);
		\add_action('woocommerce_product_options_general_product_data', [$this, 'custom_product_serving_size_text_field']);
		\add_action('woocommerce_admin_process_product_object', [$this, 'custom_product_serving_size_text_field_save'], 10, 1);
		\add_action('woocommerce_product_options_general_product_data', [$this, 'custom_product_servings_per_container_text_field']);
		\add_action('woocommerce_admin_process_product_object', [$this, 'custom_product_servings_per_container_text_field_save'], 10, 1);
		\add_action('woocommerce_product_options_general_product_data', [$this, 'custom_product_suggested_use_text_field']);
		\add_action('woocommerce_admin_process_product_object', [$this, 'custom_product_suggested_use_text_field_save'], 10, 1);
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
	
	// Hook to add product description to general data
	function custom_product_description_text_field() {
		woocommerce_wp_textarea_input(
			array(
				'id'		  => '_custom_product_description_text_field',
				'label'		  => __('Description', 'woocommerce'),
				'placeholder' => __('Enter description here', 'woocommerce'),
				'desc_tip'	  => 'true',
				'description' => __('Product Description.', 'woocommerce'),
			)
		);
	}

	// Hook to save product description to general data
	function custom_product_description_text_field_save($product) {
		$custom_field_value = isset($_POST['_custom_product_description_text_field']) ? $_POST['_custom_product_description_text_field'] : '';
		$product->update_meta_data('_custom_product_description_text_field', sanitize_text_field($custom_field_value));
	}
	
	// Hook to add product ingredients to general data
	function custom_product_ingredients_text_field() {
		woocommerce_wp_textarea_input(
			array(
				'id'		  => '_custom_product_ingredients_text_field',
				'label'		  => __('Ingredients', 'woocommerce'),
				'placeholder' => __('Enter ingredients here', 'woocommerce'),
				'desc_tip'	  => 'true',
				'description' => __('Product Ingredients.', 'woocommerce'),
			)
		);
	}

	// Hook to save product ingredients to general data
	function custom_product_ingredients_text_field_save($product) {
		$custom_field_value = isset($_POST['_custom_product_ingredients_text_field']) ? $_POST['_custom_product_ingredients_text_field'] : '';
		$product->update_meta_data('_custom_product_ingredients_text_field', sanitize_text_field($custom_field_value));
	}
	
	// Hook to add product other ingredients to general data
	function custom_product_other_ingredients_text_field() {
		woocommerce_wp_text_input(
			array(
				'id'		  => '_custom_product_other_ingredients_text_field',
				'label'		  => __('Other Ingredients', 'woocommerce'),
				'placeholder' => __('Enter other ingredients here', 'woocommerce'),
				'desc_tip'	  => 'true',
				'description' => __('Product Other Ingredients.', 'woocommerce'),
			)
		);
	}

	// Hook to save product other ingredients to general data
	function custom_product_other_ingredients_text_field_save($product) {
		$custom_field_value = isset($_POST['_custom_product_other_ingredients_text_field']) ? $_POST['_custom_product_other_ingredients_text_field'] : '';
		$product->update_meta_data('_custom_product_other_ingredients_text_field', sanitize_text_field($custom_field_value));
	}
	
	// Hook to add product cannafacts to general data
	function custom_product_cannafacts_select_field() {
		woocommerce_wp_select(
			array(
				'id'		  => '_custom_product_cannafacts_select_field',
				'label'		  => __('Cannafacts', 'woocommerce'),
				'options' => array(
					1 => 'THC',
					2 => 'CBD',
					3 => 'CBG'
				),
				'desc_tip'	  => 'true',
				'description' => __('Product Cannafacts.', 'woocommerce'),
			)
		);
	}

	// Hook to save product cannafacts to general data
	function custom_product_cannafacts_select_field_save($product) {
		$custom_field_value = isset($_POST['_custom_product_cannafacts_select_field']) ? $_POST['_custom_product_cannafacts_select_field'] : '';
		$product->update_meta_data('_custom_product_cannafacts_select_field', sanitize_text_field($custom_field_value));
	}
	
	// Hook to add product serving size to general data
	function custom_product_per_serving_text_field() {
		woocommerce_wp_text_input(
			array(
				'id'		  => '_custom_product_per_serving_text_field',
				'label'		  => __('Per Serving', 'woocommerce'),
				'placeholder' => __('Enter per serving here', 'woocommerce'),
				'desc_tip'	  => 'true',
				'description' => __('Product Per Serving.', 'woocommerce'),
			)
		);
	}

	// Hook to save product serving size to general data
	function custom_product_per_serving_size_text_field_save($product) {
		$custom_field_value = isset($_POST['_custom_product_per_serving_text_field']) ? $_POST['_custom_product_per_serving_text_field'] : '';
		$product->update_meta_data('_custom_product_per_serving_text_field', sanitize_text_field($custom_field_value));
	}
	
	// Hook to add product serving size to general data
	function custom_product_serving_size_text_field() {
		woocommerce_wp_text_input(
			array(
				'id'		  => '_custom_product_serving_size_text_field',
				'label'		  => __('Serving Size', 'woocommerce'),
				'placeholder' => __('Enter serving size here', 'woocommerce'),
				'desc_tip'	  => 'true',
				'description' => __('Product Serving Size.', 'woocommerce'),
			)
		);
	}

	// Hook to save product serving size to general data
	function custom_product_serving_size_text_field_save($product) {
		$custom_field_value = isset($_POST['_custom_product_serving_size_text_field']) ? $_POST['_custom_product_serving_size_text_field'] : '';
		$product->update_meta_data('_custom_product_serving_size_text_field', sanitize_text_field($custom_field_value));
	}
	
	// Hook to add product servings per container to general data
	function custom_product_servings_per_container_text_field() {
		woocommerce_wp_text_input(
			array(
				'id'		  => '_custom_product_servings_per_container_text_field',
				'label'		  => __('Servings Per Container', 'woocommerce'),
				'placeholder' => __('Enter servings per container here', 'woocommerce'),
				'desc_tip'	  => 'true',
				'description' => __('Product Servings Per Container.', 'woocommerce'),
			)
		);
	}

	// Hook to save product servings per container to general data
	function custom_product_servings_per_container_text_field_save($product) {
		$custom_field_value = isset($_POST['_custom_product_servings_per_container_text_field']) ? $_POST['_custom_product_servings_per_container_text_field'] : '';
		$product->update_meta_data('_custom_product_servings_per_container_text_field', sanitize_text_field($custom_field_value));
	}
	
	// Hook to add product suggested use to general data
	function custom_product_suggested_use_text_field() {
		woocommerce_wp_text_input(
			array(
				'id'		  => '_custom_product_suggested_use_text_field',
				'label'		  => __('Suggested Use', 'woocommerce'),
				'placeholder' => __('Enter suggested use here', 'woocommerce'),
				'desc_tip'	  => 'true',
				'description' => __('Product Suggested Use.', 'woocommerce'),
			)
		);
	}

	// Hook to save product suggested use to general data
	function custom_product_suggested_use_text_field_save($product) {
		$custom_field_value = isset($_POST['_custom_product_suggested_use_text_field']) ? $_POST['_custom_product_suggested_use_text_field'] : '';
		$product->update_meta_data('_custom_product_suggested_use_text_field', sanitize_text_field($custom_field_value));
	}
	
	// Hook to add product awards tag to general data
	function custom_product_awards_tag_text_field() {
		woocommerce_wp_text_input(
			array(
				'id'		  => '_custom_product_awards_tag_text_field',
				'label'		  => __('Awards Tag', 'woocommerce'),
				'placeholder' => __('Enter awards tag here', 'woocommerce'),
				'desc_tip'	  => 'true',
				'description' => __('Product Awards Tag.', 'woocommerce'),
			)
		);
	}

	// Hook to save product suggested use to general data
	function custom_product_awards_tag_text_field_save($product) {
		$custom_field_value = isset($_POST['_custom_product_awards_tag_text_field']) ? $_POST['_custom_product_awards_tag_text_field'] : '';
		$product->update_meta_data('_custom_product_awards_tag_text_field', sanitize_text_field($custom_field_value));
	}
}
