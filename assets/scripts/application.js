/**
 * This is the main entry point for project scripts used for the `WordPress frontend screen`.
 *
 * Usage: `WordPress frontend screen`.
 */

// Quantity +/- buttons on cart page
document.addEventListener('click', (e) => {
	const btn = e.target.closest('.yb-qty-btn');
	if (!btn) return;

	const qty = btn.closest('.quantity');
	if (!qty) return;

	const input = qty.querySelector('input.qty');
	if (!input) return;

	const min = parseInt(input.min, 10) || 0;
	const max = parseInt(input.max, 10) || Infinity;
	const step = parseInt(input.step, 10) || 1;
	let val = parseInt(input.value, 10) || 0;

	if (btn.classList.contains('yb-qty-minus')) {
		val = Math.max(min, val - step);
	} else {
		val = Math.min(max, val + step);
	}

	input.value = val;
	input.dispatchEvent(new Event('change', { bubbles: true }));
});

document.addEventListener('DOMContentLoaded', () => {
	const megaMenu = document.querySelector('#mega-menu');
	if (!megaMenu) return;

	// Find the Shop nav item (the <li> containing the /products/ link).
	const shopLink = document.querySelector(
		'.wp-block-navigation-item.has-child a[href*="/products/"]'
	);
	const shopLi = shopLink?.closest('.wp-block-navigation-item.has-child');
	if (!shopLi) return;

	let closeTimeout = null;
	let clickClosed = false;
	let isTouch = false;

	// Detect touch devices — set flag on first touch, clear on mouse movement.
	window.addEventListener('touchstart', () => { isTouch = true; }, { once: true, passive: true });

	function isMenuOpen() {
		return megaMenu.classList.contains('is-active');
	}

	function openMegaMenu() {
		clearTimeout(closeTimeout);
		megaMenu.classList.add('is-active');
		shopLi.classList.add('is-mega-active');
	}

	function closeMegaMenu() {
		clearTimeout(closeTimeout);
		megaMenu.classList.remove('is-active');
		shopLi.classList.remove('is-mega-active');
	}

	function scheduleMegaMenuClose() {
		clearTimeout(closeTimeout);
		closeTimeout = setTimeout(closeMegaMenu, 250);
	}

	// Hover — desktop only (skip on touch devices).
	shopLi.addEventListener('pointerenter', (e) => {
		if (e.pointerType === 'touch') return;
		clickClosed = false; // Reset on fresh hover enter.
		openMegaMenu();
	});

	shopLi.addEventListener('pointerleave', (e) => {
		if (e.pointerType === 'touch') return;
		clickClosed = false;
		scheduleMegaMenuClose();
	});

	// Keep open while pointer is over the mega menu itself.
	megaMenu.addEventListener('pointerenter', (e) => {
		if (e.pointerType === 'touch') return;
		if (clickClosed) return;
		openMegaMenu();
	});

	megaMenu.addEventListener('pointerleave', (e) => {
		if (e.pointerType === 'touch') return;
		scheduleMegaMenuClose();
	});

	// Click: toggle on Shop link click.
	shopLink.addEventListener('click', (e) => {
		e.preventDefault();

		if (isMenuOpen()) {
			closeMegaMenu();
			clickClosed = true; // Prevent hover from reopening.
		} else {
			openMegaMenu();
			clickClosed = false;
		}
	});

	// Close on Escape key.
	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape' && isMenuOpen()) {
			closeMegaMenu();
		}
	});

	// Close when clicking outside both Shop and mega menu.
	document.addEventListener('click', (e) => {
		if (isMenuOpen() && !megaMenu.contains(e.target) && !shopLi.contains(e.target)) {
			closeMegaMenu();
		}
	});
});