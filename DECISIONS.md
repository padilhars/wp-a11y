# DECISIONS.md — wp-a11y

Decisões de arquitetura não óbvias a partir do código. Ver também o plano completo de port (Fases 0–5) na sessão que iniciou o projeto.

## D1 — Fonte da verdade: `local_a11y` atual, não `_design-reference/`

O protótipo `_design-reference/*.jsx` do plugin Moodle é uma versão inicial/desatualizada (23 opções, sem `wordSpacing`/`blueLightFilter`/`bionicReading`/`magnifier`/`silenceMedia`; `focusMode`/`dyslexicFont` ainda booleanos ali, hoje steppers). O port usa o PHP/JS **atual** do `local_a11y` (`classes/options.php`, `classes/profiles.php` etc.) como fonte da verdade para a lista de opções/perfis. O protótipo só foi reaproveitado como referência de convenção (nomes de classe CSS, mecanismo de troca de classe no body).

`signLanguage` (30ª opção condicional no Moodle, dependente do plugin de terceiros VLibras) não foi portado — sem equivalente WordPress conhecido.

## D2 — Nunca misturar com o repositório `local_a11y`

Regra do usuário: o código Moodle é lido apenas como referência, nunca editado nem copiado literalmente. Isso inclui assets de terceiros (fontes, WASM) — sempre baixados de novo das fontes originais, nunca copiados da árvore do plugin Moodle. Ver `assets/fonts/THIRDPARTY.md`.

## D3 — Persistência: `localStorage` para visitantes, `usermeta` via REST para logados

Na Fase 1 (sem REST ainda), até usuário logado só tinha a preferência por navegador (`localStorage`). Na Fase 2, `assets/js/storage.js` passou a ramificar por `config.isLoggedIn`: logado usa `GET`/`POST /wp-a11y/v1/settings` (usermeta real, sincronizado entre dispositivos); visitante continua só `localStorage`. Migração automática de dados: se o servidor não tem preferência salva ainda (heurística: settings === defaults) e existe algo diferente de default no `localStorage`, esse valor é enviado ao servidor uma vez e o `localStorage` é limpo — mesmo comportamento do `storage.js` do Moodle.

## D4 — Isolamento visual do FAB/painel: wrapper `#wp-a11y-content-wrap`, não relocação via JS

O Moodle usa JS para relocar o FAB/painel para fora do `.moodle-shell` (wrapper de conteúdo que recebe os filtros CSS de opções como inverter cores/daltonismo), evitando que o próprio widget seja afetado e evitando que `filter` num ancestral quebre o `position: fixed` desses elementos (CSS `filter` cria novo *containing block* para descendentes fixos).

O WordPress não tem um wrapper de conteúdo único equivalente. Em vez de relocação via JS, o plugin abre `<div id="wp-a11y-content-wrap">` em `wp_body_open` e fecha em `wp_footer` (prioridade 9999, depois de todo mundo) — o FAB/painel são impressos **depois** de fechar essa div, como irmãos, nunca dentro dela. Todas as regras `filter`-based de `a11y-effects.css` miram `#wp-a11y-content-wrap`, nunca `body`.

As classes `a11y-*` continuam sendo aplicadas em `<body>` (para o seletor `body.a11y-x #wp-a11y-content-wrap {...}`) — só a propriedade `filter` em si é escopada ao wrapper.

## D5 — Pegadinha de especificidade CSS: `all: initial` + modificadores em classe

`#wp-a11y-fab`/`#wp-a11y-panel` usam `all: initial` para isolar o widget do CSS do tema (especificidade de seletor de ID, 1-0-0). Classes modificadoras como `.wp-a11y-fab--bottom-right` têm especificidade menor (0-1-0) e **perdem** a cascata para qualquer propriedade que `all: initial` também toque — isso inclui `position`/`top`/`right`/`bottom`/`left`/`border-radius`, quebrando silenciosamente o posicionamento do FAB (ficava preso no fluxo normal do documento, perto do rodapé, em vez de fixo no canto configurado).

Corrigido qualificando essas classes com o próprio ID (`#wp-a11y-fab.wp-a11y-fab--bottom-right`), elevando a especificidade para 1-1-0. Confirmado com Chrome headless real (`getComputedStyle` antes/depois do fix) antes de dar a Fase 1 como concluída — nunca confie apenas na leitura do HTML gerado, o bug só aparece no computed style.

## D6 — Script Modules API nativa (WP 6.5+), sem bundler

`wp_register_script_module()`/`wp_enqueue_script_module()` geram automaticamente um import map do navegador para os módulos ESM do plugin (`@wp-a11y/storage`, `@wp-a11y/effects` etc.), permitindo `import`/`import()` dinâmico nativo sem Webpack/Vite/grunt. Site já roda WordPress 7.1, então a API está disponível. Módulos avançados da Fase 3 devem usar `import()` dinâmico dentro de `panel.js` para lazy-load, mesmo padrão do `main.js` do Moodle.

## D7 — `sanitize_key()` não serve para `featureid`

