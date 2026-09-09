/**
 * Voice Commands: control the panel with spoken commands via the browser's
 * native SpeechRecognition API. In Chromium-based browsers this sends the
 * captured microphone audio to a remote speech-recognition service run by
 * the browser vendor (not by this site/plugin) - a one-time consent
 * dialog discloses this before the feature is ever actually started, same
 * pattern as local_a11y's voice_commands.js.
 *
 * @package WP_A11y
 */

const PRIVACY_ACK_KEY = 'wp_a11y_vc_privacy_ack';
const PRIVACY_NOTICE =
	'Neste navegador, ativar Comandos por Voz envia o áudio captado pelo seu microfone a um ' +
	'serviço remoto de reconhecimento de fala (não operado por este site) para convertê-lo em ' +
	'texto. Continuar?';

let recognition = null;
let pill = null;
let feedbackTimer = null;

function hasAcknowledgedPrivacyNotice() {
	try {
		return window.localStorage.getItem( PRIVACY_ACK_KEY ) === '1';
	} catch {
		return false;
	}
}

function rememberPrivacyAck() {
	try {
		window.localStorage.setItem( PRIVACY_ACK_KEY, '1' );
	} catch {
		/* consent won't be remembered - the dialog will just show again next time. */
	}
}

function getSpeechRecognitionCtor() {
	return window.SpeechRecognition || window.webkitSpeechRecognition || null;
}

function ensurePill() {
	if ( ! pill ) {
		pill = document.createElement( 'div' );
		pill.className = 'wp-a11y-vc-pill';
		pill.setAttribute( 'role', 'status' );
		document.body.appendChild( pill );
	}
	return pill;
}

function setPillText( text ) {
	ensurePill().textContent = text;
}

function showFeedback( phrase ) {
	if ( feedbackTimer ) {
		clearTimeout( feedbackTimer );
	}
	setPillText( `“${ phrase }”` );
	feedbackTimer = setTimeout( () => setPillText( 'Ouvindo…' ), 2500 );
}

function bumpStepper( actions, id, delta ) {
	const current = parseInt( actions.getOption( id ), 10 ) || 0;
	const max = actions.getOptionMax( id );
	actions.setOption( id, Math.max( 0, Math.min( max, current + delta ) ) );
}

function toggleBool( actions, id ) {
	actions.setOption( id, ! actions.getOption( id ) );
}

/**
 * Ordered command table: first pattern found as a substring of the
 * lowercased transcript wins. `run` receives the actions API built by
 * panel.js's buildFeatureActions().
 */
