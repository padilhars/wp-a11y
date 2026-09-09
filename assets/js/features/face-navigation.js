/**
 * Face Navigation: move a virtual cursor with head movement and "click" by
 * opening your mouth or blinking, using MediaPipe's FaceLandmarker running
 * entirely in the browser (the camera image is never sent anywhere - only
 * two files are fetched live from third-party CDNs to run the model
 * locally, and both are SHA-256-verified before use, closing the
 * verify-then-fetch-again (TOCTOU) gap: bytes are hashed once and the same
 * verified bytes are what actually gets executed/loaded, never a second,
 * unverified request to the same URL).
 *
 * @package WP_A11y
 */

const MP_VERSION = '0.10.18';
const MP_CDN = `https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@${ MP_VERSION }`;
const MP_WASM_BASE = `${ MP_CDN }/wasm`;
const MP_VISION_BUNDLE = `${ MP_CDN }/vision_bundle.mjs`;
const MP_VISION_BUNDLE_SHA256 = 'krpgFv0PyLvOI3CG3sYhL3KgOwbQpez9xx3cckzNurs=';
const MP_MODEL_URL = 'https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task';
const MP_MODEL_SHA256 = 'ZBhOIpsmMQe8K4BMZiXbE0H/K7cxh0sLzC/mVE4Lyf8=';

const PRIVACY_ACK_KEY = 'wp_a11y_fn_privacy_ack';
const PRIVACY_NOTICE =
	'Ativar Navegação por Face liga sua câmera. A imagem é processada inteiramente no seu ' +
	'navegador e nunca é enviada a este site ou a qualquer outro lugar - mas isso exige baixar ' +
	'um componente de terceiro (MediaPipe) para rodar localmente. Continuar?';

const CALIB_KEY = 'wp_a11y_face_calibration';
const DEAD_ZONE = 0.03;
const JAW_DWELL_MS = 400;
const JAW_MARGIN = 0.25;
const BLINK_DWELL_MS = 500;
const BLINK_MARGIN = 0.3;
const RING_RADIUS = 18;

let isRunning = false;
let animFrame = null;
let stream = null;
let videoEl = null;
let landmarker = null;
let hud = null;
let hudBody = null;
let cursorEl = null;
let ringEl = null;
let neutral = null;
let sensitivity = 3;
let captureNext = false;
let cursorPos = { x: 0, y: 0 };
let vel = { vx: 0, vy: 0 };
const jawState = { dwellStart: null, active: false };
const blinkState = { dwellStart: null, active: false };

function hasAcknowledgedPrivacyNotice() {
	try {
		return window.localStorage.getItem( PRIVACY_ACK_KEY ) === '1';
	} catch ( e ) {
		return false;
	}
}
function rememberPrivacyAck() {
	try {
		window.localStorage.setItem( PRIVACY_ACK_KEY, '1' );
	} catch ( e ) {
		/* consent won't be remembered - the dialog will just show again next time. */
	}
}

function saveCalibration() {
	try {
		window.sessionStorage.setItem( CALIB_KEY, JSON.stringify( { neutral, sensitivity } ) );
	} catch ( e ) {
		/* calibration just won't survive a page navigation this session. */
	}
}
function loadCalibration() {
	try {
		const raw = window.sessionStorage.getItem( CALIB_KEY );
		if ( ! raw ) {
			return null;
		}
		const parsed = JSON.parse( raw );
		if ( parsed && parsed.neutral && typeof parsed.neutral.m8 === 'number' ) {
			return parsed;
		}
	} catch ( e ) {
		/* fall through to null. */
	}
	return null;
}
function clearCalibration() {
	try {
		window.sessionStorage.removeItem( CALIB_KEY );
	} catch ( e ) {
		/* nothing to do. */
	}
}

function bytesToBase64( buffer ) {
	let binary = '';
	new Uint8Array( buffer ).forEach( ( byte ) => {
		binary += String.fromCharCode( byte );
	} );
	return window.btoa( binary );
}

async function fetchVerifiedBytes( url, expectedSha256Base64 ) {
	const response = await fetch( url );
	if ( ! response.ok ) {
		throw new Error( `Falha ao baixar ${ url } (HTTP ${ response.status })` );
	}
	const buffer = await response.arrayBuffer();
	const digest = await window.crypto.subtle.digest( 'SHA-256', buffer );
	if ( bytesToBase64( digest ) !== expectedSha256Base64 ) {
		throw new Error( `Verificação de integridade falhou para ${ url }` );
	}
	return buffer;
}

