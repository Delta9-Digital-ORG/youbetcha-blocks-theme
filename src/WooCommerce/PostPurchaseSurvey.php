<?php

/**
 * Post-purchase survey.
 *
 * Replaces the marketing questions that used to sit on the checkout page
 * (Wholesale For WooCommerce "Display fields on checkout"). The survey is
 * asked after payment instead: on the order-received page, and through a
 * link in the customer order emails for anyone who closes the tab.
 *
 * Answers are stored on the order (and copied to the customer once), shown
 * in the order admin screen and as a column in the orders list.
 *
 * @package YouBetchaCannabisTheme\WooCommerce
 */

declare(strict_types=1);

namespace YouBetchaCannabisTheme\WooCommerce;

use WC_Order;
use YouBetchaCannabisThemeVendor\EightshiftLibs\Services\ServiceInterface;

/**
 * PostPurchaseSurvey class.
 */
class PostPurchaseSurvey implements ServiceInterface
{
	/**
	 * Form action used for admin-post.php.
	 *
	 * @var string
	 */
	private const ACTION = 'yb_post_purchase_survey';

	/**
	 * Slug of the page that holds the [yb_post_purchase_survey] shortcode (email link target).
	 *
	 * @var string
	 */
	private const PAGE_SLUG = 'post-purchase-survey';

	/**
	 * Query arg used to show the "thanks" state after a submit.
	 *
	 * @var string
	 */
	private const DONE_ARG = 'yb_survey';

	/**
	 * Order meta keys.
	 */
	private const META_SOURCE = '_yb_hear_about_us';
	private const META_DETAIL = '_yb_hear_about_us_detail';
	private const META_WHOLESALE = '_yb_wholesale_interest';
	private const META_DONE = '_yb_survey_done';

	/**
	 * Order statuses that may see the survey (i.e. the customer actually paid).
	 *
	 * @var string[]
	 */
	private const PAID_STATUSES = ['processing', 'completed', 'on-hold'];

	/**
	 * Customer emails that carry the survey link.
	 *
	 * @var string[]
	 */
	private const EMAIL_IDS = ['customer_processing_order', 'customer_completed_order'];