const COMMANDS = [
	{ patterns: [ 'abrir painel', 'abrir o painel' ], run: ( a ) => a.openPanel() },
	{ patterns: [ 'fechar painel', 'fechar o painel' ], run: ( a ) => a.closePanel() },
	{ patterns: [ 'restaurar', 'redefinir', 'limpar tudo' ], run: ( a ) => a.reset() },

	{ patterns: [ 'aumentar texto', 'texto maior', 'fonte maior' ], run: ( a ) => bumpStepper( a, 'textSize', 1 ) },
	{ patterns: [ 'diminuir texto', 'texto menor', 'fonte menor' ], run: ( a ) => bumpStepper( a, 'textSize', -1 ) },
	{ patterns: [ 'aumentar altura de linha', 'mais altura de linha' ], run: ( a ) => bumpStepper( a, 'lineHeight', 1 ) },
	{ patterns: [ 'diminuir altura de linha', 'menos altura de linha' ], run: ( a ) => bumpStepper( a, 'lineHeight', -1 ) },
	{ patterns: [ 'mais espaçamento entre letras', 'aumentar espaçamento' ], run: ( a ) => bumpStepper( a, 'textSpacing', 1 ) },
	{ patterns: [ 'menos espaçamento entre letras', 'diminuir espaçamento' ], run: ( a ) => bumpStepper( a, 'textSpacing', -1 ) },
	{ patterns: [ 'mais espaçamento entre palavras' ], run: ( a ) => bumpStepper( a, 'wordSpacing', 1 ) },
	{ patterns: [ 'menos espaçamento entre palavras' ], run: ( a ) => bumpStepper( a, 'wordSpacing', -1 ) },

	{ patterns: [ 'alinhar à esquerda', 'texto à esquerda' ], run: ( a ) => a.setOption( 'textAlign', 1 ) },
	{ patterns: [ 'centralizar texto', 'texto centralizado' ], run: ( a ) => a.setOption( 'textAlign', 2 ) },
	{ patterns: [ 'alinhar à direita', 'texto à direita' ], run: ( a ) => a.setOption( 'textAlign', 3 ) },
	{ patterns: [ 'justificar texto', 'texto justificado' ], run: ( a ) => a.setOption( 'textAlign', 4 ) },
	{ patterns: [ 'alinhamento padrão', 'alinhamento normal' ], run: ( a ) => a.setOption( 'textAlign', 0 ) },

	{ patterns: [ 'modo escuro', 'modo noturno' ], run: ( a ) => a.setOption( 'contrast', 1 ) },
	{ patterns: [ 'contraste claro' ], run: ( a ) => a.setOption( 'contrast', 2 ) },
	{ patterns: [ 'alto contraste' ], run: ( a ) => a.setOption( 'contrast', 3 ) },
	{ patterns: [ 'sem contraste', 'contraste padrão' ], run: ( a ) => a.setOption( 'contrast', 0 ) },
	{ patterns: [ 'inverter cores' ], run: ( a ) => toggleBool( a, 'invertColors' ) },

	{ patterns: [ 'filtro protanopia', 'protanopia' ], run: ( a ) => a.setOption( 'colorChange', 1 ) },
	{ patterns: [ 'filtro deuteranopia', 'deuteranopia' ], run: ( a ) => a.setOption( 'colorChange', 2 ) },
	{ patterns: [ 'filtro tritanopia', 'tritanopia' ], run: ( a ) => a.setOption( 'colorChange', 3 ) },
	{ patterns: [ 'sem filtro de cor', 'cor padrão' ], run: ( a ) => a.setOption( 'colorChange', 0 ) },

	{ patterns: [ 'saturação alta', 'mais saturação' ], run: ( a ) => a.setOption( 'saturation', 1 ) },
	{ patterns: [ 'saturação baixa', 'menos saturação' ], run: ( a ) => a.setOption( 'saturation', 2 ) },
	{ patterns: [ 'escala de cinza', 'preto e branco', 'monocromático' ], run: ( a ) => a.setOption( 'saturation', 3 ) },
	{ patterns: [ 'saturação normal', 'sem saturação' ], run: ( a ) => a.setOption( 'saturation', 0 ) },

	{ patterns: [ 'cursor preto grande', 'cursor grande preto' ], run: ( a ) => a.setOption( 'cursor', 1 ) },
	{ patterns: [ 'cursor branco grande', 'cursor grande branco' ], run: ( a ) => a.setOption( 'cursor', 2 ) },
	{ patterns: [ 'cursor padrão', 'cursor normal' ], run: ( a ) => a.setOption( 'cursor', 0 ) },

	{ patterns: [ 'fonte legível' ], run: ( a ) => toggleBool( a, 'readableFont' ) },
	{ patterns: [ 'fonte para dislexia', 'fonte disléxica' ], run: ( a ) => a.setOption( 'dyslexicFont', a.getOption( 'dyslexicFont' ) ? 0 : 1 ) },
	{ patterns: [ 'destacar títulos', 'realçar títulos' ], run: ( a ) => toggleBool( a, 'highlightTitles' ) },
	{ patterns: [ 'destacar links', 'realçar links' ], run: ( a ) => toggleBool( a, 'highlightLinks' ) },
	{ patterns: [ 'destacar botões', 'realçar botões' ], run: ( a ) => toggleBool( a, 'highlightButtons' ) },
	{ patterns: [ 'ocultar imagens', 'esconder imagens' ], run: ( a ) => toggleBool( a, 'hideImages' ) },
	{ patterns: [ 'pausar animações', 'parar animações' ], run: ( a ) => toggleBool( a, 'pauseAnimations' ) },
	{ patterns: [ 'silenciar mídia', 'silenciar vídeo', 'silenciar áudio' ], run: ( a ) => toggleBool( a, 'silenceMedia' ) },
	{ patterns: [ 'dicas de ferramentas', 'ativar dicas' ], run: ( a ) => toggleBool( a, 'tooltips' ) },
	{ patterns: [ 'leitura biônica' ], run: ( a ) => toggleBool( a, 'bionicReading' ) },

	{ patterns: [ 'guia de leitura' ], run: ( a ) => toggleBool( a, 'readingGuide' ) },
	{ patterns: [ 'máscara de leitura' ], run: ( a ) => toggleBool( a, 'readingMask' ) },
	{ patterns: [ 'lupa', 'ativar lupa' ], run: ( a ) => toggleBool( a, 'magnifier' ) },
	{ patterns: [ 'modo foco', 'modo de foco' ], run: ( a ) => a.setOption( 'focusMode', a.getOption( 'focusMode' ) ? 0 : 2 ) },
	{ patterns: [ 'leitor de tela' ], run: ( a ) => toggleBool( a, 'screenReader' ) },
	{ patterns: [ 'teclado virtual' ], run: ( a ) => toggleBool( a, 'virtualKeyboard' ) },

	{ patterns: [ 'voltar' ], run: () => window.history.back() },
	{ patterns: [ 'avançar' ], run: () => window.history.forward() },
	{ patterns: [ 'rolar para baixo', 'descer página' ], run: () => window.scrollBy( { top: window.innerHeight * 0.8, behavior: 'smooth' } ) },
	{ patterns: [ 'rolar para cima', 'subir página' ], run: () => window.scrollBy( { top: -window.innerHeight * 0.8, behavior: 'smooth' } ) },
	{ patterns: [ 'ir para o topo', 'topo da página' ], run: () => window.scrollTo( { top: 0, behavior: 'smooth' } ) },
	{ patterns: [ 'ir para o final', 'final da página' ], run: () => window.scrollTo( { top: document.body.scrollHeight, behavior: 'smooth' } ) },
];

