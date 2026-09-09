/**
 * FAB + panel interaction: open/close, toggles, steppers, profiles, search,
 * collapsible categories, focus trap, Alt+A shortcut.
 *
 * @package WP_A11y
 */

import * as Storage from '@wp-a11y/storage';
import * as Effects from '@wp-a11y/effects';
import { applyProfile } from '@wp-a11y/profiles';
import { recordActivation } from '@wp-a11y/stats';

const FOCUSABLE_SELECTOR = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

/**
 * Option id -> feature module filename, for the "advanced" options whose
 * effect is a JS-mounted module rather than a body class. Only imported
 * (once, cached, by full URL - see syncAdvancedFeatures()) the first time
 * the option is actually turned on. Grows as Phase 3 adds more modules.
 */
const FEATURE_MODULES = {
	tooltips: 'tooltips.js',
	silenceMedia: 'silence-media.js',
	bionicReading: 'bionic-reading.js',
	readingGuide: 'reading-guide.js',
	readingMask: 'reading-mask.js',
	magnifier: 'magnifier.js',
	screenReader: 'screen-reader.js',
	virtualKeyboard: 'virtual-keyboard.js',
	voiceCommands: 'voice-commands.js',
	faceNavigation: 'face-navigation.js',
};

function readConfig() {
	const el = document.getElementById( 'wp-a11y-config' );
	if ( ! el ) {
		return { restUrl: '', nonce: '', isLoggedIn: false, collectStatsEnabled: false, featuresUrl: '' };
	}
	try {
		return JSON.parse( el.textContent );
	} catch {
		return { restUrl: '', nonce: '', isLoggedIn: false, collectStatsEnabled: false, featuresUrl: '' };
	}
}

/**
 * Wire up the whole widget. Call once, after the FAB/panel HTML exists.
 */
