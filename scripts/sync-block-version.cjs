const fs = require( 'node:fs' );
const path = require( 'node:path' );

const root = path.resolve( __dirname, '..' );
const header = fs
	.readFileSync( path.join( root, 'ruby-markup-converter.php' ) )
	.subarray( 0, 8192 )
	.toString( 'utf8' );
const match = header.match( /^[ \t/*#@]*Version:[ \t]*([^\r\n]+)/m );
const version = match && match[ 1 ].replace( /\s*(?:\*\/|\?>).*$/, '' ).trim();

if ( ! version || ! /^\d+(?:\.\d+)+(?:[-+][a-zA-Z0-9.-]+)?$/.test( version ) ) {
	throw new Error( 'Missing or invalid plugin Version header.' );
}

function findBlocks( directory ) {
	return fs.readdirSync( directory, { withFileTypes: true } ).flatMap( ( entry ) => {
		const file = path.join( directory, entry.name );
		if ( entry.isDirectory() ) {
			return findBlocks( file );
		}
		return entry.isFile() && entry.name === 'block.json' ? [ file ] : [];
	} );
}

// Validate all metadata before updating any file.
const blocks = findBlocks( path.join( root, 'editor/src' ) ).map( ( file ) => {
	const source = fs.readFileSync( file, 'utf8' );
	const metadata = JSON.parse( source );
	if ( ! metadata || Array.isArray( metadata ) || typeof metadata.name !== 'string' ) {
		throw new Error( 'Invalid block metadata: ' + file );
	}
	return { file, source, metadata };
} );
if ( blocks.length === 0 ) {
	throw new Error( 'No block.json files found in editor/src.' );
}
for ( const { file, source, metadata } of blocks ) {
	if ( metadata.version !== version ) {
		metadata.version = version;
		const indent = source.match( /\n([\t ]+)"/ );
		fs.writeFileSync( file, JSON.stringify( metadata, null, indent ? indent[ 1 ] : '\t' ) + '\n' );
	}
}
console.log( 'Block version synchronized: ' + version + ' (' + blocks.length + ' blocks).' );
