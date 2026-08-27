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
		\add_action('woocommerce_product_options_general_product_data', [$this, 'custom_product_benefits_text_field']);
		\add_action('woocommerce_admin_process_product_object', [$this, 'custom_product_benefits_text_field_save'], 10, 1);
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
		\add_action('woocommerce_product_options_general_product_data', [$this, 'custom_product_nutrition_facts_fields']);
		\add_action('woocommerce_admin_process_product_object', [$this, 'custom_product_nutrition_facts_fields_save'], 10, 1);
		\add_filter('woocommerce_get_item_data', [$this, 'miniCartItemData'], 10, 2);
		\add_action('woocommerce_before_quantity_input_field', [$this, 'quantityMinusButton']);
		\add_action('woocommerce_after_quantity_input_field', [$this, 'quantityPlusButton']);
		\add_filter('woocommerce_cart_item_subtotal', [$this, 'cartItemSaveBadge'], 20, 3);
		\add_filter('woocommerce_order_button_text', [$this, 'checkoutButtonText']);
		\add_filter('gettext', [$this, 'changeProceedToCheckoutText'], 20, 3);
		\add_action('wp_footer', [$this, 'miniCartEmptyStartShoppingHome']);
	}
	/**
	 * Give new products the starter block layout every product page needs.
	 *
	 * Replaces WooCommerce's own product template (added in 7.7.0), which this
	 * previously just unset. The single-product block lives in each product's
	 * content rather than in the shared template, so that its award badges and
	 * other block attributes are per-product — which means a product created
	 * without it renders no hero at all. Pre-populating the canvas keeps that
	 * from being something an editor has to remember.
	 *
	 * @param array $post_type_args Post type arguments.
	 *
	 * @return array
	 */
	public function restored_reset_product_template($post_type_args): array
	{
		$template = [
			['eightshift-boilerplate/single-product'],
		];

		// The description/nutrition/gallery row is a synced pattern. Resolve it
		// by slug, never by ID — the ID differs between local, youbetchadev and
		// production, and a stale one would render an empty block. If the
		// pattern is missing, ship the hero on its own rather than a broken ref.
		$pattern = \get_page_by_path(
			'product-page-description-and-nutrition-cana-facts',
			\OBJECT,
			'wp_block'
		);

		if ($pattern instanceof \WP_Post) {
			$template[] = ['core/block', ['ref' => $pattern->ID]];
		}

		$post_type_args['template'] = $template;

		// Deliberately no `template_lock` — editors can still add to, reorder,
		// or remove blocks on an individual product.

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
		$ancestorIds = \array_map('\intval', \get_ancestors($term->term_id, 'product_cat', 'taxonomy'));

		$ancestorTemplates = [];
		foreach ($ancestorIds as $ancestorId) {
			$ancestor = \get_term($ancestorId, 'product_cat');
			if ($ancestor instanceof \WP_Term && !empty($ancestor->slug)) {
				$ancestorTemplates[] = "taxonomy-product_cat-{$ancestor->slug}{$suffix}";
			}
		}

		// Style-by-level rule for the "mood" subtree: any category nested under
		// "mood" uses the shared Style-B template (compact "MOOD" label + large,
		// left-aligned term title) instead of inheriting the top-level "mood"
		// Style-A template. Injected ahead of the ancestor-slug entries so Style-B
		// wins over the parent's Style-A template, while a term's own curated
		// template (matched earlier in the hierarchy by WordPress) still takes
		// precedence. Scoped to the mood subtree, so active-ingredients/etc. are
		// unaffected.
		$moodTerm = \get_term_by('slug', 'mood', 'product_cat');
		if ($moodTerm instanceof \WP_Term && \in_array((int) $moodTerm->term_id, $ancestorIds, true)) {
			\array_unshift($ancestorTemplates, "taxonomy-product_cat-mood-nested{$suffix}");
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
		$custom_field_value = isset($_POST['_custom_product_description_text_field']) ? wp_unslash($_POST['_custom_product_description_text_field']) : '';
		$product->update_meta_data('_custom_product_description_text_field', sanitize_text_field($custom_field_value));
	}
	
	// Hook to add product ingredients to general data.
	//
	// This key used to hold the product's claims rather than its ingredients.
	// That copy has been moved to _custom_product_benefits_text_field, so the
	// key now matches its name and is free for real ingredient copy.
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
		$custom_field_value = isset($_POST['_custom_product_ingredients_text_field']) ? wp_unslash($_POST['_custom_product_ingredients_text_field']) : '';
		$product->update_meta_data('_custom_product_ingredients_text_field', sanitize_text_field($custom_field_value));
	}
	
	// Hook to add product benefits to general data.
	//
	// Holds the claims copy ("Organically Farmed…, Non-GMO, Vegan,
	// Gluten-Free") that used to sit on the ingredients key, and feeds the
	// Benefits tab on the product page.
	function custom_product_benefits_text_field() {
		woocommerce_wp_textarea_input(
			array(
				'id'		  => '_custom_product_benefits_text_field',
				'label'		  => __('Benefits', 'woocommerce'),
				'placeholder' => __('Enter benefits here', 'woocommerce'),
				'desc_tip'	  => 'true',
				'description' => __('Product Benefits — shown in the Benefits tab on the product page.', 'woocommerce'),
			)
		);
	}

	// Hook to save product benefits to general data
	function custom_product_benefits_text_field_save($product) {
		if (!isset($_POST['_custom_product_benefits_text_field'])) {
			return;
		}

		$product->update_meta_data(
			'_custom_product_benefits_text_field',
			sanitize_textarea_field(wp_unslash($_POST['_custom_product_benefits_text_field']))
		);
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
		$custom_field_value = isset($_POST['_custom_product_other_ingredients_text_field']) ? wp_unslash($_POST['_custom_product_other_ingredients_text_field']) : '';
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
		$custom_field_value = isset($_POST['_custom_product_cannafacts_select_field']) ? wp_unslash($_POST['_custom_product_cannafacts_select_field']) : '';
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
		$custom_field_value = isset($_POST['_custom_product_per_serving_text_field']) ? wp_unslash($_POST['_custom_product_per_serving_text_field']) : '';
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
		$custom_field_value = isset($_POST['_custom_product_serving_size_text_field']) ? wp_unslash($_POST['_custom_product_serving_size_text_field']) : '';
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
		$custom_field_value = isset($_POST['_custom_product_servings_per_container_text_field']) ? wp_unslash($_POST['_custom_product_servings_per_container_text_field']) : '';
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
		$custom_field_value = isset($_POST['_custom_product_suggested_use_text_field']) ? wp_unslash($_POST['_custom_product_suggested_use_text_field']) : '';
		$product->update_meta_data('_custom_product_suggested_use_text_field', sanitize_text_field($custom_field_value));
	}

	/**
	 * The FDA nutrition panel lines, in printed-label order.
	 *
	 * Keyed by the meta-key suffix — each line is stored in its own
	 * `_custom_product_nf_{key}_text_field` key, which is what the
	 * single-product block reads. Driven from one list rather than eleven
	 * near-identical method pairs, so adding a line is a one-line change.
	 *
	 * @return array<string, string> Meta suffix => admin label.
	 */
	private function nutrition_facts_fields(): array {
		return [
			'calories'           => __('Calories', 'woocommerce'),
			'total_fat'          => __('Total Fat', 'woocommerce'),
			'saturated_fat'      => __('Saturated Fat', 'woocommerce'),
			'trans_fat'          => __('Trans Fat', 'woocommerce'),
			'cholesterol'        => __('Cholesterol', 'woocommerce'),
			'sodium'             => __('Sodium', 'woocommerce'),
			'total_carbohydrate' => __('Total Carbohydrate', 'woocommerce'),
			'dietary_fiber'      => __('Dietary Fiber', 'woocommerce'),
			'total_sugars'       => __('Total Sugars', 'woocommerce'),
			'added_sugars'       => __('Added Sugars', 'woocommerce'),
			'protein'            => __('Protein', 'woocommerce'),
		];
	}

	// Hook to add the nutrition facts panel to general data
	function custom_product_nutrition_facts_fields() {
		echo '<div class="options_group">';
		echo '<p class="form-field"><strong>' . esc_html__('Nutrition Facts', 'woocommerce') . '</strong><br />';
		echo '<span class="description">' . esc_html__('Enter each line exactly as printed on the label, value column only, including the % Daily Value — for example "5mg (<1%)". Leave a line blank to hide that row on the product page.', 'woocommerce') . '</span></p>';

		foreach ($this->nutrition_facts_fields() as $key => $label) {
			woocommerce_wp_text_input(
				array(
					'id'		  => "_custom_product_nf_{$key}_text_field",
					'label'		  => $label,
					'placeholder' => __('e.g. 0g (0%)', 'woocommerce'),
					'desc_tip'	  => 'true',
					'description' => $label . __(' line of the nutrition panel, value column only.', 'woocommerce'),
				)
			);
		}

		echo '</div>';
	}

	// Hook to save the nutrition facts panel to general data
	function custom_product_nutrition_facts_fields_save($product) {
		foreach (array_keys($this->nutrition_facts_fields()) as $key) {
			$meta_key = "_custom_product_nf_{$key}_text_field";

			// Only touch keys the submitted form actually carried. A blank
			// input still posts an empty string, so clearing a line still
			// works; this only guards against a save that ran without the
			// product-data panel rendered, which would otherwise wipe all
			// eleven lines.
			if (!isset($_POST[$meta_key])) {
				continue;
			}

			// `sanitize_text_field` HTML-encodes a bare "<", which turns a
			// perfectly ordinary "5mg (<1%)" into "5mg (&lt;1%)" and prints
			// the entity on the page. Decoding afterwards restores the
			// literal character while keeping the tag-stripping — a real
			// "<script>" is dropped by the sanitizer before this runs, and
			// output is escaped with esc_html at render time.
			$value = html_entity_decode(
				sanitize_text_field(wp_unslash($_POST[$meta_key])),
				ENT_QUOTES,
				'UTF-8'
			);

			$product->update_meta_data($meta_key, $value);
		}
	}
	
	// The "Awards Tag" text field that used to sit here was never hooked in
	// register(), so it never rendered or saved, and nothing read its meta key.
	// Awards are set per product on the single-product block itself.

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

	/**
	 * Point the mini-cart empty state's "Start shopping" button at the site home.
	 *
	 * WooCommerce Blocks renders the empty mini-cart button client-side and
	 * defaults its href to the Shop page permalink. We rewrite that href to the
	 * home URL and intercept clicks so an empty cart sends shoppers home.
	 *
	 * @return void
	 */
	public function miniCartEmptyStartShoppingHome(): void
	{
		if (\is_admin()) {
			return;
		}

		$homeUrl = \wp_json_encode(\home_url('/'));
		?>
		<script>
		(function () {
			var homeUrl = <?php echo $homeUrl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
			var selector = '.wp-block-woocommerce-empty-mini-cart-contents-block a.wc-block-mini-cart__shopping-button';

			// Keep the anchor href in sync (covers middle-click / open-in-new-tab).
			document.addEventListener('mouseover', function (e) {
				if (!e.target || !e.target.closest) {
					return;
				}
				var link = e.target.closest(selector);
				if (!link) {
					return;
				}
				link.setAttribute('href', homeUrl);
			});

			// Guarantee navigation home on left-click and keyboard activation.
			document.addEventListener('click', function (e) {
				if (!e.target || !e.target.closest) {
					return;
				}
				var link = e.target.closest(selector);
				if (!link) {
					return;
				}
				e.preventDefault();
				window.location.href = homeUrl;
			});
		})();
		</script>
		<?php
	}
}
