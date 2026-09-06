# wp-a11y

Plugin de acessibilidade para WordPress — port do plugin [`local_a11y`](https://github.com/padilhars/a11y), desenvolvido originalmente para Moodle.

## Status

**Fases 0, 1 e 2 concluídas; Fase 3 em andamento (8 de 10 módulos avançados).**

Fase 0 (esqueleto de dados):

- 29 opções de acessibilidade (`WP_A11y\Options`) e 9 perfis prontos (`WP_A11y\Profiles`), transcritos do `local_a11y` atual (não do protótipo `_design-reference/`, que está desatualizado — ver DECISIONS.md D1).
- Configurações do admin em `/wp-admin/admin.php?page=wp-a11y` (5 seções: Geral, FAB, Painel, Cores, Estatísticas), armazenadas em uma única opção `wp_a11y_settings`.
- Tabela de estatísticas agregadas e anônimas (`{prefix}wp_a11y_stats`) + relatório em `/wp-admin/admin.php?page=wp-a11y-stats`.

Fase 1 (FAB + painel + efeitos CSS puros):

- Botão flutuante + painel (popover/gaveta/modal conforme configuração; sempre gaveta em telas pequenas), com busca, perfis, categorias colapsáveis, atalho `Alt+A`, `Esc` fecha, focus trap.
- 19 das 29 opções têm efeito visual real via classes `body.a11y-*` (`assets/css/a11y-effects.css`): tipografia (incl. 3 fontes OFL auto-hospedadas — Atkinson Hyperlegible, Lexend, OpenDyslexic), cores/contraste (incl. simulação de daltonismo via filtros SVG `feColorMatrix`), mídia (ocultar imagens/pausar animações) e foco/navegação (cursor, modo foco). As 10 restantes (leitor de tela, teclado virtual, comandos de voz, navegação por face, guia/máscara de leitura, lupa, tooltips, pausar mídia, leitura biônica) são módulos JS ainda não implementados (Fase 3) — o toggle muda de estado mas ainda não tem efeito visível.
- Persistência via `localStorage` para visitantes; usuários logados agora sincronizam de verdade com o servidor (Fase 2, abaixo). Script no-FOUC (`wp_body_open`) aplica o estado salvo antes da 1ª pintura.
- Testado com Chrome headless real: FAB/painel renderizam e funcionam, toggles/perfis/persistência/reload confirmados sem erros de console.

Fase 2 (REST API + estatísticas):

- `GET`/`POST /wp-json/wp-a11y/v1/settings` (autenticado, nonce `wp_rest`): usuário logado lê/escreve sua preferência real em `usermeta`, sincronizada entre dispositivos. Migração automática de qualquer preferência de visitante salva em `localStorage` no primeiro login sem preferência salva ainda.
- Script no-FOUC agora embute a preferência **real** do usuário logado (lida direto do `usermeta` no PHP) em vez de sempre depender do `localStorage` — elimina o flash de FOUC que a Fase 1 ainda tinha para quem já estava logado.
- `POST /wp-json/wp-a11y/v1/activate`: contador de ativação por opção, mesma validação/allowlist server-side de antes (Fase 0), agora de fato alimentado pelo painel — disparado (fire-and-forget) sempre que uma opção passa de padrão→ativa, só quando a coleta de estatísticas está ligada.
- Relatório de estatísticas (`/wp-admin/admin.php?page=wp-a11y-stats`) agora mostra dados reais.
- Testado ponta a ponta com Chrome headless real (login incluso): sincronização de preferência, reidratação sem FOUC após reload, e contador de estatísticas confirmados direto no banco.

Fase 3 (módulos avançados, um de cada vez — em andamento):

- **Dicas de Ferramentas** (`tooltips`): converte `[title]` nativo (pequeno, com atraso, só no mouse) numa dica visível também por foco de teclado (`assets/js/features/tooltips.js`).
- **Silenciar Mídia** (`silenceMedia`): muta todo `<audio>`/`<video>` da página, incluindo mídia que começa a tocar depois de ativado; restaura o estado original ao desativar (`silence-media.js`).
- **Leitura Biônica** (`bionicReading`): negrita a primeira metade de cada palavra do conteúdo real da página, reversível sem perder o texto original (`bionic-reading.js`).
- **Guia de Leitura** (`readingGuide`): faixa horizontal destacada (cor configurável em Cores) que acompanha o mouse verticalmente, ajudando a manter a linha de leitura (`reading-guide.js`).
- **Máscara de Leitura** (`readingMask`): duas faixas escurecidas acima/abaixo do cursor, isolando uma janela de leitura de ~120px (`reading-mask.js`).
- **Lupa** (`magnifier`): lente circular que segue o mouse mostrando uma cópia ampliada (2,2×) do conteúdo sob o cursor — clona `#wp-a11y-content-wrap` uma vez e só reposiciona via `transform` a cada movimento (`magnifier.js`).
- **Leitor de Tela** (`screenReader`): passar o mouse destaca qualquer texto da página (títulos, parágrafos, links, botões, etc.); clicar lê em voz alta via `speechSynthesis` nativo do navegador — nenhum áudio é enviado a lugar nenhum, é 100% processamento local do navegador (`screen-reader.js`).
- **Teclado Virtual** (`virtualKeyboard`): aparece automaticamente ao focar um campo de texto dentro do conteúdo da página; digita usando `execCommand('insertText'/'delete')`, que preserva posição do cursor e dispara os eventos `input` reais (`virtual-keyboard.js`).
- Cada módulo é importado dinamicamente (`import()` por URL, sem bundler) só na primeira ativação real da opção — nada é baixado à toa. Todos testados ligando/desligando de verdade num Chrome headless (efeito aplicado E revertido corretamente, posições/transformações conferidas, digitação de verdade num campo de teste, sem erro de console).

Ainda faltam 2 módulos, os mais sensíveis do ponto de vista de privacidade: comandos de voz (SpeechRecognition + aviso de privacidade obrigatório) e navegação por face (MediaPipe, com verificação de integridade do modelo baixado) — ver Fase 3 no plano de port.

**Nunca misturar este repositório com o `local_a11y`/Moodle** — o código Moodle é usado só como referência de leitura. Fontes de terceiros (OFL) foram baixadas de novo das fontes originais, não copiadas de lá — ver `assets/fonts/THIRDPARTY.md`.

## Ambiente de desenvolvimento

- WordPress instalado em `/var/www/wp-a11y/html`
- Banco de dados: MariaDB (`wp_a11y`)
- Este diretório é o repositório do plugin, montado diretamente em `wp-content/plugins/wp-a11y`
- URL: https://wp.snifrbid.com.br

## Licença

GPL v3 ou posterior — ver [LICENSE](LICENSE).
