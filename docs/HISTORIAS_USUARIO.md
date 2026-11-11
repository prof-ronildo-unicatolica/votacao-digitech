# Histórias de usuário

Formato: **Como** [perfil], **quero** [ação] **para** [benefício]. Cada história é uma **issue no GitHub** com os critérios de aceite como checklist, etiqueta de perfil e milestone da sprint. Uma história = uma issue = uma branch `feature/<nº da issue>-...` = um PR com `closes #<nº>`.

## Perfis

| Perfil      | Quem é                                          | Como entra no sistema                   |
| ----------- | ----------------------------------------------- | --------------------------------------- |
| **Admin**   | Comissão eleitoral / professor                  | Login (`users.perfil = admin`) |
| **Mesário** | Aluno ou servidor que atende na mesa            | Login (`users`)                         |
| **Eleitor** | Aluno do colegiado                              | Não faz login: é liberado pelo mesário  |
| **Representante de chapa** | Aluno que inscreve a chapa (é um dos candidatos) | Não faz login: usa o formulário público de inscrição, só durante o período (D14) |

Todo aluno, eleitor ou candidato, é identificado pela **matrícula** (`eleitores`).

## Épico A — Configuração (Admin)

### H1 · Autenticar admin e mesário
Como admin ou mesário, quero entrar com e-mail e senha para acessar só o que meu perfil permite.
- [x] Tela de login em `/login`; após entrar, admin vai para `/admin` e mesário para `/mesario`.
- [x] Mesário que tenta abrir `/admin` recebe 403.
- [x] Logout encerra a sessão.
- [x] Admin pode desativar um usuário (ele não entra mais); usuário que já liberou sessões não pode ser apagado (o banco bloqueia).
- [x] 5 tentativas erradas em 1 minuto bloqueiam por 1 minuto (middleware `throttle`).
- [x] Testes: login válido, senha errada, mesário bloqueado em `/admin`.

### H2 · Cadastrar eleição
Como admin, quero criar uma eleição com título, **período de inscrição de chapas** e **período de votação** para controlar quando cada fase está aberta.
- [x] CRUD em `/admin/eleicoes`; só uma pode estar `ativa`.
- [x] Datas obrigatórias, nesta ordem: abertura das inscrições < fim das inscrições ≤ início da votação < fim da votação (validação, com mensagem dizendo qual data está fora de ordem).
- [x] A tela da eleição mostra a fase atual: *aguardando inscrições*, *inscrições abertas*, *em homologação*, *votação aberta*, *encerrada*.
- [x] Depois que a primeira chapa se inscreve, o período de inscrição só pode ser **prorrogado** (fim mais tarde), nunca antecipado.
- [x] Ativar uma eleição desativa a anterior.
- [x] Testes: criação válida; cada par de datas fora de ordem rejeitado; ativação exclusiva; fase calculada corretamente para uma data antes, durante e depois de cada período.

### H4 · Cadastrar eleitores (individual e CSV)
Como admin, quero cadastrar eleitores um a um ou importar um CSV (matrícula, nome, turma, e-mail) para montar o eleitorado sem digitação manual.
- [x] CRUD em `/admin/eleitores` com busca por matrícula/nome e paginação.
- [x] Importação de CSV mostra quantos entraram e quais linhas falharam (e por quê).
- [x] Matrícula duplicada no CSV atualiza o nome, não cria outro registro.
- [x] Eleitor que já foi liberado em alguma eleição, ou que é candidato, não pode ser excluído (o banco bloqueia; a tela explica o motivo).
- [x] O eleitorado é importado **antes** de abrir as inscrições: os candidatos são indicados pela matrícula (H14, H15).
- [x] Testes: importação com 3 linhas válidas + 1 inválida.