O argumento REST `featureid` de `POST /wp-a11y/v1/activate` inicialmente usava `sanitize_key` como `sanitize_callback`. `sanitize_key()` **minúsculiza** a string (é feito para chaves de opção/meta, não para ids arbitrários) — `pauseAnimations` virava `pauseanimations`, que nunca batia com `Options::all_ids()` (todos camelCase), então `Stats::record_activation()` sempre retornava `false` silenciosamente (a rota respondia `200 {"recorded":false}`, sem erro nenhum). Trocado para `sanitize_text_field`, que preserva a caixa.

Só foi encontrado testando a chamada de verdade (curl direto na rota + Chrome headless) e conferindo a tabela `wp_a11y_stats` no banco - a resposta HTTP 200 sozinha não denunciava o bug. Mesma lição do D5: sempre validar contra o estado real (banco, `getComputedStyle`), nunca só o código ou uma resposta "sem erro".

## D10 — Falso alarme: "bug de calibração" da Navegação por Face era o `page.click()` do Puppeteer, não o plugin

Numa sessão anterior, um teste com Puppeteer (câmera/`FaceLandmarker` mockados) mostrando `captureNext` voltando a `false` sem `neutral` nunca ser definido (calibração nunca completava) foi registrado como um bug real do `face-navigation.js` e deixado pendente, com uma linha de debug temporária (`window.__wpA11yFaceDebug = {...}` em `detect()`) esquecida no arquivo.

Reinvestigado agora: o clique de teste no botão "Calibrar" usava `page.click('.wp-a11y-face-hud__button')` (clique baseado em coordenada). Trocando para `page.evaluate(() => elemento.click())` (clique programático via DOM), a calibração funciona perfeitamente - cursor aparece, calibração salva em `sessionStorage`, HUD mostra o estado ativo, o dwell-click (boca aberta) dispara um clique sintético de verdade no elemento sob o cursor, e o botão "Parar" desliga tudo de forma limpa. **Não havia bug nenhum no código** - `page.click()` do Puppeteer falha silenciosamente contra esse botão especificamente (sem lançar erro, sem aparecer em `page.on('pageerror')`), mesma classe de problema já visto e contornado repetidas vezes neste projeto (FAB/toggles do Moodle e do próprio wp-a11y) com clique programático em vez de coordenada.

Linha de debug removida de `detect()`. Lição: quando um teste com Puppeteer mostra um comportamento "impossível" dado o código-fonte (aqui, `captureNext` só pode virar `false` em dois lugares, e nenhum dos dois se encaixava no sintoma), suspeitar primeiro do próprio mecanismo de interação do teste antes de investir tempo depurando o código sob teste.

## D9 — Módulos avançados (Fase 3): `import()` por URL completa, nunca bare specifier

Os módulos de `assets/js/features/*.js` (tooltips, silenciar mídia, leitura biônica, e os que vierem depois) são importados dinamicamente pelo `panel.js` só na primeira ativação real da opção (`FEATURE_MODULES`), igual ao lazy-load do `main.js` do Moodle. A primeira tentativa os registrou como Script Modules do WP (`wp_register_script_module`) para poder usar um bare specifier tipo `@wp-a11y/feature-tooltips` no `import()` — **não funciona**: o import map gerado pelo WordPress só inclui módulos que estão enfileirados (`wp_enqueue_script_module`) ou que são dependência de algum módulo enfileirado; um módulo só registrado, sem nenhuma das duas coisas, fica de fora do import map, e o `import()` com bare specifier falha em runtime. Declarar como dependência do `@wp-a11y/panel` "resolveria" o import map, mas o WordPress aí imprime uma tag `<script type="module">` de verdade pra cada dependência de um módulo enfileirado — ou seja, carregaria tudo antecipadamente, destruindo o lazy-load.

Solução: o `panel.js` importa por **URL completa** (`config.featuresUrl` + nome do arquivo, vindo do PHP via `Render::render_config_script()`), não por bare specifier. `import()` com uma URL de verdade nunca precisa de import map — funciona em qualquer contexto de módulo, então nem precisa registrar esses arquivos como Script Module nenhum.

## D8 — REST URL: `rest_url()` já resolve plain vs. pretty permalinks

O site roda com permalinks "simples" (`permalink_structure` vazio), então `rest_url('wp-a11y/v1')` retorna `?rest_route=/wp-a11y/v1` em vez de `/wp-json/wp-a11y/v1`. O cliente (`storage.js`/`stats.js`) só concatena sufixos de path (`` `${config.restUrl}/settings` ``) sem nunca montar a URL base sozinho - funciona igual nos dois formatos, porque a concatenação simplesmente estende o valor do parâmetro `rest_route` quando os permalinks são simples. Nunca hardcode `/wp-json/...` em teste manual (`curl`) sem antes conferir `rest_url()` de verdade - foi exatamente isso que atrasou o diagnóstico do D7 (testes manuais batendo na URL errada pareciam indicar redirecionamento quebrado, quando na real a rota sempre funcionou).
