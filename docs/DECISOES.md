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
- **Não expor IDs previsíveis** em URLs e APIs: não dá para "chutar" o próximo registro. (A urna deixou de ter o ID do terminal na URL: ela identifica o terminal por um cookie de pareamento, D16.)
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

**Na implementação da H13 (11/11/2026):**

- **Título único na eleição**, sem diferenciar maiúsculas ("Presidente" e "presidente" são o mesmo cargo): validado na aplicação e garantido por `UNIQUE (eleicao_id, titulo)`. O mesmo título pode existir em outra eleição.
- **A ordem fica sempre 1, 2, 3... sem buracos.** Novo cargo entra no fim; as setas trocam com o vizinho; remover sobe os de baixo. A troca passa pela ordem 0 (nunca usada) para não esbarrar no `UNIQUE (eleicao_id, ordem)`.
- **Copiar de outra eleição só com a lista vazia.** Misturar com cargos já digitados criaria títulos repetidos e uma ordem difícil de prever.
- **Congelamento sem corrida:** toda alteração de cargos trava a linha da eleição (`lockForUpdate`) e confere, dentro da transação, se já há chapa inscrita (`App\Support\CargosDaEleicao`). **A inscrição (H14, H15) precisa travar a mesma linha** antes de gravar a chapa; assim uma chapa nunca é gravada contra uma lista de cargos que está mudando.

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

## D16 — Terminal pareado: a urna só funciona no computador ativado

Registrada em 06/11/2026, antes de implementar H5 a H8.

**Problema:** a urna (`/urna/{uuid}`) e a API de status não exigem login, porque o eleitor não faz login (D4). O UUID impede que alguém **adivinhe** o endereço (D11), mas não impede que ele **vaze**: numa foto da tela, no histórico do navegador ou em um print do grupo da turma. Com o link em mãos, qualquer pessoa poderia:

- abrir a urna no próprio celular e votar no lugar do eleitor que o mesário acabou de liberar para aquele terminal;
- consultar o status e ver o nome de quem está votando.

**Decisão:** cada terminal é **pareado** com um único computador antes da votação. O pareamento grava um cookie nesse navegador, e sem o cookie a urna não funciona.

1. No computador da urna, um admin ou mesário faz login e abre `/terminal/ativar`. A tela lista os terminais cadastrados; ele escolhe "este computador é o terminal 3".
2. O servidor gera um token aleatório (`Str::random(64)`), grava **só o hash** (SHA-256) em `terminais.token_hash` com a data em `terminais.ativado_em`, e envia o token num cookie `terminal` criptografado, `HttpOnly`, `Secure`, `SameSite=Strict`, válido por 24 horas.
3. Na mesma resposta, o servidor **faz logout** do usuário nesse navegador e redireciona para `/urna`. Assim o eleitor nunca encontra uma sessão de mesário aberta na urna.
4. A urna passa a usar rotas **sem ID na URL**, e o terminal sai do cookie: `GET /urna` (tela), `GET /urna/status` (polling) e `POST /urna/votar`. Um middleware `terminal` confere o hash do cookie com `terminais.token_hash` e coloca o terminal na requisição. Sem cookie, ou com cookie que não bate, a resposta é 403 com a tela "Este computador não está ativado como terminal. Chame o mesário."
5. Ativar o mesmo terminal em outro computador substitui o hash: o computador anterior perde o acesso na próxima consulta. O admin também pode **desativar** um terminal (apaga o hash).
6. O mesário só consegue liberar eleitor para terminal **ativado**. Terminal sem pareamento aparece como **inativo** no painel (estado já previsto em `IDENTIDADE_VISUAL.md`).
7. Ativação e desativação de terminal vão para a auditoria (H11), com quem fez, quando e o IP.

**Por quê assim:**

