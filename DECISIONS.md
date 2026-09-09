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

## D13 — Fase 5: versão 1.0.0, capturas de tela reais, empacotamento sem instalar nada novo no sistema

Fase 5 (polimento/documentação) do plano de port, a última.

**Versão `0.1.0` → `1.0.0`**: mesmo critério já usado no `local_a11y` (D65 lá) - cortar uma versão de verdade num marco significativo (aqui, as Fases 0-5 completas) em vez de deixar acumulando indefinidamente como "não lançado". `0.1.0` nunca foi uma versão publicada de verdade, só o placeholder do esqueleto inicial (Fase 0).

**Capturas de tela reais, não mockup**: 4 imagens (`docs/screenshots/`) tiradas com Chrome headless de verdade contra o site rodando - FAB fechado, painel com perfis + categoria de tipografia aberta, painel com cores/mídia abertas, e uma combinação de efeitos realmente aplicados (alto contraste + fonte para dislexia, capturada com o painel fechado para mostrar o resultado visual de verdade, não a UI de configuração). Ao tirar a 3ª, a categoria "Cores e Contraste" abria mas ficava fora da área visível do painel (que tem scroll interno) - corrigido com `scrollIntoView()` antes do screenshot, não just esperando mais tempo.

**Atributos de clique do painel são diferentes dos do `local_a11y`**: ao automatizar essas capturas descobri que este plugin usa `data-category` (não `data-category-id`) nas seções, e que clicar em `[data-option-id]` sozinho atinge a **linha inteira** da opção (`.wp-a11y-option`, o `<div>` que só exibe o label), não o elemento clicável de verdade - o botão real é um filho com sua própria classe (`.wp-a11y-toggle` ou `.wp-a11y-stepper`, ambos também com o mesmo `data-option-id`), e é nele que `panel.js` escuta o clique (via `event.target.closest(...)` na delegação em `panel`). Diferente da estrutura do `local_a11y` (`role="button"` na linha inteira, D76 lá) - aqui a linha em si não é clicável, só o botão dentro dela. Documentado aqui porque não é óbvio a partir de uma leitura rápida do HTML gerado, e um teste automatizado que erre esse detalhe simplesmente não clica em nada, sem erro nenhum.

**Empacotamento sem instalar nada novo no sistema**: `bin/build-zip.sh` (novo) monta um `wp-a11y.zip` pronto para "Adicionar novo plugin > Enviar plugin" no wp-admin, via `rsync` (exclui `.git`, `DECISIONS.md`, `docs/`, `bin/` - os mesmos padrões ancorados na raiz com `/` na frente, mesma lição do D13 do `local_a11y` sobre `--exclude` sem barra inicial capturar caminhos aninhados por engano) seguido de compressão. O comando `zip` não está instalado neste ambiente; em vez de instalá-lo (mudança de sistema fora do escopo do plugin), o script usa a extensão `ZipArchive` do próprio PHP via `php -r`, que já é uma dependência garantida de qualquer stack que rode este plugin. Verificado inspecionando o `.zip` gerado (`python3 zipfile`): todos os arquivos de runtime presentes, nenhum arquivo de desenvolvimento vazando.

## D12 — Fase 4: auditoria PHPCS (WordPress-Extra) + ESLint

Ferramentas instaladas fora do repositório (`/tmp/a11y-qa/wp-a11y-cs`, `/tmp/a11y-qa/wp-a11y-eslint` - Composer/npm não fazem parte deste plugin, D6), rodadas contra `includes/*.php` e `assets/js/**/*.js`.

**PHPCS (`WordPress-Extra`)**: 3 erros reais, todos em `class-stats.php` - `$wpdb->query()`/`get_results()` com o nome da tabela interpolado direto na string (`"...{$table_name}..."`), incluindo um dentro de `$wpdb->prepare()` (que não protege identificadores, só valores) e um com um comentário `phpcs:ignore` **com o código de sniff errado** (`PreparedSQL.NotPrepared` em vez de `PreparedSQL.InterpolatedNotPrepared`), então não suprimia nada de verdade. Não era um SQL injection de verdade na prática (`$table_name` é sempre `$wpdb->prefix . 'wp_a11y_stats'`, nunca entrada externa), mas trocado mesmo assim pelo placeholder `%i` (identificador, suportado desde WP 6.2 - este plugin já exige 6.5+) via `$wpdb->prepare()` nos 3 lugares (INSERT, SELECT, DROP), removendo o comentário de supressão que não fazia nada. Verificado ao vivo: ativei `collectstats` temporariamente, contei uma ativação de verdade (`wp db query` confirmou a linha), chamei `Stats::get_all()` via `wp eval` (confirmou o SELECT), depois limpei a linha de teste e devolvi `collectstats` ao valor original.

