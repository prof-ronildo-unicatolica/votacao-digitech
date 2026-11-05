# Decisões de arquitetura

Registro das decisões tomadas em 09/09/2026 (D13 a D15 em 05/11/2026), com o porquê. Mudar uma decisão é permitido, mas exige atualizar este arquivo.

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

**Ordem e horário também identificam:** se `votos` tivesse `id` sequencial ou `created_at`, bastaria ordenar as duas tabelas e emparelhar linha a linha. Por isso `votos` **não tem timestamps** e o `id` é **UUID v4 aleatório** (D11). Não adicione coluna de horário em `votos`; se precisar, grave só a data.

## D4 — Eleitores não são usuários

**Decisão:** `users` guarda só quem faz login (admin e mesários). Eleitores ficam em `eleitores`, sem senha, e são identificados pela matrícula pelo mesário. Como distinguir admin de mesário é decisão da equipe (H1).

**Por quê:** o eleitor nunca faz login: ele é liberado presencialmente pelo mesário. Misturar os dois em uma tabela `alunos` com `tipo` (como no modelo antigo) obrigava a ter `senha_hash` nulo para a maioria e confundia autenticação com cadastro eleitoral.

## D10 — Migrations do scaffold só com chaves e constraints

**Decisão:** as tabelas do domínio nascem com PK, FKs, chaves naturais únicas (`matricula`, `numero`) e as constraints que codificam regras de negócio. Nenhum outro atributo.

**Por quê:** modelar os atributos é parte do aprendizado da equipe. O que não pode ficar a critério de cada um é a integridade: sigilo do voto, voto único, cascatas. Isso o scaffold fixa e testa (`ConstraintsTest`). A lista de decisões pendentes por tabela está em `MODELO_DADOS.md`.

## D11 — UUID como chave primária no domínio

**Decisão:** `eleicoes`, `chapas`, `eleitores`, `terminais`, `sessoes_votacao` e `votos` usam UUID (`$table->uuid('id')->primary()`, `foreignUuid(...)` e trait `HasUuids` nos models). `users` e as tabelas internas do Laravel (`sessions`, `jobs`, `cache`...) continuam com `bigint`.

**Por quê:**
- **Sigilo:** em `votos`, um `id` auto-incremento revela a ordem dos votos. O model `Voto` sobrescreve `newUniqueId()` para gerar UUID **v4 aleatório**. O padrão do `HasUuids` é ordenado por tempo, e vazaria a ordem do mesmo jeito.
- **Não expor IDs previsíveis** em URLs e APIs (`/urna/{terminal}`, `/api/terminais/{terminal}/status`): não dá para "chutar" o próximo registro.
- **Prática de mercado:** a equipe aprende desde cedo a lidar com chaves não numéricas.

**Custo:** índices maiores (`char(36)`) e IDs menos legíveis no DBeaver. Para identificar algo para humanos, use a chave natural (`chapas.numero`, `terminais.numero`, `eleitores.matricula`).

## D12 — Identidade visual com fonte única de tokens

**Decisão:** cores, fontes, espaços e raios vivem em `design/tokens/tokens.json` (formato W3C Design Tokens). O CSS (`public/css/tokens.css`) é **gerado** por `php design/tokens/gerar-css.php`; o Figma importa o mesmo arquivo pelo plugin Tokens Studio. Base: marca da UniCatólica (laranja `#F5821F`, grafite, Poppins). Guia em `IDENTIDADE_VISUAL.md`.

**Por quê:** sem fonte única, o Figma e o código divergem na primeira semana (o rascunho inicial já usava um azul `#003865` que não existe na marca). O gerador é PHP para não exigir Node (D2), e o `TokensTest` falha se o CSS for editado à mão ou se uma combinação de texto cair abaixo do contraste AA.

**Neutralidade:** o laranja da marca nunca é cor de chapa e todas as chapas têm o mesmo peso visual na urna.

## D5 — Uma eleição por vez, mas várias no histórico

**Decisão:** tudo (chapas, sessões, votos) tem `eleicao_id`. Como marcar a eleição ativa é decisão da equipe (H2).

**Por quê:** o modelo antigo não tinha `eleicao_id` em `chapas` e `votos`, então o sistema servia para uma única eleição e depois precisava ser zerado. Com `eleicao_id`, o próximo semestre é só cadastrar outra eleição.

**Resultado é histórico:** eleição ou chapa que já recebeu votos não pode ser apagada (RESTRICT em `votos`), e o voto só pode apontar para uma chapa da mesma eleição (FK composta). Apagar continua possível para eleição/chapa sem votos, como um cadastro errado. Para "tirar do ar" uma eleição encerrada, use um atributo de situação (H2), não `DELETE`.

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

## D13 — Chapa com cargos: um candidato por cargo, voto na chapa

Registrada em 05/11/2026, com a ampliação do escopo para a inscrição de chapas.

**Decisão:** cada eleição define seus **cargos** (`cargos`, ordenados por `ordem`): por exemplo Presidente, Vice-presidente, 1º Secretário, 2º Secretário e Tesoureiro. Toda chapa precisa de **um candidato por cargo** (`candidatos`), e cada candidato é um aluno identificado pela **matrícula** (`eleitores`). O eleitor continua votando **na chapa inteira**.

