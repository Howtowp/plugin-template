<?php
/**
 * Persian UI strings.
 *
 * Admin and customer copy for a How to WP plugin lives in this enum, not in a
 * gettext catalog. Customer-facing cases listed in customer_map() may be
 * overridden in the option htw_pt_texts. Every other case returns its value.
 *
 * PluginName stays the human plugin name so scripts/init.sh can rename it.
 * Rewrite the other cases in Persian for the product.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate;

defined( 'ABSPATH' ) || exit;

enum Text: string {
	case PluginName        = 'Plugin Template';
	case Settings          = 'تنظیمات';
	case Enabled           = 'فعال';
	case Notice            = 'یادداشت مدیریت';
	case NoticeHint        = 'این متن فقط در پیشخوان نشان داده می‌شود.';
	case SiteUrl           = 'نشانی سایت';
	case SiteUrlHint       = 'نشانی http یا https، چپ‌به‌راست.';
	case CustomerHello     = 'سلام، خوش آمدید.';
	case CustomerHelloHint = 'متن مشتری. خالی یعنی متن پیش‌فرض.';
	case DeleteData        = 'هنگام حذف افزونه، داده‌ها پاک شوند';
	case Save              = 'ذخیره';
	case Saved             = 'تنظیمات ذخیره شد.';
	case SaveFailed        = 'ذخیرهٔ تنظیمات انجام نشد.';
	case InvalidSettings   = 'مقدار واردشده معتبر نیست.';
	case InvalidUrl        = 'نشانی باید یک URL معتبر http یا https باشد.';
	case Forbidden         = 'اجازهٔ این کار را ندارید.';
	case BluwpRequired     = 'Bluwp از ۰.۲.۱ تا پیش از ۰.۳ لازم است.';
	case SampleCron        = 'نمونهٔ زمان‌بندی';

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
	 * Resolved string: override for customer cases, otherwise the enum value.
	 */
	public function text(): string {
		$map = self::customer_map();
		if ( ! isset( $map[ $this->name ] ) ) {
			return $this->value;
		}
		$overrides = self::overrides();
		if ( isset( $overrides[ $this->name ] ) && is_string( $overrides[ $this->name ] ) ) {
			return $overrides[ $this->name ];
		}
		return $this->value;
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
		if ( '' === trim( $value ) || self::CustomerHello->value === $value ) {
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
