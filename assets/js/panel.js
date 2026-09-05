/**
 * FAB + panel interaction: open/close, toggles, steppers, profiles, search,
 * collapsible categories, focus trap, Alt+A shortcut.
 *
 * @package WP_A11y
 */

import * as Storage from '@wp-a11y/storage';
import * as Effects from '@wp-a11y/effects';
import { applyProfile } from '@wp-a11y/profiles';

const FOCUSABLE_SELECTOR = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

/**
 * Wire up the whole widget. Call once, after the FAB/panel HTML exists.
 */
export function init() {
	const fab = document.getElementById( 'wp-a11y-fab' );
	const panel = document.getElementById( 'wp-a11y-panel' );

	if ( ! fab || ! panel ) {
		return;
	}

	const optionMeta = Array.from( panel.querySelectorAll( '.wp-a11y-option' ) ).map( ( el ) => ( {
		id: el.dataset.optionId,
		kind: el.dataset.kind,
		max: parseInt( el.dataset.max, 10 ) || 0,
		effect: el.dataset.effect,
	} ) );

	const defaults = Storage.getDefaultSettings( optionMeta );
	let settings = Storage.getSettings( optionMeta );
	let activeProfileId = null;

	const badge = document.getElementById( 'wp-a11y-fab-badge' );
	const status = document.getElementById( 'wp-a11y-panel-status' );
	const search = document.getElementById( 'wp-a11y-search' );

	function countActive() {
		return optionMeta.reduce( ( count, option ) => {
			const value = settings[ option.id ];
			const active = option.kind === 'stepper' ? ( parseInt( value, 10 ) || 0 ) > 0 : value === true;
			return active ? count + 1 : count;
		}, 0 );
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
				valueEl.textContent = level === 0 ? 'Padrão' : `${ level }/${ max }`;
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

	function commit() {
		Effects.apply( settings, optionMeta );
		Storage.saveSettings( settings );
		render();
	}

	// Initial hydration: apply whatever localStorage actually holds (may
	// differ from the server-rendered defaults - Phase 2 will sync this
	// server-side for logged-in users instead).
	commit();

	fab.addEventListener( 'click', () => togglePanel() );

	document.getElementById( 'wp-a11y-panel-close' ).addEventListener( 'click', () => closePanel() );

	document.getElementById( 'wp-a11y-panel-reset' ).addEventListener( 'click', () => {
		settings = { ...defaults };
		activeProfileId = null;
		commit();
	} );

	panel.addEventListener( 'click', ( event ) => {
		const toggleBtn = event.target.closest( '.wp-a11y-toggle' );
		if ( toggleBtn ) {
			const id = toggleBtn.dataset.optionId;
			settings[ id ] = ! settings[ id ];
			activeProfileId = null;
			commit();
			return;
		}

		const stepperBtn = event.target.closest( '.wp-a11y-stepper' );
		if ( stepperBtn ) {
			const id = stepperBtn.dataset.optionId;
			const max = parseInt( stepperBtn.dataset.max, 10 ) || 0;
			const current = parseInt( settings[ id ], 10 ) || 0;
			settings[ id ] = ( current + 1 ) % ( max + 1 );
			activeProfileId = null;
			commit();
			return;
		}

		const profileBtn = event.target.closest( '.wp-a11y-profile' );
		if ( profileBtn ) {
			const id = profileBtn.dataset.profileId;
			if ( activeProfileId === id ) {
				settings = { ...defaults };
				activeProfileId = null;
			} else {
				const applyMap = JSON.parse( profileBtn.dataset.apply || '{}' );
				settings = applyProfile( defaults, applyMap );
				activeProfileId = id;
			}
			commit();
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