Restante (57 avisos) era só desalinhamento de `=>`/`=` em arrays - corrigido automaticamente com `phpcbf`, sem mudança de comportamento (confirmado com o mesmo smoke test do FAB/painel depois). 3 avisos finais aceitos como não-problema: usar `$default`/`$class`/`$page` como nome de parâmetro (padrão do próprio WordPress core) e o parâmetro `$page` não utilizado nos callbacks de exporter/eraser da Privacy API (assinatura fixa da API, ver D11).

**ESLint**: sem config própria no plugin (D6 - sem bundler/Node em produção), então usei um `eslint.config.js` avulso e genérico (não o `@wordpress/eslint-plugin` oficial - a versão atual dele exige ESLint 9/10 com uma cadeia de dependências que não valia a pena brigar para uma auditoria única) contra `assets/js/**`. Zero erros reais depois de completar a lista de globals do navegador que faltavam (`Event`, `NodeFilter`, `SpeechSynthesisUtterance`, `setTimeout`/`clearTimeout` - faltavam só na minha config, não no código). 18 avisos cosméticos, todos limpos: 17 blocos `catch ( e ) { /* comentário, e nunca usado */ }` convertidos para `catch {` (optional catch binding, ES2019+, já coberto pelos navegadores que este plugin já assume para SpeechRecognition/MediaPipe/Script Modules) e um `let vel` que só é mutado (nunca reatribuído) trocado para `const`. Comportamento re-verificado depois: os dois testes mock (voz e navegação por face) e o smoke test geral do painel, todos passando igual a antes.

## D11 — Privacy API: exporter/eraser + política de privacidade sugerida

Fase 4 do plano de port. `local_a11y`'s `classes/privacy/provider.php` só precisa de um exporter (Moodle apaga `user_preferences` automaticamente na exclusão de conta, uma vez declarada via `add_user_preference()`) - o WordPress **não** tem esse comportamento automático para usermeta arbitrário: é preciso registrar um eraser explícito (`wp_privacy_personal_data_erasers`) além do exporter (`wp_privacy_personal_data_exporters`), senão a ferramenta de exclusão de dados pessoais (Ferramentas > Apagar Dados Pessoais) nunca saberia que este plugin guarda algo.

`class-privacy.php` (novo) registra os três ganchos padrão do WordPress: os dois filtros acima (lendo/apagando `Manager::USER_META_KEY` do usuário pedido, buscado por e-mail via `get_user_by()`) e `wp_add_privacy_policy_content()` em `admin_init`, com texto sugerido cobrindo três coisas: (1) a preferência em si (usermeta para logados, `localStorage` para visitantes - nunca sai do navegador de quem não está logado); (2) Comandos por Voz - o áudio do microfone é enviado pelo próprio navegador a um serviço remoto do fabricante (não deste site); (3) Navegação por Face - a imagem da câmera nunca sai do navegador, mas dois arquivos de terceiros (MediaPipe) são baixados ao vivo (mesmo achado do D62 do `local_a11y`, mesma transparência). A tabela de estatísticas (`wp_wp_a11y_stats`) é deliberadamente **não** declarada aqui, mesmo raciocínio do `local_a11y` D47: não tem nenhuma coluna que identifique usuário/sessão/IP, nada para exportar ou apagar por pessoa.

Verificado com um usuário real (`wp eval`): salvei preferências de teste, `export_settings()` devolveu os 29 valores sanitizados no formato esperado pela ferramenta de exportação, `erase_settings()` removeu o usermeta de fato (confirmado lendo de novo, vazio depois), e `add_privacy_policy_content()` roda sem erro fatal - o aviso "chamada incorretamente" que aparece ao testar via `wp eval`/CLI é esperado e não indica bug: é o próprio WordPress detectando que `is_admin()` é falso fora de uma requisição HTTP real a `/wp-admin/`, não uma falha do código (que só chama essa função dentro do hook `admin_init`, exatamente como a documentação do WordPress pede).

