/**
 * Fire-and-forget activation counter. Server re-checks whether stats
 * collection is actually enabled and whether the id is real - this client
 * flag is only an optimization to skip pointless requests, never trusted.
 *
 * @package WP_A11y
 */

/**
 * @param {string} featureid
 * @param {{restUrl: string, nonce: string}} config
 */
export function recordActivation( featureid, config ) {
	fetch( `${ config.restUrl }/activate`, {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': config.nonce,
		},
		body: JSON.stringify( { featureid } ),
	} ).catch( () => {
		/* best-effort only - never surfaced to the user. */
	} );
}
