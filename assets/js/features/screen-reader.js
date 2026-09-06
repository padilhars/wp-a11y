/**
 * Screen Reader: click-to-read text-to-speech using the browser's native
 * Web Speech API (`speechSynthesis`) - no audio is sent anywhere, this is
 * 100% local browser processing.
 *
 * While active, hovering a readable element inside the page content draws
 * an outline; clicking it (instead of activating the link/button normally)
 * reads it aloud and shows a small status pill with a stop button.
 *
 * @package WP_A11y
 */

const READABLE_SELECTOR = 'h1, h2, h3, h4, h5, h6, p, a, button, li, td, th, span, label, figcaption, [data-a11y-readable]';
const HOVER_CLASS = 'a11y-screenreader-hover';

let bound = false;
let statusEl = null;
let hoveredEl = null;

function ensureStatusEl() {
	if ( ! statusEl ) {
		statusEl = document.createElement( 'div' );
		statusEl.className = 'wp-a11y-screenreader-status';
		statusEl.hidden = true;

		const label = document.createElement( 'span' );
		label.className = 'wp-a11y-screenreader-status__text';

		const stopBtn = document.createElement( 'button' );
		stopBtn.type = 'button';
		stopBtn.className = 'wp-a11y-screenreader-status__stop';
		stopBtn.textContent = 'Parar';
		stopBtn.addEventListener( 'click', stopSpeaking );

		statusEl.appendChild( label );
		statusEl.appendChild( stopBtn );
		document.body.appendChild( statusEl );
	}
	return statusEl;
}

function findReadable( target ) {
	if ( ! target || ! target.closest ) {
		return null;
	}
	const wrap = target.closest( '#wp-a11y-content-wrap' );
	if ( ! wrap ) {
		return null;
	}
	return target.closest( READABLE_SELECTOR );
}

function onMouseOver( event ) {
	const el = findReadable( event.target );
	if ( el === hoveredEl ) {
		return;
	}
	if ( hoveredEl ) {
		hoveredEl.classList.remove( HOVER_CLASS );
	}
	hoveredEl = el;
	if ( hoveredEl ) {
		hoveredEl.classList.add( HOVER_CLASS );
	}
}

function stopSpeaking() {
	window.speechSynthesis.cancel();
	const el = ensureStatusEl();
	el.hidden = true;
}

function onClick( event ) {
	const el = findReadable( event.target );
	if ( ! el ) {
		return;
	}
	event.preventDefault();
	event.stopPropagation();

	const text = ( el.textContent || '' ).trim();
	if ( ! text ) {
		return;
	}

	window.speechSynthesis.cancel();
	const utterance = new SpeechSynthesisUtterance( text );
	utterance.lang = document.documentElement.lang || 'pt-BR';

	const status = ensureStatusEl();
	const label = status.querySelector( '.wp-a11y-screenreader-status__text' );
	label.textContent = text.length > 80 ? `${ text.slice( 0, 80 ) }…` : text;
	status.hidden = false;

	utterance.addEventListener( 'end', () => {
		status.hidden = true;
	} );
	utterance.addEventListener( 'error', () => {
		status.hidden = true;
	} );

	window.speechSynthesis.speak( utterance );
}

function enable() {
	if ( bound ) {
		return;
	}
	document.addEventListener( 'mouseover', onMouseOver, true );
	document.addEventListener( 'click', onClick, true );
	bound = true;
}

function disable() {
	if ( ! bound ) {
		return;
	}
	document.removeEventListener( 'mouseover', onMouseOver, true );
	document.removeEventListener( 'click', onClick, true );
	if ( hoveredEl ) {
		hoveredEl.classList.remove( HOVER_CLASS );
		hoveredEl = null;
	}
	window.speechSynthesis.cancel();
	if ( statusEl ) {
		statusEl.hidden = true;
	}
	bound = false;
}

/**
 * @param {boolean} active
 */
export function sync( active ) {
	if ( ! window.speechSynthesis ) {
		return; // unsupported browser: option simply has no effect.
	}
	if ( active ) {
		enable();
	} else {
		disable();
	}
}
