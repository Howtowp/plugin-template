/**
 * Build this plugin's admin bundle with the shared bluwp-vuejs kit.
 *
 * The kit source is not vendored here. Point this script at a checkout of
 * bluwp-vuejs pinned to the 0.2 line in kit.json, with an app at
 * apps/<slug>/. Output is written into assets/ by the kit (JS, CSS, fonts).
 * Do not copy or hand-edit the compiled CSS.
 *
 *   node development/build-admin.mjs [path-to-bluwp-vuejs] [--strict]
 *
 * Without the kit, the script warns and exits 0 so CI can run. --strict exits 1.
 */
import { execFileSync } from 'node:child_process'
import { existsSync, readFileSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' )
const args = process.argv.slice( 2 )
const strict = args.includes( '--strict' )
const rest = args.filter( ( arg ) => '--strict' !== arg )
const given = rest[0] && ! rest[0].startsWith( '-' ) ? rest.shift() : ''
const frontend = given || process.env.BLUWP_VUEJS || resolve( root, '..', 'bluwp-vuejs' )
const kit = JSON.parse( readFileSync( resolve( root, 'development', 'kit.json' ), 'utf8' ) )
const builder = resolve( frontend, 'tools', 'build-plugin.mjs' )
const source = resolve( frontend, 'apps', kit.app )

if ( ! existsSync( builder ) ) {
	const message = [
		`bluwp-vuejs was not found at ${ frontend }.`,
		'Admin assets were not rebuilt. Pass the kit checkout, or set BLUWP_VUEJS.',
		'See docs/design-system.md. Do not copy compiled kit CSS into the plugin.',
	].join( ' ' )
	if ( strict ) {
		console.error( message )
		process.exit( 1 )
	}
	console.warn( message )
	process.exit( 0 )
}

execFileSync( process.execPath, [ builder, root, '--source', source, ...rest ], { stdio: 'inherit' } )
