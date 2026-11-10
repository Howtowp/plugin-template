#!/usr/bin/env node
/**
 * Adds a "Silence is golden" index.php to build/ and every sub-folder.
 * Runs automatically after `npm run build` (npm "postbuild"), because wp-scripts
 * empties build/ on every build. `bin/` is never shipped.
 */
const fs = require( 'fs' );
const path = require( 'path' );

const root = path.join( __dirname, '..', 'build' );
const contents = '<?php\n// Silence is golden.\n';

function walk( dir ) {
	fs.writeFileSync( path.join( dir, 'index.php' ), contents );
	for ( const entry of fs.readdirSync( dir, { withFileTypes: true } ) ) {
		if ( entry.isDirectory() ) {
			walk( path.join( dir, entry.name ) );
		}
	}
}

if ( fs.existsSync( root ) ) {
	walk( root );
}
