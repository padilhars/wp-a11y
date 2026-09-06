/**
 * Magnifier: a circular lens that follows the mouse, showing a zoomed-in
 * clone of the page content under the cursor.
 *
 * Technique: clone `#wp-a11y-content-wrap` once (on enable), scale the
 * clone with a CSS transform, and re-translate it on every mousemove so
 * the point under the cursor in the *original* page stays centered inside
 * the lens. Cheap (just a transform update per frame), but the clone is a
 * snapshot - it won't reflect page changes that happen while the
 * magnifier stays on (acceptable for a first pass; re-cloned fresh every
 * time the option is turned back on).
 *
 * @package WP_A11y
 */

const LENS_SIZE = 220;
const ZOOM = 2.2;

let lens = null;
let clone = null;
let bound = false;

function buildLens() {
	lens = document.createElement( 'div' );
	lens.className = 'wp-a11y-magnifier-lens';
	lens.style.width = `${ LENS_SIZE }px`;
	lens.style.height = `${ LENS_SIZE }px`;

	const wrap = document.getElementById( 'wp-a11y-content-wrap' );
	clone = wrap ? wrap.cloneNode( true ) : document.createElement( 'div' );
	clone.removeAttribute( 'id' );
	clone.className = 'wp-a11y-magnifier-clone';
	clone.style.width = `${ wrap ? wrap.getBoundingClientRect().width : 0 }px`;

	lens.appendChild( clone );
	document.body.appendChild( lens );
}

function onMouseMove( event ) {
	if ( ! lens ) {
		return;
	}
	lens.style.left = `${ event.clientX - LENS_SIZE / 2 }px`;
	lens.style.top = `${ event.clientY - LENS_SIZE / 2 }px`;

	const wrap = document.getElementById( 'wp-a11y-content-wrap' );
	if ( ! wrap || ! clone ) {
		return;
	}
	const wrapRect = wrap.getBoundingClientRect();
	const relX = event.clientX - wrapRect.left;
	const relY = event.clientY - wrapRect.top;
	const tx = -relX * ZOOM + LENS_SIZE / 2;
	const ty = -relY * ZOOM + LENS_SIZE / 2;
	clone.style.transform = `translate(${ tx }px, ${ ty }px) scale(${ ZOOM })`;
}

function enable() {
	if ( bound ) {
		return;
	}
	buildLens();
	document.addEventListener( 'mousemove', onMouseMove );
	bound = true;
}

function disable() {
	if ( ! bound ) {
		return;
	}
	document.removeEventListener( 'mousemove', onMouseMove );
	if ( lens ) {
		lens.remove();
		lens = null;
		clone = null;
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