/**
 * Fetches, hashes, and verifies `url` once, then imports the SAME verified
 * bytes as a same-origin blob: module - never a second, unverified request
 * back to `url` itself.
 */
async function loadVisionBundle() {
	if ( window.__wpA11yMediaPipe ) {
		return window.__wpA11yMediaPipe;
	}
	const buffer = await fetchVerifiedBytes( MP_VISION_BUNDLE, MP_VISION_BUNDLE_SHA256 );
	const blobUrl = URL.createObjectURL( new Blob( [ buffer ], { type: 'text/javascript' } ) );
	try {
		const mod = await import( blobUrl );
		window.__wpA11yMediaPipe = { FaceLandmarker: mod.FaceLandmarker, FilesetResolver: mod.FilesetResolver };
		return window.__wpA11yMediaPipe;
	} finally {
		URL.revokeObjectURL( blobUrl );
	}
}

async function buildLandmarker() {
	const { FaceLandmarker, FilesetResolver } = await loadVisionBundle();
	// NOTE: unlike local_a11y, the WASM fileset itself is fetched live from
	// the CDN rather than bundled locally + hash-verified - see DECISIONS.md
	// for why (avoids vendoring multiple binaries just for this port).
	const vision = await FilesetResolver.forVisionTasks( MP_WASM_BASE );
	const modelBytes = await fetchVerifiedBytes( MP_MODEL_URL, MP_MODEL_SHA256 );
	return FaceLandmarker.createFromOptions( vision, {
		baseOptions: { modelAssetBuffer: new Uint8Array( modelBytes ), delegate: 'GPU' },
		runningMode: 'VIDEO',
		numFaces: 1,
		outputFaceBlendshapes: true,
		outputFacialTransformationMatrixes: true,
	} );
}

function clamp( value, min, max ) {
	return Math.max( min, Math.min( max, value ) );
}

function findScore( categories, name ) {
	const found = categories && categories.find( ( c ) => c.categoryName === name );
	return found ? found.score : 0;
}

function updateCursorPosition( m8, m9 ) {
	const speed = sensitivity * 6;
	const dx = m8 - neutral.m8;
	const dy = m9 - neutral.m9;
	// Negated: the mirrored preview means "turn head right" should move the
	// cursor right, matching what the user sees of themselves.
	const targetVx = Math.abs( dx ) > DEAD_ZONE ? -dx * speed : 0;
	const targetVy = Math.abs( dy ) > DEAD_ZONE ? -dy * speed : 0;
	vel.vx = 0.2 * targetVx + 0.8 * vel.vx;
	vel.vy = 0.2 * targetVy + 0.8 * vel.vy;
	cursorPos.x = clamp( cursorPos.x + vel.vx, 0, window.innerWidth - 1 );
	cursorPos.y = clamp( cursorPos.y + vel.vy, 0, window.innerHeight - 1 );
}

function applyEdgeScroll() {
	const edge = Math.max( 70, window.innerHeight * 0.09 );
	const SCROLL_MAX = 20;
	if ( cursorPos.y < edge ) {
		window.scrollBy( 0, -( ( edge - cursorPos.y ) / edge ) * SCROLL_MAX );
	} else if ( cursorPos.y > window.innerHeight - edge ) {
		window.scrollBy( 0, ( ( cursorPos.y - ( window.innerHeight - edge ) ) / edge ) * SCROLL_MAX );
	}
}

function setDwellProgress( fraction ) {
	if ( ! ringEl ) {
		return;
	}
	const circumference = 2 * Math.PI * RING_RADIUS;
	ringEl.style.strokeDasharray = `${ circumference }`;
	ringEl.style.strokeDashoffset = `${ circumference * ( 1 - fraction ) }`;
}

function syntheticClick( x, y ) {
	const el = document.elementFromPoint( x, y );
	if ( ! el ) {
		return;
	}
	el.focus( { preventScroll: true } );
	[ 'pointerdown', 'pointerup' ].forEach( ( type ) => {
		el.dispatchEvent( new PointerEvent( type, { bubbles: true, cancelable: true, clientX: x, clientY: y } ) );
	} );
	el.dispatchEvent( new MouseEvent( 'click', { bubbles: true, cancelable: true, clientX: x, clientY: y } ) );
}

function handleDwell( state, score, threshold, durationMs, now ) {
	if ( score >= threshold ) {
		if ( null === state.dwellStart ) {
			state.dwellStart = now;
		}
		const elapsed = now - state.dwellStart;
		setDwellProgress( Math.min( 1, elapsed / durationMs ) );
		if ( elapsed >= durationMs && ! state.active ) {
			state.active = true;
			syntheticClick( cursorPos.x, cursorPos.y );
		}
	} else {
		state.dwellStart = null;
		state.active = false;
		setDwellProgress( 0 );
	}
}

