/**
 * This is the main entry point for project scripts used for the `WordPress frontend screen`.
 *
 * Usage: `WordPress frontend screen`.
 */

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

	function openMegaMenu() {
		clearTimeout(closeTimeout);
		megaMenu.classList.add('is-active');
	}

	function scheduleMegaMenuClose() {
		closeTimeout = setTimeout(() => {
			megaMenu.classList.remove('is-active');
		}, 200);
	}

	// Hover: open on Shop mouseenter, schedule close on mouseleave.
	shopLi.addEventListener('mouseenter', openMegaMenu);
	shopLi.addEventListener('mouseleave', scheduleMegaMenuClose);

	// Keep open while hovering on the mega menu itself.
	megaMenu.addEventListener('mouseenter', openMegaMenu);
	megaMenu.addEventListener('mouseleave', scheduleMegaMenuClose);

	// Click: toggle on Shop link click (prevent navigation, toggle mega menu).
	shopLink.addEventListener('click', (e) => {
		e.preventDefault();
		megaMenu.classList.toggle('is-active');
	});

	// Close on Escape key.
	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape' && megaMenu.classList.contains('is-active')) {
			megaMenu.classList.remove('is-active');
		}
	});

	// Close when clicking outside both Shop and mega menu.
	document.addEventListener('click', (e) => {
		if (
			megaMenu.classList.contains('is-active') &&
			!megaMenu.contains(e.target) &&
			!shopLi.contains(e.target)
		) {
			megaMenu.classList.remove('is-active');
		}
	});
});