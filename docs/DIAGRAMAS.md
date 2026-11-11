# Diagramas UML

Casos de uso e diagramas de sequência do sistema. O diagrama entidade-relacionamento do banco fica em [`MODELO_DADOS.md`](MODELO_DADOS.md).

Os diagramas são escritos em [Mermaid](https://mermaid.js.org/). O GitHub desenha os blocos ` ```mermaid ` direto nesta página; no VS Code, a extensão *Markdown Preview Mermaid Support* mostra a prévia. Para testar uma alteração, cole o bloco em [mermaid.live](https://mermaid.live).

**Cuidado ao editar:** não use `;` no texto dos diagramas de sequência, porque o Mermaid o lê como fim de comando e o diagrama inteiro deixa de aparecer.

**Mantenha atualizado:** quem implementa uma história ajusta, na mesma PR, o caso de uso dela (cor de situação) e o diagrama de sequência correspondente, se o fluxo mudou. Os diagramas marcados como *planejado* são o contrato da história: se a implementação precisar de outro caminho, mude o diagrama e explique na PR.

## Casos de uso

Situação de cada caso de uso, pela cor:

```mermaid
flowchart LR
    A([Concluído]):::feito ~~~ B([Em revisão, PR aberta]):::revisao ~~~ C([A fazer]):::afazer
    classDef feito fill:#d1f2dc,stroke:#1e7a44,color:#0f3d22
    classDef revisao fill:#fff1c2,stroke:#9a6b00,color:#4d3500
    classDef afazer fill:#eeeeee,stroke:#8a8a8a,color:#333333
```

Para mudar a situação, troque a classe do caso de uso nas linhas `class ... feito` / `revisao` / `afazer` no fim do bloco.

### Configuração e inscrição de chapas

Antes do dia da votação: a comissão (admin) monta a eleição e o eleitorado; o representante de chapa inscreve a chapa pelo formulário público, sem login (D14).

```mermaid
flowchart LR
    ADM["Admin<br/>(comissão eleitoral)"]:::ator
    REP["Representante<br/>de chapa"]:::ator

    subgraph SIS["Sistema de votação"]
        UC01(["UC01 · Entrar no sistema<br/>H1"])
        UC02(["UC02 · Gerenciar usuários<br/>H1"])
        UC03(["UC03 · Cadastrar eleição e períodos<br/>H2"])
        UC04(["UC04 · Ativar eleição<br/>H2"])
        UC05(["UC05 · Definir cargos<br/>H13"])
        UC06(["UC06 · Cadastrar eleitores<br/>H4"])
        UC07(["UC07 · Importar eleitores por CSV<br/>H4"])
        UC08(["UC08 · Cadastrar terminais<br/>H5"])
        UC09(["UC09 · Inscrever chapa pelo formulário<br/>H14"])
        UC10(["UC10 · Conferir aluno pela matrícula<br/>H14"])
        UC11(["UC11 · Importar chapas por CSV<br/>H15"])
        UC24(["UC24 · Conferir e confirmar a importação<br/>H4, H15"])
        UC12(["UC12 · Homologar ou indeferir chapa<br/>H3"])
        UC13(["UC13 · Consultar auditoria<br/>H11"])
    end

    ADM --- UC01
    ADM --- UC02
    ADM --- UC03
    ADM --- UC04
    ADM --- UC05
    ADM --- UC06
    ADM --- UC07
    ADM --- UC08
    ADM --- UC11
    ADM --- UC12
    ADM --- UC13
    REP --- UC09

    UC07 -. "«extend»" .-> UC06
    UC09 -. "«include»" .-> UC10
    UC11 -. "«include»" .-> UC24
    UC07 -. "«include»" .-> UC24

    class UC01,UC02,UC03,UC04 feito
    class UC05,UC06,UC07,UC08,UC09,UC10,UC11,UC24 revisao
    class UC12,UC13 afazer
    classDef ator fill:#2b2b2b,stroke:#2b2b2b,color:#ffffff
    classDef feito fill:#d1f2dc,stroke:#1e7a44,color:#0f3d22
    classDef revisao fill:#fff1c2,stroke:#9a6b00,color:#4d3500
    classDef afazer fill:#eeeeee,stroke:#8a8a8a,color:#333333
```

### Dia da votação e resultado

O eleitor não faz login: o mesário o libera para um terminal pareado (D4, D16). O admin também pode fazer tudo o que o mesário faz (generalização: o admin **é um** mesário com mais permissões, D15).

```mermaid
flowchart LR
    ADM["Admin<br/>(comissão eleitoral)"]:::ator
    MES["Mesário"]:::ator
    ELE["Eleitor"]:::ator
    ADM -- "generaliza<br/>(também é mesário)" --> MES

    subgraph SIS["Sistema de votação"]
        UC01(["UC01 · Entrar no sistema<br/>H1"])
        UC14(["UC14 · Ativar computador como terminal<br/>H5"])
        UC15(["UC15 · Liberar eleitor<br/>H6"])
        UC16(["UC16 · Conferir nome pela matrícula<br/>H6"])
        UC17(["UC17 · Cancelar sessão<br/>H6"])
        UC18(["UC18 · Acompanhar a mesa<br/>H9"])
        UC19(["UC19 · Aguardar liberação na urna<br/>H7"])
        UC20(["UC20 · Votar em chapa, branco ou nulo<br/>H8"])
        UC21(["UC21 · Confirmar a escolha<br/>H8"])
        UC22(["UC22 · Apurar resultado<br/>H10"])
        UC23(["UC23 · Exportar a ata<br/>H10"])
    end

    MES --- UC01
    MES --- UC14
    MES --- UC15
    MES --- UC17
    MES --- UC18
    ELE --- UC19
    ELE --- UC20
    ADM --- UC22
    ADM --- UC23

    UC15 -. "«include»" .-> UC16
    UC17 -. "«extend»" .-> UC15
    UC20 -. "«include»" .-> UC21
    UC23 -. "«extend»" .-> UC22

    class UC01 feito
    class UC14 revisao
    class UC15,UC16,UC17,UC18,UC19,UC20,UC21,UC22,UC23 afazer
    classDef ator fill:#2b2b2b,stroke:#2b2b2b,color:#ffffff
    classDef feito fill:#d1f2dc,stroke:#1e7a44,color:#0f3d22
    classDef revisao fill:#fff1c2,stroke:#9a6b00,color:#4d3500
    classDef afazer fill:#eeeeee,stroke:#8a8a8a,color:#333333
```

**Como ler:** linha cheia liga o ator ao caso de uso que ele executa. `«include»` é um passo que **sempre** acontece dentro do caso de uso de origem (não dá para liberar sem conferir o nome). `«extend»` é um comportamento **opcional** que estende o caso de uso de destino (importar por CSV é outra forma de cadastrar eleitores; cancelar é opcional depois de liberar).

## Diagramas de sequência

| Diagrama | Histórias | Situação |
| --- | --- | --- |
| [S1 · Importar eleitores por CSV](#s1--importar-eleitores-por-csv-h4) | H4, D19 | Implementado (PR #54; conferência na D19) |
| [S2 · Ativar o computador como terminal](#s2--ativar-o-computador-como-terminal-h5-d16) | H5, D16 | Implementado (PR #55) |
| [S3 · Inscrever chapa pelo formulário](#s3--inscrever-chapa-pelo-formulário-h14) | H14 | Implementado (PR #58) |
| [S6 · Importar chapas por CSV](#s6--importar-chapas-por-csv-h15-d19) | H15, D19 | Implementado (PR #59) |
| [S4 · Liberar um eleitor](#s4--liberar-um-eleitor-h6) | H6 | Planejado |
| [S5 · Urna aguarda e o eleitor vota](#s5--urna-aguarda-e-o-eleitor-vota-h7-h8) | H7, H8 | Planejado |

Nos planejados, nomes de colunas como `situacao` e `expira_em` são sugestões (`MODELO_DADOS.md`): a equipe decide na história.

### S1 · Importar eleitores por CSV (H4)

Mesmo fluxo das chapas (D19, S6): enviar só confere; o admin vê os campos com erro, o que é novo e o que vai mudar, e escolhe cancelar (rollback), ignorar as linhas com erro ou confirmar. Matrícula já cadastrada atualiza, não duplica (D17).

```mermaid
sequenceDiagram
    autonumber
    actor A as Admin
    participant C as ImportacaoEleitoresController
    participant I as ImportadorEleitores
    participant S as ArquivoEmConferencia
    participant DB as Banco

    A->>C: POST /admin/eleitores/importar (arquivo, CSRF)
    C->>I: analisar(conteúdo)
    I->>I: LeitorCsv: BOM, Windows-1252, separador<br/>cabeçalho com apelidos, colunas extras ignoradas
    alt arquivo inválido (sem cabeçalho, vazio, binário)
        I-->>C: ArquivoInvalido
        C-->>A: volta com os erros do arquivo (nada gravado)
    else arquivo legível
        loop cada linha
            I->>I: limpa e valida (EleitorRegras, Nomes)
        end
        I->>I: matrícula repetida no arquivo marca todas
        I->>DB: SELECT eleitores das matrículas do arquivo
        I->>I: novo, atualizar (antes e depois) ou igual
        C->>S: guarda o arquivo
        C-->>A: 302 /admin/eleitores/importar/conferir
    end

    A->>C: GET conferir
    C-->>A: linhas com erro, cadastros que mudam, novos,<br/>opções cancelar, ignorar e confirmar (com a assinatura)

    alt cancelar (rollback)
        A->>C: POST confirmar (acao=cancelar)
        C->>S: apaga o arquivo
        C-->>A: "nenhum aluno foi gravado"
    else ignorar os erros ou confirmar
        A->>C: POST confirmar (acao, assinatura)
        C->>I: importar(conteúdo, assinatura, ignorarErros)
        I->>DB: BEGIN
        I->>I: analisar de novo
        alt assinatura diferente ou UNIQUE recusado
            I->>DB: ROLLBACK
            C-->>A: volta para a conferência atualizada
        else igual à conferência
            I->>DB: INSERT dos novos e UPDATE dos que mudam
            I->>DB: COMMIT
            C->>S: apaga o arquivo
            C-->>A: relatório: cadastrados, atualizados,<br/>sem alteração e ignorados
        end
    end
```

### S2 · Ativar o computador como terminal (H5, D16)

O banco guarda só o hash do token; o navegador é deslogado na mesma resposta, para o eleitor nunca encontrar uma sessão de mesário aberta.

```mermaid
sequenceDiagram
    autonumber
    actor M as Mesário (no computador da urna)
    participant T as TerminalAtivacaoController
    participant W as Middleware terminal
    participant U as UrnaController
    participant DB as Banco

    M->>T: GET /terminal/ativar (logado)
    T->>DB: SELECT terminais
    T-->>M: "Qual terminal é este computador?"
    M->>T: POST /terminal/ativar (terminal_id, CSRF)
    T->>T: token = Str::random(64)
    T->>DB: UPDATE terminais SET token_hash = sha256(token), ativado_em = agora
    Note over DB: o computador pareado antes perde o acesso
    T->>T: logout e invalida a sessão
    T-->>M: 302 /urna + cookie "terminal" (criptografado,<br/>HttpOnly, SameSite=Strict, 24 h)

    M->>W: GET /urna (com o cookie)
    W->>DB: terminal com token_hash = sha256(cookie)<br/>e ativado_em há menos de 24 h?
    alt encontrado
        W->>U: segue, com o terminal na requisição
        U-->>M: 200 "Aguardando liberação" (Terminal N)
    else sem cookie, cookie antigo, desativado ou expirado
        W-->>M: 403 "Este computador não está ativado como terminal. Chame o mesário."
    end
```

### S3 · Inscrever chapa pelo formulário (H14)

Página pública, sem login. Chapa e candidatos entram juntos ou não entram (D14). As regras ficam em `App\Support\InscricaoDeChapa`, reaproveitadas pelo CSV (H15, D18).

```mermaid
sequenceDiagram
    autonumber
    actor R as Representante de chapa
    participant F as InscricaoController
    participant I as InscricaoDeChapa
    participant DB as Banco

    R->>F: GET /inscricao
    F->>DB: eleição ativa e seus cargos
    alt fora do período de inscrição
        F-->>R: datas de abertura e fim, sem formulário
    else inscrições abertas
        F-->>R: formulário com um campo de matrícula por cargo
        loop ao sair de cada campo de matrícula
            R->>F: GET /inscricao/aluno?matricula= (até 30 por minuto por IP)
            F->>DB: SELECT nome FROM eleitores
            F-->>R: só o nome do aluno, ou "matrícula não encontrada"
        end
        R->>F: POST /inscricao (nome da chapa, contato, matrículas, CSRF)
        Note over F: throttle: 5 envios por minuto por IP
        F->>I: inscrever(eleição, nome, matrículas, contato, formulário)
        I->>DB: BEGIN e trava a linha da eleição (FOR UPDATE)
        I->>I: confere o período NO SERVIDOR e valida:<br/>cargo vazio, matrícula inexistente ou repetida,<br/>aluno já em outra chapa, nome repetido
        alt inválido
            I->>DB: ROLLBACK
            I-->>F: InscricaoRecusada (uma mensagem por campo)
            F-->>R: volta com o erro em cada campo
        else válido
            I->>DB: INSERT chapas (próximo número livre, situação pendente)
            I->>DB: INSERT candidatos (um por cargo)
            I->>DB: COMMIT
            opt o banco recusou por UNIQUE (número, nome ou aluno)
                I->>I: tenta de novo, até 3 vezes
            end
            I-->>F: chapa
            F-->>R: 302 /inscricao/comprovante (o id fica na sessão de quem inscreveu)
        end
    end
```

### S6 · Importar chapas por CSV (H15, D19)

Enviar só confere. O admin vê cada campo com erro e escolhe cancelar (rollback), ignorar as chapas com erro ou confirmar. A confirmação grava numa transação só e desfaz tudo se a situação mudou desde a conferência.

```mermaid
sequenceDiagram
    autonumber
    actor A as Admin
    participant C as ImportacaoChapasController
    participant I as ImportadorChapas
    participant R as InscricaoDeChapa
    participant S as Storage e sessão
    participant DB as Banco

    A->>C: POST /admin/chapas/importar (arquivo, CSRF)
    C->>I: analisar(eleição, conteúdo)
    I->>I: LeitorCsv: BOM, Windows-1252, separador
    I->>I: cabeçalho: colunas da chapa e colunas de cargo,<br/>nomes comparados e padronizados (Nomes)
    alt cabeçalho ou arquivo inválido
        I-->>C: ArquivoInvalido (lista de erros)
        C-->>A: volta com os erros do arquivo (nada gravado)
    else arquivo legível
        loop cada linha (uma chapa)
            I->>R: conferir(nome, cargos, matrículas)
            R->>DB: SELECT eleitores, users, candidatos
            R-->>I: erros por cargo ou nome
        end
        I->>I: repetidos no arquivo marcam as duas chapas
        C->>S: guarda o arquivo e o id da eleição
        C-->>A: 302 /admin/chapas/importar/conferir
    end

    A->>C: GET conferir
    C->>I: analisar de novo (situação atual)
    C-->>A: linhas com os campos com erro, ajustes de nome,<br/>opções cancelar, ignorar e confirmar (com a assinatura)

    alt cancelar (rollback)
        A->>C: POST confirmar (acao=cancelar)
        C->>S: apaga o arquivo
        C-->>A: "nenhuma chapa foi gravada"
    else ignorar os erros ou confirmar
        A->>C: POST confirmar (acao, assinatura)
        C->>I: importar(conteúdo, assinatura, ignorarErros)
        I->>DB: BEGIN
        I->>R: travar(eleição)
        R->>DB: SELECT eleicoes FOR UPDATE e confere o período
        I->>I: analisar de novo, com a trava
        alt assinatura diferente, período fechado ou UNIQUE recusado
            I->>DB: ROLLBACK
            C-->>A: volta para a conferência atualizada
        else igual à conferência
            opt eleição sem cargos
                I->>DB: INSERT cargos (ordem das colunas)
            end
            loop cada chapa pronta
                I->>R: criar(número do arquivo, origem csv)
                R->>DB: INSERT chapas (pendente) e candidatos
            end
            I->>DB: COMMIT
            C->>S: apaga o arquivo
            C-->>A: relatório: importadas, ignoradas e cargos criados
        end
    end
```

### S4 · Liberar um eleitor (H6)

*Planejado.* Uma linha por eleitor e eleição (`UNIQUE`): liberar de novo reaproveita a linha, nunca cria outra.

```mermaid
sequenceDiagram
    autonumber
    actor Me as Mesário
    participant P as Painel do mesário
    participant DB as Banco

    Me->>P: digita a matrícula
    P->>DB: SELECT eleitor
    P-->>Me: nome e turma para conferir (ou "não encontrado")
    Me->>P: POST liberar (matrícula, terminal, CSRF)
    P->>P: votação aberta? terminal ativado?<br/>terminal sem sessão aberta?
    P->>DB: SELECT sessão (eleição, eleitor)
    alt já votou
        P-->>Me: recusa: "eleitor já votou"
    else nenhuma sessão
        P->>DB: INSERT sessão (aberta, terminal, mesário, expira em 30 min)
        P-->>Me: "Terminal N liberado para Fulano"
    else sessão expirada ou cancelada
        P->>DB: UPDATE a MESMA linha (aberta, terminal, mesário, horários)
        P-->>Me: "Terminal N liberado para Fulano"
    end
    P->>DB: auditoria: liberação (H11)
```

### S5 · Urna aguarda e o eleitor vota (H7, H8)

*Planejado.* O ponto crítico é a ordem da transação: **reivindicar a sessão antes de gravar o voto**. Assim duplo clique, F5 ou duas abas não geram dois votos (`MODELO_DADOS.md`, "Registro de um voto").

```mermaid
sequenceDiagram
    autonumber
    actor E as Eleitor
    participant N as Urna (navegador pareado)
    participant U as UrnaController
    participant DB as Banco

    loop a cada 3 segundos
        N->>U: GET /urna/status (cookie do terminal)
        U->>DB: sessão aberta e não expirada deste terminal?
        U-->>N: livre, ou liberado com o nome do eleitor
    end
    N-->>E: nome do eleitor e chapas HOMOLOGADAS,<br/>cada uma com seus candidatos por cargo
    E->>N: escolhe a chapa (ou branco/nulo)
    N-->>E: "Você escolheu a chapa 2. Confirmar?"
    E->>N: Confirmar
    N->>U: POST /urna/votar (chapa, CSRF, cookie)
    U->>DB: BEGIN
    U->>DB: UPDATE sessão SET situacao = votou<br/>WHERE terminal = o do cookie AND situacao = aberta<br/>AND expira_em > agora
    alt 0 linhas (segundo clique, expirou ou foi cancelada)
        U->>DB: ROLLBACK
        U-->>N: recusa, e a urna volta a "aguardando"
    else 1 linha
        U->>DB: INSERT votos (eleicao_id DA SESSÃO, chapa_id)
        Note over DB: o voto não tem eleitor, terminal nem horário (D3).<br/>Chapa de outra eleição: a FK composta rejeita e tudo é desfeito
        U->>DB: COMMIT
        U-->>N: "Voto registrado"
        N-->>E: confirmação e, após 5 s, volta a "aguardando"
    end
    Note over U,DB: nada que ligue a chapa ao eleitor vai para log, auditoria ou sessão HTTP
```
