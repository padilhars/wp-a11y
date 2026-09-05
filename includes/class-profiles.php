<?php
/**
 * Accessibility profile definitions.
 *
 * @package WP_A11y
 */

namespace WP_A11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static registry of the plugin's 9 ready-made accessibility profiles.
 *
 * Ported from local_a11y's classes/profiles.php (Moodle plugin, read-only
 * reference — see DECISIONS.md). Selecting a profile always resets settings
 * to defaults and then applies the profile's `apply` map (never merges with
 * whatever custom settings were active before).
 */
class Profiles {

	/**
	 * All 9 profiles.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function all() {
		return array(
			array(
				'id'       => 'lowVision',
				'icon'     => 'eyeLow',
				'tone'     => 'blue',
				'labelkey' => 'profile_lowvision',
				'desckey'  => 'profile_lowvision_desc',
				'apply'    => array(
					'textSize'         => 3,
					'contrast'         => 3,
					'cursor'           => 1,
					'highlightLinks'   => true,
					'highlightButtons' => true,
				),
			),
			array(
				'id'       => 'colorBlind',
				'icon'     => 'droplets',
				'tone'     => 'amber',
				'labelkey' => 'profile_colorblind',
				'desckey'  => 'profile_colorblind_desc',
				'apply'    => array(
					'colorChange'    => 2,
					'highlightLinks' => true,
					'saturation'     => 1,
				),
			),
			array(
				'id'       => 'dyslexia',
				'icon'     => 'book',
				'tone'     => 'violet',
				'labelkey' => 'profile_dyslexia',
				'desckey'  => 'profile_dyslexia_desc',
				'apply'    => array(
					'dyslexicFont' => 1,
					'textSpacing'  => 2,
					'lineHeight'   => 2,
					'readingGuide' => true,
				),
			),
			array(
				'id'       => 'adhd',
				'icon'     => 'zap',
				'tone'     => 'pink',
				'labelkey' => 'profile_adhd',
				'desckey'  => 'profile_adhd_desc',
				'apply'    => array(
					'readingMask'     => true,
					'focusMode'       => 2,
					'pauseAnimations' => true,
				),
			),
			array(
				'id'       => 'senior',
				'icon'     => 'user',
				'tone'     => 'green',
				'labelkey' => 'profile_senior',
				'desckey'  => 'profile_senior_desc',
				'apply'    => array(
					'readableFont'     => true,
					'textSize'         => 2,
					'highlightButtons' => true,
					'cursor'           => 1,
					'lineHeight'       => 1,
				),
			),
			array(
				'id'       => 'epilepsy',
				'icon'     => 'alertTriangle',
				'tone'     => 'red',
				'labelkey' => 'profile_epilepsy',
				'desckey'  => 'profile_epilepsy_desc',
				'apply'    => array(
					'pauseAnimations' => true,
					'saturation'      => 2,
					'contrast'        => 1,
				),
			),
			array(
				'id'       => 'motor',
				'icon'     => 'hand',
				'tone'     => 'cyan',
				'labelkey' => 'profile_motor',
				'desckey'  => 'profile_motor_desc',
				'apply'    => array(
					'cursor'           => 1,
					'highlightButtons' => true,
					'tooltips'         => true,
					'focusMode'        => 0,
				),
			),
			array(
				'id'       => 'cognitive',
				'icon'     => 'brain',
				'tone'     => 'teal',
				'labelkey' => 'profile_cognitive',
				'desckey'  => 'profile_cognitive_desc',
				'apply'    => array(
					'focusMode'       => 2,
					'pauseAnimations' => true,
					'readableFont'    => true,
					'lineHeight'      => 2,
					'hideImages'      => false,
				),
			),
			array(
				'id'       => 'night',
				'icon'     => 'moon',
				'tone'     => 'slate',
				'labelkey' => 'profile_night',
				'desckey'  => 'profile_night_desc',
				'apply'    => array(
					'contrast'   => 1,
					'saturation' => 2,
				),
			),
		);
	}

	/**
	 * Look up a single profile definition by id.
	 *
	 * @param string $id Profile id.
	 * @return array<string, mixed>|null
	 */
	public static function get( $id ) {
		foreach ( self::all() as $profile ) {
			if ( $profile['id'] === $id ) {
				return $profile;
			}
		}

		return null;
	}

	/**
	 * Tone color palette used for profile card styling.
	 *
	 * bg/text/icon/border, one entry per tone used above. Approximate
	 * Tailwind-scale values (50/700/500/100) - refine visually in Phase 1.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function tone_colors() {
		return array(
			'blue'   => array(
				'bg'     => '#eff6ff',
				'text'   => '#1d4ed8',
				'icon'   => '#3b82f6',
				'border' => '#dbeafe',
			),
			'amber'  => array(
				'bg'     => '#fffbeb',
				'text'   => '#b45309',
				'icon'   => '#f59e0b',
				'border' => '#fef3c7',
			),
			'violet' => array(
				'bg'     => '#f5f3ff',
				'text'   => '#6d28d9',
				'icon'   => '#8b5cf6',
				'border' => '#ede9fe',
			),
			'pink'   => array(
				'bg'     => '#fdf2f8',
				'text'   => '#be185d',
				'icon'   => '#ec4899',
				'border' => '#fce7f3',
			),
			'green'  => array(
				'bg'     => '#f0fdf4',
				'text'   => '#15803d',
				'icon'   => '#22c55e',
				'border' => '#dcfce7',
			),
			'red'    => array(
				'bg'     => '#fef2f2',
				'text'   => '#b91c1c',
				'icon'   => '#ef4444',
				'border' => '#fee2e2',
			),
			'cyan'   => array(
				'bg'     => '#ecfeff',
				'text'   => '#0e7490',
				'icon'   => '#06b6d4',
				'border' => '#cffafe',
			),
			'teal'   => array(
				'bg'     => '#f0fdfa',
				'text'   => '#0f766e',
				'icon'   => '#14b8a6',
				'border' => '#ccfbf1',
			),
			'slate'  => array(
				'bg'     => '#f8fafc',
				'text'   => '#334155',
				'icon'   => '#64748b',
				'border' => '#f1f5f9',
			),
		);
	}
}