	/**
	 * Register all the hooks.
	 *
	 * @return void
	 */
	public function register(): void
	{
		\add_action('woocommerce_thankyou', [$this, 'renderOnThankYou'], 20);
		\add_shortcode('yb_post_purchase_survey', [$this, 'renderShortcode']);
		\add_action('template_redirect', [$this, 'preventSurveyPageCache']);

		\add_action('admin_post_' . self::ACTION, [$this, 'handleSubmit']);
		\add_action('admin_post_nopriv_' . self::ACTION, [$this, 'handleSubmit']);

		\add_action('woocommerce_email_after_order_table', [$this, 'renderEmailLink'], 20, 4);

		\add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'renderAdminAnswers']);
		\add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'addOrdersColumn']);
		\add_filter('manage_edit-shop_order_columns', [$this, 'addOrdersColumn']);
		\add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'renderOrdersColumn'], 10, 2);
		\add_action('manage_shop_order_posts_custom_column', [$this, 'renderOrdersColumnLegacy'], 10, 2);
	}

	/**
	 * "How did you hear about us?" answers, value => label.
	 *
	 * @return array<string, string>
	 */
	public static function sources(): array
	{
		return [
			'search' => \__('Google or web search', 'youbetcha-cannabis-theme'),
			'instagram' => \__('Instagram', 'youbetcha-cannabis-theme'),
			'facebook' => \__('Facebook', 'youbetcha-cannabis-theme'),
			'friend' => \__('Friend or family', 'youbetcha-cannabis-theme'),
			'store' => \__('Saw it in a store', 'youbetcha-cannabis-theme'),
			'event' => \__('Event', 'youbetcha-cannabis-theme'),
			'sales_rep' => \__('Sales rep', 'youbetcha-cannabis-theme'),
			'other' => \__('Other', 'youbetcha-cannabis-theme'),
		];
	}

	/**
	 * Follow-up prompt per answer. Answers not listed get no follow-up.
	 *
	 * @return array<string, string>
	 */
	private function detailPrompts(): array
	{
		return [
			'store' => \__('Which store?', 'youbetcha-cannabis-theme'),
			'event' => \__('Which event?', 'youbetcha-cannabis-theme'),
			'sales_rep' => \__('Who was your rep?', 'youbetcha-cannabis-theme'),
			'other' => \__('Where did you hear about us?', 'youbetcha-cannabis-theme'),
		];
	}

	/**
	 * Show the survey under the order details on the order-received page.
	 *
	 * @param int|string $orderId Order ID.
	 *
	 * @return void
	 */
	public function renderOnThankYou($orderId): void
	{
		$order = \wc_get_order($orderId);

		if (!$order instanceof WC_Order) {
			return;
		}

		$returnUrl = $order->get_checkout_order_received_url();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if (isset($_GET[self::DONE_ARG])) {
			echo $this->thanksMarkup(); // phpcs:ignore Eightshift.Security.HelpersEscape.OutputNotEscaped
			return;
		}

		if (!$this->canSurvey($order)) {
			return;
		}

		echo $this->formMarkup($order, $returnUrl); // phpcs:ignore Eightshift.Security.HelpersEscape.OutputNotEscaped
	}

	/**
	 * [yb_post_purchase_survey] — used on the page the order emails link to.
	 *
	 * Reads the order key from ?key=wc_order_… (the order key is the access token,
	 * the same way WooCommerce's own order-received URL works).
	 *
	 * @return string
	 */
	public function renderShortcode(): string
	{
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if (isset($_GET[self::DONE_ARG])) {
			return $this->thanksMarkup();
		}

		$key = isset($_GET['key']) ? \sanitize_text_field(\wp_unslash($_GET['key'])) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$order = $this->orderFromKey(\is_string($key) ? $key : '');

		if (!$order) {
			return $this->noticeMarkup(\__('This survey link is not valid. Thanks for stopping by!', 'youbetcha-cannabis-theme'));
		}

		if (!$this->canSurvey($order)) {
			return $this->noticeMarkup(\__('We already have your answers for this order. Thank you!', 'youbetcha-cannabis-theme'));
		}

		$returnUrl = \add_query_arg('key', $order->get_order_key(), (string) \get_permalink());

		return $this->formMarkup($order, $returnUrl);
	}

	/**
	 * Keep page caches off the per-order survey page.
	 *
	 * @return void
	 */
	public function preventSurveyPageCache(): void
	{
		if (\is_page(self::PAGE_SLUG)) {
			\nocache_headers();
		}
	}

	/**
	 * Save survey answers to the order, then redirect back.
	 *
	 * @return void
	 */
	public function handleSubmit(): void
	{
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- The order key is the credential, as on WooCommerce's order-received page; nonces would break on cached pages and for guests.
		$key = isset($_POST['order_key']) ? \sanitize_text_field(\wp_unslash($_POST['order_key'])) : '';
		$source = isset($_POST['hear_about_us']) ? \sanitize_key(\wp_unslash($_POST['hear_about_us'])) : '';
		$detail = isset($_POST['hear_about_us_detail']) ? \sanitize_text_field(\wp_unslash($_POST['hear_about_us_detail'])) : '';
		$wholesale = !empty($_POST['wholesale_interest']);
		$returnUrl = isset($_POST['return_url']) ? \esc_url_raw(\wp_unslash($_POST['return_url'])) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$returnUrl = \wp_validate_redirect($returnUrl, \home_url('/'));
		$order = $this->orderFromKey(\is_string($key) ? $key : '');
		$sources = self::sources();

		if (!isset($sources[$source])) {
			$source = '';
		}

		if (!$order || !$this->canSurvey($order) || ($source === '' && !$wholesale)) {
			\wp_safe_redirect($returnUrl);
			exit;
		}

		$prompts = $this->detailPrompts();
		$detail = isset($prompts[$source]) ? \mb_substr($detail, 0, 200) : '';

		$order->update_meta_data(self::META_SOURCE, $source);
		$order->update_meta_data(self::META_DETAIL, $detail);
		$order->update_meta_data(self::META_WHOLESALE, $wholesale ? 'yes' : 'no');
		$order->update_meta_data(self::META_DONE, \time());
		$order->save();

		$order->add_order_note($this->summary($order));

		$customerId = $order->get_customer_id();

		// First answer wins on the customer: it is the original attribution.
		if ($customerId > 0 && $source !== '' && !\get_user_meta($customerId, 'yb_hear_about_us', true)) {
			\update_user_meta($customerId, 'yb_hear_about_us', $source);
			\update_user_meta($customerId, 'yb_hear_about_us_detail', $detail);
		}

		if ($customerId > 0 && $wholesale) {
			\update_user_meta($customerId, 'yb_wholesale_interest', 'yes');
		}

		\wp_safe_redirect(\add_query_arg(self::DONE_ARG, 'thanks', $returnUrl));
		exit;
	}

	/**
	 * Add a survey link to the customer processing/completed emails.
	 *
	 * @param WC_Order|mixed $order Order.
	 * @param bool $sentToAdmin Whether the email goes to the store.
	 * @param bool $plainText Plain-text email.
	 * @param object|null $email WC_Email Email object.
	 *
	 * @return void
	 */
	public function renderEmailLink($order, $sentToAdmin, $plainText, $email = null): void
	{
		if ($sentToAdmin || !$order instanceof WC_Order || !\is_object($email) || !\in_array($email->id ?? '', self::EMAIL_IDS, true)) {
			return;
		}

		if (!$this->canSurvey($order)) {
			return;
		}

		$url = $this->surveyUrl($order);

		if (!$url) {
			return;
		}

		$heading = \__('One quick question', 'youbetcha-cannabis-theme');
		$text = \__('How did you hear about You Betcha? It takes 10 seconds and helps a small brand a lot.', 'youbetcha-cannabis-theme');
		$button = \__('Answer now', 'youbetcha-cannabis-theme');

		if ($plainText) {
			echo "\n" . \esc_html($heading) . "\n" . \esc_html($text) . "\n" . \esc_url_raw($url) . "\n\n";
			return;
		}

		?>
		<div style="margin:0 0 40px;padding:20px 24px;border:2px solid #EAEAEA;border-radius:12px;">
			<h2 style="margin:0 0 8px;"><?php echo \esc_html($heading); ?></h2>
			<p style="margin:0 0 16px;"><?php echo \esc_html($text); ?></p>
			<a href="<?php echo \esc_url($url); ?>" style="display:inline-block;padding:12px 28px;background:#417360;color:#ffffff;border-radius:100px;font-weight:700;text-decoration:none;text-transform:uppercase;letter-spacing:0.04em;"><?php echo \esc_html($button); ?></a>
		</div>
		<?php
	}

	/**
	 * Show the answers on the order edit screen.
	 *
	 * @param WC_Order|mixed $order Order.
	 *
	 * @return void
	 */
	public function renderAdminAnswers($order): void
	{
		if (!$order instanceof WC_Order || !$order->get_meta(self::META_DONE)) {
			return;
		}

		$source = $this->sourceLabel($order);
		$detail = (string) $order->get_meta(self::META_DETAIL);
		$wholesale = $order->get_meta(self::META_WHOLESALE) === 'yes';

		?>
		<p class="form-field form-field-wide">
			<strong><?php \esc_html_e('Post-purchase survey', 'youbetcha-cannabis-theme'); ?></strong><br>
			<?php \esc_html_e('Heard about us:', 'youbetcha-cannabis-theme'); ?>
			<?php echo \esc_html($source !== '' ? $source : '—'); ?>
			<?php if ($detail !== '') : ?>
				(<?php echo \esc_html($detail); ?>)
			<?php endif; ?>
			<?php if ($wholesale) : ?>
				<br><?php \esc_html_e('Interested in wholesale: yes', 'youbetcha-cannabis-theme'); ?>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * Add a "Heard about us" column to the orders list.
	 *
	 * @param array<string, string> $columns Columns.
	 *
	 * @return array<string, string>
	 */
	public function addOrdersColumn($columns): array
	{
		$out = [];

		foreach ((array) $columns as $id => $label) {
			$out[$id] = $label;

			if ($id === 'order_status') {
				$out['yb_hear_about_us'] = \__('Heard about us', 'youbetcha-cannabis-theme');
			}
		}

		if (!isset($out['yb_hear_about_us'])) {
			$out['yb_hear_about_us'] = \__('Heard about us', 'youbetcha-cannabis-theme');
		}

		return $out;
	}

	/**
	 * Render the orders-list column (HPOS screen).
	 *
	 * @param string $column Column ID.
	 * @param WC_Order|mixed $order Order.
	 *
	 * @return void
	 */
	public function renderOrdersColumn($column, $order): void
	{
		if ($column !== 'yb_hear_about_us' || !$order instanceof WC_Order) {
			return;
		}

		$label = $this->sourceLabel($order);

		if ($order->get_meta(self::META_WHOLESALE) === 'yes') {
			$label = \trim($label . ' · ' . \__('wholesale interest', 'youbetcha-cannabis-theme'), ' ·');
		}

		echo \esc_html($label !== '' ? $label : '—');
	}

	/**
	 * Render the orders-list column (legacy posts screen).
	 *
	 * @param string $column Column ID.
	 * @param int $postId Order ID.
	 *
	 * @return void
	 */
	public function renderOrdersColumnLegacy($column, $postId): void
	{
		$this->renderOrdersColumn($column, \wc_get_order($postId));
	}

	/**
	 * Whether this order should be asked the survey.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return bool
	 */
	private function canSurvey(WC_Order $order): bool
	{
		if ($order->get_meta(self::META_DONE)) {
			return false;
		}

		if (!$order->has_status(self::PAID_STATUSES)) {
			return false;
		}

		$customerId = $order->get_customer_id();

		if ($customerId > 0) {
			$user = \get_userdata($customerId);

			// Wholesale buyers went through the wholesale registration form already.
			if ($user && \in_array('default_wholesaler', (array) $user->roles, true)) {
				return false;
			}
		}

		/**
		 * Filter whether an order gets the post-purchase survey.
		 *
		 * @param bool $show Show the survey.
		 * @param WC_Order $order Order.
		 */
		return (bool) \apply_filters('yb_post_purchase_survey_show', true, $order);
	}

	/**
	 * Resolve an order from its order key.
	 *
	 * @param string $key Order key (wc_order_…).
	 *
	 * @return WC_Order|null
	 */
	private function orderFromKey(string $key): ?WC_Order
	{
		if ($key === '' || \strpos($key, 'wc_order_') !== 0) {
			return null;
		}

		$order = \wc_get_order(\wc_get_order_id_by_order_key($key));

		if (!$order instanceof WC_Order || !\hash_equals($order->get_order_key(), $key)) {
			return null;
		}

		return $order;
	}

	/**
	 * Survey page URL for an order, or '' when the page does not exist.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return string
	 */
	private function surveyUrl(WC_Order $order): string
	{
		$page = \get_page_by_path(self::PAGE_SLUG);

		if (!$page || \get_post_status($page) !== 'publish') {
			return '';
		}

		return \add_query_arg('key', $order->get_order_key(), (string) \get_permalink($page));
	}

	/**
	 * Human label for the stored answer.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return string
	 */
	private function sourceLabel(WC_Order $order): string
	{
		$source = (string) $order->get_meta(self::META_SOURCE);
		$sources = self::sources();

		return $sources[$source] ?? '';
	}

	/**
	 * Order note text.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return string
	 */
	private function summary(WC_Order $order): string
	{
		$parts = [];
		$source = $this->sourceLabel($order);
		$detail = (string) $order->get_meta(self::META_DETAIL);

		if ($source !== '') {
			$parts[] = \sprintf(
				/* translators: %s: answer, e.g. "Instagram". */
				\__('heard about us via %s', 'youbetcha-cannabis-theme'),
				$detail !== '' ? "{$source} ({$detail})" : $source
			);
		}

		if ($order->get_meta(self::META_WHOLESALE) === 'yes') {
			$parts[] = \__('interested in wholesale', 'youbetcha-cannabis-theme');
		}

		return \__('Post-purchase survey:', 'youbetcha-cannabis-theme') . ' ' . \implode('; ', $parts) . '.';
	}

	/**
	 * Survey form markup.
	 *
	 * @param WC_Order $order Order.
	 * @param string $returnUrl Where to come back to after submit.
	 *
	 * @return string
	 */
	private function formMarkup(WC_Order $order, string $returnUrl): string
	{
		$prompts = $this->detailPrompts();

		\ob_start();
		?>
		<section class="yb-survey" aria-labelledby="yb-survey-title">
			<h2 class="yb-survey__title" id="yb-survey-title"><?php \esc_html_e('One quick question', 'youbetcha-cannabis-theme'); ?></h2>
			<p class="yb-survey__intro"><?php \esc_html_e('Optional, and it helps a small brand a lot.', 'youbetcha-cannabis-theme'); ?></p>

			<form class="yb-survey__form" method="post" action="<?php echo \esc_url(\admin_url('admin-post.php')); ?>">
				<input type="hidden" name="action" value="<?php echo \esc_attr(self::ACTION); ?>">
				<input type="hidden" name="order_key" value="<?php echo \esc_attr($order->get_order_key()); ?>">
				<input type="hidden" name="return_url" value="<?php echo \esc_url($returnUrl); ?>">

				<fieldset class="yb-survey__fieldset">
					<legend class="yb-survey__legend"><?php \esc_html_e('How did you hear about us?', 'youbetcha-cannabis-theme'); ?></legend>
					<div class="yb-survey__options">
						<?php foreach (self::sources() as $value => $label) : ?>
							<label class="yb-survey__option">
								<input type="radio" name="hear_about_us" value="<?php echo \esc_attr($value); ?>" required>
								<span><?php echo \esc_html($label); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</fieldset>

				<div class="yb-survey__detail">
					<label for="yb-survey-detail">
						<?php foreach ($prompts as $value => $prompt) : ?>
							<span class="yb-survey__prompt" data-for="<?php echo \esc_attr($value); ?>"><?php echo \esc_html($prompt); ?></span>
						<?php endforeach; ?>
						<span class="yb-survey__prompt yb-survey__prompt--fallback"><?php \esc_html_e('Anything to add?', 'youbetcha-cannabis-theme'); ?></span>
						<span class="yb-survey__optional"><?php \esc_html_e('(optional)', 'youbetcha-cannabis-theme'); ?></span>
					</label>
					<input type="text" id="yb-survey-detail" name="hear_about_us_detail" maxlength="200" autocomplete="off">
				</div>

				<label class="yb-survey__check">
					<input type="checkbox" name="wholesale_interest" value="1">
					<span>
						<?php \esc_html_e('I run a business and I\'m interested in wholesale.', 'youbetcha-cannabis-theme'); ?>
						<a href="<?php echo \esc_url(\home_url('/wholesale-registration/')); ?>"><?php \esc_html_e('Apply here', 'youbetcha-cannabis-theme'); ?></a>
					</span>
				</label>

				<button type="submit" class="yb-survey__submit"><?php \esc_html_e('Send', 'youbetcha-cannabis-theme'); ?></button>
			</form>
		</section>
		<?php

		return (string) \ob_get_clean();
	}

	/**
	 * "Thanks" state.
	 *
	 * @return string
	 */
	private function thanksMarkup(): string
	{
		return $this->noticeMarkup(\__('Thanks, that really helps us!', 'youbetcha-cannabis-theme'));
	}

	/**
	 * Simple message box.
	 *
	 * @param string $message Message.
	 *
	 * @return string
	 */
	private function noticeMarkup(string $message): string
	{
		return '<section class="yb-survey yb-survey--notice"><p class="yb-survey__intro">' . \esc_html($message) . '</p></section>';
	}
}
