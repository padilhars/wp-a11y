/**
 * Reading Mask: dims everything above and below a horizontal "window"
 * strip that follows the mouse, isolating the current line being read.
 *
 * @package WP_A11y
 */

const WINDOW_HALF_HEIGHT = 60;

let topEl = null;
let bottomEl = null;
let bound = false;

function ensureEls() {
	if ( ! topEl ) {
		topEl = document.createElement( 'div' );
		topEl.className = 'wp-a11y-reading-mask wp-a11y-reading-mask--top';
		document.body.appendChild( topEl );
	}
	if ( ! bottomEl ) {
		bottomEl = document.createElement( 'div' );
		bottomEl.className = 'wp-a11y-reading-mask wp-a11y-reading-mask--bottom';
		document.body.appendChild( bottomEl );
	}
	return { topEl, bottomEl };
}

function onMouseMove( event ) {
	const { topEl: top, bottomEl: bottom } = ensureEls();
	const windowTop = Math.max( 0, event.clientY - WINDOW_HALF_HEIGHT );
	const windowBottom = event.clientY + WINDOW_HALF_HEIGHT;
	top.style.height = `${ windowTop }px`;
	bottom.style.top = `${ windowBottom }px`;
}

function enable() {
	if ( bound ) {
		return;
	}
	const { topEl: top, bottomEl: bottom } = ensureEls();
	top.hidden = false;
	bottom.hidden = false;
	document.addEventListener( 'mousemove', onMouseMove );
	bound = true;
}

function disable() {
	if ( ! bound ) {
		return;
	}
	document.removeEventListener( 'mousemove', onMouseMove );
	if ( topEl ) {
		topEl.hidden = true;
	}
	if ( bottomEl ) {
		bottomEl.hidden = true;
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
