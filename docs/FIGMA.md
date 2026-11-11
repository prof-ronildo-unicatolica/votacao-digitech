# Figma no projeto

Sim, há espaço e é bem-vindo: as três interfaces têm públicos e contextos diferentes (eleitor em pé numa urna, mesário atendendo fila, admin em mesa), e desenhar antes evita retrabalho em Blade.

## Conta

Figma tem plano **Education** gratuito para alunos e professores: <https://www.figma.com/education/>. Criar um único arquivo de equipe, "Votação DigiTech".

## Ponto de partida: identidade visual pronta

A paleta, a tipografia, os espaçamentos e a marca já estão definidos em **[IDENTIDADE_VISUAL.md](IDENTIDADE_VISUAL.md)**, com uma fonte única: `design/tokens/tokens.json`. Não recrie as cores à mão no Figma; importe:

1. Instale o plugin **Tokens Studio for Figma** (gratuito) no arquivo da equipe.
2. *Tokens Studio → Settings → Token Format*: **W3C DTCG**.
3. *Tools → Load from file* → escolha `design/tokens/tokens.json`.
4. *Styles & Variables → Create variables* (cores, espaços, raios) e *Create styles* (tipografia, sombras).
5. Arraste os arquivos de `design/marca/` para uma página "Marca".
6. Instale as fontes **Poppins**, **Inter** e **JetBrains Mono** (Google Fonts) na sua máquina, ou o Figma vai substituí-las.
7. Componentes e telas prontos: rode `php artisan serve`, instale a extensão do Chrome **html.to.design** e importe `http://localhost:8000/estilo` (vitrine com todos os componentes) e as telas de referência em `/estilo/telas/urna-escolha`, `/mesario`, `/apuracao`, `/login`... Cada página vira um frame editável; promova os blocos a componentes com os mesmos nomes dos componentes Blade (`x-botao`, `x-status-terminal`...).

Mudou um token no Figma? Exporte com o Tokens Studio, substitua `tokens.json`, rode `php design/tokens/gerar-css.php` e abra um PR: o código acompanha.

## O que prototipar (Sprint 0)

Cada tela tem uma issue com etiqueta `ui-ux`: #23 design system, #24 login, #25 admin, #26 painel do mesário, #27 urna, #28 resultado, #29 teste de usabilidade. O quadro do projeto está em <https://github.com/users/prof-ronildo-unicatolica/projects/1>.

| Tela                         | Contexto de uso                                   | Pontos de atenção                                              |
| ---------------------------- | ------------------------------------------------- | -------------------------------------------------------------- |
| **Urna** (H7, H8)            | Tela cheia, sem mouse às vezes, eleitor nervoso   | Fonte ≥ 24px, botões ≥ 48px, 3 estados: aguardando → escolha → confirmação → obrigado |
| **Painel do mesário** (H6, H9) | Notebook, atendimento rápido                    | Campo de matrícula em foco ao abrir; feedback claro de erro (já votou, terminal ocupado); grade de terminais com cor por estado |
| **Admin** (H2–H5, H10)       | Mesa, sem pressa                                  | Padrão lista + formulário reutilizável; importação de CSV com resultado; página de resultado com gráfico |

Também: **tela de login** (H1) e um **estado de erro** genérico.

## Ordem de trabalho

1. **Wireframe** em baixa fidelidade (caixas e texto) das telas acima. Revisar em 30 minutos com o grupo.
2. **Tokens**: já prontos (seção acima). Use as variáveis importadas; se faltar alguma, proponha no `tokens.json` por PR.
3. **Alta fidelidade** só da urna e do painel do mesário (são as telas críticas no dia).
4. Protótipo clicável do fluxo liberar → votar → confirmar, para testar com 3 alunos fora da equipe.

## Como conecta com o código

- A issue de cada história linka o frame do Figma.
- A branch da história só começa com o frame aprovado ("Definição de pronto" em `HISTORIAS_USUARIO.md`).
- Bootstrap 5 tem kit oficial na Figma Community ("Bootstrap 5 UI Kit"); usar acelera e mantém o protótipo fiel ao que dá para construir.

## Acessibilidade desde o desenho

Contraste mínimo 4.5:1 (plugin "Contrast" no Figma), foco visível nos botões, e a urna precisa ser operável só com Tab + Enter.
