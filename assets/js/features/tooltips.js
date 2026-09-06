/**
 * Tooltips: converts native `[title]` attributes (small, delayed, mouse-only,
 * never shown on keyboard focus) into a visible, always-consistent custom
 * tooltip bubble that also appears on keyboard focus.
 *
 * @package WP_A11y
 */

const DATA_ATTR = 'data-a11y-tooltip';
let bubble = null;
let bound = false;

function ensureBubble() {
	if ( ! bubble ) {
		bubble = document.createElement( 'div' );
		bubble.className = 'wp-a11y-tooltip-bubble';
		bubble.setAttribute( 'role', 'tooltip' );
		bubble.hidden = true;
		document.body.appendChild( bubble );
	}
	return bubble;
}

function show( target ) {
	const text = target.getAttribute( DATA_ATTR );
	if ( ! text ) {
		return;
	}
	const el = ensureBubble();
	el.textContent = text;
	el.hidden = false;
	const rect = target.getBoundingClientRect();
	el.style.top = `${ rect.bottom + window.scrollY + 6 }px`;
	el.style.left = `${ rect.left + window.scrollX }px`;
}

function hide() {
	if ( bubble ) {
		bubble.hidden = true;
	}
}

function onShowEvent( event ) {
	const target = event.target.closest( `[${ DATA_ATTR }]` );
	if ( target ) {
		show( target );
	}
}

function onHideEvent( event ) {
	const target = event.target.closest( `[${ DATA_ATTR }]` );
	if ( target ) {
		hide();
	}
}

function enable() {
	if ( bound ) {
		return;
	}
	const wrap = document.getElementById( 'wp-a11y-content-wrap' );
	if ( wrap ) {
		wrap.querySelectorAll( '[title]' ).forEach( ( el ) => {
			if ( ! el.hasAttribute( DATA_ATTR ) ) {
				el.setAttribute( DATA_ATTR, el.getAttribute( 'title' ) );
				el.removeAttribute( 'title' );
			}
		} );
	}
	document.addEventListener( 'mouseover', onShowEvent );
	document.addEventListener( 'mouseout', onHideEvent );
	document.addEventListener( 'focusin', onShowEvent );
	document.addEventListener( 'focusout', onHideEvent );
	bound = true;
}

function disable() {
	if ( ! bound ) {
		return;
	}
	document.removeEventListener( 'mouseover', onShowEvent );
	document.removeEventListener( 'mouseout', onHideEvent );
	document.removeEventListener( 'focusin', onShowEvent );
	document.removeEventListener( 'focusout', onHideEvent );
	hide();
	const wrap = document.getElementById( 'wp-a11y-content-wrap' );
	if ( wrap ) {
		wrap.querySelectorAll( `[${ DATA_ATTR }]` ).forEach( ( el ) => {
			el.setAttribute( 'title', el.getAttribute( DATA_ATTR ) );
			el.removeAttribute( DATA_ATTR );
		} );
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
