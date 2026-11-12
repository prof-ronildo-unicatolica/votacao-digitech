# Guia do usuário

Como usar o Votação DigiTech numa eleição de verdade, do cadastro ao resultado. O guia é dividido por quem usa: a **comissão eleitoral**, os **alunos que inscrevem chapas**, os **mesários** e o **eleitor na urna**.

> As telas deste guia vêm de uma eleição de exemplo (dados fictícios). As fotos são tiradas pelo teste de ponta a ponta: para atualizá-las depois de mudar uma tela, rode `cd e2e && npm run guia` (veja [TESTES.md](TESTES.md)).

## Sumário

1. [Quem faz o quê](#1-quem-faz-o-quê)
2. [O calendário de uma eleição](#2-o-calendário-de-uma-eleição)
3. [Comissão: preparar a eleição](#3-comissão-preparar-a-eleição)
4. [Alunos: inscrever uma chapa](#4-alunos-inscrever-uma-chapa)
5. [Comissão: homologar as chapas](#5-comissão-homologar-as-chapas)
6. [Dia da votação: montar as urnas](#6-dia-da-votação-montar-as-urnas)
7. [Mesário: liberar o eleitor](#7-mesário-liberar-o-eleitor)
8. [Eleitor: votar na urna](#8-eleitor-votar-na-urna)
9. [Situações do dia a dia na mesa](#9-situações-do-dia-a-dia-na-mesa)
10. [Comissão: resultado e ata](#10-comissão-resultado-e-ata)
11. [Comissão: auditoria](#11-comissão-auditoria)
12. [Perguntas frequentes](#12-perguntas-frequentes)

## 1. Quem faz o quê

| Pessoa | Entra com | Onde trabalha | O que faz |
|---|---|---|---|
| **Comissão eleitoral** (perfil *Admin*) | e-mail e senha | `/admin` | Cadastra a eleição, os cargos, os eleitores, os terminais e os mesários. Homologa as chapas. Vê o resultado, a ata e a auditoria. |
| **Aluno que inscreve chapa** | sem login | `/inscricao` | Inscreve a chapa durante o período de inscrição. |
| **Mesário** (perfil *Mesário*) | e-mail e senha | `/mesario` | Ativa os computadores das urnas, confere o documento do eleitor e libera a urna para ele. |
| **Eleitor** | sem login | a urna (`/urna`) | Vota no terminal que o mesário liberou. |

Para entrar, abra `/login` e use o e-mail e a senha que a comissão cadastrou. Depois de entrar, cada perfil cai direto na sua tela: a comissão no painel (`/admin`), o mesário na mesa (`/mesario`).

![Tela de login](guia/login.png)

## 2. O calendário de uma eleição

Toda eleição passa por quatro fases, sempre nesta ordem. O sistema muda de fase sozinho, pelas datas que a comissão cadastrou:

| Fase | Quando | O que pode acontecer |
|---|---|---|
| **Inscrições abertas** | da abertura ao fim das inscrições | Alunos inscrevem chapas. A comissão pode corrigir candidatos. |
| **Em homologação** | do fim das inscrições até a votação abrir | A comissão homologa ou indefere cada chapa. A composição das chapas não muda mais. |
| **Votação aberta** | do início ao fim da votação | Mesários liberam eleitores e eles votam. A comissão vê só o comparecimento. |
| **Encerrada** | depois do fim da votação | A comissão vê o resultado e exporta a ata. |

Só **uma eleição fica ativa** por vez. É ela que aparece no formulário de inscrição, na mesa e nas urnas.

## 3. Comissão: preparar a eleição

O menu da comissão fica à esquerda. O **Início** mostra a eleição ativa, a fase em que ela está, as datas e os números principais.

![Painel da comissão](guia/painel.png)

Faça os passos abaixo **antes de abrir as inscrições**.

### 3.1 Cadastrar a eleição

Em **Eleições → Nova eleição**, informe o título e as quatro datas: abertura e fim das inscrições, início e fim da votação. A votação precisa começar no fim das inscrições ou depois. O intervalo entre os dois é o tempo da homologação.

![Nova eleição](guia/eleicao-nova.png)

A lista de eleições mostra a fase de cada uma e avisa o que falta (por exemplo, "Sem cargos: inscrições não abrem"). Use **Ativar** para escolher qual é a eleição em andamento.

![Lista de eleições](guia/eleicoes.png)

### 3.2 Cadastrar os cargos

Em **Cargos**, adicione um cargo por vez (Presidente, Vice-presidente, etc.). Cada chapa terá **um candidato para cada cargo**. As setas mudam a ordem, que é a mesma do formulário de inscrição e da urna.

![Cargos](guia/cargos.png)

### 3.3 Importar os eleitores

Os eleitores são os alunos que podem votar. Os candidatos também são indicados pela matrícula, então importe a lista **antes das inscrições**.

Em **Eleitores → Importar CSV**, envie um arquivo com uma linha por aluno e o cabeçalho `matricula;nome;turma;email` (turma e e-mail são opcionais). O link **Baixar o modelo** traz um arquivo pronto para preencher no Excel.

![Importar eleitores](guia/eleitores-importar.png)

Nada é gravado ao enviar. Primeiro aparece uma **conferência** com o que é novo, o que vai mudar e o que tem erro. Só depois de conferir você confirma (ou cancela).

![Conferir importação](guia/eleitores-conferir.png)

Também dá para cadastrar ou corrigir um aluno de cada vez, pelo botão **Novo eleitor** e pelo **Editar** de cada linha.

![Lista de eleitores](guia/eleitores.png)

### 3.4 Cadastrar os terminais

Um **terminal** é um computador onde se vota. Em **Terminais → Novo terminal**, cadastre um para cada computador, com número e local (por exemplo, "Terminal 1, Laboratório 1").

![Terminais](guia/terminais.png)

Terminal que já recebeu eleitores não pode ser excluído, porque faz parte da ata. Se não for mais usar, **desative**.

### 3.5 Cadastrar os mesários

Em **Usuários → Novo usuário**, cadastre cada mesário com o perfil **Mesário**. Se o mesário também for aluno, informe a matrícula: assim ele não pode ser inscrito como candidato.

![Novo usuário](guia/usuario-novo.png)

O perfil **Admin** é para os membros da comissão. Ele vê tudo. O **Mesário** só libera eleitores.

![Usuários](guia/usuarios.png)

## 4. Alunos: inscrever uma chapa

Durante as inscrições, qualquer aluno abre `/inscricao` (não precisa de login), preenche o nome da chapa, um e-mail de contato e a **matrícula de um candidato para cada cargo**. Ao digitar a matrícula, o nome do aluno aparece embaixo para conferir.

![Inscrição de chapa](guia/inscricao.png)

Depois de enviar, aparece o **comprovante**, com o número da chapa. Guarde-o (imprima ou tire um print). Toda chapa entra como **pendente** até a comissão analisar.

![Comprovante de inscrição](guia/inscricao-comprovante.png)

Fora do período, o formulário avisa que as inscrições estão fechadas.

![Inscrições fechadas](guia/inscricao-fechada.png)

> A comissão também pode inscrever várias chapas de uma vez por CSV, em **Chapas → Importar CSV**, durante o período de inscrição. As chapas importadas também entram como pendentes.

## 5. Comissão: homologar as chapas

Em **Chapas**, cada chapa aparece com a situação (pendente, homologada ou indeferida), a origem (formulário ou CSV) e os candidatos de cada cargo. As abas filtram por situação.

![Chapas inscritas](guia/chapas-inscritas.png)

- **Homologar:** confira a composição e confirme. Só chapas homologadas aparecem na urna.

  ![Homologar](guia/chapas-homologar.png)

- **Indeferir:** escreva o motivo (obrigatório) e confirme. O motivo fica registrado na chapa.

  ![Indeferir](guia/chapas-indeferir.png)

- **Reabrir:** devolve uma chapa homologada ou indeferida para pendente, para analisar de novo. Só funciona até a votação abrir.
- **Trocar um candidato:** durante as inscrições, a comissão pode trocar a matrícula de um cargo. A chapa volta para pendente, porque a composição mudou.
- **Excluir:** só para chapa que ainda não recebeu votos.
- **Histórico da chapa:** mostra tudo o que aconteceu com ela, com data e autor.

Quando a votação abre, a tela de chapas fica **somente leitura**.

## 6. Dia da votação: montar as urnas

Faça isto em **cada computador que vai ser urna**, antes de os eleitores chegarem:

1. No computador da urna, abra `/login` e entre com a conta de um mesário (ou da comissão).
2. Abra `/terminal/ativar` (ou, na tela da mesa, clique em **Ativar este computador como terminal**).
3. Escolha qual terminal é este computador e clique em **Ativar e abrir a urna**.

![Ativar terminal](guia/terminal-ativar.png)

Ao ativar, o mesário é **desconectado** daquele navegador, para o eleitor nunca encontrar uma sessão de mesário aberta. O computador fica pareado como urna por **24 horas** e mostra "Aguardando liberação".

![Urna aguardando liberação](guia/urna-espera.png)

Se alguém abrir `/urna` num computador que não foi ativado, aparece o aviso abaixo. Basta ativar como descrito acima.

![Urna não ativada](guia/urna-nao-ativada.png)

> Deixe o navegador da urna em tela cheia (tecla **F11**). Não feche a aba: a urna troca de tela sozinha quando o mesário libera alguém.

## 7. Mesário: liberar o eleitor

Na tela **Mesa receptora** (`/mesario`), o mesário vê o comparecimento, quantos terminais estão livres, a hora em que a votação encerra e o estado de cada terminal.

![Mesa receptora](guia/mesa.png)

Para cada eleitor que chega:

1. **Confira o documento** do aluno.
2. Digite a **matrícula** e clique em **Conferir**. Aparecem o nome e a turma, para comparar com o documento.
3. Escolha um **terminal livre** e clique em **Liberar** (o botão traz o nome do eleitor).

![Conferir e liberar](guia/mesa-conferir.png)

A mesa confirma a liberação, e em poucos segundos a cédula aparece na urna escolhida. O eleitor tem **30 minutos** para votar.

![Eleitor liberado](guia/mesa-liberado.png)

O painel de terminais se atualiza sozinho a cada 5 segundos. Um terminal **Votando** mostra quem está na cabine, mas **nunca** em quem a pessoa vota.

![Terminais em uso](guia/mesa-votando.png)

## 8. Eleitor: votar na urna

1. Na urna, aparece "Olá" com o nome do eleitor. Ele escolhe **uma** opção: uma chapa, **Branco** ou **Nulo**, e clica em **Continuar**.

   ![Cédula](guia/urna-cedula.png)

2. A urna mostra a escolha para conferir. Para mudar, clique em **Corrigir**. Se estiver certo, clique em **Confirmar voto**.

   ![Confirmação](guia/urna-confirmacao.png)

3. Pronto: aparece "Voto registrado". A tela não diz em quem a pessoa votou e volta sozinha para "Aguardando liberação" em alguns segundos, pronta para o próximo eleitor.

   ![Voto registrado](guia/urna-registrado.png)

O voto é secreto: o sistema registra **que** o aluno votou, mas o voto é guardado sem ligação com quem votou.

## 9. Situações do dia a dia na mesa

**O eleitor já votou.** A conferência avisa e o botão de liberar não aparece. Cada aluno vota uma vez só.

![Eleitor já votou](guia/mesa-ja-votou.png)

**Liberou no terminal errado, ou o eleitor desistiu.** No cartão do terminal, clique em **Cancelar sessão** e confirme. A urna volta para a espera e o eleitor pode ser liberado de novo (a conferência mostra "Sessão anterior cancelada").

![Cancelar sessão](guia/mesa-cancelar.png)

**O eleitor demorou mais de 30 minutos.** A liberação expira sozinha, a urna volta para a espera e o terminal fica livre. Basta conferir e liberar de novo (a conferência mostra "Sessão anterior expirada").

![Liberação expirada](guia/mesa-expirada.png)

**Acabou o horário.** No fim da votação, a mesa para de liberar e mostra "Liberação indisponível". Quem estava votando naquele momento não consegue mais confirmar.

![Votação encerrada na mesa](guia/mesa-encerrada.png)

**Matrícula não encontrada.** O aluno não está no eleitorado. Só a comissão pode cadastrá-lo, em **Eleitores**.

## 10. Comissão: resultado e ata

Durante a votação, **Resultado** mostra só o comparecimento. Os votos ficam fechados até o fim.

![Resultado durante a votação](guia/resultado-parcial.png)

Depois do encerramento, aparecem os votos de cada opção, a participação, os brancos e nulos, a chapa vencedora e os eleitos de cada cargo.

![Resultado](guia/resultado.png)

Dois botões exportam a apuração:

- **Exportar CSV:** a ata em planilha, para abrir no Excel.
- **Ata para imprimir ou PDF:** abre a ata pronta para imprimir. Para salvar em PDF, use **Imprimir → Salvar como PDF** no navegador.

![Ata](guia/ata.png)

A ata traz impresso o **código da auditoria** (o último elo da corrente de hashes). Guarde a ata impressa: ela serve para provar, depois, que o registro de auditoria não foi mexido.

## 11. Comissão: auditoria

Em **Auditoria**, a comissão vê tudo o que aconteceu no sistema: quem fez, o quê, quando e de qual computador. Os filtros buscam por eleição, tipo de ação, pessoa, período ou texto (matrícula, número da chapa, e-mail). **Exportar CSV** baixa o que está filtrado.

![Auditoria](guia/auditoria.png)

O registro **só acrescenta**: nada nele pode ser alterado ou apagado pelo sistema. Cada evento é encadeado ao anterior, e qualquer alteração feita direto no banco de dados é detectada. A verificação técnica está em [AUDITORIA.md](AUDITORIA.md).

A auditoria mostra cada liberação e cada voto registrado, com o terminal, mas **nunca** a escolha do eleitor. Para saber se um aluno já votou, basta conferir a matrícula na mesa.

## 12. Perguntas frequentes

**A urna não sai de "Aguardando liberação" depois que liberei.**
Espere alguns segundos: a urna verifica a cada 3 segundos. Confira se liberou no terminal certo (o número aparece no topo da urna). Se a internet do computador caiu, recarregue a página (F5).

**Fechei a aba da urna sem querer.**
Abra `/urna` de novo no mesmo navegador. O pareamento vale 24 horas, então não precisa ativar outra vez.

**A urna diz "Terminal não ativado".**
O pareamento passou de 24 horas, foi feito em outro navegador ou a comissão desativou o terminal. Ative de novo pelo `/terminal/ativar`.

**Um aluno não está na lista de eleitores.**
A comissão cadastra em **Eleitores → Novo eleitor**, ou reimporta o CSV (matrícula que já existe só é atualizada).

**Preciso mudar a data da votação.**
A comissão edita a eleição em **Eleições → Editar**. Toda alteração fica registrada na auditoria.

**Esqueci a senha.**
Peça a um membro da comissão: em **Usuários → Editar**, ele define uma senha nova.
