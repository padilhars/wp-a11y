# wp-a11y

Plugin de acessibilidade para WordPress — port do plugin [`local_a11y`](https://github.com/padilhars/a11y), desenvolvido originalmente para Moodle.

## Status

**Fase 0 concluída** (esqueleto de dados, sem UI de painel ainda):

- 29 opções de acessibilidade (`WP_A11y\Options`) e 9 perfis prontos (`WP_A11y\Profiles`), transcritos do `local_a11y` atual (não do protótipo `_design-reference/`, que está desatualizado — ver DECISIONS abaixo).
- Configurações do admin em `/wp-admin/admin.php?page=wp-a11y` (5 seções: Geral, FAB, Painel, Cores, Estatísticas), armazenadas em uma única opção `wp_a11y_settings`.
- Preferência por usuário logado: um único `usermeta` (`wp_a11y_settings`, JSON) com validação/clamp server-side (`WP_A11y\Manager::sanitize_settings()`).
- Tabela de estatísticas agregadas e anônimas (`{prefix}wp_a11y_stats`) + relatório em `/wp-admin/admin.php?page=wp-a11y-stats`.

Ainda faltam: FAB/painel visível no front-end, efeitos CSS, REST API de sincronização, e os módulos avançados (leitor de tela, teclado virtual, comandos de voz, navegação por face, etc.) — ver as fases seguintes no plano de port.

**Nunca misturar este repositório com o `local_a11y`/Moodle** — o código Moodle é usado só como referência de leitura.

## Ambiente de desenvolvimento

- WordPress instalado em `/var/www/wp-a11y/html`
- Banco de dados: MariaDB (`wp_a11y`)
- Este diretório é o repositório do plugin, montado diretamente em `wp-content/plugins/wp-a11y`
- URL: https://wp.snifrbid.com.br

## Licença

GPL v3 ou posterior — ver [LICENSE](LICENSE).
