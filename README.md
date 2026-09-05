# wp-a11y

Plugin de acessibilidade para WordPress — port do plugin [`local_a11y`](https://github.com/padilhars/a11y), desenvolvido originalmente para Moodle.

## Status

**Fase 0 + Fase 1 concluídas.**

Fase 0 (esqueleto de dados):

- 29 opções de acessibilidade (`WP_A11y\Options`) e 9 perfis prontos (`WP_A11y\Profiles`), transcritos do `local_a11y` atual (não do protótipo `_design-reference/`, que está desatualizado — ver DECISIONS.md D1).
- Configurações do admin em `/wp-admin/admin.php?page=wp-a11y` (5 seções: Geral, FAB, Painel, Cores, Estatísticas), armazenadas em uma única opção `wp_a11y_settings`.
- Tabela de estatísticas agregadas e anônimas (`{prefix}wp_a11y_stats`) + relatório em `/wp-admin/admin.php?page=wp-a11y-stats`.

Fase 1 (FAB + painel + efeitos CSS puros):

- Botão flutuante + painel (popover/gaveta/modal conforme configuração; sempre gaveta em telas pequenas), com busca, perfis, categorias colapsáveis, atalho `Alt+A`, `Esc` fecha, focus trap.
- 19 das 29 opções têm efeito visual real via classes `body.a11y-*` (`assets/css/a11y-effects.css`): tipografia (incl. 3 fontes OFL auto-hospedadas — Atkinson Hyperlegible, Lexend, OpenDyslexic), cores/contraste (incl. simulação de daltonismo via filtros SVG `feColorMatrix`), mídia (ocultar imagens/pausar animações) e foco/navegação (cursor, modo foco). As 10 restantes (leitor de tela, teclado virtual, comandos de voz, navegação por face, guia/máscara de leitura, lupa, tooltips, pausar mídia, leitura biônica) são módulos JS ainda não implementados (Fase 3) — o toggle muda de estado mas ainda não tem efeito visível.
- Persistência **apenas via `localStorage`** por enquanto (para todo mundo, inclusive logado) — sincronização por usuário via REST/`usermeta` é a Fase 2. Script no-FOUC (`wp_body_open`) aplica o estado salvo antes da 1ª pintura.
- Testado com Chrome headless real: FAB/painel renderizam e funcionam, toggles/perfis/persistência/reload confirmados sem erros de console.

Ainda faltam: REST API de sincronização por usuário, relatório de estatísticas alimentado de verdade (sem endpoint de ativação ainda), e os 10 módulos avançados — ver as fases seguintes no plano de port.

**Nunca misturar este repositório com o `local_a11y`/Moodle** — o código Moodle é usado só como referência de leitura. Fontes de terceiros (OFL) foram baixadas de novo das fontes originais, não copiadas de lá — ver `assets/fonts/THIRDPARTY.md`.

## Ambiente de desenvolvimento

- WordPress instalado em `/var/www/wp-a11y/html`
- Banco de dados: MariaDB (`wp_a11y`)
- Este diretório é o repositório do plugin, montado diretamente em `wp-content/plugins/wp-a11y`
- URL: https://wp.snifrbid.com.br

## Licença

GPL v3 ou posterior — ver [LICENSE](LICENSE).
