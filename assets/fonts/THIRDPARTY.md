# Fontes de terceiros

Todas licenciadas sob a [SIL Open Font License 1.1](https://scripts.sil.org/OFL) (permite embutir/redistribuir com o plugin). Baixadas diretamente das fontes originais (Google Fonts / npm), nunca copiadas da árvore do plugin `local_a11y` (Moodle) — ver a regra de nunca misturar repositórios em CLAUDE.md/DECISIONS.md.

| Arquivo | Fonte | Uso |
|---|---|---|
| `atkinson-hyperlegible-{400,700}.woff2` | [Atkinson Hyperlegible](https://fonts.google.com/specimen/Atkinson+Hyperlegible), Google Fonts (Braille Institute) | Opção "Fonte Legível" |
| `lexend-{400,700}.woff2` | [Lexend](https://fonts.google.com/specimen/Lexend), Google Fonts | Opção "Fonte para Dislexia", nível 1 |
| `opendyslexic-{400,700}.woff2` | [OpenDyslexic](https://opendyslexic.org/) via pacote npm `@fontsource/opendyslexic` | Opção "Fonte para Dislexia", nível 2 |
| `inter-{400,700}.woff2` | [Inter](https://fonts.google.com/specimen/Inter), Google Fonts | Fonte da interface do próprio painel/FAB (não é uma opção de acessibilidade - `assets/css/panel.css`) |

Ver `LICENSE-opendyslexic-OFL.txt` para o texto integral (idêntico para as três fontes — todas OFL 1.1, apenas o titular do copyright difere).
