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
- [x] Importação de CSV em `/admin/eleitores/importar`, no fluxo da D19 (o mesmo das chapas, H15): **enviar não grava**; a conferência mostra os **campos com erro** (linha e coluna), os cadastros **novos**, os que **vão mudar** (antes → depois) e os nomes padronizados; o admin **cancela** (rollback), **ignora as linhas com erro** ou **confirma**. A confirmação é uma transação só e desfaz tudo se o cadastro mudou desde a conferência.
- [x] Matrícula que já existe no cadastro atualiza o nome, não cria outro registro (turma e e-mail vazios no arquivo não apagam). Matrícula **repetida dentro do arquivo** marca todas as linhas (a comissão decide qual vale).
- [x] Integridade: matrícula sem espaços no meio, no formato de `EleitorRegras`, com aviso para notação científica do Excel; nome padronizado (CAIXA ALTA → "Maria das Graças"); e-mail em minúsculas; colunas desconhecidas são ignoradas e listadas.
- [x] Relatório ao final: cadastrados, atualizados, sem alteração e linhas ignoradas (linha e motivo).
- [x] Eleitor que já foi liberado em alguma eleição, ou que é candidato, não pode ser excluído (o banco bloqueia; a tela explica o motivo).
- [x] O eleitorado é importado **antes** de abrir as inscrições: os candidatos são indicados pela matrícula (H14, H15).
- [x] Testes com arquivos em `tests/Fixtures/eleitores`: **limpo** (também com BOM e Windows-1252, e o modelo para baixar), **alterado** (apelidos no cabeçalho, coluna extra, vírgula, CRLF, linhas em branco, CAIXA ALTA, espaços, nome entre aspas) e **mal formatados** (sem cabeçalho, só cabeçalho, vazio, `.xlsx` renomeado, separador tab, colunas a mais); linhas válidas + inválidas; cancelar, ignorar, confirmar; rollback quando o cadastro muda durante a conferência.

### H5 · Cadastrar terminais
Como admin, quero cadastrar os terminais (número e local) para saber em qual computador cada eleitor será liberado.
- [x] CRUD em `/admin/terminais`; número único.
- [x] Lista mostra o estado atual: inativo (não pareado), livre, liberado (com nome do eleitor) ou votando (a cédula está na urna, H7).
- [x] **Pareamento (D16):** em `/terminal/ativar`, admin ou mesário logado no computador da urna escolhe qual terminal aquele computador é. O servidor grava o hash de um token em `terminais.token_hash` (e `ativado_em`), envia o token num cookie `terminal` (criptografado, `HttpOnly`, `Secure`, `SameSite=Strict`, 24 horas), faz logout desse navegador e redireciona para `/urna`.
- [x] Ativar o mesmo terminal em outro computador invalida o anterior. O admin pode desativar um terminal (apaga o hash).
- [x] Testes: ativação grava só o hash (o token não aparece no banco); reativação invalida o cookie antigo; usuário é deslogado após ativar; visitante sem login não acessa `/terminal/ativar`.

## Épico B — Inscrição de chapas

Fluxo e regras em `MODELO_DADOS.md`, seção "Inscrição de chapas". Decisões D13 e D14.

### H13 · Definir os cargos da eleição
Como admin, quero definir os cargos que toda chapa precisa preencher (ex.: Presidente, Vice-presidente, 1º Secretário, 2º Secretário, Tesoureiro) para que as inscrições tenham a mesma estrutura.
- [x] Em `/admin/eleicoes/{eleicao}/cargos`: adicionar, renomear, reordenar e remover cargos; título obrigatório e único na eleição.
- [x] A tela mostra "cada chapa terá N candidatos" (N = número de cargos).
- [x] Botão "copiar cargos de outra eleição" para não redigitar a cada semestre.
- [x] Depois que a primeira chapa se inscreve, os cargos ficam **bloqueados** (só leitura), com aviso explicando o porquê.
- [x] As inscrições não abrem sem ao menos um cargo (aviso na tela da eleição). *O formulário (H14) recusa a inscrição sem cargos; no CSV (H15), o cabeçalho cria os cargos quando a eleição ainda não tem (D19).*
- [x] Testes: criação e reordenação; título repetido rejeitado; alteração bloqueada com chapa inscrita.

