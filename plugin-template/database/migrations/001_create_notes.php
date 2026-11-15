<?php
/**
 * Example migration. Bluwp runs files in database/migrations in order.
 *
 * The third argument of create() skips the table when it already exists.
 * down() refuses to drop it: a later migration must not destroy rows on rollback.
 * The settings screen stores its values in an option; this table is only the pattern.
 *
 * There is no index.php in this directory. Bluwp loads every PHP file here.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

use Forouzandea\Bluwp\Database\Migrations\Migration;
use Forouzandea\Bluwp\Database\Schema\Blueprint;

defined( 'ABSPATH' ) || exit;

return new class() extends Migration {

	/**
	 * Create the notes table if it is missing.
	 */
	public function up(): void {
		$this->schema()->create(
			'htw_pt_notes',
			static function ( Blueprint $table ): void {
				$table->id();
				$table->string( 'title', 160 );
				$table->longText( 'body' );
				$table->boolean( 'enabled' );
			},
			true
		);
	}

	/**
	 * Live rows are not dropped from a migration.
	 *
	 * @throws \LogicException Always. Rollback must not delete stored notes.
	 */
	public function down(): void {
		throw new \LogicException( 'Refusing to drop htw_pt_notes.' );
	}
};