- **Hash no banco, token no cookie:** quem ler o banco (backup, DBeaver) não consegue se passar por um terminal. É o mesmo princípio da senha.
- **Sem ID na URL:** se o computador é quem diz qual terminal ele é, não sobra link para vazar. Isso substitui o `/urna/{terminal}` e o `/api/terminais/{terminal}/status` previstos em D11.
- **Rotas no grupo `web`, não em `routes/api.php`:** o grupo `api` do Laravel não descriptografa cookies nem verifica CSRF. A urna é uma página do próprio sistema, então usa o grupo `web` com CSRF no voto.
- **24 horas:** cobre um dia de votação. Se a votação durar mais de um dia, o mesário reativa os terminais na abertura de cada dia, o que também serve de checagem de que cada computador está no lugar certo.
- **Sem software nos terminais:** continua bastando um navegador em modo quiosque (README, objetivo 4).

**Na implementação (H5, 11/11/2026):**

- **As 24 horas valem também no servidor.** O middleware só aceita o token se `ativado_em` tiver menos de 24 horas. Um cookie copiado não dura mais que o pareamento, mesmo que alguém altere a validade dele no navegador.
- **`Secure` segue `SESSION_SECURE_COOKIE`.** Em produção (`.env.production.example`) é `true`. No desenvolvimento local em `http://` o cookie sai sem `Secure`; senão o navegador o descartaria e a urna nunca funcionaria no XAMPP.
- **`/api/terminais/{uuid}/status` saiu.** O status agora é `GET /urna/status`, que exige o cookie. `/api/health` continua público para o smoke test.
- **O estado do terminal** (`Terminal::estado()`) hoje é *inativo* ou *livre*. *Liberado* e *votando* entram com a situação da sessão (H6, H7).

**O que isto não resolve:** quem tem acesso físico ao computador pode tentar copiar o cookie pelas ferramentas do navegador. O modo quiosque (sem barra de endereço nem F12) e o mesário olhando a mesa são a defesa. `HttpOnly` impede que um script da página leia o cookie.

**Alternativas descartadas:**

- *Restringir por IP:* na rede da faculdade os computadores costumam sair pelo mesmo IP público (NAT), e o IP muda entre laboratórios.
- *Mesário logado na própria urna:* o eleitor ficaria a um clique do painel do mesário.
- *Código digitado na urna a cada eleitor:* mais seguro contra cookie copiado, mas atrasa a fila e cria mais um ponto de erro no dia. Pode virar melhoria futura.

## D17 — Eleitorado único, apto em toda eleição

Registrada em 11/11/2026, na implementação da H4.

**Decisão:** `eleitores` é **um cadastro só de alunos**, com `matricula`, `nome`, `turma` (opcional) e `email` (opcional). Todo aluno cadastrado é apto a votar em **qualquer** eleição. Não há tabela `eleicao_eleitor`.

**Por quê:** a eleição estudantil é de todos os alunos do curso, e a lista muda pouco de um semestre para outro. Uma tabela de aptos por eleição obrigaria a reimportar ou copiar o eleitorado a cada eleição, sem ganho para o caso real. O voto duplo já é barrado por `sessoes_votacao UNIQUE (eleicao_id, eleitor_id)`, e o candidato continua precisando estar cadastrado (`candidatos.eleitor_id`).

- **O CSV atualiza, não duplica:** matrícula que já existe atualiza o nome (e a turma e o e-mail, se vierem preenchidos). Assim a comissão pode reimportar a lista do semestre sem apagar nada.
- **Linha a linha, não tudo ou nada:** diferente das chapas (D14), uma linha de aluno com erro não invalida as outras. O relatório mostra a linha e o motivo para corrigir e importar de novo.
- **Excluir é exceção:** eleitor que já foi liberado (está na ata) ou é candidato não pode ser excluído. O banco bloqueia (RESTRICT) e a tela explica o motivo.

**Se mudar:** se um dia houver eleições para públicos diferentes (ex.: só uma turma), crie `eleicao_eleitor` e passe a checar a aptidão na liberação (H6). A migration nova não precisa mexer em `eleitores`.

## D18 — Inscrição pública: regras num lugar só, contato e nome da chapa