### H5 · Cadastrar terminais
Como admin, quero cadastrar os terminais (número e local) para saber em qual computador cada eleitor será liberado.
- [ ] CRUD em `/admin/terminais`; número único.
- [ ] Lista mostra o estado atual: inativo (não pareado), livre, liberado (com nome do eleitor) ou votando.
- [ ] **Pareamento (D16):** em `/terminal/ativar`, admin ou mesário logado no computador da urna escolhe qual terminal aquele computador é. O servidor grava o hash de um token em `terminais.token_hash` (e `ativado_em`), envia o token num cookie `terminal` (criptografado, `HttpOnly`, `Secure`, `SameSite=Strict`, 24 horas), faz logout desse navegador e redireciona para `/urna`.
- [ ] Ativar o mesmo terminal em outro computador invalida o anterior. O admin pode desativar um terminal (apaga o hash).
- [ ] Testes: ativação grava só o hash (o token não aparece no banco); reativação invalida o cookie antigo; usuário é deslogado após ativar; visitante sem login não acessa `/terminal/ativar`.

## Épico B — Inscrição de chapas

Fluxo e regras em `MODELO_DADOS.md`, seção "Inscrição de chapas". Decisões D13 e D14.

### H13 · Definir os cargos da eleição
Como admin, quero definir os cargos que toda chapa precisa preencher (ex.: Presidente, Vice-presidente, 1º Secretário, 2º Secretário, Tesoureiro) para que as inscrições tenham a mesma estrutura.
- [x] Em `/admin/eleicoes/{eleicao}/cargos`: adicionar, renomear, reordenar e remover cargos; título obrigatório e único na eleição.
- [x] A tela mostra "cada chapa terá N candidatos" (N = número de cargos).
- [x] Botão "copiar cargos de outra eleição" para não redigitar a cada semestre.
- [x] Depois que a primeira chapa se inscreve, os cargos ficam **bloqueados** (só leitura), com aviso explicando o porquê.
- [x] As inscrições não abrem sem ao menos um cargo (aviso na tela da eleição). *O formulário (H14) e o CSV (H15) também precisam recusar a inscrição sem cargos.*
- [x] Testes: criação e reordenação; título repetido rejeitado; alteração bloqueada com chapa inscrita.

### H14 · Inscrever chapa pelo formulário
Como representante de chapa, quero inscrever minha chapa pelo sistema, dentro do período de inscrição, informando o nome da chapa e a matrícula de um candidato para cada cargo.
- [ ] Página pública `/inscricao` (sem login) para a eleição ativa. Fora do período, mostra as datas de abertura e fim e **não** exibe o formulário.
- [ ] O formulário tem um campo de matrícula **por cargo**, na ordem dos cargos; ao sair do campo, mostra o nome do aluno para conferência (ou "matrícula não encontrada").
- [ ] Também pede nome da chapa e contato do responsável (e-mail/telefone: decisão da equipe).
- [ ] Rejeita, com mensagem por campo: cargo sem candidato, matrícula inexistente, a mesma matrícula em dois cargos, aluno que já está em outra chapa da eleição, envio fora do período (checado **no servidor**, no momento do envio, não só ao abrir a página).
- [ ] Grava chapa e candidatos na **mesma transação**, travando a linha da eleição (`lockForUpdate`, como em `CargosDaEleicao`, D13), com situação `pendente` e o próximo número livre; mostra um comprovante (número da chapa, data e hora, composição).
- [ ] `throttle` no envio (ex.: 5 por minuto por IP) e CSRF.
- [ ] A página pública não lista as outras chapas inscritas nem dados de alunos além do nome confirmado no próprio formulário.
- [ ] Testes: inscrição válida; antes da abertura e depois do fim rejeitadas; cargo faltando; matrícula inexistente; aluno em duas chapas; duas inscrições ao mesmo tempo recebem números diferentes.

