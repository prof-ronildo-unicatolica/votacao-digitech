# Histórias de usuário

Formato: **Como** [perfil], **quero** [ação] **para** [benefício]. Cada história vira um cartão no Trello com os critérios de aceite como checklist. Uma história = uma branch `feature/hN-...` = um PR.

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
- [ ] Sessão expira em 30 minutos; mesário pode liberar de novo se expirou.
- [ ] Registra quem liberou (`mesario_id`).
- [ ] Testes: liberação válida, eleitor já votou, terminal ocupado, fora do período.

### H7 · Urna aguarda liberação
Como eleitor, quero que a urna do terminal mostre "aguardando" até o mesário me liberar, e então mostre meu nome e as chapas.
- [ ] `/urna/{terminal}` consulta `/api/terminais/{id}/status` a cada 3 segundos.
- [ ] Quando `liberada = true`, mostra nome do eleitor e as chapas da eleição ativa.
- [ ] Tela em modo quiosque: sem menu, fonte grande, botões de no mínimo 48px.
- [ ] Testes: status muda de falso para verdadeiro após liberação.

### H8 · Votar
Como eleitor, quero escolher uma chapa (ou branco/nulo), confirmar e ver "voto registrado" para ter certeza de que votei.
- [ ] Seleção → tela de confirmação ("Você escolheu a chapa 2 — Movimento. Confirmar?") → registro.
- [ ] Voto e encerramento da sessão na mesma transação (ver `MODELO_DADOS.md`).
- [ ] Duplo clique ou F5 não gera segundo voto (sessão já está `votou`).
- [ ] A urna volta a "aguardando" 5 segundos após confirmar.
- [ ] Testes: voto válido, voto branco, segundo voto rejeitado, sessão expirada rejeitada.

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
- Cartão do Trello com o link do PR.