### H14 · Inscrever chapa pelo formulário
Como representante de chapa, quero inscrever minha chapa pelo sistema, dentro do período de inscrição, informando o nome da chapa e a matrícula de um candidato para cada cargo.
- [x] Página pública `/inscricao` (sem login) para a eleição ativa. Fora do período, mostra as datas de abertura e fim e **não** exibe o formulário.
- [x] O formulário tem um campo de matrícula **por cargo**, na ordem dos cargos; ao sair do campo, mostra o nome do aluno para conferência (ou "matrícula não encontrada").
- [x] Também pede nome da chapa e contato do responsável (e-mail obrigatório e telefone opcional, D18).
- [x] Recusa como candidato o aluno que é mesário ou admin (usuário com essa matrícula, D19).
- [x] Rejeita, com mensagem por campo: cargo sem candidato, matrícula inexistente, a mesma matrícula em dois cargos, aluno que já está em outra chapa da eleição, envio fora do período (checado **no servidor**, no momento do envio, não só ao abrir a página).
- [x] Grava chapa e candidatos na **mesma transação**, travando a linha da eleição (`lockForUpdate`, como em `CargosDaEleicao`, D13), com situação `pendente` e o próximo número livre; mostra um comprovante (número da chapa, data e hora, composição).
- [x] `throttle` no envio (ex.: 5 por minuto por IP) e CSRF.
- [x] A página pública não lista as outras chapas inscritas nem dados de alunos além do nome confirmado no próprio formulário.
- [x] Testes: inscrição válida; antes da abertura e depois do fim rejeitadas; cargo faltando; matrícula inexistente; aluno em duas chapas; duas inscrições ao mesmo tempo recebem números diferentes.

### H15 · Importar chapas por CSV
Como admin, quero importar as chapas de um arquivo CSV para registrar inscrições recebidas por outro canal sem digitação manual.
- [x] Em `/admin/chapas/importar`, só durante o período de inscrição da eleição ativa; aceita o formato de `MODELO_DADOS.md` ("Formato do CSV de chapas"): **uma linha por chapa e uma coluna por cargo**, com link para baixar o modelo `docs/exemplos/chapas.csv`.
- [x] **O cabeçalho define os cargos (D19):** se a eleição ainda não tem cargos, as colunas os criam, na ordem do arquivo; se já tem (H13), as colunas precisam ser exatamente esses cargos (falta ou sobra de coluna recusa o arquivo).
- [x] **Nomes conferidos e padronizados:** cargos e colunas são comparados sem acento, maiúsculas, espaços e pontuação ("VICE PRESIDENTE" = "Vice-presidente"); nomes em CAIXA ALTA ou todo em minúsculas são arrumados, e "1o"/"1°" vira "1º". A tela lista cada ajuste feito.
- [x] **Nada é gravado ao enviar.** A tela de conferência mostra cada linha, com os **campos com erro** destacados e o motivo (linha e coluna, ex.: "Linha 7, Tesoureiro: Matrícula 2024999 não cadastrada no eleitorado").
- [x] **Opções depois da conferência:** cancelar tudo (rollback: o arquivo é descartado, nenhuma chapa é gravada), **ignorar as chapas com erro** e importar só as prontas, e por fim **confirmar**. Com erros, a escolha é obrigatória.
- [x] **A confirmação é uma transação só**, com a eleição travada (D13). Se algo mudou desde a conferência (uma chapa se inscreveu pelo formulário, um cargo mudou, o período fechou), **tudo é desfeito** e a conferência aparece de novo.
- [x] Mesmas regras da H14, **por chapa, tudo ou nada**: uma célula ruim rejeita a chapa inteira; as demais chapas do arquivo podem entrar.
- [x] **Integridade da matrícula (D19):** formato (letras, números, ponto e hífen), aluno cadastrado, um cargo só por aluno, uma chapa só por aluno na eleição (inclusive entre chapas do mesmo arquivo: as duas são marcadas), e **mesário ou admin não pode ser candidato** (vale também para o formulário da H14). Matrícula que o Excel virou notação científica ("2,02E+06") tem aviso próprio.
- [x] Relatório ao final: chapas importadas (número e nome), chapas ignoradas com linha e motivo, e cargos criados.
- [x] Chapas importadas entram como `pendente`, origem `csv`, e passam pela homologação (H3).
- [x] Testes com arquivos em `tests/Fixtures/chapas`: **limpo** (também com BOM e em Windows-1252), **alterado** (vírgula, CRLF, linhas em branco, maiúsculas, espaços, colunas fora de ordem, apelidos no cabeçalho) e **mal formatados** (cabeçalho errado, coluna repetida, linha com colunas a mais, só cabeçalho, vazio, .xlsx renomeado, separador tab, número e e-mail inválidos); chapas com cargo faltando, matrícula inexistente, cargo que não existe, aluno em duas chapas do arquivo, número já existente, mesário candidato; cancelar, ignorar, confirmar e rollback quando a situação muda; importação fora do período.

