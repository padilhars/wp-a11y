/**
 * Profile-apply logic. Profile data itself (id, apply map) lives in the
 * server-rendered `data-apply` attribute on each .wp-a11y-profile card, not
 * duplicated here - see templates/profile-card.php.
 *
 * @package WP_A11y
 */

/**
 * Selecting a profile always resets to defaults, then layers the profile's
 * partial `apply` map on top - never merges with whatever was active before.
 *
 * @param {Object<string, boolean|number>} defaults
 * @param {Object<string, boolean|number>} applyMap
 * @return {Object<string, boolean|number>}
 */
export function applyProfile( defaults, applyMap ) {
	return { ...defaults, ...applyMap };
}