export async function init() {
	const fab = document.getElementById( 'wp-a11y-fab' );
	const panel = document.getElementById( 'wp-a11y-panel' );

	if ( ! fab || ! panel ) {
		return;
	}

	const config = readConfig();

	const optionMeta = Array.from( panel.querySelectorAll( '.wp-a11y-option' ) ).map( ( el ) => ( {
		id: el.dataset.optionId,
		kind: el.dataset.kind,
		max: parseInt( el.dataset.max, 10 ) || 0,
		effect: el.dataset.effect,
	} ) );

	const defaults = Storage.getDefaultSettings( optionMeta );
	let settings = await Storage.getSettings( optionMeta, config );
	let activeProfileId = null;
	const loadedFeatureModules = {};

	const badge = document.getElementById( 'wp-a11y-fab-badge' );
	const status = document.getElementById( 'wp-a11y-panel-status' );
	const search = document.getElementById( 'wp-a11y-search' );

	function isOptionActive( id ) {
		const meta = optionMeta.find( ( option ) => option.id === id );
		if ( ! meta ) {
			return false;
		}
		const value = settings[ id ];
		return meta.kind === 'stepper' ? ( parseInt( value, 10 ) || 0 ) > 0 : value === true;
	}

	function countActive() {
		return optionMeta.reduce( ( count, option ) => {
			return isOptionActive( option.id ) ? count + 1 : count;
		}, 0 );
	}

	/**
	 * Build the small API a feature module can use to drive the panel
	 * itself - only voice-commands.js (running arbitrary spoken commands)
	 * and face-navigation.js/voice-commands.js's `declineActivation` (when
	 * the user declines the privacy consent dialog) currently need this;
	 * the rest ignore the second argument `sync()` receives.
	 *
	 * @param {string} ownOptionId The id of the option this module belongs to.
	 */
	function buildFeatureActions( ownOptionId ) {
		return {
			openPanel: () => openPanel(),
			closePanel: () => closePanel(),
			togglePanel: () => togglePanel(),
			getOption: ( id ) => settings[ id ],
			getOptionMax: ( id ) => {
				const meta = optionMeta.find( ( option ) => option.id === id );
				return meta ? meta.max : 0;
			},
			setOption: ( id, value ) => {
				if ( ! ( id in settings ) ) {
					return;
				}
				const previous = { ...settings };
				settings[ id ] = value;
				activeProfileId = null;
				commit( previous );
			},
			reset: () => {
				const previous = { ...settings };
				settings = { ...defaults };
				activeProfileId = null;
				commit( previous );
			},
			// Called by a module when the user declines its privacy consent
			// dialog: turns its OWN option back off so the panel toggle
			// never shows "on" for a feature that didn't actually start.
			declineActivation: () => {
				const previous = { ...settings };
				settings[ ownOptionId ] = false;
				commit( previous );
			},
		};
	}

	/**
	 * Dynamic-import each Phase 3 feature module on its option's first real
	 * activation (never before), then keep it in sync on every commit.
	 * Always re-reads the CURRENT live active state inside the `.then()`
	 * (not a value captured before the import started) so a user rapidly
	 * toggling an option on then off before the import resolves still ends
	 * up with the correct final state - same race guard as local_a11y's
	 * `wantActive` in main.js.
	 */
	function syncAdvancedFeatures() {
		Object.keys( FEATURE_MODULES ).forEach( ( id ) => {
			const active = isOptionActive( id );
			if ( ! loadedFeatureModules[ id ] ) {
				if ( ! active ) {
					return;
				}
				loadedFeatureModules[ id ] = import( `${ config.featuresUrl }${ FEATURE_MODULES[ id ] }` );
			}
			loadedFeatureModules[ id ]
				.then( ( mod ) => mod.sync( isOptionActive( id ), buildFeatureActions( id ) ) )
				.catch( () => {
					delete loadedFeatureModules[ id ];
				} );
		} );
	}

	function render() {
		const active = countActive();

		if ( badge ) {
			badge.textContent = String( active );
			badge.hidden = active === 0;
		}

		if ( status ) {
			status.textContent = active > 0
				? ( active === 1 ? '1 opção ativa' : `${ active } opções ativas` )
				: 'Nenhuma opção ativa';
		}

		panel.querySelectorAll( '.wp-a11y-option' ).forEach( ( row ) => {
			const id = row.dataset.optionId;
			const kind = row.dataset.kind;
			const value = settings[ id ];

			if ( 'stepper' === kind ) {
				const max = parseInt( row.dataset.max, 10 ) || 0;
				const level = parseInt( value, 10 ) || 0;
				const button = row.querySelector( '.wp-a11y-stepper' );
				const valueEl = row.querySelector( '.wp-a11y-stepper__value' );
				button.classList.toggle( 'is-active', level > 0 );
				button.dataset.value = String( level );
				// Per-level labels (e.g. "Lexend", "Alto") rendered server-side
				// into data-levels - falls back to a bare fraction only if
				// that's somehow missing, never as the normal case.
				let levels = [];
				try {
					levels = JSON.parse( button.dataset.levels || '[]' );
				} catch {
					levels = [];
				}
				valueEl.textContent = levels[ level ] || `${ level }/${ max }`;
				row.querySelectorAll( '.wp-a11y-stepper__dot' ).forEach( ( dot, index ) => {
					dot.classList.toggle( 'is-filled', index < level );
				} );
			} else {
				const button = row.querySelector( '.wp-a11y-toggle' );
				button.setAttribute( 'aria-checked', value === true ? 'true' : 'false' );
			}
		} );

		panel.querySelectorAll( '.wp-a11y-category' ).forEach( ( section ) => {
			const rows = Array.from( section.querySelectorAll( '.wp-a11y-option' ) );
			const activeCount = rows.reduce( ( count, row ) => {
				const id = row.dataset.optionId;
				const kind = row.dataset.kind;
				const value = settings[ id ];
				const isActive = kind === 'stepper' ? ( parseInt( value, 10 ) || 0 ) > 0 : value === true;
				return isActive ? count + 1 : count;
			}, 0 );
			const countEl = section.querySelector( '.wp-a11y-category__count' );
			if ( countEl ) {
				countEl.textContent = String( activeCount );
				countEl.hidden = activeCount === 0;
			}
		} );

		panel.querySelectorAll( '.wp-a11y-profile' ).forEach( ( card ) => {
			card.classList.toggle( 'is-active', card.dataset.profileId === activeProfileId );
		} );
	}

	function recordNewActivations( previous, current ) {
		if ( ! config.collectStatsEnabled ) {
			return;
		}
		optionMeta.forEach( ( option ) => {
			const defaultValue = defaults[ option.id ];
			const wasActive = previous[ option.id ] !== defaultValue;
			const isActive = current[ option.id ] !== defaultValue;
			if ( ! wasActive && isActive ) {
				recordActivation( option.id, config );
			}
		} );
	}

	function commit( previousSettings ) {
		Effects.apply( settings, optionMeta );
		render();
		syncAdvancedFeatures();
		Storage.saveSettings( settings, config );
		if ( previousSettings ) {
			recordNewActivations( previousSettings, settings );
		}
	}

	// Initial hydration: apply whatever the effective settings are (server
	// usermeta for logged-in users, localStorage for guests - see storage.js).
	commit( null );

	fab.addEventListener( 'click', () => togglePanel() );

	document.getElementById( 'wp-a11y-panel-close' ).addEventListener( 'click', () => closePanel() );

	document.getElementById( 'wp-a11y-panel-reset' ).addEventListener( 'click', () => {
		const previous = { ...settings };
		settings = { ...defaults };
		activeProfileId = null;
		commit( previous );
	} );

	panel.addEventListener( 'click', ( event ) => {
		const helpBtn = event.target.closest( '.wp-a11y-help-btn' );
		if ( helpBtn ) {
			const block = helpBtn.closest( '.wp-a11y-option' ).querySelector( '.wp-a11y-help-block' );
			const expanded = helpBtn.getAttribute( 'aria-expanded' ) === 'true';
			helpBtn.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
			if ( block ) {
				block.hidden = expanded;
			}
			return;
		}

		// The whole row is clickable (icon/label/desc included), not just the
		// switch/stepper button itself - both templates already put
		// data-option-id/data-kind/data-max on the outer .wp-a11y-option div,
		// so reading from `optionRow` (not the inner button) covers a click
		// anywhere on the row. Excludes .wp-a11y-help-btn (handled above) and
		// clicks inside an open .wp-a11y-help-block (selecting/reading its
		// text shouldn't also flip the switch).
		const optionRow = event.target.closest( '.wp-a11y-option[data-kind]' );
		if ( optionRow && ! event.target.closest( '.wp-a11y-help-block' ) ) {
			const previous = { ...settings };
			const id = optionRow.dataset.optionId;
			if ( 'stepper' === optionRow.dataset.kind ) {
				const max = parseInt( optionRow.dataset.max, 10 ) || 0;
				const current = parseInt( settings[ id ], 10 ) || 0;
				settings[ id ] = ( current + 1 ) % ( max + 1 );
			} else {
				settings[ id ] = ! settings[ id ];
			}
			activeProfileId = null;
			commit( previous );
			return;
		}

		const profileBtn = event.target.closest( '.wp-a11y-profile' );
		if ( profileBtn ) {
			const previous = { ...settings };
			const id = profileBtn.dataset.profileId;
			if ( activeProfileId === id ) {
				settings = { ...defaults };
				activeProfileId = null;
			} else {
				const applyMap = JSON.parse( profileBtn.dataset.apply || '{}' );
				settings = applyProfile( defaults, applyMap );
				activeProfileId = id;
			}
			commit( previous );
			return;
		}

		const categoryBtn = event.target.closest( '.wp-a11y-category__toggle' );
		if ( categoryBtn ) {
			const expanded = categoryBtn.getAttribute( 'aria-expanded' ) === 'true';
			categoryBtn.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
			document.getElementById( categoryBtn.getAttribute( 'aria-controls' ) ).hidden = expanded;
		}
	} );

	if ( search ) {
		search.addEventListener( 'input', () => {
			const query = search.value.trim().toLowerCase();

			panel.querySelectorAll( '.wp-a11y-category' ).forEach( ( section ) => {
				let anyVisible = false;

				section.querySelectorAll( '.wp-a11y-option' ).forEach( ( row ) => {
					const label = row.querySelector( '.wp-a11y-option__label' ).textContent.toLowerCase();
					const matches = '' === query || label.indexOf( query ) !== -1;
					row.hidden = ! matches;
					if ( matches ) {
						anyVisible = true;
					}
				} );

				const optionsEl = section.querySelector( '.wp-a11y-category__options' );
				const toggleBtn = section.querySelector( '.wp-a11y-category__toggle' );
				section.hidden = query !== '' && ! anyVisible;

				if ( query !== '' ) {
					optionsEl.hidden = false;
					toggleBtn.setAttribute( 'aria-expanded', 'true' );
				}
			} );
		} );
	}

	document.addEventListener( 'keydown', ( event ) => {
		if ( event.altKey && ( event.key === 'a' || event.key === 'A' ) ) {
			event.preventDefault();
			togglePanel();
			return;
		}

		if ( ! panel.hidden && event.key === 'Escape' ) {
			closePanel();
			return;
		}

		if ( ! panel.hidden && event.key === 'Tab' ) {
			trapFocus( event );
		}
	} );

	function togglePanel() {
		if ( panel.hidden ) {
			openPanel();
		} else {
			closePanel();
		}
	}

	function openPanel() {
		panel.hidden = false;
		fab.setAttribute( 'aria-expanded', 'true' );
		const firstFocusable = panel.querySelector( FOCUSABLE_SELECTOR );
		if ( firstFocusable ) {
			firstFocusable.focus();
		}
	}

	function closePanel() {
		panel.hidden = true;
		fab.setAttribute( 'aria-expanded', 'false' );
		fab.focus();
	}

	function trapFocus( event ) {
		const focusable = Array.from( panel.querySelectorAll( FOCUSABLE_SELECTOR ) ).filter(
			( el ) => ! el.hidden && el.offsetParent !== null
		);
		if ( ! focusable.length ) {
			return;
		}
		const first = focusable[ 0 ];
		const last = focusable[ focusable.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}
}
