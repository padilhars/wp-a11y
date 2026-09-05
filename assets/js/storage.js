/**
 * Settings persistence.
 *
 * Phase 1: localStorage only, for every visitor (guest or logged-in).
 * Phase 2 will branch logged-in users to a REST-backed usermeta sync,
 * exactly like local_a11y's amd/src/storage.js branches on `isLoggedIn`.
 *
 * @package WP_A11y
 */

export const STORAGE_KEY = 'wp_a11y_settings';

/**
 * Build the all-false/all-zero defaults object from option metadata.
 *
 * @param {Array<{id: string, kind: string}>} optionMeta
 * @return {Object<string, boolean|number>}
 */
export function getDefaultSettings( optionMeta ) {
	const defaults = {};
	optionMeta.forEach( ( option ) => {
		defaults[ option.id ] = option.kind === 'stepper' ? 0 : false;
	} );
	return defaults;
}

/**
 * Validate/clamp a raw settings object against known option metadata.
 * Unknown keys are dropped; missing keys fall back to default; stepper
 * values are clamped to [0, max]; booleans accept loose "true"/1 forms.
 *
 * @param {*} raw
 * @param {Array<{id: string, kind: string, max: number}>} optionMeta
 * @return {Object<string, boolean|number>}
 */
export function sanitize( raw, optionMeta ) {
	const defaults = getDefaultSettings( optionMeta );
	const source = raw && typeof raw === 'object' ? raw : {};
	const clean = {};

	optionMeta.forEach( ( option ) => {
		const value = source[ option.id ];

		if ( option.kind === 'stepper' ) {
			const level = Number.isFinite( parseInt( value, 10 ) ) ? parseInt( value, 10 ) : 0;
			clean[ option.id ] = Math.max( 0, Math.min( option.max || 0, level ) );
		} else {
			clean[ option.id ] = value === true || value === 1 || value === '1' || value === 'true';
		}

		if ( ! ( option.id in source ) ) {
			clean[ option.id ] = defaults[ option.id ];
		}
	} );

	return clean;
}

/**
 * Read settings from localStorage (sanitized). Falls back to defaults on
 * missing/corrupt data - never throws.
 *
 * @param {Array<Object>} optionMeta
 * @return {Object<string, boolean|number>}
 */
export function getSettings( optionMeta ) {
	try {
		const raw = window.localStorage.getItem( STORAGE_KEY );
		if ( ! raw ) {
			return getDefaultSettings( optionMeta );
		}
		return sanitize( JSON.parse( raw ), optionMeta );
	} catch ( e ) {
		return getDefaultSettings( optionMeta );
	}
}

/**
 * Persist settings to localStorage.
 *
 * @param {Object<string, boolean|number>} settings
 */
export function saveSettings( settings ) {
	try {
		window.localStorage.setItem( STORAGE_KEY, JSON.stringify( settings ) );
	} catch ( e ) {
		/* storage unavailable (private mode, quota) - state stays in-memory only. */
	}
}