Registrada em 11/11/2026, na implementação da H14.

**Decisão:** as regras da inscrição ficam em **uma classe**, `App\Support\InscricaoDeChapa`, usada pelo formulário público (H14) e pelo CSV da comissão (H15). Ela recebe a eleição, o nome da chapa, a matrícula de cada cargo e o contato, e grava tudo ou nada. Quando recusa, devolve uma mensagem por campo (`geral`, `nome`, `candidatos.{cargo_id}`): o formulário mostra cada uma no campo dela, e o CSV pode traduzir para a linha do arquivo.

- **Período conferido no envio, dentro da transação.** A transação trava a linha da eleição (a mesma trava da tela de cargos, D13) e só então confere a fase. Uma página aberta às 17:59 e enviada às 18:01 é recusada.
- **Número da chapa:** o próximo livre (`max + 1`). Com a trava, duas inscrições ao mesmo tempo esperam uma pela outra. Se o banco ainda assim recusar por `UNIQUE`, a inscrição tenta de novo (até 3 vezes); na nova volta, a validação enxerga o que a outra gravou.
- **Contato do responsável:** e-mail obrigatório e telefone opcional. Não aparecem em nenhuma página pública, nem no comprovante.
- **Nome da chapa único na eleição** (sem diferenciar maiúsculas), com `UNIQUE (eleicao_id, nome)`. Duas chapas com o mesmo nome confundiriam o eleitor na urna.
- **Comprovante só na sessão de quem inscreveu.** O id da chapa fica na sessão HTTP do navegador, não na URL: não existe link público que mostre a composição de uma chapa pendente.
- **Conferência do nome pela matrícula** (`GET /inscricao/aluno`): devolve só o nome, só com as inscrições abertas, e no máximo 30 consultas por minuto por IP. Sem isso, a página permitiria varrer o cadastro de alunos.
- **Limite de envios:** 5 por minuto por IP (`throttle:inscricao`). Os envios com erro também contam, porque o objetivo é barrar quem manda inscrições em massa.

**Fora desta história:** o IP de quem inscreveu vai para a auditoria (H11); a lista e a homologação das chapas são a H3.

## D19 — Importação de chapas: conferir antes de gravar, cabeçalho cria cargos, equipe não concorre

Registrada em 11/11/2026, na implementação da H15.

**Decisão:** o CSV de chapas é gravado em **duas etapas**. Enviar só confere (`App\Support\ImportadorChapas::analisar`) e guarda o arquivo em `storage/app/private/importacoes`; a tela mostra cada campo com erro. O admin escolhe **cancelar** (rollback: nada foi gravado e o arquivo é descartado), **ignorar as chapas com erro** ou **confirmar**. A confirmação (`importar`) roda numa transação só, com a eleição travada, e confere de novo: se a assinatura da análise mudou (uma chapa se inscreveu pelo formulário, um aluno foi excluído, um cargo mudou), **desfaz tudo** e mostra a conferência atualizada.

- **Uma linha por chapa, uma coluna por cargo** (e não uma linha por candidato, como no rascunho anterior). A planilha que a comissão recebe já costuma ser assim, e uma chapa incompleta aparece como célula vazia, fácil de ver.
- **O cabeçalho cria os cargos** quando a eleição ainda não tem nenhum. Se já tem, as colunas são conferidas com eles: a importação não renomeia nem acrescenta cargo de eleição que já tem (isso é a tela da H13, com as travas dela).
- **Nomes comparados por chave** (sem acento, maiúsculas, espaços e pontuação) e **padronizados** quando vêm todos em maiúsculas ou todos em minúsculas. Texto que já mistura os dois ("DCE Livre") é respeitado. Cada ajuste aparece na conferência, para o admin ver antes de confirmar.
- **Mesmas regras da H14**: o importador usa `InscricaoDeChapa::conferir`, `travar` e `criar`. O número da chapa vem do arquivo (no formulário, é o próximo livre).
- **Aluno repetido entre chapas do arquivo marca as duas.** Escolher "a primeira linha ganha" seria arbitrário; a comissão decide.
- **Quem trabalha na eleição não concorre.** `users.matricula` (opcional, única, precisa existir em `eleitores`) liga o mesário ou admin que é aluno ao cadastro dele. A inscrição (formulário e CSV) recusa esse aluno como candidato, e o cadastro de usuário recusa matrícula de quem já é candidato na eleição atual. Mesário desativado continua contando: ele pode ser reativado.
- **Integridade da matrícula:** formato de `EleitorRegras::MATRICULA`, espaços no meio removidos ("2024 101"), e aviso próprio para notação científica do Excel ("2,02E+06").
- **Limites:** arquivo de até 1 MB, 500 chapas e 20 cargos.

