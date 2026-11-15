<?php
/**
 * JSON schema for the sample settings form.
 *
 * The Bluwp Vue kit renders this. PHP does not print the admin page.
 * A URL field sets direction to ltr so it stays left-to-right inside the RTL shell.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\App\Schemas;

defined( 'ABSPATH' ) || exit;

use HowToWP\PluginTemplate\App\Repositories\SettingsRepository;
use HowToWP\PluginTemplate\Text;

/**
 * Form contract consumed by the admin kit.
 */
final class SettingsSchema {

	/**
	 * Schema for the current stored values.
	 *
	 * @return array<string, mixed>
	 */
	public static function make(): array {
		$stored   = SettingsRepository::all();
		$override = Text::overrides()[ Text::CustomerHello->name ] ?? '';
		$schema   = array(
			'id'     => 'settings',
			'type'   => 'form',
			'title'  => Text::Settings->text(),
			'layout' => array(
				'columns' => 2,
			),
			'fields' => array(
				self::field( 'enabled', Text::Enabled->text(), $stored['enabled'], 'switch' ) + array(
					'inlineLabel' => true,
				),
				self::field(
					'delete_data_on_uninstall',
					Text::DeleteData->text(),
					$stored['delete_data_on_uninstall'],
					'switch'
				) + array(
					'inlineLabel' => true,
				),
				self::field( 'site_url', Text::SiteUrl->text(), $stored['site_url'] ) + array(
					'direction'   => 'ltr',
					'description' => Text::SiteUrlHint->text(),
				),
				self::field( 'notice', Text::Notice->text(), $stored['notice'], 'textarea' ) + array(
					'rows'        => 2,
					'fullWidth'   => true,
					'description' => Text::NoticeHint->text(),
				),
				self::field( 'customer_hello', Text::CustomerHello->text(), $override, 'textarea' ) + array(
					'rows'        => 2,
					'fullWidth'   => true,
					'placeholder' => Text::CustomerHello->value,
					'description' => Text::CustomerHelloHint->text(),
				),
			),
			'submit' => array(
				'label'    => Text::Save->text(),
				'endpoint' => 'admin/settings',
			),
		);

		if ( ! function_exists( 'apply_filters' ) ) {
			return $schema;
		}
		$filtered = apply_filters( 'htw_pt_settings_schema', $schema );
		return is_array( $filtered ) ? $filtered : $schema;
	}

	/**
	 * One form field.
	 *
	 * @param string $name  Field name.
	 * @param string $label Visible label.
	 * @param mixed  $value Stored or empty value.
	 * @param string $type  Kit field type.
	 * @return array<string, mixed>
	 */
	private static function field( string $name, string $label, mixed $value, string $type = 'text' ): array {
		return array(
			'name'     => $name,
			'label'    => $label,
			'type'     => $type,
			'default'  => $value,
			'required' => false,
		);
	}
}
