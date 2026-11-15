<?php
/**
 * Posted arrays are sanitized with map_deep before the caller sees them.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Tests\Unit;

use HowToWP\PluginTemplate\Support\Posted;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/posted-stubs.php';

final class PostedTest extends TestCase {

	protected function tearDown(): void {
		$_POST = array();
		parent::tearDown();
	}

	public function test_map_deep_sanitizes_nested_values_before_return(): void {
		$_POST = array(
			'_wpnonce' => 'ok',
			'rows'     => array( ' <b>a</b> ', array( ' x ' ) ),
		);

		$clean = Posted::array( 'rows', 'save_sample' );

		$this->assertSame( array( 'a', array( 'x' ) ), $clean );
	}

	public function test_a_bad_nonce_returns_an_empty_array(): void {
		$_POST = array(
			'_wpnonce' => 'no',
			'rows'     => array( 'a' ),
		);

		$this->assertSame( array(), Posted::array( 'rows', 'save_sample' ) );
	}
}
