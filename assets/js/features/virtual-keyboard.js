/**
 * Virtual Keyboard: an on-screen QWERTY (pt-BR extras) keyboard that
 * appears at the bottom of the viewport whenever an editable field inside
 * the page content is focused, and types into it via `execCommand`
 * (correctly preserves cursor position/selection and fires real `input`
 * events, unlike manually poking `.value`).
 *
 * @package WP_A11y
 */

const ROWS = [
	[ '1', '2', '3', '4', '5', '6', '7', '8', '9', '0' ],
	[ 'q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p' ],
	[ 'a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'ç' ],
	[ 'shift', 'z', 'x', 'c', 'v', 'b', 'n', 'm', 'backspace' ],
	[ 'á', 'é', 'í', 'ó', 'ú', 'â', 'ê', 'ô', 'ã', 'õ' ],
	[ 'space', 'enter' ],
];

let bound = false;
let keyboardEl = null;
let target = null;
let shiftOn = false;

function isEditable( el ) {
	if ( ! el ) {
		return false;
	}
	if ( el.isContentEditable ) {
		return true;
	}
	const tag = el.tagName;
	if ( tag === 'TEXTAREA' ) {
		return true;
	}
	if ( tag === 'INPUT' ) {
		const type = ( el.getAttribute( 'type' ) || 'text' ).toLowerCase();
		return [ 'text', 'search', 'email', 'url', 'tel', 'number', 'password' ].includes( type );
	}
	return false;
}

function focusTarget() {
	if ( target ) {
		target.focus();
	}
}

function insertText( text ) {
	focusTarget();
	if ( ! document.execCommand( 'insertText', false, text ) ) {
		// Fallback for browsers without execCommand support.
		if ( target && 'value' in target ) {
			const start = target.selectionStart ?? target.value.length;
			const end = target.selectionEnd ?? target.value.length;
			target.value = target.value.slice( 0, start ) + text + target.value.slice( end );
			target.selectionStart = target.selectionEnd = start + text.length;
			target.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		}
	}
}

function deleteBack() {
	focusTarget();
	if ( ! document.execCommand( 'delete', false, null ) ) {
		if ( target && 'value' in target ) {
			const start = target.selectionStart ?? target.value.length;
			const end = target.selectionEnd ?? target.value.length;
			if ( start === end && start > 0 ) {
				target.value = target.value.slice( 0, start - 1 ) + target.value.slice( end );
				target.selectionStart = target.selectionEnd = start - 1;
			} else {
				target.value = target.value.slice( 0, start ) + target.value.slice( end );
				target.selectionStart = target.selectionEnd = start;
			}
			target.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		}
	}
}

function keyLabel( key ) {
	if ( 'backspace' === key ) {
		return '⌫';
	}
	if ( 'enter' === key ) {
		return '⏎';
	}
	if ( 'space' === key ) {
		return '␣';
	}
	if ( 'shift' === key ) {
		return '⇧';
	}
	return shiftOn ? key.toUpperCase() : key;
}

function renderKeys() {
	if ( ! keyboardEl ) {
		return;
	}
	keyboardEl.querySelectorAll( '.wp-a11y-vk-key' ).forEach( ( btn ) => {
		btn.textContent = keyLabel( btn.dataset.key );
		btn.classList.toggle( 'is-active', 'shift' === btn.dataset.key && shiftOn );
	} );
}

function handleKey( key ) {
	if ( 'shift' === key ) {
		shiftOn = ! shiftOn;
		renderKeys();
		return;
	}
	if ( 'backspace' === key ) {
		deleteBack();
		return;
	}
	if ( 'space' === key ) {
		insertText( ' ' );
		return;
	}
	if ( 'enter' === key ) {
		insertText( '\n' );
		return;
	}
	insertText( shiftOn ? key.toUpperCase() : key );
	if ( shiftOn ) {
		shiftOn = false;
		renderKeys();
	}
}

function buildKeyboard() {
	keyboardEl = document.createElement( 'div' );
	keyboardEl.className = 'wp-a11y-virtual-keyboard';
	keyboardEl.hidden = true;

	ROWS.forEach( ( row ) => {
		const rowEl = document.createElement( 'div' );
		rowEl.className = 'wp-a11y-vk-row';
		row.forEach( ( key ) => {
			const btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = `wp-a11y-vk-key wp-a11y-vk-key--${ key.length > 1 ? key : 'char' }`;
			btn.dataset.key = key;
			btn.textContent = keyLabel( key );
			// Keep focus on the real input - a normal click would blur it.
			btn.addEventListener( 'mousedown', ( e ) => e.preventDefault() );
			btn.addEventListener( 'click', () => handleKey( key ) );
			rowEl.appendChild( btn );
		} );
		keyboardEl.appendChild( rowEl );
	} );

	document.body.appendChild( keyboardEl );
}

function onFocusIn( event ) {
	const el = event.target;
	if ( ! el.closest || ! el.closest( '#wp-a11y-content-wrap' ) || ! isEditable( el ) ) {
		return;
	}
	target = el;
	if ( ! keyboardEl ) {
		buildKeyboard();
	}
	keyboardEl.hidden = false;
}

function onFocusOut( event ) {
	// Losing focus to one of our own keyboard buttons doesn't count -
	// mousedown already preventDefault()s that, but stay defensive.
	if ( event.relatedTarget && keyboardEl && keyboardEl.contains( event.relatedTarget ) ) {
		return;
	}
	if ( keyboardEl ) {
		keyboardEl.hidden = true;
	}
	target = null;
}

function enable() {
	if ( bound ) {
		return;
	}
	document.addEventListener( 'focusin', onFocusIn );
	document.addEventListener( 'focusout', onFocusOut );
	bound = true;
}

function disable() {
	if ( ! bound ) {
		return;
	}
	document.removeEventListener( 'focusin', onFocusIn );
	document.removeEventListener( 'focusout', onFocusOut );
	if ( keyboardEl ) {
		keyboardEl.remove();
		keyboardEl = null;
	}
	target = null;
	shiftOn = false;
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