### H3 · Homologar chapas
Como admin (comissão eleitoral), quero analisar as chapas inscritas e homologar ou indeferir cada uma para que só chapas válidas apareçam na urna.
- [x] `/admin/chapas` lista as chapas da eleição (a ativa, ou outra escolhida na lista) com número, nome, situação (`pendente`, `homologada`, `indeferida`), origem (formulário/CSV), contato e a composição completa (cargo → nome e matrícula). Filtro por situação, com a contagem de cada uma.
- [x] Homologar e indeferir exigem confirmação (o botão abre um painel com "Confirmar"); indeferir exige **motivo** (mínimo de 10 caracteres). Uma chapa homologada ou indeferida pode voltar para `pendente` ("Reabrir análise") **até o início da votação**; depois, a tela fica só para leitura (D20).
- [x] A composição não pode ser editada depois do fim das inscrições (D14); antes, o admin corrige um candidato com as mesmas validações da H14 (incluindo mesário/admin, D19). Corrigir uma chapa homologada a devolve para `pendente`.
- [x] Só chapas `homologadas` aparecem na urna e na apuração: `Eleicao::chapasNaUrna()` é a consulta única para H7, H8 e H10 (D20). A página inicial pública também mostra só as homologadas. *O voto (H8) ainda precisa recusar chapa fora dessa lista.*
- [x] A votação não pode começar sem ao menos uma chapa homologada: aviso no painel, na lista de eleições e na lista de chapas. *O bloqueio de fato fica na liberação (H6) e na urna (H7).*
- [x] Não é possível excluir chapa depois que a eleição recebeu votos (o banco bloqueia e a tela explica). A exclusão só existe até a votação abrir.
- [x] Toda homologação, indeferimento (com motivo), reabertura, troca de candidato e exclusão vai para a auditoria (H11 fase 1). A chapa também guarda quem decidiu por último (`analisada_por_id`, `analisada_em`).
- [x] Testes: homologar, indeferir sem motivo rejeitado, reabrir até a votação, chapa pendente fora da urna, correção de candidato com as regras da H14, edição bloqueada após o fim das inscrições, exclusão bloqueada com votos, avisos, acesso negado ao mesário.

## Épico C — Dia da votação

### H6 · Liberar votação de um eleitor
Como mesário, quero digitar a matrícula, conferir o nome e escolher o terminal para liberar o voto daquele eleitor.
- [x] Em `/mesario`, a busca por matrícula mostra nome e turma (e a situação da sessão, se houver) antes de confirmar; só então aparecem o terminal e o botão "Liberar". O admin também entra.
- [x] Bloqueia se: eleição fora do período de votação (antes ou depois), nenhuma chapa homologada (D20), eleitor já votou, eleitor com liberação em andamento em outro terminal, terminal já tem sessão aberta, terminal não está ativado ou com pareamento vencido (D16), e o mesário tentando liberar o próprio voto (D19, `users.matricula`).
- [x] Sessão expira em 30 minutos (sem tarefa agendada: aberta com prazo vencido **é** expirada, D21); o mesário libera de novo se expirou **ou se cancelou**.
- [x] Liberar de novo **reaproveita a mesma sessão** (atualiza situação, terminal, mesário e horários): o banco só aceita uma sessão por eleitor e eleição (`UNIQUE`). Nunca reabre sessão já `votou`.
- [x] Mesário pode cancelar uma sessão aberta que ainda não votou (com confirmação). O cancelamento é atômico: se o eleitor votar no mesmo instante, ele não acontece.
- [x] Registra quem liberou (`mesario_id`); cada liberação, re-liberação (com o terminal e a situação anteriores), cancelamento e **tentativa recusada** vai para a auditoria (H11 fase 1). A sessão guarda só o estado atual; o histórico está na auditoria.
- [x] Painel dos terminais: livre, liberado (nome e minutos restantes, com "Cancelar sessão") e inativo; comparecimento e horário de encerramento. *Atualização automática e o estado "votando" são da H9 e da H7.*
- [x] Testes: liberação válida, eleitor já votou, terminal ocupado, terminal inativo (e pareamento vencido), fora do período, sem chapa homologada, re-liberação após expirar (continua 1 linha em `sessoes_votacao`), cancelar e liberar de novo, não cancela quem votou, eleitor em outro terminal, mesário não libera o próprio voto.

