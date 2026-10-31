# Histórias de usuário

Formato: **Como** [perfil], **quero** [ação] **para** [benefício]. Cada história é uma **issue no GitHub** com os critérios de aceite como checklist, etiqueta de perfil e milestone da sprint. Uma história = uma issue = uma branch `feature/<nº da issue>-...` = um PR com `closes #<nº>`.

## Perfis

| Perfil      | Quem é                                          | Como entra no sistema                   |
| ----------- | ----------------------------------------------- | --------------------------------------- |
| **Admin**   | Comissão eleitoral / professor                  | Login (`users`; como diferenciar do mesário é parte da H1) |
| **Mesário** | Aluno ou servidor que atende na mesa            | Login (`users`)                         |
| **Eleitor** | Aluno do colegiado                              | Não faz login: é liberado pelo mesário  |

## Épico A — Configuração (Admin)

### H1 · Autenticar admin e mesário
Como admin ou mesário, quero entrar com e-mail e senha para acessar só o que meu perfil permite.
- [ ] Tela de login em `/login`; após entrar, admin vai para `/admin` e mesário para `/mesario`.
- [ ] Mesário que tenta abrir `/admin` recebe 403.
- [ ] Logout encerra a sessão.
- [ ] Admin pode desativar um usuário (ele não entra mais); usuário que já liberou sessões não pode ser apagado (o banco bloqueia).
- [ ] 5 tentativas erradas em 1 minuto bloqueiam por 1 minuto (middleware `throttle`).
- [ ] Testes: login válido, senha errada, mesário bloqueado em `/admin`.

### H2 · Cadastrar eleição
Como admin, quero criar uma eleição com título, início e fim para controlar quando a votação está aberta.
- [ ] CRUD em `/admin/eleicoes`; só uma pode estar `ativa`.
- [ ] `fim` deve ser depois de `inicio` (validação).
- [ ] Ativar uma eleição desativa a anterior.
- [ ] Testes: criação válida, `fim < inicio` rejeitado, ativação exclusiva.

### H3 · Cadastrar chapas
Como admin, quero cadastrar as chapas da eleição ativa com número, nome e descrição para que apareçam na urna.
- [ ] CRUD em `/admin/chapas`; número único na eleição.
- [ ] Não é possível excluir chapa depois que a eleição recebeu votos.
- [ ] Testes: número duplicado rejeitado, exclusão bloqueada com votos.

### H4 · Cadastrar eleitores (individual e CSV)
Como admin, quero cadastrar eleitores um a um ou importar um CSV (matrícula, nome, turma, e-mail) para montar o eleitorado sem digitação manual.
- [ ] CRUD em `/admin/eleitores` com busca por matrícula/nome e paginação.
- [ ] Importação de CSV mostra quantos entraram e quais linhas falharam (e por quê).
- [ ] Matrícula duplicada no CSV atualiza o nome, não cria outro registro.
- [ ] Eleitor que já foi liberado em alguma eleição não pode ser excluído (o banco bloqueia; a tela explica o motivo).
- [ ] Testes: importação com 3 linhas válidas + 1 inválida.

### H5 · Cadastrar terminais
Como admin, quero cadastrar os terminais (número e local) para saber em qual computador cada eleitor será liberado.
- [ ] CRUD em `/admin/terminais`; número único.
- [ ] Lista mostra o estado atual: livre, liberado (com nome do eleitor) ou votando.

## Épico B — Dia da votação

### H6 · Liberar votação de um eleitor
Como mesário, quero digitar a matrícula, conferir o nome e escolher o terminal para liberar o voto daquele eleitor.
- [ ] Busca por matrícula mostra nome e turma antes de confirmar.
- [ ] Bloqueia se: eleição fora do período, eleitor já votou, terminal já tem sessão aberta.
- [ ] Sessão expira em 30 minutos; mesário pode liberar de novo se expirou **ou se cancelou** (ex.: eleitor foi para o terminal errado).
- [ ] Liberar de novo **reaproveita a mesma sessão** (atualiza situação, terminal, mesário e horários): o banco só aceita uma sessão por eleitor e eleição (`UNIQUE`). Nunca reabre sessão já `votou`.
- [ ] Mesário pode cancelar uma sessão aberta que ainda não votou.
- [ ] Registra quem liberou (`mesario_id`); cada liberação, re-liberação e cancelamento vai para a auditoria (H11), já que a sessão guarda só o estado atual.
- [ ] Testes: liberação válida, eleitor já votou, terminal ocupado, fora do período, re-liberação após expirar (continua 1 linha em `sessoes_votacao`).

### H7 · Urna aguarda liberação
Como eleitor, quero que a urna do terminal mostre "aguardando" até o mesário me liberar, e então mostre meu nome e as chapas.
- [ ] `/urna/{terminal}` consulta `/api/terminais/{id}/status` a cada 3 segundos.
- [ ] Quando `liberada = true`, mostra nome do eleitor e as chapas da eleição ativa.
- [ ] Tela em modo quiosque: sem menu, fonte grande, botões de no mínimo 48px.
- [ ] Testes: status muda de falso para verdadeiro após liberação.

### H8 · Votar
Como eleitor, quero escolher uma chapa (ou branco/nulo), confirmar e ver "voto registrado" para ter certeza de que votei.
- [ ] Seleção → tela de confirmação ("Você escolheu a chapa 2 — Movimento. Confirmar?") → registro.
- [ ] Voto e encerramento da sessão na mesma transação, **reivindicando a sessão antes de gravar o voto** (ver `MODELO_DADOS.md`, "Registro de um voto").
- [ ] Duplo clique, F5 ou duas abas não geram segundo voto.
- [ ] A eleição do voto vem da sessão liberada, não do formulário; a chapa precisa ser dessa eleição.
- [ ] Nada que ligue a chapa escolhida ao eleitor vai para log, auditoria ou sessão HTTP.
- [ ] A urna volta a "aguardando" 5 segundos após confirmar.
- [ ] Testes: voto válido, voto branco, segundo voto rejeitado, sessão expirada rejeitada, chapa de outra eleição rejeitada.

### H9 · Acompanhar a mesa
Como mesário, quero ver no painel quais terminais estão livres, liberados ou votando para organizar a fila.
- [ ] Painel atualiza sozinho (polling) sem recarregar a página.
- [ ] Mostra contagem de comparecimento (quantos já votaram / aptos), sem mostrar votos por chapa.

## Épico C — Resultado e controle

### H10 · Apurar resultado
Como admin, quero ver o resultado por chapa, brancos, nulos e participação depois que a eleição encerrar.
- [ ] Antes do `fim` da eleição, `/admin/resultado` mostra só o comparecimento.
- [ ] Depois do `fim`: tabela + gráfico (Chart.js) + chapa vencedora + participação (%).
- [ ] Cálculos em `App\Support\Apuracao` (classe pura, com testes unitários).
- [ ] Exportar CSV/PDF da ata.
- [ ] Testes: resultado bloqueado antes do fim, correto depois.

### H11 · Auditar ações
Como admin, quero ver um log de quem fez login, liberou sessões e alterou cadastros para dar transparência ao processo.
- [ ] Registra: login (sucesso/falha), logout, liberação e cancelamento de sessão, CRUD de eleição/chapa/eleitor.
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