### H15 · Importar chapas por CSV
Como admin, quero importar as chapas de um arquivo CSV para registrar inscrições recebidas por outro canal sem digitação manual.
- [ ] Em `/admin/chapas/importar`, só durante o período de inscrição; aceita o formato de `MODELO_DADOS.md` ("Formato do CSV de chapas"), com link para baixar o modelo `docs/exemplos/chapas.csv`.
- [ ] Mesmas regras da H14, validadas **por chapa, tudo ou nada**: uma linha ruim rejeita a chapa inteira; as demais chapas do arquivo entram.
- [ ] Relatório ao final: chapas importadas (número e nome) e chapas rejeitadas, com linha do arquivo e motivo (ex.: "linha 7: matrícula 2024999 não cadastrada").
- [ ] Chapas importadas entram como `pendente` e passam pela homologação (H3).
- [ ] Testes: arquivo com 2 chapas válidas + 1 com cargo faltando (entram 2); matrícula inexistente; cargo que não existe na eleição; mesmo aluno em duas chapas do arquivo; número de chapa já existente; importação fora do período.

### H3 · Homologar chapas
Como admin (comissão eleitoral), quero analisar as chapas inscritas e homologar ou indeferir cada uma para que só chapas válidas apareçam na urna.
- [ ] `/admin/chapas` lista as chapas da eleição com número, nome, situação (`pendente`, `homologada`, `indeferida`), origem (formulário/CSV) e a composição completa (cargo → nome e matrícula).
- [ ] Homologar e indeferir exigem confirmação; indeferir exige **motivo**. Uma chapa indeferida pode voltar para `pendente` até o início da votação.
- [ ] A composição não pode ser editada depois do fim das inscrições (D14); antes, o admin pode corrigir um candidato, com as mesmas validações da H14.
- [ ] Só chapas `homologadas` aparecem na urna e na apuração. A votação não pode começar sem ao menos uma chapa homologada (aviso na tela da eleição).
- [ ] Não é possível excluir chapa depois que a eleição recebeu votos (o banco bloqueia).
- [ ] Toda homologação/indeferimento vai para a auditoria (H11).
- [ ] Testes: homologar, indeferir sem motivo rejeitado, chapa pendente não aparece na urna, edição bloqueada após o fim das inscrições, exclusão bloqueada com votos.

## Épico C — Dia da votação

### H6 · Liberar votação de um eleitor
Como mesário, quero digitar a matrícula, conferir o nome e escolher o terminal para liberar o voto daquele eleitor.
- [ ] Busca por matrícula mostra nome e turma antes de confirmar.
- [ ] Bloqueia se: eleição fora do período, eleitor já votou, terminal já tem sessão aberta, terminal não está ativado (D16).
- [ ] Sessão expira em 30 minutos; mesário pode liberar de novo se expirou **ou se cancelou** (ex.: eleitor foi para o terminal errado).
- [ ] Liberar de novo **reaproveita a mesma sessão** (atualiza situação, terminal, mesário e horários): o banco só aceita uma sessão por eleitor e eleição (`UNIQUE`). Nunca reabre sessão já `votou`.
- [ ] Mesário pode cancelar uma sessão aberta que ainda não votou.
- [ ] Registra quem liberou (`mesario_id`); cada liberação, re-liberação e cancelamento vai para a auditoria (H11), já que a sessão guarda só o estado atual.
- [ ] Testes: liberação válida, eleitor já votou, terminal ocupado, terminal inativo, fora do período, re-liberação após expirar (continua 1 linha em `sessoes_votacao`).

### H7 · Urna aguarda liberação
Como eleitor, quero que a urna do terminal mostre "aguardando" até o mesário me liberar, e então mostre meu nome e as chapas.
- [ ] `/urna` consulta `/urna/status` a cada 3 segundos. Não há ID na URL: o terminal vem do cookie de pareamento (H5, D16), conferido por um middleware `terminal`. As rotas ficam no grupo `web` (o grupo `api` não lê cookies).
- [ ] Sem cookie válido, `/urna`, `/urna/status` e `/urna/votar` respondem 403 com a tela "Este computador não está ativado como terminal. Chame o mesário." O status nunca devolve o nome do eleitor para quem não é o terminal pareado.
- [ ] Quando `liberada = true`, mostra nome do eleitor e as chapas **homologadas** da eleição ativa, cada uma com sua composição (cargo → candidato) na ordem dos cargos.
- [ ] Tela em modo quiosque: sem menu, fonte grande, botões de no mínimo 48px.
- [ ] Testes: status muda de falso para verdadeiro após liberação; sem cookie → 403; cookie de outro terminal não vê a liberação deste; cookie antigo (terminal reativado em outro computador) → 403.

