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

	// Below 669px the mega menu doubles as the mobile menu: an injected
	// burger toggles it and the hover wiring below stands down.
	const mobileMq = window.matchMedia('(max-width: 668px)');

	// Find the Shop nav item (the <li> containing the /products/ link).
	const shopLink = document.querySelector(
		'.wp-block-navigation-item.has-child a[href*="/products/"]'
	);
	const shopLi = shopLink?.closest('.wp-block-navigation-item.has-child');
	if (!shopLi) return;

	let closeTimeout = null;
	let clickClosed = false;
	let isTouch = false;
	let burger = null; // injected below; open/close sync its aria state

	// Detect touch devices — set flag on first touch, clear on mouse movement.
	window.addEventListener('touchstart', () => { isTouch = true; }, { once: true, passive: true });

	function isMenuOpen() {
		return megaMenu.classList.contains('is-active');
	}

	function openMegaMenu() {
		clearTimeout(closeTimeout);
		megaMenu.classList.add('is-active');
		shopLi.classList.add('is-mega-active');

		if (mobileMq.matches) {
			// Pin the overlay just under the sticky header and lock the page.
			const header = document.querySelector('header.wp-block-template-part');
			const top = header ? Math.max(0, Math.round(header.getBoundingClientRect().bottom)) : 0;
			megaMenu.style.setProperty('--yb-mnav-top', `${top}px`);
			document.body.classList.add('yb-mnav-open');
		}

		burger?.setAttribute('aria-expanded', 'true');
	}

	function closeMegaMenu() {
		clearTimeout(closeTimeout);
		megaMenu.classList.remove('is-active');
		shopLi.classList.remove('is-mega-active');
		document.body.classList.remove('yb-mnav-open');
		burger?.setAttribute('aria-expanded', 'false');
	}

	function scheduleMegaMenuClose() {
		clearTimeout(closeTimeout);
		closeTimeout = setTimeout(closeMegaMenu, 250);
	}

	// Hover — desktop only (skip on touch devices and in mobile mode).
	shopLi.addEventListener('pointerenter', (e) => {
		if (e.pointerType === 'touch' || mobileMq.matches) return;
		clickClosed = false; // Reset on fresh hover enter.
		openMegaMenu();
	});

	shopLi.addEventListener('pointerleave', (e) => {
		if (e.pointerType === 'touch' || mobileMq.matches) return;
		clickClosed = false;
		scheduleMegaMenuClose();
	});

	// Keep open while pointer is over the mega menu itself.
	megaMenu.addEventListener('pointerenter', (e) => {
		if (e.pointerType === 'touch' || mobileMq.matches) return;
		if (clickClosed) return;
		openMegaMenu();
	});

	megaMenu.addEventListener('pointerleave', (e) => {
		if (e.pointerType === 'touch' || mobileMq.matches) return;
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

	// Close when clicking outside the Shop item, the mega menu, and the
	// mobile burger.
	document.addEventListener('click', (e) => {
		if (
			isMenuOpen() &&
			!megaMenu.contains(e.target) &&
			!shopLi.contains(e.target) &&
			!(burger && burger.contains(e.target))
		) {
			closeMegaMenu();
		}
	});

	// ── Mobile mode (≤668px): burger + accordions ───────────────────────
	// The burger lives in the header row (centered by _mobile-nav.scss and
	// hidden at desktop widths) and toggles the same mega menu.
	const headerColumns = document.querySelector(
		'header.wp-block-template-part .wc-blocks-header-pattern > .wp-block-columns'
	);

	if (headerColumns) {
		burger = document.createElement('button');
		burger.className = 'yb-mnav-burger';
		burger.setAttribute('aria-expanded', 'false');
		burger.setAttribute('aria-controls', 'mega-menu');
		burger.setAttribute('aria-label', 'Open menu');
		burger.innerHTML = '<span></span><span></span><span></span>';
		headerColumns.appendChild(burger);

		burger.addEventListener('click', () => {
			if (isMenuOpen()) {
				closeMegaMenu();
			} else {
				openMegaMenu();
			}
		});
	}

	// Accordions: every DDC-Hardware paragraph in the menus column is a
	// section header — the bordered pills (Day/Any/Night Time) and the
	// plain Strength / Additional Benefits headings. Its section is the
	// group that also holds the links; Strength/Benefits double-wrap the
	// header, so step up when the header is alone in its group.
	const heads = megaMenu.querySelectorAll(
		':scope > .wp-block-columns > .wp-block-column:nth-child(2) p[class*="ddc-hardware"]'
	);

	heads.forEach((head) => {
		let section = head.parentElement;
		if (section.querySelectorAll('p').length === 1) {
			section = section.parentElement;
		}

		head.classList.add('yb-mnav-head');
		section.classList.add('yb-mnav-section');

		// Non-pill headers (Strength, Additional Benefits) hold the long
		// lists — those flow in two columns when open.
		if (!head.classList.contains('has-border-color')) {
			section.classList.add('yb-mnav-section--cols');
		}

		section.addEventListener('click', (e) => {
			if (!mobileMq.matches) return;

			// Links navigate — except the header's own link, which toggles.
			const link = e.target.closest('a');
			if (link && !head.contains(link)) return;
			if (link) e.preventDefault();

			const opening = !section.classList.contains('is-open');
			megaMenu.querySelectorAll('.yb-mnav-section').forEach((other) => {
				other.classList.toggle('is-open', other === section && opening);
			});
		});
	});

	// Featured tiles (can/Beverages, pouch/Gummies): make the whole tile
	// tap through to its image link's destination.
	megaMenu
		.querySelectorAll(':scope > .wp-block-columns > .wp-block-column:first-child > .wp-block-group')
		.forEach((tile) => {
			tile.addEventListener('click', (e) => {
				if (!mobileMq.matches) return;
				if (e.target.closest('a')) return;

				const href = tile.querySelector('figure a')?.getAttribute('href');
				if (href) window.location.assign(href);
			});
		});

	// Leaving mobile mode with the menu open: reset to the desktop state.
	mobileMq.addEventListener('change', () => {
		closeMegaMenu();
	});
});