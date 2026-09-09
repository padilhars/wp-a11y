# Changelog

Todas as mudanças notáveis do `wp-a11y` são documentadas aqui. Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/).

## [1.0.0] - 2026-09-09

Port completo do plugin de acessibilidade [`local_a11y`](https://github.com/padilhars/a11y) (Moodle) para WordPress, do zero até uma versão de verdade (Fases 0-5). Nunca houve uma versão publicada antes desta - `0.1.0` era só o placeholder inicial do esqueleto do plugin.

### Added

- **Fase 0 - dados e configuração**: 29 opções de acessibilidade (`Options`) e 9 perfis prontos (`Profiles`), transcritos do `local_a11y` atual (não do protótipo `_design-reference/`, desatualizado - ver DECISIONS.md D1). Configurações do admin em `/wp-admin/admin.php?page=wp-a11y` (5 seções), armazenadas em uma única opção `wp_a11y_settings`. Tabela de estatísticas agregadas e anônimas (`{prefix}wp_a11y_stats`) + relatório em `/wp-admin/admin.php?page=wp-a11y-stats`.
- **Fase 1 - FAB, painel e efeitos CSS**: botão flutuante + painel (popover/gaveta/modal conforme configuração), busca, perfis, categorias colapsáveis, atalho `Alt+A`, `Esc` fecha, focus trap. 19 das 29 opções com efeito visual real via classes `body.a11y-*`. Persistência via `localStorage` para visitantes. Script anti-FOUC (`wp_body_open`).
- **Fase 2 - REST API + estatísticas reais**: `GET`/`POST /wp-json/wp-a11y/v1/settings` (usuário logado, `usermeta` sincronizado entre dispositivos, migração automática do `localStorage` no primeiro login). `POST /wp-json/wp-a11y/v1/activate` (contador de ativação por opção, allowlist server-side). Relatório de estatísticas com dados reais.
- **Fase 3 - os 10 módulos avançados** (`assets/js/features/*.js`, importados dinamicamente só na primeira ativação real de cada opção): Dicas de Ferramentas, Silenciar Mídia, Leitura Biônica, Guia de Leitura, Máscara de Leitura, Lupa, Leitor de Tela (`speechSynthesis`), Teclado Virtual, Comandos por Voz (`SpeechRecognition`, ~35 comandos, aviso de privacidade obrigatório) e Navegação por Face (MediaPipe FaceLandmarker, cursor virtual por movimento de cabeça, clique por boca/olhos, verificação de integridade SHA-256 dos arquivos de terceiros).
- **Fase 4 - privacidade + auditoria**: Privacy API completa (`class-privacy.php`) - exporter/eraser da preferência pessoal (`usermeta`) e texto sugerido de política de privacidade cobrindo Comandos por Voz/Navegação por Face. Auditoria PHPCS (`WordPress-Extra`) e ESLint.
- **Fase 5 - polimento**: este changelog, `languages/wp-a11y.pot` (101 strings), capturas de tela reais (`docs/screenshots/`), script de empacotamento (`bin/build-zip.sh`).

### Fixed

- `class-stats.php`: 3 consultas com o nome da tabela interpolado direto na string em vez do placeholder `%i` do `$wpdb->prepare()` (não explorável na prática - o nome vem sempre de `$wpdb->prefix`, nunca de entrada externa - mas corrigido para seguir o padrão correto de qualquer forma); um comentário de supressão de aviso apontava para o código de sniff errado e não suprimia nada. Ver DECISIONS.md D12.

### Security

- Privacy API: exporter e eraser registrados para a única preferência pessoal armazenada (`wp_a11y_settings`); antes desta versão, um pedido de exclusão de dados via Ferramentas > Apagar Dados Pessoais não tinha como saber que este plugin guardava algo. Ver DECISIONS.md D11.
- Navegação por Face: os dois arquivos de terceiros (MediaPipe) necessários são baixados ao vivo e verificados por SHA-256 antes do uso, fechando o mesmo gap TOCTOU que o `local_a11y` trata (D62 lá).

### Docs

- `DECISIONS.md`: 12 decisões registradas cobrindo toda a arquitetura do port (persistência, isolamento visual do widget, Script Modules API, REST, lazy-load de módulos avançados, Privacy API, auditoria de qualidade) - ver o arquivo para o histórico completo.
