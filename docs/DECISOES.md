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

**Por quê:** o modelo antigo tinha `votos.eleitor_id`, o que permitia a qualquer pessoa com acesso ao banco saber o voto de cada aluno. Em eleição isso é inaceitável, mesmo em projeto acadêmico. O teste `SigiloDoVotoTest` falha se alguém adicionar essas colunas.

**Limitação conhecida:** `votos.registrado_em` e `sessoes_votacao.votou_em` podem ser correlacionados por horário se houver poucos votantes. Mitigação simples para a Sprint 3: gravar `registrado_em` truncado ao minuto, ou só a data.

## D4 — Eleitores não são usuários

**Decisão:** `users` guarda só admin e mesários (com `perfil`). Eleitores ficam em `eleitores`, sem senha, e são identificados pela matrícula pelo mesário.

**Por quê:** o eleitor nunca faz login: ele é liberado presencialmente pelo mesário. Misturar os dois em uma tabela `alunos` com `tipo` (como no modelo antigo) obrigava a ter `senha_hash` nulo para a maioria e confundia autenticação com cadastro eleitoral.

## D5 — Uma eleição por vez, mas várias no histórico

**Decisão:** tudo (chapas, sessões, votos) tem `eleicao_id`. Só uma eleição tem `ativa = true`.

**Por quê:** o modelo antigo não tinha `eleicao_id` em `chapas` e `votos`, então o sistema servia para uma única eleição e depois precisava ser zerado. Com `eleicao_id`, o próximo semestre é só cadastrar outra eleição.

## D6 — Figma antes do código de tela

**Decisão:** cada tela tem um protótipo no Figma aprovado antes da história entrar em desenvolvimento. Detalhes em `FIGMA.md`.

## D7 — Git Flow com PR obrigatório e CI

Detalhes em `GIT_FLOW.md`. Resumo: `main` (produção), `develop` (integração), `feature/*` por história, PR com CI verde e uma aprovação.

## D8 — Hostinger compartilhada como produção

Detalhes em `DEPLOY_HOSTINGER.md`. Consequências: sem workers de fila (`QUEUE_CONNECTION=sync`), cache em arquivo, agendador via cron do hPanel.

## D9 — Trello como gestão

Cartões = histórias de `HISTORIAS_USUARIO.md`. Colunas sugeridas: **Backlog → Sprint atual → Em desenvolvimento → Em revisão (PR aberto) → Concluído**. Todo cartão em "Em desenvolvimento" tem uma branch; todo cartão em "Em revisão" tem um PR.
