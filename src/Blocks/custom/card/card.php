<?php

/**
 * Template for the Card Block.
 *
 * @package YouBetchaCannabisTheme
 */

use YouBetchaCannabisThemeVendor\EightshiftLibs\Helpers\Components;

echo Components::render('card', Components::props('card', $attributes));