## D10 — Falso alarme: "bug de calibração" da Navegação por Face era o `page.click()` do Puppeteer, não o plugin

Numa sessão anterior, um teste com Puppeteer (câmera/`FaceLandmarker` mockados) mostrando `captureNext` voltando a `false` sem `neutral` nunca ser definido (calibração nunca completava) foi registrado como um bug real do `face-navigation.js` e deixado pendente, com uma linha de debug temporária (`window.__wpA11yFaceDebug = {...}` em `detect()`) esquecida no arquivo.

Reinvestigado agora: o clique de teste no botão "Calibrar" usava `page.click('.wp-a11y-face-hud__button')` (clique baseado em coordenada). Trocando para `page.evaluate(() => elemento.click())` (clique programático via DOM), a calibração funciona perfeitamente - cursor aparece, calibração salva em `sessionStorage`, HUD mostra o estado ativo, o dwell-click (boca aberta) dispara um clique sintético de verdade no elemento sob o cursor, e o botão "Parar" desliga tudo de forma limpa. **Não havia bug nenhum no código** - `page.click()` do Puppeteer falha silenciosamente contra esse botão especificamente (sem lançar erro, sem aparecer em `page.on('pageerror')`), mesma classe de problema já visto e contornado repetidas vezes neste projeto (FAB/toggles do Moodle e do próprio wp-a11y) com clique programático em vez de coordenada.

Linha de debug removida de `detect()`. Lição: quando um teste com Puppeteer mostra um comportamento "impossível" dado o código-fonte (aqui, `captureNext` só pode virar `false` em dois lugares, e nenhum dos dois se encaixava no sintoma), suspeitar primeiro do próprio mecanismo de interação do teste antes de investir tempo depurando o código sob teste.

## D9 — Módulos avançados (Fase 3): `import()` por URL completa, nunca bare specifier

Os módulos de `assets/js/features/*.js` (tooltips, silenciar mídia, leitura biônica, e os que vierem depois) são importados dinamicamente pelo `panel.js` só na primeira ativação real da opção (`FEATURE_MODULES`), igual ao lazy-load do `main.js` do Moodle. A primeira tentativa os registrou como Script Modules do WP (`wp_register_script_module`) para poder usar um bare specifier tipo `@wp-a11y/feature-tooltips` no `import()` — **não funciona**: o import map gerado pelo WordPress só inclui módulos que estão enfileirados (`wp_enqueue_script_module`) ou que são dependência de algum módulo enfileirado; um módulo só registrado, sem nenhuma das duas coisas, fica de fora do import map, e o `import()` com bare specifier falha em runtime. Declarar como dependência do `@wp-a11y/panel` "resolveria" o import map, mas o WordPress aí imprime uma tag `<script type="module">` de verdade pra cada dependência de um módulo enfileirado — ou seja, carregaria tudo antecipadamente, destruindo o lazy-load.

Solução: o `panel.js` importa por **URL completa** (`config.featuresUrl` + nome do arquivo, vindo do PHP via `Render::render_config_script()`), não por bare specifier. `import()` com uma URL de verdade nunca precisa de import map — funciona em qualquer contexto de módulo, então nem precisa registrar esses arquivos como Script Module nenhum.

## D8 — REST URL: `rest_url()` já resolve plain vs. pretty permalinks

O site roda com permalinks "simples" (`permalink_structure` vazio), então `rest_url('wp-a11y/v1')` retorna `?rest_route=/wp-a11y/v1` em vez de `/wp-json/wp-a11y/v1`. O cliente (`storage.js`/`stats.js`) só concatena sufixos de path (`` `${config.restUrl}/settings` ``) sem nunca montar a URL base sozinho - funciona igual nos dois formatos, porque a concatenação simplesmente estende o valor do parâmetro `rest_route` quando os permalinks são simples. Nunca hardcode `/wp-json/...` em teste manual (`curl`) sem antes conferir `rest_url()` de verdade - foi exatamente isso que atrasou o diagnóstico do D7 (testes manuais batendo na URL errada pareciam indicar redirecionamento quebrado, quando na real a rota sempre funcionou).