function dispatch( phrase, actions ) {
	const lowered = phrase.toLowerCase();
	const command = COMMANDS.find( ( c ) => c.patterns.some( ( pattern ) => lowered.includes( pattern ) ) );
	showFeedback( phrase );
	if ( command ) {
		command.run( actions );
	}
}

function start( actions ) {
	if ( recognition ) {
		return;
	}

	const Ctor = getSpeechRecognitionCtor();
	if ( ! Ctor ) {
		setPillText( 'Comandos de voz não são suportados neste navegador.' );
		ensurePill().hidden = false;
		return;
	}

	if ( ! hasAcknowledgedPrivacyNotice() ) {
		if ( ! window.confirm( PRIVACY_NOTICE ) ) { // eslint-disable-line no-alert
			actions.declineActivation();
			return;
		}
		rememberPrivacyAck();
	}

	recognition = new Ctor();
	recognition.continuous = true;
	recognition.interimResults = false;
	const pageLang = document.documentElement.lang || 'pt-BR';
	recognition.lang = pageLang.toLowerCase().startsWith( 'pt' ) ? 'pt-BR' : pageLang;

	recognition.onresult = ( event ) => {
		const last = event.results[ event.results.length - 1 ];
		if ( last && last.isFinal ) {
			dispatch( last[ 0 ].transcript, actions );
		}
	};
	recognition.onend = () => {
		if ( recognition ) {
			try {
				recognition.start();
			} catch {
				/* already starting/started - browser will fire another onend soon. */
			}
		}
	};
	recognition.onerror = () => {
		/* non-fatal (e.g. no-speech timeout) - onend fires next and restarts it. */
	};

	ensurePill().hidden = false;
	setPillText( 'Ouvindo…' );
	recognition.start();
}

function stop() {
	if ( recognition ) {
		recognition.onend = null;
		try {
			recognition.stop();
		} catch {
			/* already stopped. */
		}
		recognition = null;
	}
	if ( feedbackTimer ) {
		clearTimeout( feedbackTimer );
		feedbackTimer = null;
	}
	if ( pill ) {
		pill.remove();
		pill = null;
	}
}

/**
 * @param {boolean} active
 * @param {object}  actions Built by panel.js's buildFeatureActions().
 */
export function sync( active, actions ) {
	if ( active ) {
		start( actions );
	} else {
		stop();
	}
}
