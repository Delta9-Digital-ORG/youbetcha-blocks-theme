<?php

/**
 * Replace core's wp_template/wp_template_part uniqueness check.
 *
 * Core's `wp_filter_wp_template_unique_post_slug` uses WP_Query with `post__not_in`
 * to detect duplicate slugs. On WP Engine's object cache the result for that query
 * pattern can include the post being updated, so every save of an existing template
 * appends `-2`, then `-2-2`, etc. — and the editor's cached template ID stops matching
 * the row, breaking subsequent saves with "No templates exist with that id."
 *
 * The replacement runs the equivalent uniqueness check via direct `$wpdb` SQL with
 * a `wp_theme` taxonomy join. No WP_Query, no object cache involvement.
 *
 * @package YouBetchaCannabisTheme\Overrides;
 */

declare(strict_types=1);

namespace YouBetchaCannabisTheme\Overrides;

use YouBetchaCannabisThemeVendor\EightshiftLibs\Services\ServiceInterface;

/**
 * TemplateSlugCache class.
 */
class TemplateSlugCache implements ServiceInterface
{
	/**
	 * Register all the hooks.
	 *
	 * @return void
	 */
	public function register(): void
	{
		\add_action('init', [$this, 'replaceCoreFilter'], 1);
	}

	/**
	 * Swap core's broken filter for ours at the same priority.
	 *
	 * @return void
	 */
	public function replaceCoreFilter(): void
	{
		\remove_filter('pre_wp_unique_post_slug', 'wp_filter_wp_template_unique_post_slug', 10);
		\add_filter('pre_wp_unique_post_slug', [$this, 'uniqueTemplateSlug'], 10, 6);
	}

	/**
	 * Direct-SQL replacement for the wp_template/wp_template_part slug uniqueness check.
	 *
	 * @param string|null $overrideSlug Value to short-circuit with; null means run the check.
	 * @param string      $slug         Requested slug.
	 * @param int         $postId       Post being saved.
	 * @param string      $postStatus   Post status.
	 * @param string      $postType     Post type.
	 * @param int         $postParent   Post parent.
	 *
	 * @return string|null
	 */
	public function uniqueTemplateSlug($overrideSlug, $slug, $postId, $postStatus, $postType, $postParent = 0)
	{
		if ($postType !== 'wp_template' && $postType !== 'wp_template_part') {
			return $overrideSlug;
		}

		if (!$overrideSlug) {
			$overrideSlug = $slug;
		}

		$theme = \get_stylesheet();
		$terms = \get_the_terms($postId, 'wp_theme');
		if ($terms && !\is_wp_error($terms)) {
			$theme = $terms[0]->name;
		}

		$themeTerm   = \get_term_by('name', $theme, 'wp_theme');
		$themeTermId = ($themeTerm && !\is_wp_error($themeTerm)) ? (int) $themeTerm->term_id : 0;
		if ($themeTermId === 0) {
			return $overrideSlug;
		}

		global $wpdb;
		$candidate = $overrideSlug;
		$suffix    = 2;

		// Cap iterations defensively — a real conflict resolves in 1–2 passes.
		for ($i = 0; $i < 100; $i++) {
			$found = (int) $wpdb->get_var($wpdb->prepare(
				"SELECT p.ID
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
				 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
				 WHERE p.post_name = %s
				   AND p.post_type = %s
				   AND p.ID <> %d
				   AND tt.taxonomy = 'wp_theme'
				   AND tt.term_id = %d
				 LIMIT 1",
				$candidate,
				$postType,
				(int) $postId,
				$themeTermId
			));

			if ($found === 0) {
				return $candidate;
			}

			$candidate = $overrideSlug . '-' . $suffix;
			$suffix++;
		}

		return $candidate;
	}
}