function updateCursorEl() {
	if ( cursorEl ) {
		cursorEl.style.left = `${ cursorPos.x - 20 }px`;
		cursorEl.style.top = `${ cursorPos.y - 20 }px`;
	}
}

function detect() {
	if ( ! isRunning ) {
		return;
	}
	if ( videoEl && videoEl.readyState >= 2 ) {
		const results = landmarker.detectForVideo( videoEl, performance.now() );
		const matrix = results.facialTransformationMatrixes && results.facialTransformationMatrixes[ 0 ]
			? results.facialTransformationMatrixes[ 0 ].data
			: null;
		const categories = results.faceBlendshapes && results.faceBlendshapes[ 0 ]
			? results.faceBlendshapes[ 0 ].categories
			: null;

		if ( matrix && categories ) {
			const jawOpen = findScore( categories, 'jawOpen' );
			const blink = ( findScore( categories, 'eyeBlinkLeft' ) + findScore( categories, 'eyeBlinkRight' ) ) / 2;

			if ( captureNext ) {
				neutral = { m8: matrix[ 8 ], m9: matrix[ 9 ], jaw: jawOpen, blink };
				saveCalibration();
				captureNext = false;
				enterActive();
			}

			if ( neutral ) {
				updateCursorPosition( matrix[ 8 ], matrix[ 9 ] );
				applyEdgeScroll();
				updateCursorEl();
				const now = performance.now();
				handleDwell( jawState, jawOpen, Math.min( 0.9, neutral.jaw + JAW_MARGIN ), JAW_DWELL_MS, now );
				handleDwell( blinkState, blink, Math.min( 0.9, neutral.blink + BLINK_MARGIN ), BLINK_DWELL_MS, now );
			}
		}
	}
	animFrame = requestAnimationFrame( detect );
}

function buildHud() {
	hud = document.createElement( 'div' );
	hud.className = 'wp-a11y-face-hud';
	hudBody = document.createElement( 'div' );
	hudBody.className = 'wp-a11y-face-hud__body';
	hud.appendChild( hudBody );
	document.body.appendChild( hud );
}

function buildCursor() {
	cursorEl = document.createElement( 'div' );
	cursorEl.className = 'wp-a11y-face-cursor';
	cursorEl.innerHTML =
		'<svg width="40" height="40" viewBox="0 0 40 40" aria-hidden="true">' +
		`<circle class="wp-a11y-face-cursor__ring" cx="20" cy="20" r="${ RING_RADIUS }" />` +
		'</svg><span class="wp-a11y-face-cursor__dot"></span>';
	document.body.appendChild( cursorEl );
	ringEl = cursorEl.querySelector( '.wp-a11y-face-cursor__ring' );
}

function renderLoading() {
	hudBody.innerHTML = '<p class="wp-a11y-face-hud__status">Carregando modelo…</p>';
}

function renderCalibrating( actions ) {
	hudBody.innerHTML = '';
	const preview = document.createElement( 'video' );
	preview.className = 'wp-a11y-face-hud__preview';
	preview.autoplay = true;
	preview.muted = true;
	preview.playsInline = true;
	preview.srcObject = stream;

	const hint = document.createElement( 'p' );
	hint.className = 'wp-a11y-face-hud__hint';
	hint.textContent = 'Olhe para a câmera em posição neutra e clique em Calibrar.';

	const button = document.createElement( 'button' );
	button.type = 'button';
	button.className = 'wp-a11y-face-hud__button';
	button.textContent = 'Calibrar';
	button.addEventListener( 'click', () => {
		captureNext = true;
	} );

	hudBody.appendChild( preview );
	hudBody.appendChild( hint );
	hudBody.appendChild( button );
	void actions;
}

