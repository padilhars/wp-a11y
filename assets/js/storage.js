/**
 * Settings persistence.
 *
 * Guests: localStorage only. Logged-in users: REST-backed usermeta sync
 * (Phase 2), with a one-time migration of any pre-existing localStorage
 * blob into the server on first login with no stored preference yet -
 * mirrors local_a11y's amd/src/storage.js exactly.
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

		if ( ! ( option.id in source ) ) {
			clean[ option.id ] = defaults[ option.id ];
			return;
		}

		if ( option.kind === 'stepper' ) {
			const level = Number.isFinite( parseInt( value, 10 ) ) ? parseInt( value, 10 ) : 0;
			clean[ option.id ] = Math.max( 0, Math.min( option.max || 0, level ) );
		} else {
			clean[ option.id ] = value === true || value === 1 || value === '1' || value === 'true';
		}
	} );

	return clean;
}

/**
 * Whether a settings object is identical to the all-defaults object -
 * used as a (imperfect, but same as Moodle's) heuristic for "the server
 * has no real stored preference yet".
 *
 * @param {Object<string, boolean|number>} settings
 * @param {Object<string, boolean|number>} defaults
 * @return {boolean}
 */
function isAllDefaults( settings, defaults ) {
	return Object.keys( defaults ).every( ( id ) => settings[ id ] === defaults[ id ] );
}

function readLocal( optionMeta ) {
	try {
		const raw = window.localStorage.getItem( STORAGE_KEY );
		if ( ! raw ) {
			return null;
		}
		return sanitize( JSON.parse( raw ), optionMeta );
	} catch ( e ) {
		return null;
	}
}

function writeLocal( settings ) {
	try {
		window.localStorage.setItem( STORAGE_KEY, JSON.stringify( settings ) );
	} catch ( e ) {
		/* storage unavailable (private mode, quota) - state stays in-memory only. */
	}
}

function clearLocal() {
	try {
		window.localStorage.removeItem( STORAGE_KEY );
	} catch ( e ) {
		/* nothing to do. */
	}
}

async function restRequest( config, method, body ) {
	const response = await fetch( `${ config.restUrl }/settings`, {
		method,
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': config.nonce,
		},
		body: body ? JSON.stringify( body ) : undefined,
	} );

	if ( ! response.ok ) {
		throw new Error( `wp-a11y REST ${ method } /settings failed: ${ response.status }` );
	}

	return response.json();
}

/**
 * Read the effective settings for the current visitor.
 *
 * @param {Array<Object>} optionMeta
 * @param {{isLoggedIn: boolean, restUrl: string, nonce: string}} config
 * @return {Promise<Object<string, boolean|number>>}
 */
export async function getSettings( optionMeta, config ) {
	const defaults = getDefaultSettings( optionMeta );

	if ( ! config.isLoggedIn ) {
		return readLocal( optionMeta ) || defaults;
	}

	let serverSettings;
	try {
		serverSettings = sanitize( await restRequest( config, 'GET' ), optionMeta );
	} catch ( e ) {
		// Network hiccup: fall back to whatever's local, or defaults.
		return readLocal( optionMeta ) || defaults;
	}

	// One-time guest->user migration: only if the server looks untouched
	// AND there's a genuinely different local blob to migrate.
	const local = readLocal( optionMeta );
	if ( local && isAllDefaults( serverSettings, defaults ) && ! isAllDefaults( local, defaults ) ) {
		try {
			const migrated = sanitize( await restRequest( config, 'POST', local ), optionMeta );
			clearLocal();
			return migrated;
		} catch ( e ) {
			// Migration failed - keep using the local copy for this session,
			// try again next load.
			return local;
		}
	}

	return serverSettings;
}

/**
 * Persist settings for the current visitor.
 *
 * @param {Object<string, boolean|number>} settings
 * @param {{isLoggedIn: boolean, restUrl: string, nonce: string}} config
 */
export async function saveSettings( settings, config ) {
	if ( ! config.isLoggedIn ) {
		writeLocal( settings );
		return;
	}

	try {
		await restRequest( config, 'POST', settings );
	} catch ( e ) {
		// Keep a local cache so the change isn't silently lost on a flaky
		// connection - not authoritative, just a best-effort fallback.
		writeLocal( settings );
	}
}
