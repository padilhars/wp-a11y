/**
 * Bionic Reading: bolds the first ~half of each word's letters, giving the
 * eye a faster anchor point per word. Reversible: every transformed text
 * node is wrapped in a single `span.a11y-bionic-wrap`, so disabling just
 * replaces each wrapper with a plain text node holding the same content.
 *
 * @package WP_A11y
 */

const SKIP_TAGS = new Set( [ 'SCRIPT', 'STYLE', 'TEXTAREA', 'INPUT', 'CODE', 'PRE', 'NOSCRIPT' ] );
const WORD_RE = /[A-Za-zÀ-ÖØ-öø-ÿ]+/g;
let active = false;

function escapeHtml( text ) {
	return text.replace( /&/g, '&amp;' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
}

function bionicHtml( text ) {
	return text.replace( WORD_RE, ( word ) => {
		if ( word.length < 2 ) {
			return word;
		}
		const boldLen = Math.ceil( word.length * 0.5 );
		return `<b class="a11y-bionic-b">${ word.slice( 0, boldLen ) }</b>${ word.slice( boldLen ) }`;
	} );
}

function shouldSkip( node ) {
	let el = node.parentElement;
	while ( el ) {
		if ( SKIP_TAGS.has( el.tagName ) || el.isContentEditable ) {
			return true;
		}
		if ( el.id === 'wp-a11y-content-wrap' ) {
			break;
		}
		el = el.parentElement;
	}
	return false;
}

function enable() {
	if ( active ) {
		return;
	}
	const wrap = document.getElementById( 'wp-a11y-content-wrap' );
	if ( ! wrap ) {
		return;
	}

	const walker = document.createTreeWalker( wrap, NodeFilter.SHOW_TEXT, null );
	const targets = [];
	let node;
	while ( ( node = walker.nextNode() ) ) {
		if ( node.nodeValue.trim() && ! shouldSkip( node ) ) {
			targets.push( node );
		}
	}

	targets.forEach( ( textNode ) => {
		const escaped = escapeHtml( textNode.nodeValue );
		const html = bionicHtml( escaped );
		if ( html === escaped ) {
			return; // no letters to bold (numbers/punctuation/whitespace only).
		}
		const span = document.createElement( 'span' );
		span.className = 'a11y-bionic-wrap';
		span.innerHTML = html;
		textNode.parentNode.replaceChild( span, textNode );
	} );

	active = true;
}

function disable() {
	if ( ! active ) {
		return;
	}
	const wrap = document.getElementById( 'wp-a11y-content-wrap' );
	if ( wrap ) {
		wrap.querySelectorAll( 'span.a11y-bionic-wrap' ).forEach( ( span ) => {
			const parent = span.parentNode;
			if ( ! parent ) {
				return;
			}
			parent.replaceChild( document.createTextNode( span.textContent ), span );
			parent.normalize();
		} );
	}
	active = false;
}

/**
 * @param {boolean} isActive
 */
export function sync( isActive ) {
	if ( isActive ) {
		enable();
	} else {
		disable();
	}
}