function renderActive( actions ) {
	hudBody.innerHTML = '';

	const preview = document.createElement( 'video' );
	preview.className = 'wp-a11y-face-hud__preview';
	preview.autoplay = true;
	preview.muted = true;
	preview.playsInline = true;
	preview.srcObject = stream;

	const status = document.createElement( 'p' );
	status.className = 'wp-a11y-face-hud__status';
	status.textContent = '● Câmera ativa';

	const hint1 = document.createElement( 'p' );
	hint1.className = 'wp-a11y-face-hud__hint';
	hint1.textContent = 'Clique: abra a boca ou pisque com os dois olhos';

	const hint2 = document.createElement( 'p' );
	hint2.className = 'wp-a11y-face-hud__hint';
	hint2.textContent = 'Rolar página: leve o cursor até a borda superior/inferior';

	const sensRow = document.createElement( 'label' );
	sensRow.className = 'wp-a11y-face-hud__sens';
	sensRow.textContent = 'Velocidade do Cursor Virtual';
	const sensInput = document.createElement( 'input' );
	sensInput.type = 'range';
	sensInput.min = '1';
	sensInput.max = '5';
	sensInput.step = '1';
	sensInput.value = String( sensitivity );
	sensInput.addEventListener( 'input', () => {
		sensitivity = parseInt( sensInput.value, 10 ) || 3;
		saveCalibration();
	} );
	sensRow.appendChild( sensInput );

	const stopButton = document.createElement( 'button' );
	stopButton.type = 'button';
	stopButton.className = 'wp-a11y-face-hud__button wp-a11y-face-hud__button--stop';
	stopButton.textContent = 'Parar';
	stopButton.addEventListener( 'click', () => actions.setOption( 'faceNavigation', false ) );

	hudBody.appendChild( preview );
	hudBody.appendChild( status );
	hudBody.appendChild( hint1 );
	hudBody.appendChild( hint2 );
	hudBody.appendChild( sensRow );
	hudBody.appendChild( stopButton );
}

function renderError( message ) {
	hudBody.innerHTML = '';
	const heading = document.createElement( 'p' );
	heading.className = 'wp-a11y-face-hud__status';
	heading.textContent = 'Erro ao iniciar';
	const detail = document.createElement( 'p' );
	detail.className = 'wp-a11y-face-hud__hint';
	detail.textContent = message; // never innerHTML: message may echo raw error text.
	hudBody.appendChild( heading );
	hudBody.appendChild( detail );
}

let activeActions = null;

function enterActive() {
	if ( ! cursorEl ) {
		buildCursor();
	}
	cursorEl.hidden = false;
	cursorPos = { x: window.innerWidth / 2, y: window.innerHeight / 2 };
	renderActive( activeActions );
}

async function start( actions ) {
	if ( isRunning ) {
		return;
	}

	if ( ! navigator.mediaDevices || ! navigator.mediaDevices.getUserMedia ) {
		return; // unsupported browser: option simply has no effect.
	}

	if ( ! hasAcknowledgedPrivacyNotice() ) {
		if ( ! window.confirm( PRIVACY_NOTICE ) ) { // eslint-disable-line no-alert
			actions.declineActivation();
			return;
		}
		rememberPrivacyAck();
	}

	activeActions = actions;
	buildHud();
	renderLoading();

	try {
		const landmarkerPromise = buildLandmarker();
		stream = await navigator.mediaDevices.getUserMedia( {
			video: { width: 640, height: 480, facingMode: 'user' },
		} );
		landmarker = await landmarkerPromise;

		videoEl = document.createElement( 'video' );
		videoEl.srcObject = stream;
		videoEl.muted = true;
		videoEl.playsInline = true;
		await videoEl.play();

		isRunning = true;

		const existing = loadCalibration();
		if ( existing ) {
			neutral = existing.neutral;
			sensitivity = existing.sensitivity;
			enterActive();
		} else {
			renderCalibrating( actions );
		}

		animFrame = requestAnimationFrame( detect );
	} catch ( e ) {
		renderError( e && e.message ? e.message : String( e ) );
	}
}

function stop() {
	isRunning = false;
	if ( animFrame ) {
		cancelAnimationFrame( animFrame );
		animFrame = null;
	}
	if ( stream ) {
		stream.getTracks().forEach( ( track ) => track.stop() );
		stream = null;
	}
	if ( landmarker ) {
		try {
			landmarker.close();
		} catch ( e ) {
			/* already closed. */
		}
		landmarker = null;
	}
	if ( hud ) {
		hud.remove();
		hud = null;
		hudBody = null;
	}
	if ( cursorEl ) {
		cursorEl.remove();
		cursorEl = null;
		ringEl = null;
	}
	videoEl = null;
	neutral = null;
	captureNext = false;
	jawState.dwellStart = null;
	jawState.active = false;
	blinkState.dwellStart = null;
	blinkState.active = false;
	activeActions = null;
}

/**
 * @param {boolean} active
 * @param {object}  actions Built by panel.js's buildFeatureActions().
 */
export function sync( active, actions ) {
	if ( active ) {
		start( actions );
	} else {
		clearCalibration(); // explicit user-off always forces recalibration next time.
		stop();
	}
}
