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
		\add_filter('taxonomy_template_hierarchy', [$this, 'inheritAncestorCategoryTemplate']);
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
		\add_filter('woocommerce_get_item_data', [$this, 'miniCartItemData'], 10, 2);
		\add_action('woocommerce_before_quantity_input_field', [$this, 'quantityMinusButton']);
		\add_action('woocommerce_after_quantity_input_field', [$this, 'quantityPlusButton']);
		\add_filter('woocommerce_cart_item_subtotal', [$this, 'cartItemSaveBadge'], 20, 3);
		\add_filter('woocommerce_order_button_text', [$this, 'checkoutButtonText']);
		\add_filter('gettext', [$this, 'changeProceedToCheckoutText'], 20, 3);
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

	/**
	 * Make nested product categories inherit the nearest ancestor's block template.
	 *
	 * WordPress' term-template hierarchy only matches a term's OWN slug
	 * (taxonomy-product_cat-{slug}) before falling back to the generic
	 * taxonomy-product_cat template — it never walks up to parent terms. So a deep
	 * category such as active-ingredients/cbd-active-ingredients/5mg-cbd-active-ingredients
	 * skips the curated "cbd-active-ingredients" template and drops to the generic one.
	 *
	 * This injects each ancestor's taxonomy-product_cat-{ancestor-slug} into the
	 * hierarchy (nearest ancestor first) just before the generic template, so a child
	 * term renders with the closest ancestor template that actually exists.
	 *
	 * @param string[] $templates Template hierarchy (most specific first).
	 *
	 * @return string[]
	 */
	public function inheritAncestorCategoryTemplate($templates): array
	{
		if (!\is_array($templates) || !\is_tax('product_cat')) {
			return $templates;
		}

		$term = \get_queried_object();
		if (!($term instanceof \WP_Term) || empty($term->parent)) {
			return $templates;
		}

		// Locate the generic template entry and mirror its suffix (with/without .php).
		$genericIndex = false;
		$suffix = '';
		foreach ($templates as $index => $candidate) {
			if ($candidate === 'taxonomy-product_cat' || $candidate === 'taxonomy-product_cat.php') {
				$genericIndex = $index;
				$suffix = ($candidate === 'taxonomy-product_cat.php') ? '.php' : '';
				break;
			}
		}

		if ($genericIndex === false) {
			return $templates;
		}

		// Ancestors are returned nearest-first (immediate parent → root).
		$ancestorTemplates = [];
		foreach (\get_ancestors($term->term_id, 'product_cat', 'taxonomy') as $ancestorId) {
			$ancestor = \get_term($ancestorId, 'product_cat');
			if ($ancestor instanceof \WP_Term && !empty($ancestor->slug)) {
				$ancestorTemplates[] = "taxonomy-product_cat-{$ancestor->slug}{$suffix}";
			}
		}

		if (!empty($ancestorTemplates)) {
			\array_splice($templates, $genericIndex, 0, $ancestorTemplates);
		}

		return $templates;
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

	/**
	 * Inject product metadata into mini-cart item data.
	 *
	 * Adds ingredients, nutrition facts, active ingredients, and mood
	 * to the cart item data so they render in the WooCommerce Blocks mini-cart.
	 */
	public function miniCartItemData(array $itemData, array $cartItem): array
	{
		$productId = $cartItem['product_id'] ?? 0;

		if (!$productId) {
			return $itemData;
		}

		$productCategories = get_the_terms($productId, 'product_cat');

		if (empty($productCategories) || is_wp_error($productCategories)) {
			return $itemData;
		}

		// Helper: get child category names under a parent slug.
		$getChildCats = function (string $parentSlug) use ($productId, $productCategories): array {
			$parentTerm = get_term_by('slug', $parentSlug, 'product_cat');
			if (!$parentTerm) {
				return [];
			}

			$children = [];
			foreach ($productCategories as $cat) {
				if ($cat->parent === $parentTerm->term_id) {
					$children[] = $cat;
				}
			}
			return $children;
		};

		// 1. Ingredients — child category names under "ingredients"
		$ingredientCats = $getChildCats('ingredients');
		if (!empty($ingredientCats)) {
			$names = array_map(function ($cat) {
				return $cat->name;
			}, $ingredientCats);

			$itemData[] = [
				'key'   => 'Ingredients',
				'value' => implode(', ', $names),
			];
		}

		// 2. Nutrition Facts — first line of description from child cats under "nutrition-facts"
		$nutritionCats = $getChildCats('nutrition-facts');
		if (!empty($nutritionCats)) {
			$facts = [];
			foreach ($nutritionCats as $cat) {
				$desc = wp_strip_all_tags($cat->description);
				$lines = array_filter(preg_split('/\r\n|\r|\n/', $desc), function ($line) {
					return !empty(trim($line));
				});
				$lines = array_values($lines);
				if (!empty($lines)) {
					$facts[] = trim($lines[0]);
				}
			}

			if (!empty($facts)) {
				$itemData[] = [
					'key'   => 'Nutrition',
					'value' => implode(' · ', $facts),
				];
			}
		}

		// 3. Mood — child category name under "mood"
		$moodCats = $getChildCats('mood');
		if (!empty($moodCats)) {
			$itemData[] = [
				'key'   => 'Mood',
				'value' => $moodCats[0]->name,
			];
		}

		// 4. Active Ingredients — child cats under "active-ingredient-label" with grandchildren for dosage
		$activeIngredientCats = $getChildCats('active-ingredient-label');
		if (!empty($activeIngredientCats)) {
			$activeParts = [];
			foreach ($activeIngredientCats as $cat) {
				// Get grandchildren (dosage info)
				$grandchildren = get_terms([
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'parent'     => $cat->term_id,
				]);

				if (!empty($grandchildren) && !is_wp_error($grandchildren)) {
					// Check product is assigned to these grandchildren
					$productCatIds = array_map(function ($c) {
						return $c->term_id;
					}, $productCategories);

					foreach ($grandchildren as $gc) {
						if (in_array($gc->term_id, $productCatIds, true)) {
							$parts = explode(' ', $gc->name);
							$dose = $parts[0] ?? '';
							$compound = $parts[1] ?? $cat->name;
							$activeParts[] = $dose . ' ' . $compound;
						}
					}
				} else {
					$activeParts[] = $cat->name;
				}
			}

			if (!empty($activeParts)) {
				$itemData[] = [
					'key'   => 'Active',
					'value' => implode(', ', $activeParts),
				];
			}
		}

		return $itemData;
	}

	/**
	 * Append a "Save $X.XX" badge to the cart item subtotal.
	 *
	 * Works with both WooCommerce sale prices and pack pricing.
	 */
	public function cartItemSaveBadge(string $subtotal, array $cartItem, string $cartItemKey): string
	{
		$product = $cartItem['data'] ?? null;

		if (!$product) {
			return $subtotal;
		}

		$quantity = (int) ($cartItem['quantity'] ?? 1);
		$regularPrice = (float) $product->get_regular_price();
		$saved = 0;

		// Pack pricing savings: regular × qty vs pack price
		if (!empty($cartItem['wc_pack_price'])) {
			$packPrice = (float) $cartItem['wc_pack_price'];
			$regularTotal = $regularPrice * $quantity;
			$saved = $regularTotal - $packPrice;
		}
		// WooCommerce sale price savings
		elseif ($product->is_on_sale()) {
			$salePrice = (float) $product->get_sale_price();
			if ($regularPrice > 0 && $salePrice > 0) {
				$saved = ($regularPrice - $salePrice) * $quantity;
			}
		}

		if ($saved > 0.01) {
			$badge = '<span class="yb-save-badge">Save ' . \wc_price($saved) . '</span>';
			return $subtotal . $badge;
		}

		return $subtotal;
	}

	/**
	 * Change "Proceed to checkout" text to "Checkout".
	 */
	public function changeProceedToCheckoutText($translated, $text, $domain) // This function can't have types in the signature because the wholesale plugin passes it NULL on the settings page. 
	{
		if ($domain === 'woocommerce' && $text === 'Proceed to checkout') {
			return 'Checkout';
		}
		return $translated;
	}

	/**
	 * Change order button text on checkout page.
	 */
	public function checkoutButtonText(): string
	{
		return 'Checkout';
	}

	/**
	 * Add minus button before quantity input.
	 */
	public function quantityMinusButton(): void
	{
		echo '<button type="button" class="yb-qty-btn yb-qty-minus" aria-label="Decrease quantity">&minus;</button>';
	}

	/**
	 * Add plus button after quantity input.
	 */
	public function quantityPlusButton(): void
	{
		echo '<button type="button" class="yb-qty-btn yb-qty-plus" aria-label="Increase quantity">+</button>';
	}
}