**Por quê:**
- **Um cargo = uma vaga.** "1º e 2º secretário" são dois cargos, não um cargo com duas vagas. Assim `UNIQUE (chapa_id, cargo_id)` garante no banco que não há dois presidentes na mesma chapa, sem coluna de "posição". A quantidade de candidatos por chapa é simplesmente o número de cargos da eleição.
- **Candidato é aluno, não texto livre.** Ligar o candidato a `eleitores` pela matrícula evita nome digitado errado, permite checar que o aluno existe e `UNIQUE (eleicao_id, eleitor_id)` impede o mesmo aluno em duas chapas.
- **Cargos por eleição, não globais.** Colegiados diferentes têm estruturas diferentes, e a eleição do próximo semestre pode mudar a lista sem alterar o histórico.
- **Voto na chapa, não por cargo.** Mantém a cédula simples e a apuração (`votos`) intacta. "Mais de um cargo por cédula" (voto separado para presidente, vice...) continua fora do escopo.

**Integridade (no banco, testada em `ConstraintsTest`):** FKs compostas `(eleicao_id, chapa_id)` e `(eleicao_id, cargo_id)`, iguais à de `votos`: chapa, cargo e candidato são da mesma eleição. Aluno candidato não pode ser apagado (RESTRICT).

**Seguindo D10:** as novas tabelas nascem só com chaves e constraints; o título do cargo e os demais atributos são da equipe (H13, H14).

## D14 — Inscrição de chapas com período, formulário público, CSV e homologação

**Decisão:** cada eleição tem um **período de inscrição** de chapas, anterior ao período de votação. Durante ele, a chapa se inscreve por um **formulário público** (sem login), e a comissão também pode **importar um CSV**. Toda inscrição entra **pendente**; a comissão (admin) **homologa** ou **indefere** com motivo, e só chapas homologadas vão para a urna.

**Por quê:**
- **Formulário público, sem login:** coerente com D4 (aluno não é usuário). Quem preenche é um representante da chapa; o sistema confere as matrículas, não a identidade de quem digita. A **homologação** pela comissão é a barreira contra inscrição falsa ou com aluno que não autorizou, como no processo em papel.
- **CSV para a comissão:** inscrições recebidas por outro canal (papel, e-mail) entram sem digitação manual, pelas mesmas regras do formulário e também como pendentes, para haver um só fluxo de homologação.
- **Período fechado:** depois do fim das inscrições a composição das chapas congela. Isso evita troca de candidato às vésperas da votação e dá uma data clara para a comissão analisar.
- **Tudo ou nada por chapa:** chapa incompleta não existe. O formulário e o CSV gravam a chapa e todos os candidatos na mesma transação.

**Consequência no cronograma:** as inscrições acontecem **antes** da votação, então formulário, CSV e homologação (H13, H14, H15, H3) precisam estar em produção primeiro. O roadmap foi reordenado para isso.

**Riscos e respostas:** spam no formulário público → `throttle` e homologação; aluno inscrito sem saber → a comissão pode exigir confirmação (decisão da equipe na H14, ex.: e-mail de confirmação para cada candidato); dados pessoais em página pública → a página pública só confirma o recebimento, sem listar nomes de outras chapas antes da homologação.

## D15 — Perfil em coluna e "só uma eleição ativa" no banco

Registrada em 05/11/2026, na implementação de H1 e H2.

**Perfil:** `users.perfil` é uma coluna de texto com dois valores, `admin` e `mesario` (enum `App\Enums\Perfil`), e não uma tabela `perfis` nem um pacote de permissões. São só dois perfis, fixos, e a regra de acesso é uma linha por rota (middleware `perfil:admin`). Se um dia houver permissões finas (ex.: "mesário que também importa eleitores"), aí vale um pacote como `spatie/laravel-permission`.

- **Admin também acessa `/mesario`**, para acompanhar a mesa. Mesário em `/admin` recebe 403.
- **Desativar, não apagar:** `users.ativo = false` impede o login e derruba quem já estava logado na próxima requisição (middleware `ativo`). Quem já liberou sessões faz parte da ata e não pode ser apagado (RESTRICT).
- **O admin não se tranca para fora:** não pode desativar, excluir nem rebaixar a si mesmo. Por isso sempre sobra pelo menos um admin ativo.
- **Primeiro admin em produção:** `php artisan digitech:admin`, porque o seed não roda em produção. A senha é perguntada no terminal, nunca passada como opção, para não ficar no histórico.
- **Tentativas de login:** 5 erradas por minuto para o mesmo e-mail + IP bloqueiam por 1 minuto. Só os erros contam (RateLimiter no `LoginRequest`), então quem acerta não é penalizado.

**Só uma eleição ativa, garantido pelo banco:** `eleicoes.ativa` é `TRUE` ou `NULL`, nunca `FALSE`, e tem índice `UNIQUE`. MySQL, MariaDB e SQLite aceitam vários `NULL` num índice único, mas só um `TRUE`. O model `Eleicao` converte `false` em `NULL` ao gravar. Ativar uma eleição desativa a anterior na mesma transação; se duas ativações acontecerem ao mesmo tempo, o banco rejeita uma em vez de deixar duas eleições ativas.

**Fase calculada, não gravada:** a fase (aguardando inscrições, inscrições abertas, em homologação, votação aberta, encerrada) sai das quatro datas (`App\Enums\FaseEleicao::em`, função pura com testes unitários). Gravar a fase numa coluna exigiria um agendador para trocá-la na hora certa, e a hospedagem compartilhada só tem cron de minuto em minuto (D8).
