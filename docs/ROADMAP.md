# Roadmap

Cinco sprints de duas semanas. Cada sprint fecha com demo e PR `develop → main`. Datas: ajustar ao calendário da disciplina.

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
| Front  | Wireframes de login, admin, mesário e urna (`FIGMA.md`); link do frame em cada issue     |
| Testes | Entender `tests/` e o CI; definir o que cada história precisa cobrir (`TESTES.md`)       |
| QA     | Revisar as issues das 12 histórias e o quadro do GitHub Projects; combinar a definição de pronto |

## Sprint 1 — Admin monta uma eleição (H1, H2, H3, H5)

| Perfil | Entrega                                                                                  |
| ------ | ---------------------------------------------------------------------------------------- |
| Back   | Login com perfis admin/mesário; CRUD de eleição, chapas e terminais                      |
| Dados  | Migrations dos atributos de `users`, `eleicoes`, `chapas`, `terminais`; seeds e factories |
| Front  | Layout do admin (menu + lista + formulário) em Blade/Bootstrap, a partir do Figma        |
| Testes | Testes de login (sucesso, senha errada, mesário barrado do admin) e dos CRUDs            |
| QA     | Critérios de aceite fechados antes de codar; teste manual de cada CRUD; demo             |

## Sprint 2 — Dia da votação de ponta a ponta (H4, H6, H7, H8)

| Perfil | Entrega                                                                                  |
| ------ | ---------------------------------------------------------------------------------------- |
| Back   | Importar eleitores por CSV; liberar sessão; API de status da urna; registrar voto em transação |
| Dados  | Atributos de `eleitores`, `sessoes_votacao` (situação, horários) e `votos` (branco/nulo) |
| Front  | Painel do mesário; urna em modo quiosque com polling e tela de confirmação               |
| Testes | Voto único, sessão expirada, duplo clique, CSV com linha inválida                        |
| QA     | Simulação com 3 pessoas: mesário, eleitor, observador; registrar falhas como issues `bug` |

## Sprint 3 — Resultado, painel da mesa e auditoria (H9, H10, H11)

| Perfil | Entrega                                                                                  |
| ------ | ---------------------------------------------------------------------------------------- |
| Back   | Apuração (`App\Support\Apuracao`), bloqueio do resultado antes do fim, log de auditoria  |
| Dados  | Tabela de auditoria; consulta de apuração; revisão de índices                            |
| Front  | Painel da mesa ao vivo; página de resultado com gráfico (Chart.js); exportação           |
| Testes | Apuração (unitários), resultado bloqueado/liberado, auditoria sem dado de voto           |
| QA     | Conferir resultado do sistema contra contagem manual de uma simulação                    |

## Sprint 4 — Produção (H12)

| Perfil | Entrega                                                                                  |
| ------ | ---------------------------------------------------------------------------------------- |
| Back   | Deploy na Hostinger (`DEPLOY_HOSTINGER.md`), `.env` de produção, script de deploy         |
| Dados  | Migrations em produção; backup do banco antes e depois da eleição                        |
| Front  | Ajustes de responsividade e acessibilidade (contraste, teclado na urna)                  |
| Testes | Smoke test em produção; CI bloqueando merge sem teste                                    |
| QA     | Simulação de eleição com a turma; checklist do dia; apresentação final                   |

## Ordem de dependência

```
H1 login ─┬─▶ H2 eleição ─▶ H3 chapas ─┐
          ├─▶ H5 terminais ────────────┼─▶ H6 liberar ─▶ H7 urna ─▶ H8 votar ─▶ H10 resultado
          └─▶ H4 eleitores ────────────┘                              └─▶ H9 painel da mesa
H11 auditoria: começa após H1 e cresce junto
H12 deploy: após H8 (publicar antes, como teste, é bem-vindo)
```

## Riscos

| Risco                                     | Resposta                                                            |
| ----------------------------------------- | ------------------------------------------------------------------- |
| Equipe travar na instalação               | Sprint 0 inteira para isso; par com quem já conseguiu               |
| Hostinger sem SSH no plano                | Confirmar na Sprint 0; alternativa por FTP em `DEPLOY_HOSTINGER.md` |
| Migration nova quebrar o sigilo do voto   | `ConstraintsTest` roda no CI                                        |
