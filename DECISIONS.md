# DECISIONS.md — wp-a11y

Decisões de arquitetura não óbvias a partir do código. Ver também o plano completo de port (Fases 0–5) na sessão que iniciou o projeto.

## D1 — Fonte da verdade: `local_a11y` atual, não `_design-reference/`

O protótipo `_design-reference/*.jsx` do plugin Moodle é uma versão inicial/desatualizada (23 opções, sem `wordSpacing`/`blueLightFilter`/`bionicReading`/`magnifier`/`silenceMedia`; `focusMode`/`dyslexicFont` ainda booleanos ali, hoje steppers). O port usa o PHP/JS **atual** do `local_a11y` (`classes/options.php`, `classes/profiles.php` etc.) como fonte da verdade para a lista de opções/perfis. O protótipo só foi reaproveitado como referência de convenção (nomes de classe CSS, mecanismo de troca de classe no body).

`signLanguage` (30ª opção condicional no Moodle, dependente do plugin de terceiros VLibras) não foi portado — sem equivalente WordPress conhecido.

## D2 — Nunca misturar com o repositório `local_a11y`

Regra do usuário: o código Moodle é lido apenas como referência, nunca editado nem copiado literalmente. Isso inclui assets de terceiros (fontes, WASM) — sempre baixados de novo das fontes originais, nunca copiados da árvore do plugin Moodle. Ver `assets/fonts/THIRDPARTY.md`.

## D3 — Persistência: `localStorage` para todos na Fase 1, `usermeta` na Fase 2

Sem REST API ainda (Fase 2), então mesmo usuários logados só têm a preferência salva por navegador (`localStorage`), não sincronizada entre dispositivos. `WP_A11y\Manager::get_current_user_settings()`/`save_user_settings()` já leem/escrevem um único `usermeta` JSON (mesma forma que o Moodle usa via `user_preferences`), prontos para quando a Fase 2 acrescentar o endpoint REST que efetivamente os invoca a partir do navegador.

## D4 — Isolamento visual do FAB/painel: wrapper `#wp-a11y-content-wrap`, não relocação via JS

O Moodle usa JS para relocar o FAB/painel para fora do `.moodle-shell` (wrapper de conteúdo que recebe os filtros CSS de opções como inverter cores/daltonismo), evitando que o próprio widget seja afetado e evitando que `filter` num ancestral quebre o `position: fixed` desses elementos (CSS `filter` cria novo *containing block* para descendentes fixos).

O WordPress não tem um wrapper de conteúdo único equivalente. Em vez de relocação via JS, o plugin abre `<div id="wp-a11y-content-wrap">` em `wp_body_open` e fecha em `wp_footer` (prioridade 9999, depois de todo mundo) — o FAB/painel são impressos **depois** de fechar essa div, como irmãos, nunca dentro dela. Todas as regras `filter`-based de `a11y-effects.css` miram `#wp-a11y-content-wrap`, nunca `body`.

As classes `a11y-*` continuam sendo aplicadas em `<body>` (para o seletor `body.a11y-x #wp-a11y-content-wrap {...}`) — só a propriedade `filter` em si é escopada ao wrapper.

## D5 — Pegadinha de especificidade CSS: `all: initial` + modificadores em classe

`#wp-a11y-fab`/`#wp-a11y-panel` usam `all: initial` para isolar o widget do CSS do tema (especificidade de seletor de ID, 1-0-0). Classes modificadoras como `.wp-a11y-fab--bottom-right` têm especificidade menor (0-1-0) e **perdem** a cascata para qualquer propriedade que `all: initial` também toque — isso inclui `position`/`top`/`right`/`bottom`/`left`/`border-radius`, quebrando silenciosamente o posicionamento do FAB (ficava preso no fluxo normal do documento, perto do rodapé, em vez de fixo no canto configurado).

Corrigido qualificando essas classes com o próprio ID (`#wp-a11y-fab.wp-a11y-fab--bottom-right`), elevando a especificidade para 1-1-0. Confirmado com Chrome headless real (`getComputedStyle` antes/depois do fix) antes de dar a Fase 1 como concluída — nunca confie apenas na leitura do HTML gerado, o bug só aparece no computed style.

## D6 — Script Modules API nativa (WP 6.5+), sem bundler

`wp_register_script_module()`/`wp_enqueue_script_module()` geram automaticamente um import map do navegador para os módulos ESM do plugin (`@wp-a11y/storage`, `@wp-a11y/effects` etc.), permitindo `import`/`import()` dinâmico nativo sem Webpack/Vite/grunt. Site já roda WordPress 7.1, então a API está disponível. Módulos avançados da Fase 3 devem usar `import()` dinâmico dentro de `panel.js` para lazy-load, mesmo padrão do `main.js` do Moodle.