**Por quê:** importar direto (como o CSV de eleitores, H4) grava metade do arquivo quando a outra metade tem erro, e a comissão não tem como desfazer. Conferir primeiro deixa o admin decidir com tudo à vista, e a transação única garante que o que ele viu é o que foi gravado.

**Estendida ao CSV de eleitores (H4), em 11/11/2026.** O importador de eleitores, que gravava as linhas válidas na hora, passou para o mesmo fluxo: enviar → conferir → cancelar, ignorar as linhas com erro ou confirmar, com transação única e assinatura. A conferência mostra também o que **vai mudar** em quem já está cadastrado (antes → depois). Matrícula repetida no arquivo, que antes valia pela última linha, agora marca todas as linhas. As peças são compartilhadas: `LeitorCsv`, `Nomes` (com `siglas: false` para nome de pessoa), `ArquivoEmConferencia` (arquivo guardado até a decisão) e o componente `x-decisao-importacao`. **Toda importação nova segue este fluxo** (skill `.claude/skills/importacao-csv`).

## D20 — Homologação: decisão reversível até a votação, urna só com homologadas

Registrada em 11/11/2026, na implementação da H3.

**Decisão:** as regras ficam em `App\Support\HomologacaoDeChapa`, que trava a linha da eleição como a inscrição e os cargos (D13) e confere a fase dentro da transação.

- **Transições:** `pendente` → `homologada` ou `indeferida` (com motivo de pelo menos 10 caracteres). Para mudar uma decisão, a comissão **reabre a análise** (volta para `pendente`) e decide de novo. Assim, cada decisão é um ato explícito, e não um clique que troca uma situação pela outra.
- **Até quando:** a situação muda durante as inscrições e a homologação, e para quando a **votação abre**. Depois disso, a urna já mostra as chapas, e mudar o que o eleitor vê no meio da votação seria injusto.
- **Composição:** o admin troca um candidato só com as inscrições abertas (D14), pelas regras da H14 (`InscricaoDeChapa::conferir` com `propria`, para a chapa não conflitar consigo mesma). Trocar o candidato de uma chapa **homologada** a devolve para `pendente`: a comissão homologou outra composição.
- **Urna e apuração:** `Eleicao::chapasNaUrna()` (só homologadas, pelo número) é a única consulta para listar chapas na urna (H7), validar o voto (H8) e apurar (H10). A página inicial pública também passou a mostrar só homologadas.
- **Votação sem chapa homologada:** a H3 avisa no painel, na lista de eleições e na lista de chapas. O bloqueio fica na liberação do eleitor (H6) e na urna (H7), que devem recusar abrir sem chapas.
- **Excluir:** só até a votação abrir. Com votos, o banco recusa (`votos.chapa_id` RESTRICT) e a tela explica. Para recusar uma inscrição, o caminho é indeferir, porque o motivo fica registrado.
- **Quem decidiu:** `analisada_por_id` (nullable, `nullOnDelete`) e `analisada_em` guardam a última decisão. O histórico (homologou, reabriu, indeferiu) é da auditoria (H11).
- **Confirmação sem JavaScript:** cada ação abre um painel (`<details>`) com o botão "Confirmar". Um clique sozinho não decide nada.

