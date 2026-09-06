/**
 * Silence Media: mutes every <audio>/<video> on the page, including ones
 * that start playing after activation (autoplay carousels, embeds, etc.).
 * Restores each element's original muted state on disable.
 *
 * @package WP_A11y
 */

const originalMuted = new WeakMap();
let bound = false;

function rememberAndMute( el ) {
	if ( ! originalMuted.has( el ) ) {
		originalMuted.set( el, el.muted );
	}
	el.muted = true;
}

function onPlay( event ) {
	const el = event.target;
	if ( el && ( el.tagName === 'AUDIO' || el.tagName === 'VIDEO' ) ) {
		rememberAndMute( el );
	}
}

function enable() {
	if ( bound ) {
		return;
	}
	const wrap = document.getElementById( 'wp-a11y-content-wrap' );
	if ( wrap ) {
		wrap.querySelectorAll( 'audio, video' ).forEach( rememberAndMute );
	}
	// Capturing phase: `play` doesn't bubble, so listen on the way down.
	document.addEventListener( 'play', onPlay, true );
	bound = true;
}

function disable() {
	if ( ! bound ) {
		return;
	}
	document.removeEventListener( 'play', onPlay, true );
	const wrap = document.getElementById( 'wp-a11y-content-wrap' );
	if ( wrap ) {
		wrap.querySelectorAll( 'audio, video' ).forEach( ( el ) => {
			if ( originalMuted.has( el ) ) {
				el.muted = originalMuted.get( el );
				originalMuted.delete( el );
			}
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
