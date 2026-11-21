<?php
/**
 * Sample schema and Text enum, without WordPress.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Tests\Unit;

use HowToWP\PluginTemplate\App\Repositories\SettingsRepository;
use HowToWP\PluginTemplate\App\Schemas\SettingsSchema;
use HowToWP\PluginTemplate\Text;
use PHPUnit\Framework\TestCase;

final class SkeletonTest extends TestCase {

	public function test_text_enum_resolves_without_wordpress(): void {
		$this->assertSame( 'Hello, welcome.', Text::CustomerHello->text() );
		$this->assertSame( 'Hello, welcome.', Text::CustomerHello->catalog() );
		$this->assertSame( array(), Text::overrides() );
		$this->assertContains( Text::CustomerHello, Text::customer_texts() );
		$resolved = Text::resolve(
			array(
				'label' => Text::Save,
				'nested' => array( Text::Settings ),
			)
		);
		$this->assertSame(
			array(
				'label' => 'Save',
				'nested' => array( 'Settings' ),
			),
			$resolved
		);
	}

	public function test_settings_schema_is_a_form_with_an_ltr_url(): void {
		$schema = SettingsSchema::make();
		$this->assertSame( 'form', $schema['type'] );
		$this->assertSame( 'admin/settings', $schema['submit']['endpoint'] );
		$url = null;
		foreach ( $schema['fields'] as $field ) {
			if ( 'site_url' === $field['name'] ) {
				$url = $field;
			}
		}
		$this->assertIsArray( $url );
		$this->assertSame( 'ltr', $url['direction'] );
	}

	public function test_boolean_normalizer_accepts_only_truthy_tokens(): void {
		$this->assertTrue( SettingsRepository::is_on( true ) );
		$this->assertTrue( SettingsRepository::is_on( 1 ) );
		$this->assertTrue( SettingsRepository::is_on( 'yes' ) );
		$this->assertFalse( SettingsRepository::is_on( '0' ) );
		$this->assertFalse( SettingsRepository::is_on( 'false' ) );
		$this->assertFalse( SettingsRepository::is_on( null ) );
		$this->assertSame( '', SettingsRepository::all()['notice'] );
	}
}
