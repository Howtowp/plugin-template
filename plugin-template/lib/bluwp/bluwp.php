<?php
declare(strict_types=1);
/**
 * Plugin Name: Bluwp
 * Description: Shared Bluwp framework provider.
 * Version: 0.3.0
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Text Domain: bluwp
 * Domain Path: /lang
 */
defined('ABSPATH') || exit;
require_once __DIR__ . '/bridge.php';
// WordPress owns standalone inclusion. Embedded consumers use the resolver.
BluwpBridgeV1::provide(__DIR__);