## D21 — Sessão de votação: expiração calculada, travas e o mesário fora do próprio voto

Registrada em 11/11/2026, na implementação da H6.

**Decisão:** as regras ficam em `App\Support\LiberacaoDeEleitor`, usada pela mesa (`MesaController`).

- **Situação gravada:** `aberta`, `votou` ou `cancelada`. **Expirada é calculada**: uma sessão `aberta` com `expira_em` no passado. Sem tarefa agendada (cron) para "expirar" sessões, não há janela em que o banco diga "aberta" e o relógio diga "vencida". A urna (H7) e o voto (H8) usam o mesmo critério (`emAndamento`).
- **Uma linha por eleitor e eleição:** a re-liberação (expirada ou cancelada) faz `UPDATE` na mesma linha. `votou` é final.
- **Travas:** a liberação trava a linha do **terminal** (`lockForUpdate`), então dois mesários não liberam o mesmo terminal ao mesmo tempo. Trava também a **sessão do eleitor**, se existir, para o mesmo eleitor não ir para dois terminais. Na primeira liberação simultânea do mesmo eleitor, o `UNIQUE (eleicao_id, eleitor_id)` decide, e a tela explica.
- **Cancelar é atômico:** `UPDATE ... WHERE situacao = 'aberta'`. Se o eleitor votou no mesmo instante, o cancelamento não acontece.
- **Bloqueios antes de liberar:** votação aberta pelas datas, ao menos uma chapa homologada (D20), terminal ativado (D16) e livre, eleitor sem voto e sem liberação em andamento.
- **O mesário não libera o próprio voto:** se `users.matricula` (D19) é a matrícula digitada, outro mesário precisa liberar.
- **Conferir antes de liberar:** a busca (`GET /mesario?matricula=`) só mostra nome, turma e situação. Liberar é um `POST` separado, com o terminal escolhido.
- **Painel:** atualizar é manual por enquanto. A atualização automática é a H9, e o estado "votando" vem da urna (H7).
- **Fora desta história:** a auditoria de cada liberação e cancelamento (H11).

## D22 — Auditoria: eventos com intenção, na transação da ação, nunca o voto

Registrada em 11/11/2026. Plano completo em `docs/AUDITORIA.md`. Implementação na H11, em fases. **A fase 1 está implementada** (acesso, terminais, sessões, chapas).

**Decisão:**
- **Uma tabela só-acrescenta (`auditoria`)**, gravada por um único ponto (`App\Support\Auditoria::registrar`), com um catálogo fechado de ações (`App\Enums\AcaoAuditoria`).
- **Cada classe de regra registra explicitamente a ação que executou** (`chapa.homologada`, `sessao.reliberada`), na **mesma transação**. Observers automáticos de model ficam de fora.
- **Tentativas recusadas** (login errado, liberação barrada) são registradas fora da transação desfeita.

**Por quê:**
- **Na mesma transação**, não existe evento de algo que foi desfeito, nem ação sem evento.
- **Observers** registrariam "chapa alterada" em vez de "chapa homologada", seriam disparados pelo seed e não saberiam o motivo do indeferimento.
- **O voto não gera evento** (só `sessao.votou`, sem a chapa), porque a ordem dos eventos permitiria casar sessão e voto (D3).

**Ordem:**
- **A fase 1** (acesso, terminais, sessões e chapas) entra **antes da H8**, para o voto já nascer registrando `sessao.votou`.
- **O que já aconteceu sem auditoria não é reconstruído.** Exceção: a última decisão de cada chapa, que já está em `analisada_por_id` e `analisada_em` (D20).

**Alternativas descartadas:**
- *Pacote pronto (ex.: `spatie/laravel-activitylog`)*: é baseado em observers, e para o sigilo do voto e para a intenção dos eventos o controle explícito é melhor. Também é uma dependência a menos para a turma entender.
- *Só o `laravel.log`*: texto solto, sem filtro, misturado com erros. Também corre o risco de receber o corpo do `POST /urna/votar`.

