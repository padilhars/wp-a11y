/**
 * Applies the current settings to the page as body classes.
 *
 * Only options with `effect: 'css'` are handled here - the rest are Phase 3
 * JS-mounted overlay modules (screen reader, virtual keyboard, voice
 * commands, face navigation, reading guide/mask, magnifier, tooltips,
 * bionic reading, silence media) and have no visible effect yet.
 *
 * @package WP_A11y
 */

/**
 * camelCase id -> kebab-case CSS token, e.g. `textSize` -> `text-size`.
 *
 * @param {string} id
 * @return {string}
 */
export function kebab( id ) {
	return id.replace( /([A-Z])/g, '-$1' ).toLowerCase();
}

/**
 * Recompute and apply body classes for every css-driven option.
 *
 * @param {Object<string, boolean|number>} settings
 * @param {Array<{id: string, kind: string, max: number, effect: string}>} optionMeta
 */
export function apply( settings, optionMeta ) {
	const body = document.body;

	Array.from( body.classList )
		.filter( ( cls ) => cls.indexOf( 'a11y-' ) === 0 )
		.forEach( ( cls ) => body.classList.remove( cls ) );

	optionMeta
		.filter( ( option ) => option.effect === 'css' )
		.forEach( ( option ) => {
			const token = kebab( option.id );
			const value = settings[ option.id ];

			if ( option.kind === 'stepper' ) {
				const level = parseInt( value, 10 ) || 0;
				if ( level > 0 ) {
					body.classList.add( `a11y-${ token }-${ level }` );
				}
			} else if ( value === true ) {
				body.classList.add( `a11y-${ token }` );
			}
		} );

	// focusMode level 3 forces hideImages, same as local_a11y's manager.php.
	if ( ( parseInt( settings.focusMode, 10 ) || 0 ) >= 3 ) {
		body.classList.add( 'a11y-hide-images' );
	}
}
