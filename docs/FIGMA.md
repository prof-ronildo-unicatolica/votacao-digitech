# Figma no projeto

Sim, há espaço e é bem-vindo: as três interfaces têm públicos e contextos diferentes (eleitor em pé numa urna, mesário atendendo fila, admin em mesa), e desenhar antes evita retrabalho em Blade.

## Conta

Figma tem plano **Education** gratuito para alunos e professores: <https://www.figma.com/education/>. Criar um único arquivo de equipe, "Votação DigiTech".

## O que prototipar (Sprint 0)

| Tela                         | Contexto de uso                                   | Pontos de atenção                                              |
| ---------------------------- | ------------------------------------------------- | -------------------------------------------------------------- |
| **Urna** (H7, H8)            | Tela cheia, sem mouse às vezes, eleitor nervoso   | Fonte ≥ 24px, botões ≥ 48px, 3 estados: aguardando → escolha → confirmação → obrigado |
| **Painel do mesário** (H6, H9) | Notebook, atendimento rápido                    | Campo de matrícula em foco ao abrir; feedback claro de erro (já votou, terminal ocupado); grade de terminais com cor por estado |
| **Admin** (H2–H5, H10)       | Mesa, sem pressa                                  | Padrão lista + formulário reutilizável; importação de CSV com resultado; página de resultado com gráfico |

Também: **tela de login** (H1) e um **estado de erro** genérico.

## Ordem de trabalho

1. **Wireframe** em baixa fidelidade (caixas e texto) das telas acima. Revisar em 30 minutos com o grupo.
2. **Tokens**: paleta (primária, sucesso, erro, neutros), tipografia, espaçamento. Esses tokens viram variáveis CSS em `resources/css` ou classes do Bootstrap.
3. **Alta fidelidade** só da urna e do painel do mesário (são as telas críticas no dia).
4. Protótipo clicável do fluxo liberar → votar → confirmar, para testar com 3 alunos fora da equipe.

## Como conecta com o código

- O cartão no Trello de cada história linka o frame do Figma.
- A branch da história só começa com o frame aprovado ("Definição de pronto" em `HISTORIAS_USUARIO.md`).
- Bootstrap 5 tem kit oficial na Figma Community ("Bootstrap 5 UI Kit"); usar acelera e mantém o protótipo fiel ao que dá para construir.

## Acessibilidade desde o desenho

Contraste mínimo 4.5:1 (plugin "Contrast" no Figma), foco visível nos botões, e a urna precisa ser operável só com Tab + Enter.