## D23 — Urna montada no servidor, polling só para saber quando recarregar

Registrada em 11/11/2026, na implementação da H7.

**Decisão:** `/urna` devolve a tela pronta, "aguardando" ou a cédula, montada pelo Blade com os dados da sessão em andamento deste terminal. Um script pequeno consulta `/urna/status` a cada 3 segundos. O status traz só `liberada` e uma `versao`: um resumo do id da sessão e da hora da liberação, que não revela nada do eleitor. Quando a versão muda em relação à que está na tela, o script **recarrega a página**. Com resposta 403, também recarrega e mostra "não ativado".

**Por quê:**
- **Nada de dado pessoal no polling.** O status nunca leva nome nem matrícula: a cédula com o nome só sai na resposta de `/urna`, que passa pelo mesmo middleware do cookie (D16).
- **Uma fonte de verdade.** As mesmas regras decidem o que a tela mostra, tanto na primeira carga quanto depois da mudança: sessão em andamento (D21), eleição ativa, votação aberta e chapas homologadas (D20). Não existe uma segunda versão das regras em JavaScript.
- **A versão, e não só o "liberada".** Se o mesário cancelar e liberar outra pessoa no mesmo terminal entre duas consultas, `liberada` continua verdadeiro, mas a versão muda e a tela troca de eleitor.
- **"Votando".** Ao montar a cédula, a sessão grava `exibida_em`, uma vez só. É o que separa "liberado, o eleitor ainda não chegou" de "a cédula está na tela", no painel da mesa.
- **Sem framework de front:** combina com D2 (sem Node), e o script cabe num arquivo parcial (`urna/_monitor.blade.php`). Na vitrine `/estilo`, ele não roda.

## D24 — Voto: confirmar sem guardar, versão da liberação, branco diferente de nulo

Registrada em 11/11/2026, na implementação da H8.

**Decisão:**
- **Três passos, dois POSTs.** Cédula → `POST /urna/confirmar` → `POST /urna/votar` → "Voto registrado".
  - A confirmação só mostra a escolha num formulário com campos ocultos (`escolha` e `versao`). Ela não grava nada: nem banco, nem sessão HTTP, nem flash.
  - A escolha nunca vai na URL, porque URLs vão para o log de acesso do servidor (Apache/Hostinger) com horário e IP.
- **Sem `validate()` no voto:** um erro de validação guardaria a entrada (a chapa) na sessão HTTP com `old()`. Escolha inválida vira `VotoRecusado`, com uma mensagem que não cita a escolha.
- **Ordem da transação** (`App\Support\RegistroDeVoto`):
  1. localizar a sessão **deste terminal** (cookie) cuja **versão** é a da tela (D23);
  2. conferir a opção na cédula (`chapasNaUrna`, branco ou nulo);
  3. **reivindicar** a sessão (`UPDATE ... WHERE situacao = 'aberta' AND expira_em > agora`);
  4. inserir o voto com a eleição **da sessão**;
  5. auditar `sessao.votou` sem a chapa (D22).

  Qualquer falha desfaz tudo.
- **Duplo envio:** se a sessão daquela versão já votou, a resposta é "Voto registrado" de novo, sem segundo voto e sem trocar o primeiro. Assim, um duplo clique não mostra ao eleitor um erro sobre um voto que deu certo.
- **Versão da liberação:** se o mesário cancela a sessão da Maria e libera o João no mesmo terminal, uma tela de confirmação da Maria esquecida aberta não vota pelo João.
- **Branco e nulo:** `votos.tipo` é `chapa` (com `chapa_id`), `branco` ou `nulo` (sem chapa). Branco e nulo contam diferente na apuração (H10). No MySQL e MariaDB, um CHECK garante a combinação. No SQLite dos testes, a regra fica na aplicação.
- **"Voto registrado"** volta para "aguardando" em 5 segundos, por `<meta http-equiv="refresh">`, que não depende de JavaScript.