### H7 · Urna aguarda liberação
Como eleitor, quero que a urna do terminal mostre "aguardando" até o mesário me liberar, e então mostre meu nome e as chapas.
- [x] `/urna` consulta `/urna/status` a cada 3 segundos. Não há ID na URL: o terminal vem do cookie de pareamento (H5, D16), conferido por um middleware `terminal`. As rotas ficam no grupo `web` (o grupo `api` não lê cookies). A tela é montada no servidor; o script só recarrega quando a liberação muda (liberou, cancelou, expirou, D23).
- [x] Sem cookie válido, `/urna` e `/urna/status` respondem 403 com a tela "Este computador não está ativado como terminal. Chame o mesário." O status **nunca** devolve o nome do eleitor: só `liberada` e uma `versao` da liberação. *`/urna/votar` é da H8 e entra no mesmo middleware.*
- [x] Quando liberada, mostra nome do eleitor e as chapas **homologadas** da eleição ativa (`chapasNaUrna`, D20), na ordem do número, cada uma com sua composição (cargo → candidato) na ordem dos cargos, mais branco e nulo. Com a votação encerrada, volta para "aguardando".
- [x] Quando a cédula aparece, a sessão grava `exibida_em` e o terminal passa a **votando** no painel da mesa e na lista de terminais.
- [x] Tela em modo quiosque: sem menu, fonte grande, opções e botões com 88 px de altura (`--dt-tamanho-toque-urna`).
- [x] Testes: status muda de falso para verdadeiro após liberação; sem cookie → 403; cookie de outro terminal não vê a liberação deste; cookie antigo (terminal reativado em outro computador) → 403; o status não traz nome nem matrícula; cédula só com homologadas e composição em ordem; "votando"; expirada, cancelada e votação encerrada voltam para "aguardando".

### H8 · Votar
Como eleitor, quero escolher uma chapa (ou branco/nulo), confirmar e ver "voto registrado" para ter certeza de que votei.
- [x] Seleção → tela de confirmação ("Confirme seu voto": a chapa com a lista de candidatos, ou branco/nulo; "Corrigir" ou "Confirmar voto") → registro. A confirmação é um `POST` que não grava nada, nem na sessão HTTP (D24).
- [x] O voto é na chapa inteira, não por cargo (D13). Branco e nulo são diferentes (`votos.tipo`, D24).
- [x] Voto e encerramento da sessão na mesma transação, **reivindicando a sessão antes de gravar o voto** (ver `MODELO_DADOS.md`, "Registro de um voto"; `App\Support\RegistroDeVoto`).
- [x] Duplo clique, F5 ou duas abas não geram segundo voto: o segundo envio encontra a sessão "votou" e vai para "Voto registrado", sem trocar o voto. O botão também se desabilita no primeiro clique.
- [x] A eleição do voto vem da sessão liberada, não do formulário; a chapa precisa estar na cédula (homologada, da mesma eleição; a FK composta é a última defesa).
- [x] O voto (`POST /urna/votar`, com CSRF) só é aceito do terminal pareado em que a sessão foi liberada; o terminal vem do cookie, nunca do formulário (D16). Além disso, o voto carrega a **versão da liberação** que estava na tela (D23): se o mesário cancelou e liberou outra pessoa no mesmo terminal, a confirmação antiga não vale.
- [x] Nada que ligue a chapa escolhida ao eleitor vai para log, auditoria ou sessão HTTP: escolha só em `POST` (nunca na URL), sem `validate()` (que guardaria a entrada na sessão), auditoria só com `sessao.votou` (D22).
- [x] A urna volta a "aguardando" 5 segundos após confirmar.
- [x] Testes: voto válido, voto branco e nulo, segundo voto rejeitado, sessão expirada e cancelada rejeitadas, chapa de outra eleição ou não homologada rejeitada, voto enviado de outro terminal (ou sem cookie) rejeitado, confirmação antiga em nova liberação, eleição vinda da sessão, votação encerrada, sigilo (sessão HTTP, URL, auditoria e colunas do voto).

### H9 · Acompanhar a mesa
Como mesário, quero ver no painel quais terminais estão livres, liberados ou votando para organizar a fila.
- [x] Painel atualiza sozinho a cada 5 segundos (`GET /mesario/painel`), sem recarregar a página: troca os indicadores e os terminais e acompanha o seletor de terminal do formulário. O que o mesário está digitando continua lá. Pausa com a aba escondida e enquanto um "Cancelar sessão" está aberto (D25).
- [x] Terminais não pareados aparecem como **inativo** e não podem ser escolhidos para liberar (D16). Os estados são livre, liberado, votando (cédula na tela, H7) e inativo.
- [x] Mostra contagem de comparecimento (quantos já votaram / aptos, com porcentagem), sem mostrar votos por chapa.
- [x] Testes: estados acompanhando liberar → votando → votou; comparecimento sem nenhuma chapa no painel; inativo desabilitado no seletor; script de atualização na página; painel sem login → 401.

