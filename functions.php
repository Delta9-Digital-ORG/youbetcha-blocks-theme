<?php

/**
 * Theme Name: You Betcha Cannabis
 * Description: You Betcha Cannabis
 * Author: Eightshift team
 * Author URI: https://eightshift.com/
 * Version: 1.0.0
 * License: MIT
 * License URI: http://www.gnu.org/licenses/gpl.html
 * Text Domain: you-betcha-cannabis
 *
 * @package YouBetchaCannabis
 */

declare(strict_types=1);

namespace YouBetchaCannabis;

use EightshiftLibs\Cli\Cli;

/**
 * If this file is called directly, abort.
 */
if (! \defined('WPINC')) {
	die;
}

/**
 * Include the autoloader so we can dynamically include the rest of the classes.
 */
if (!\file_exists(__DIR__ . '/vendor/autoload.php')) {
	return;
}

require __DIR__ . '/vendor/autoload.php';

/**
 * Run all WPCLI commands.
 */
if (\class_exists(Cli::class)) {
	(new Cli())->load('boilerplate');
}
