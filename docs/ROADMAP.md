# Roadmap

Cinco sprints de duas semanas. Cada sprint fecha com demo e PR `develop → staging` (homologação); `staging → main` quando a homologação aprova. Datas: ajustar ao calendário da disciplina.

## Os cinco perfis

| Perfil     | Responsável por                                                                  | Onde mexe                                   |
| ---------- | -------------------------------------------------------------------------------- | ------------------------------------------- |
| **Back**   | Rotas, controllers, regras de negócio, autenticação                              | `routes/`, `app/Http/`, `app/Models/`       |
| **Dados**  | Migrations, seeds, factories, integridade do banco                               | `database/`, `docs/MODELO_DADOS.md`         |
| **Front**  | Protótipos no Figma, telas Blade, Bootstrap, responsividade                      | `resources/views/`, Figma                   |
| **Testes** | Testes automatizados (unitários e de integração), CI                             | `tests/`, `.github/workflows/`              |
| **QA**     | Histórias e critérios de aceite, issues e quadro, revisão de PR, testes manuais, demo | `docs/HISTORIAS_USUARIO.md`, Issues, PRs |

Regra: todo PR tem revisão de outro perfil. QA revisa se atende à história; Testes revisa se tem teste.

## Sprint 0 — Ambiente e alinhamento

| Perfil | Entrega                                                                                  |
| ------ | ---------------------------------------------------------------------------------------- |
| Todos  | Scaffold rodando local (`INSTALACAO_*.md`), `php artisan test` verde, um PR de treino (`GITHUB_DESKTOP.md`); etiqueta do seu perfil nas issues |
| Back   | Ler `DECISOES.md`; mapear o que o Laravel já resolve (`Auth`, CSRF, validação)           |
| Dados  | Propor os atributos de cada tabela (tabela de perguntas em `MODELO_DADOS.md`)            |
| Front  | Design system e protótipos de login, admin, mesário e urna (issues `ui-ux` #23 a #27)     |
| Testes | Entender `tests/` e o CI; definir o que cada história precisa cobrir (`TESTES.md`)       |
| QA     | Revisar as issues das 15 histórias e o quadro do GitHub Projects; combinar a definição de pronto |

## Sprint 1 — Admin monta a eleição (H1, H2, H13, H4, H5)

O eleitorado (H4) sobe para esta sprint: os candidatos são indicados pela matrícula, então os alunos precisam estar cadastrados antes de abrir as inscrições.

| Perfil | Entrega                                                                                  |
| ------ | ---------------------------------------------------------------------------------------- |
| Back   | Login com perfis admin/mesário; CRUD de eleição com os dois períodos; cargos; importação de eleitores por CSV; terminais |
| Dados  | Atributos de `users`, `eleicoes` (datas de inscrição e votação), `cargos` (título), `eleitores`, `terminais`; seeds e factories |
| Front  | Layout do admin (menu + lista + formulário); tela de cargos com reordenação              |
| Testes | Login; ordem das datas da eleição; cargos bloqueados com chapa inscrita; CSV de eleitores com linha inválida |
| QA     | Critérios de aceite fechados antes de codar; teste manual de cada CRUD; demo             |

## Sprint 2 — Inscrição de chapas (H14, H15, H3)

**Publicar em produção ao fim desta sprint** se a eleição real estiver próxima: o período de inscrição acontece antes da votação (D14).

| Perfil | Entrega                                                                                  |
| ------ | ---------------------------------------------------------------------------------------- |
| Back   | Formulário público de inscrição (período, transação, número da chapa, throttle); importação de chapas por CSV; homologação |
| Dados  | Atributos de `chapas` (nome, situação, motivo, contato, origem) e, se preciso, `candidatos` |
| Front  | Formulário público com conferência do nome por matrícula; comprovante; relatório do CSV; tela de homologação |
| Testes | Fora do período; chapa incompleta; aluno em duas chapas; CSV tudo-ou-nada por chapa; pendente não vai para a urna |
| QA     | Simulação: 3 chapas se inscrevem (uma com erro), comissão homologa 2 e indefere 1        |

## Sprint 3 — Dia da votação de ponta a ponta (H6, H7, H8, H9)

| Perfil | Entrega                                                                                  |
| ------ | ---------------------------------------------------------------------------------------- |
| Back   | Liberar sessão; API de status da urna; registrar voto em transação                       |
| Dados  | Atributos de `sessoes_votacao` (situação, horários) e `votos` (branco/nulo)              |
| Front  | Painel do mesário; urna em modo quiosque com polling, composição das chapas e confirmação; protótipo do resultado (#28) e teste de usabilidade (#29) |
| Testes | Voto único, sessão expirada, duplo clique, só chapas homologadas na urna                 |
| QA     | Simulação com 3 pessoas: mesário, eleitor, observador; registrar falhas como issues `bug` |

## Sprint 4 — Resultado, auditoria e produção (H10, H11, H12)

| Perfil | Entrega                                                                                  |
| ------ | ---------------------------------------------------------------------------------------- |
| Back   | Apuração (`App\Support\Apuracao`), bloqueio do resultado antes do fim, log de auditoria; deploy final (`DEPLOY_HOSTINGER.md`) |
| Dados  | Tabela de auditoria; consulta de apuração; revisão de índices; backup antes e depois da eleição |
| Front  | Página de resultado com gráfico (Chart.js) e eleitos por cargo; exportação; acessibilidade (contraste, teclado na urna) |
| Testes | Apuração (unitários), resultado bloqueado/liberado, auditoria sem dado de voto; smoke test em produção |
| QA     | Conferir resultado do sistema contra contagem manual; simulação com a turma; apresentação final |

## Ordem de dependência

```
H1 login ─┬─▶ H2 eleição ─▶ H13 cargos ─┬─▶ H14 formulário ─┬─▶ H3 homologar ─┐
          ├─▶ H4 eleitores ─────────────┴─▶ H15 CSV chapas ─┘                 │
          └─▶ H5 terminais ───────────────────────────────────────────────────┼─▶ H6 liberar ─▶ H7 urna ─▶ H8 votar ─▶ H10 resultado
                                                                              │                                └─▶ H9 painel da mesa
H11 auditoria: começa após H1 e cresce junto (inscrição e homologação incluídas)
H12 deploy: após H3, se as inscrições forem reais; deploy final após H8
```

## Riscos

| Risco                                     | Resposta                                                            |
| ----------------------------------------- | ------------------------------------------------------------------- |
| Equipe travar na instalação               | Sprint 0 inteira para isso; par com quem já conseguiu               |
| Hostinger sem SSH no plano                | Confirmar na Sprint 0; alternativa por FTP em `DEPLOY_HOSTINGER.md` |
| Migration nova quebrar o sigilo do voto   | `ConstraintsTest` roda no CI                                        |
| Período de inscrição começar antes de o sistema estar pronto | Sprint 2 publica as inscrições; plano B: comissão recebe em papel e importa por CSV (H15) |
| Inscrição falsa no formulário público     | Homologação pela comissão (H3), throttle, auditoria com IP (H11)     |
| Banco local desatualizado após editar migration já rodada | Nunca editar migration que já foi para `develop`: criar outra. Em dev, `php artisan migrate:fresh --seed` |