## Épico D — Resultado e controle

### H10 · Apurar resultado
Como admin, quero ver o resultado por chapa, brancos, nulos e participação depois que a eleição encerrar.
- [x] Antes do `fim` da eleição, `/admin/resultado` mostra só o comparecimento. O CSV e a ata também são recusados (D26).
- [x] Depois do `fim`: tabela + gráfico (Chart.js) + chapa vencedora **com seus eleitos por cargo** + participação (%). Empate no primeiro lugar não tem vencedora: a tela mostra as empatadas e a comissão desempata pelo regimento (D26).
- [x] Só chapas homologadas entram na apuração (`chapasNaUrna`, D20). Se o total de votos não bater com o comparecimento, a tela e a ata avisam.
- [x] Cálculos em `App\Support\Apuracao` (classe pura, com testes unitários). A busca no banco fica em `App\Support\ResultadoDaEleicao`, usada pela tela, pelo CSV e pela ata.
- [x] Exportar CSV/PDF da ata: CSV para baixar e ata em página A4 para imprimir ou salvar em PDF pelo navegador (D26). Consultar o resultado e exportar a ata entram na auditoria (`resultado.consultado`, `ata.exportada` com o SHA-256).
- [x] Testes: resultado bloqueado antes do fim, correto depois (`ResultadoTest`, `tests/Unit/ApuracaoTest`).

### H11 · Auditar ações
Como admin, quero ver um log de quem fez login, liberou sessões e alterou cadastros para dar transparência ao processo.
Plano completo em `docs/AUDITORIA.md` (catálogo de eventos, tabela, fases), decisão D22.
- [x] **Fase 1 (antes da H8):** tabela `auditoria` só-acrescenta, `App\Support\Auditoria::registrar` gravando **na mesma transação** da ação, enum `AcaoAuditoria`. Registra login (sucesso, falha e bloqueio) e logout; **ativação e desativação de terminal** (com IP, D16); liberação, re-liberação, cancelamento e **tentativa recusada** de liberação (H6); **inscrição de chapa** pelo formulário (com IP, H14); **homologação, indeferimento (com motivo), reabertura, troca de candidato e exclusão** (H3).
- [x] **Fase 2:** CRUD de eleição, cargo, eleitor, terminal e usuário; importações de eleitores e chapas confirmadas ou canceladas (nome e SHA-256 do arquivo, contagens). Alteração guarda só o que mudou (antes e depois); senha e token nunca entram (`AuditoriaCadastrosTest`).
- [x] **Fase 3:** tela `/admin/auditoria` (só admin) com filtro por eleição, categoria, ação, usuário, período e matrícula/número; detalhes; exportação CSV (também auditada, com o SHA-256); atalho "Histórico" em cada chapa (`AuditoriaTelaTest`). *As últimas recusas no painel do mesário ficaram de fora: a tela filtra por "Liberação recusada".*
- [x] **Fase 4 (opcional, antes da H12):** corrente de hashes com comando de verificação (`php artisan digitech:auditoria:verificar`) e o hash final impresso na ata (D27, `CorrenteDeHashesTest`). *O usuário do banco sem `UPDATE`/`DELETE` na tabela fica no roteiro de deploy (H12), se a hospedagem permitir.*
- [ ] **Nunca** registra em quem alguém votou: o voto não gera evento; `sessao.votou` (H8) não tem a chapa.
- [ ] Testes: cada ação do catálogo gera um evento com ator, IP e alvo; ação desfeita não deixa evento; o model recusa alteração e exclusão; nenhum evento tem `chapa_id` depois de uma votação simulada; tela só para admin. *Fases 1 a 4 prontas (`AuditoriaTest`, `AuditoriaCadastrosTest`, `AuditoriaTelaTest`, `CorrenteDeHashesTest`); votação simulada pronta com a H8 (`VotoTest`, sigilo).*

### H12 · Publicar em produção
Como equipe, queremos o sistema no ar na Hostinger com HTTPS para realizar a eleição real.
- [ ] Segue `DEPLOY_HOSTINGER.md`; `/api/health` responde `banco: ok` em produção.
- [ ] `APP_DEBUG=false`, seed não executado, senha do admin trocada.

## Definição de pronto (vale para todas)

- Protótipo da tela aprovado no Figma (quando houver tela).
- Testes escritos e passando (`php artisan test`).
- PR revisado e mergeado em `develop`.
- Issue fechada pelo PR (`closes #<nº>` na descrição).
