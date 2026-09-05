<?php
/**
 * Accessibility option definitions.
 *
 * @package WP_A11y
 */

namespace WP_A11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static registry of the plugin's accessibility options.
 *
 * Ported from local_a11y's classes/options.php (Moodle plugin, kept as a
 * read-only reference — see DECISIONS.md). `signLanguage` is intentionally
 * not ported: it existed only because of a third-party Moodle plugin
 * (VLibras) with no WordPress equivalent.
 */
class Options {

	/**
	 * Category display order. First one is open by default in the panel.
	 *
	 * @return array<string>
	 */
	public static function category_order() {
		return array( 'typography', 'color', 'media', 'navigation', 'advanced' );
	}

	/**
	 * Categories that start expanded in the panel.
	 *
	 * @return array<string>
	 */
	public static function category_default_open() {
		return array( 'typography' );
	}

	/**
	 * All 29 accessibility options.
	 *
	 * Each entry: id, cat, kind ('toggle'|'stepper'), icon, labelkey, desckey
	 * (nullable), hashelp (bool), max (stepper only), levelprefix (stepper only).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function all() {
		return array(
			array(
				'id'       => 'readableFont',
				'cat'      => 'typography',
				'kind'     => 'toggle',
				'icon'     => 'type',
				'labelkey' => 'option_readablefont',
				'desckey'  => null,
				'hashelp'  => false,
			),
			array(
				'id'          => 'dyslexicFont',
				'cat'         => 'typography',
				'kind'        => 'stepper',
				'icon'        => 'bookOpen',
				'labelkey'    => 'option_dyslexicfont',
				'desckey'     => null,
				'hashelp'     => false,
				'max'         => 2,
				'levelprefix' => 'dyslexicfont_level',
			),
			array(
				'id'       => 'highlightTitles',
				'cat'      => 'typography',
				'kind'     => 'toggle',
				'icon'     => 'heading',
				'labelkey' => 'option_highlighttitles',
				'desckey'  => null,
				'hashelp'  => false,
				'admincolor' => 'highlighttitlescolor',
			),
			array(
				'id'       => 'highlightLinks',
				'cat'      => 'typography',
				'kind'     => 'toggle',
				'icon'     => 'link',
				'labelkey' => 'option_highlightlinks',
				'desckey'  => null,
				'hashelp'  => false,
				'admincolor' => 'highlightlinkscolor',
			),
			array(
				'id'       => 'highlightButtons',
				'cat'      => 'typography',
				'kind'     => 'toggle',
				'icon'     => 'squareButton',
				'labelkey' => 'option_highlightbuttons',
				'desckey'  => null,
				'hashelp'  => false,
				'admincolor' => 'highlightbuttonscolor',
			),
			array(
				'id'          => 'textSize',
				'cat'         => 'typography',
				'kind'        => 'stepper',
				'icon'        => 'textSize',
				'labelkey'    => 'option_textsize',
				'desckey'     => null,
				'hashelp'     => false,
				'max'         => 4,
				'levelprefix' => 'textsize_level',
			),
			array(
				'id'          => 'lineHeight',
				'cat'         => 'typography',
				'kind'        => 'stepper',
				'icon'        => 'lineHeight',
				'labelkey'    => 'option_lineheight',
				'desckey'     => null,
				'hashelp'     => false,
				'max'         => 3,
				'levelprefix' => 'lineheight_level',
			),
			array(
				'id'          => 'textSpacing',
				'cat'         => 'typography',
				'kind'        => 'stepper',
				'icon'        => 'textSpacing',
				'labelkey'    => 'option_textspacing',
				'desckey'     => null,
				'hashelp'     => false,
				'max'         => 3,
				'levelprefix' => 'textspacing_level',
			),
			array(
				'id'          => 'wordSpacing',
				'cat'         => 'typography',
				'kind'        => 'stepper',
				'icon'        => 'moveHorizontal',
				'labelkey'    => 'option_wordspacing',
				'desckey'     => null,
				'hashelp'     => false,
				'max'         => 3,
				'levelprefix' => 'wordspacing_level',
			),
			array(
				'id'          => 'textAlign',
				'cat'         => 'typography',
				'kind'        => 'stepper',
				'icon'        => 'textAlign',
				'labelkey'    => 'option_textalign',
				'desckey'     => null,
				'hashelp'     => false,
				'max'         => 4,
				'levelprefix' => 'textalign_level',
			),
			array(
				'id'       => 'bionicReading',
				'cat'      => 'typography',
				'kind'     => 'toggle',
				'icon'     => 'bold',
				'labelkey' => 'option_bionicreading',
				'desckey'  => null,
				'hashelp'  => true,
			),
			array(
				'id'          => 'contrast',
				'cat'         => 'color',
				'kind'        => 'stepper',
				'icon'        => 'contrast',
				'labelkey'    => 'option_contrast',
				'desckey'     => null,
				'hashelp'     => false,
				'max'         => 3,
				'levelprefix' => 'contrast_level',
			),
			array(
				'id'       => 'invertColors',
				'cat'      => 'color',
				'kind'     => 'toggle',
				'icon'     => 'invertColors',
				'labelkey' => 'option_invertcolors',
				'desckey'  => null,
				'hashelp'  => false,
			),
			array(
				'id'          => 'colorChange',
				'cat'         => 'color',
				'kind'        => 'stepper',
				'icon'        => 'pipette',
				'labelkey'    => 'option_colorchange',
				'desckey'     => 'option_colorchange_desc',
				'hashelp'     => false,
				'max'         => 3,
				'levelprefix' => 'colorchange_level',
			),
			array(
				'id'          => 'saturation',
				'cat'         => 'color',
				'kind'        => 'stepper',
				'icon'        => 'saturation',
				'labelkey'    => 'option_saturation',
				'desckey'     => null,
				'hashelp'     => false,
				'max'         => 3,
				'levelprefix' => 'saturation_level',
			),
			array(
				'id'          => 'blueLightFilter',
				'cat'         => 'color',
				'kind'        => 'stepper',
				'icon'        => 'moon',
				'labelkey'    => 'option_bluelightfilter',
				'desckey'     => null,
				'hashelp'     => false,
				'max'         => 3,
				'levelprefix' => 'bluelightfilter_level',
			),
			array(
				'id'       => 'hideImages',
				'cat'      => 'media',
				'kind'     => 'toggle',
				'icon'     => 'imageOff',
				'labelkey' => 'option_hideimages',
				'desckey'  => null,
				'hashelp'  => true,
			),
			array(
				'id'       => 'pauseAnimations',
				'cat'      => 'media',
				'kind'     => 'toggle',
				'icon'     => 'pause',
				'labelkey' => 'option_pauseanimations',
				'desckey'  => null,
				'hashelp'  => true,
			),
			array(
				'id'       => 'silenceMedia',
				'cat'      => 'media',
				'kind'     => 'toggle',
				'icon'     => 'volumeX',
				'labelkey' => 'option_silencemedia',
				'desckey'  => null,
				'hashelp'  => true,
			),
			array(
				'id'       => 'tooltips',
				'cat'      => 'media',
				'kind'     => 'toggle',
				'icon'     => 'tooltip',
				'labelkey' => 'option_tooltips',
				'desckey'  => null,
				'hashelp'  => false,
			),
			array(
				'id'       => 'readingGuide',
				'cat'      => 'navigation',
				'kind'     => 'toggle',
				'icon'     => 'ruler',
				'labelkey' => 'option_readingguide',
				'desckey'  => null,
				'hashelp'  => true,
				'admincolor' => 'readingguidecolor',
			),
			array(
				'id'       => 'readingMask',
				'cat'      => 'navigation',
				'kind'     => 'toggle',
				'icon'     => 'mask',
				'labelkey' => 'option_readingmask',
				'desckey'  => null,
				'hashelp'  => true,
			),
			array(
				'id'       => 'magnifier',
				'cat'      => 'navigation',
				'kind'     => 'toggle',
				'icon'     => 'zoomIn',
				'labelkey' => 'option_magnifier',
				'desckey'  => null,
				'hashelp'  => true,
			),
			array(
				'id'          => 'cursor',
				'cat'         => 'navigation',
				'kind'        => 'stepper',
				'icon'        => 'mousePointer',
				'labelkey'    => 'option_cursor',
				'desckey'     => null,
				'hashelp'     => false,
				'max'         => 2,
				'levelprefix' => 'cursor_level',
			),
			array(
				'id'          => 'focusMode',
				'cat'         => 'navigation',
				'kind'        => 'stepper',
				'icon'        => 'focus',
				'labelkey'    => 'option_focusmode',
				'desckey'     => 'option_focusmode_desc',
				'hashelp'     => false,
				'max'         => 3,
				'levelprefix' => 'focusmode_level',
			),
			array(
				'id'       => 'screenReader',
				'cat'      => 'advanced',
				'kind'     => 'toggle',
				'icon'     => 'volume',
				'labelkey' => 'option_screenreader',
				'desckey'  => 'option_screenreader_desc',
				'hashelp'  => true,
			),
			array(
				'id'       => 'virtualKeyboard',
				'cat'      => 'advanced',
				'kind'     => 'toggle',
				'icon'     => 'keyboard',
				'labelkey' => 'option_virtualkeyboard',
				'desckey'  => null,
				'hashelp'  => true,
			),
			array(
				'id'       => 'voiceCommands',
				'cat'      => 'advanced',
				'kind'     => 'toggle',
				'icon'     => 'mic',
				'labelkey' => 'option_voicecommands',
				'desckey'  => null,
				'hashelp'  => true,
			),
			array(
				'id'       => 'faceNavigation',
				'cat'      => 'advanced',
				'kind'     => 'toggle',
				'icon'     => 'scanFace',
				'labelkey' => 'option_facenavigation',
				'desckey'  => 'option_facenavigation_desc',
				'hashelp'  => true,
			),
		);
	}

	/**
	 * All option ids.
	 *
	 * @return array<string>
	 */
	public static function all_ids() {
		return array_column( self::all(), 'id' );
	}

	/**
	 * Look up a single option definition by id.
	 *
	 * @param string $id Option id.
	 * @return array<string, mixed>|null
	 */
	public static function get( $id ) {
		foreach ( self::all() as $option ) {
			if ( $option['id'] === $id ) {
				return $option;
			}
		}

		return null;
	}

	/**
	 * Options grouped by category, in category display order.
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public static function grouped_by_category() {
		$groups = array_fill_keys( self::category_order(), array() );

		foreach ( self::all() as $option ) {
			$groups[ $option['cat'] ][] = $option;
		}

		return $groups;
	}
}
