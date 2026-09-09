# Decisões de arquitetura

Registro das decisões tomadas em 09/09/2026, com o porquê. Mudar uma decisão é permitido, mas exige atualizar este arquivo.

## D1 — Um único repositório

**Decisão:** o projeto vive em `votacao-digitech`. O repositório `mesario-digitech` foi arquivado.

**Por quê:** os dois repositórios descreviam o mesmo sistema (painel do mesário, urna, admin, apuração) com o mesmo banco. O "mesário" é um **perfil de usuário** e uma **tela**, não um sistema. Dois repositórios com banco compartilhado geram migrações conflitantes, deploy duplo e dúvida sobre onde cada coisa mora. Um repositório, uma pasta `mesario/` de rotas e views.

Quando separar faria sentido: só se a urna precisasse rodar offline em outro dispositivo (app nativo). Não é o caso.

## D2 — Laravel 12 como framework

**Decisão:** Laravel 12 com Blade + Bootstrap 5. Sem Node no scaffold.

**Por quê Laravel:** o que o plano antigo mandava a equipe escrever à mão (parser de `.env`, classe `Csrf`, `Validador`, `AutenticacaoMesario`, `RateLimiter`, `AuditLog`, conexão PDO, paginação) o Laravel já traz pronto e testado: `.env`, middleware CSRF, Form Requests, `Auth`, `throttle`, migrations, Eloquent, paginação. Isso removeu cerca de metade do plano de implementação e tira o risco de a equipe reinventar segurança.

**Por quê não algo "mais na moda":** a moda em PHP é o próprio Laravel (com Livewire ou Inertia). Node/Next.js exigiria VPS na Hostinger e um time operando servidor. Symfony é mais verboso para iniciantes. Para o requisito "hospedagem compartilhada Hostinger" com equipe de alunos, Laravel é a escolha com menos atrito e mais material de estudo em português.

**Por quê Laravel 12 e não 13:** o Laravel 13 exige PHP 8.3, e o XAMPP mais recente (8.2.12) traz PHP 8.2. O Laravel 12 recebe correções de segurança até fevereiro de 2027. Quando o XAMPP atualizar (ou a equipe migrar para Laravel Herd/LAMP com PHP 8.3), a atualização para o 13 é uma troca de versão no `composer.json`.

**Evolução opcional:** Livewire 3 para as telas interativas (urna com polling, painel do mesário) sem escrever JavaScript. Decidir na Sprint 2, depois que a equipe dominar Blade.

## D3 — Sigilo do voto no modelo de dados

**Decisão:** a tabela `votos` **não** tem `eleitor_id`, `terminal_id` nem `sessao_id`. "Quem votou" fica em `sessoes_votacao` (com UNIQUE por eleitor e eleição); "em quem votou" fica em `votos`, sem ligação.

**Por quê:** o modelo antigo tinha `votos.eleitor_id`, o que permitia a qualquer pessoa com acesso ao banco saber o voto de cada aluno. Em eleição isso é inaceitável, mesmo em projeto acadêmico. O teste `ConstraintsTest` falha se alguém adicionar essas colunas.

**Atenção ao adicionar atributos:** um horário exato de voto em `votos` pode ser correlacionado com o horário da sessão se houver poucos votantes. Mitigação: gravar truncado ao minuto, ou só a data.

## D4 — Eleitores não são usuários

**Decisão:** `users` guarda só quem faz login (admin e mesários). Eleitores ficam em `eleitores`, sem senha, e são identificados pela matrícula pelo mesário. Como distinguir admin de mesário é decisão da equipe (H1).

**Por quê:** o eleitor nunca faz login: ele é liberado presencialmente pelo mesário. Misturar os dois em uma tabela `alunos` com `tipo` (como no modelo antigo) obrigava a ter `senha_hash` nulo para a maioria e confundia autenticação com cadastro eleitoral.

## D10 — Migrations do scaffold só com chaves e constraints

**Decisão:** as tabelas do domínio nascem com PK, FKs, chaves naturais únicas (`matricula`, `numero`) e as constraints que codificam regras de negócio. Nenhum outro atributo.

**Por quê:** modelar os atributos é parte do aprendizado da equipe. O que não pode ficar a critério de cada um é a integridade: sigilo do voto, voto único, cascatas. Isso o scaffold fixa e testa (`ConstraintsTest`). A lista de decisões pendentes por tabela está em `MODELO_DADOS.md`.

## D5 — Uma eleição por vez, mas várias no histórico

**Decisão:** tudo (chapas, sessões, votos) tem `eleicao_id`. Como marcar a eleição ativa é decisão da equipe (H2).

**Por quê:** o modelo antigo não tinha `eleicao_id` em `chapas` e `votos`, então o sistema servia para uma única eleição e depois precisava ser zerado. Com `eleicao_id`, o próximo semestre é só cadastrar outra eleição.

## D6 — Figma antes do código de tela

**Decisão:** cada tela tem um protótipo no Figma aprovado antes da história entrar em desenvolvimento. Detalhes em `FIGMA.md`.

## D7 — Git Flow com PR obrigatório e CI

Detalhes em `GIT_FLOW.md`. Resumo: `develop` (integração dos alunos, branch padrão) → `staging` (homologação) → `main` (produção). `staging` e `main` são só do professor e nasceram vazias; `feature/*` por história, PR para `develop` com CI verde e uma aprovação. As regras estão aplicadas como rulesets no GitHub, com bypass apenas para o administrador do repositório.

## D8 — Hostinger compartilhada como produção

Detalhes em `DEPLOY_HOSTINGER.md`. Consequências: sem workers de fila (`QUEUE_CONNECTION=sync`), cache em arquivo, agendador via cron do hPanel.

## D9 — GitHub Issues como gestão (não Trello)

**Decisão:** cada história de `HISTORIAS_USUARIO.md` é uma **issue** no GitHub, com etiqueta de perfil (`geral`, `front`, `back`, `dados`, `testes`, `qa`) e **milestone** da sprint. O quadro é o GitHub Projects do repositório.

**Por quê:** a issue fecha sozinha quando o PR diz `closes #12`; CI, revisão e discussão ficam no mesmo lugar; os alunos aprendem uma ferramenta só. Trello exigiria copiar link de cartão para PR e vice-versa.

**Como usar:** toda branch nasce de uma issue (`feature/12-liberar-votacao`); todo PR cita a issue; issue sem PR aberto em duas semanas volta para discussão na reunião de sprint.