### H8 · Votar
Como eleitor, quero escolher uma chapa (ou branco/nulo), confirmar e ver "voto registrado" para ter certeza de que votei.
- [ ] Seleção → tela de confirmação ("Você escolheu a chapa 2 — Movimento", com a lista de candidatos. "Confirmar?") → registro.
- [ ] O voto é na chapa inteira, não por cargo (D13).
- [ ] Voto e encerramento da sessão na mesma transação, **reivindicando a sessão antes de gravar o voto** (ver `MODELO_DADOS.md`, "Registro de um voto").
- [ ] Duplo clique, F5 ou duas abas não geram segundo voto.
- [ ] A eleição do voto vem da sessão liberada, não do formulário; a chapa precisa ser dessa eleição.
- [ ] O voto (`POST /urna/votar`, com CSRF) só é aceito do terminal pareado em que a sessão foi liberada; o terminal vem do cookie, nunca do formulário (D16).
- [ ] Nada que ligue a chapa escolhida ao eleitor vai para log, auditoria ou sessão HTTP.
- [ ] A urna volta a "aguardando" 5 segundos após confirmar.
- [ ] Testes: voto válido, voto branco, segundo voto rejeitado, sessão expirada rejeitada, chapa de outra eleição rejeitada, voto enviado de outro terminal (ou sem cookie) rejeitado.

### H9 · Acompanhar a mesa
Como mesário, quero ver no painel quais terminais estão livres, liberados ou votando para organizar a fila.
- [ ] Painel atualiza sozinho (polling) sem recarregar a página.
- [ ] Terminais não pareados aparecem como **inativo** e não podem ser escolhidos para liberar (D16).
- [ ] Mostra contagem de comparecimento (quantos já votaram / aptos), sem mostrar votos por chapa.

## Épico D — Resultado e controle

### H10 · Apurar resultado
Como admin, quero ver o resultado por chapa, brancos, nulos e participação depois que a eleição encerrar.
- [ ] Antes do `fim` da eleição, `/admin/resultado` mostra só o comparecimento.
- [ ] Depois do `fim`: tabela + gráfico (Chart.js) + chapa vencedora **com seus eleitos por cargo** + participação (%).
- [ ] Só chapas homologadas entram na apuração.
- [ ] Cálculos em `App\Support\Apuracao` (classe pura, com testes unitários).
- [ ] Exportar CSV/PDF da ata.
- [ ] Testes: resultado bloqueado antes do fim, correto depois.

### H11 · Auditar ações
Como admin, quero ver um log de quem fez login, liberou sessões e alterou cadastros para dar transparência ao processo.
- [ ] Registra: login (sucesso/falha), logout, liberação e cancelamento de sessão, **ativação e desativação de terminal** (com IP, D16), CRUD de eleição/cargo/chapa/eleitor, **inscrição de chapa** (formulário ou CSV, com IP no caso do formulário), **homologação e indeferimento** (com motivo).
- [ ] **Nunca** registra em quem alguém votou.
- [ ] Tela `/admin/auditoria` com filtro por ação e data.

### H12 · Publicar em produção
Como equipe, queremos o sistema no ar na Hostinger com HTTPS para realizar a eleição real.
- [ ] Segue `DEPLOY_HOSTINGER.md`; `/api/health` responde `banco: ok` em produção.
- [ ] `APP_DEBUG=false`, seed não executado, senha do admin trocada.

## Definição de pronto (vale para todas)

- Protótipo da tela aprovado no Figma (quando houver tela).
- Testes escritos e passando (`php artisan test`).
- PR revisado e mergeado em `develop`.
- Issue fechada pelo PR (`closes #<nº>` na descrição).
