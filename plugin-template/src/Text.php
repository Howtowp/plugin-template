<?php
/**
 * User-facing strings.
 *
 * English is the source language. Each case value is the msgid. catalog()
 * passes that literal to __() so the fa_IR catalog can replace it. Customer
 * cases listed in customer_map() may still be overridden in htw_pt_texts.
 *
 * PluginName stays the human plugin name so scripts/init.sh can rename it.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate;

defined( 'ABSPATH' ) || exit;

enum Text: string {
	case PluginName        = 'Plugin Template';
	case Settings          = 'Settings';
	case Enabled           = 'Enabled';
	case Notice            = 'Admin note';
	case NoticeHint        = 'This text is shown only in the dashboard.';
	case SiteUrl           = 'Site address';
	case SiteUrlHint       = 'An http or https address, left to right.';
	case CustomerHello     = 'Hello, welcome.';
	case CustomerHelloHint = 'Customer text. Leave empty to use the default.';
	case DeleteData        = 'Delete data when the plugin is deleted';
	case Save              = 'Save';
	case Saved             = 'Settings saved.';
	case SaveFailed        = 'Settings could not be saved.';
	case InvalidSettings   = 'The value is not valid.';
	case InvalidUrl        = 'The address must be a valid http or https URL.';
	case Forbidden         = 'You are not allowed to do this.';
	case BluwpRequired     = 'Bluwp is not bundled with this plugin, or it is not compatible with the 0.3 line.';
	case SampleCron        = 'Sample schedule';

	public const OPTION = 'htw_pt_texts';

	/**
	 * Case name => legacy option key. Only these cases can be overridden.
	 *
	 * @return array<string, string>
	 */
	private static function customer_map(): array {
		return array(
			self::CustomerHello->name => 'customer_hello',
		);
	}

	/**
	 * Customer-facing cases, in enum order.
	 *
	 * @return array<int, self>
	 */
	public static function customer_texts(): array {
		$names = array_keys( self::customer_map() );
		return array_values(
			array_filter(
				self::cases(),
				static function ( Text $text ) use ( $names ): bool {
					return in_array( $text->name, $names, true );
				}
			)
		);
	}

	/**
	 * Translated catalog string. Customer overrides are not applied.
	 */
	public function catalog(): string {
		if ( ! function_exists( '__' ) ) {
			return $this->value;
		}
		return match ( $this ) {
			self::PluginName => __( 'Plugin Template', 'plugin-template' ),
			self::Settings => __( 'Settings', 'plugin-template' ),
			self::Enabled => __( 'Enabled', 'plugin-template' ),
			self::Notice => __( 'Admin note', 'plugin-template' ),
			self::NoticeHint => __( 'This text is shown only in the dashboard.', 'plugin-template' ),
			self::SiteUrl => __( 'Site address', 'plugin-template' ),
			self::SiteUrlHint => __( 'An http or https address, left to right.', 'plugin-template' ),
			self::CustomerHello => __( 'Hello, welcome.', 'plugin-template' ),
			self::CustomerHelloHint => __( 'Customer text. Leave empty to use the default.', 'plugin-template' ),
			self::DeleteData => __( 'Delete data when the plugin is deleted', 'plugin-template' ),
			self::Save => __( 'Save', 'plugin-template' ),
			self::Saved => __( 'Settings saved.', 'plugin-template' ),
			self::SaveFailed => __( 'Settings could not be saved.', 'plugin-template' ),
			self::InvalidSettings => __( 'The value is not valid.', 'plugin-template' ),
			self::InvalidUrl => __( 'The address must be a valid http or https URL.', 'plugin-template' ),
			self::Forbidden => __( 'You are not allowed to do this.', 'plugin-template' ),
			self::BluwpRequired => __(
				'Bluwp is not bundled with this plugin, or it is not compatible with the 0.3 line.',
				'plugin-template'
			),
			self::SampleCron => __( 'Sample schedule', 'plugin-template' ),
		};
	}

	/**
	 * Resolved string: override for customer cases, otherwise the translation.
	 */
	public function text(): string {
		$map = self::customer_map();
		if ( ! isset( $map[ $this->name ] ) ) {
			return $this->catalog();
		}
		$overrides = self::overrides();
		$override  = $overrides[ $this->name ] ?? null;
		if ( is_string( $override ) && '' !== $override ) {
			return $override;
		}
		return $this->catalog();
	}

	/**
	 * Stored customer overrides keyed by enum case name.
	 *
	 * Legacy keys in customer_map() are still read.
	 *
	 * @return array<string, string>
	 */
	public static function overrides(): array {
		if ( ! function_exists( 'get_option' ) ) {
			return array();
		}
		$stored = get_option( self::OPTION );
		if ( ! is_array( $stored ) ) {
			return array();
		}
		$clean = array();
		foreach ( self::customer_map() as $name => $legacy ) {
			$value = $stored[ $name ] ?? ( $stored[ $legacy ] ?? null );
			if ( is_string( $value ) ) {
				$clean[ $name ] = $value;
			}
		}
		return $clean;
	}

	/**
	 * Replace enum cases inside schema arrays with their resolved text.
	 *
	 * @param array<mixed> $items Schema fragments that may contain Text cases.
	 * @return array<mixed>
	 */
	public static function resolve( array $items ): array {
		return array_map(
			static function ( mixed $item ): mixed {
				if ( $item instanceof self ) {
					return $item->text();
				}
				if ( is_array( $item ) ) {
					return self::resolve( $item );
				}
				return $item;
			},
			$items
		);
	}

	/**
	 * Persist one customer override. Empty or the default clears the override.
	 *
	 * The caller has already checked length. Tags are stripped again here.
	 *
	 * @param string $value Submitted customer text.
	 */
	public static function save_customer( string $value ): bool {
		$value = wp_strip_all_tags( $value );
		if ( 1 !== preg_match( '/\A.{0,500}\z/us', $value ) ) {
			return false;
		}
		$name     = self::CustomerHello->name;
		$stored   = self::overrides();
		$previous = $stored;
		$default  = self::CustomerHello->catalog();
		if ( '' === trim( $value ) || self::CustomerHello->value === $value || $default === $value ) {
			unset( $stored[ $name ] );
		} else {
			$stored[ $name ] = $value;
		}
		if ( $stored === $previous ) {
			return true;
		}
		if ( ! function_exists( 'update_option' ) ) {
			return false;
		}
		return false !== update_option( self::OPTION, $stored, false );
	}
}
