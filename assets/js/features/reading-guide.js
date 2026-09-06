/**
 * Reading Guide: a highlighted horizontal band that follows the mouse
 * vertically, helping the eye keep its place while reading long content.
 *
 * @package WP_A11y
 */

let el = null;
let bound = false;

function ensureEl() {
	if ( ! el ) {
		el = document.createElement( 'div' );
		el.className = 'wp-a11y-reading-guide';
		document.body.appendChild( el );
	}
	return el;
}

function onMouseMove( event ) {
	const guide = ensureEl();
	guide.style.top = `${ event.clientY - 18 }px`;
}

function enable() {
	if ( bound ) {
		return;
	}
	ensureEl().hidden = false;
	document.addEventListener( 'mousemove', onMouseMove );
	bound = true;
}

function disable() {
	if ( ! bound ) {
		return;
	}
	document.removeEventListener( 'mousemove', onMouseMove );
	if ( el ) {
		el.hidden = true;
	}
	bound = false;
}

/**
 * @param {boolean} active
 */
export function sync( active ) {
	if ( active ) {
		enable();
	} else {
		disable();
	}
}
