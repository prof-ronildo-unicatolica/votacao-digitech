---
name: importacao-csv
description: Padrão de ingestão de CSV do votacao-digitech (leitura tolerante, nomes padronizados, conferência antes de gravar, cancelar/ignorar erros/confirmar com rollback, testes com arquivos limpos, alterados e mal formatados). Use ao criar ou mudar qualquer importação de arquivo (chapas H15, eleitores H4, outras).
---

# Importação de CSV

Referência: H15 (`App\Support\ImportadorChapas`, `ImportacaoChapasController`, decisão D19). O CSV de eleitores (H4, `ImportadorEleitores`) é mais antigo e grava direto; ao mexer nele, considere migrar para este padrão.

## Leitura: `App\Support\LeitorCsv`

Sempre leia por ele, nunca com `fgetcsv` solto:

- Remove BOM, converte Windows-1252 (Excel), detecta `;` ou `,` pela linha do cabeçalho, aceita CRLF e campos entre aspas.
- Recusa conteúdo binário (ex.: `.xlsx` renomeado) com mensagem de como exportar.
- `LeitorCsv::vazia($campos)` para pular linhas em branco e `;;;`.
- Cabeçalho comparado por `Nomes::chave` (sem acento, maiúsculas, espaço, hífen, sublinhado) e com apelidos (`numero`, `Número da chapa`, `E-mail`…).

## Nomes: `App\Support\Nomes`

- `chave()` para comparar ("VICE PRESIDENTE" = "Vice-presidente").
- `formatar()` para gravar: tira espaços sobrando, "1o"/"1°" vira "1º", e arruma maiúsculas e minúsculas só quando o texto veio todo em maiúsculas ou todo em minúsculas (conectivos em minúscula, sigla curta mantida). Texto misto é respeitado.
- **Mostre cada ajuste na conferência**: o admin precisa ver o que vai ser gravado.

## Fluxo: conferir antes de gravar

1. **Enviar:** valida o upload (`mimes:csv,txt`, tamanho), roda `analisar()` **sem gravar** e guarda o arquivo em `Storage::disk('local')` (`importacoes/`), com o caminho e o id da eleição na sessão. Erro do arquivo inteiro (`ArquivoInvalido`, uma lista de mensagens) volta para o upload.
2. **Conferir:** analisa de novo (situação atual) e mostra cada linha, com **as células com erro destacadas** (`.dt-celula-erro`) e o motivo como "Linha N, coluna: motivo", em ordem de coluna.
3. **Decidir:** cancelar (rollback: apaga o arquivo, nada foi gravado), ignorar os erros (importa só as linhas prontas) ou confirmar. Com erros, a escolha é obrigatória. O formulário manda de volta a `assinatura()` da análise.
4. **Confirmar:** `DB::transaction` + trava (`lockForUpdate` na eleição), analisa de novo e compara a assinatura. Diferente, período fechado ou `UniqueConstraintViolationException` significam **desfazer tudo** e voltar para a conferência com aviso. Igual, grava e apaga o arquivo.
5. **Relatório:** o que entrou, o que ficou de fora (linha e motivo) e o que foi criado.

Regras de negócio **não** ficam no importador: ele chama a mesma classe do formulário (ex.: `InscricaoDeChapa::conferir/travar/criar`), para as duas portas de entrada recusarem as mesmas coisas.

## Integridade da matrícula

Formato `EleitorRegras::MATRICULA`; espaços no meio removidos; aviso próprio para notação científica do Excel (`2,02E+06`); aluno cadastrado; um cargo por aluno; uma chapa por aluno (também entre linhas do mesmo arquivo: marque **todas** as envolvidas); mesário ou admin (`users.matricula`) não é candidato.

## Testes: arquivos de verdade

Fixtures em `tests/Fixtures/<assunto>/`, um arquivo por cenário, e um teste para cada:

- **Limpo**, também com BOM e convertido para Windows-1252 no próprio teste (`mb_convert_encoding`).
- **Alterado** (como sai de planilhas reais): vírgula, CRLF, linhas em branco e `,,,`, CAIXA ALTA, espaços, colunas fora de ordem, apelidos no cabeçalho, campo entre aspas com separador dentro.
- **Mal formatado:** cabeçalho sem coluna obrigatória, coluna repetida, coluna sem título com dados, linha com colunas a mais, só cabeçalho, vazio, `.xlsx` renomeado, separador tab, número, e-mail e matrícula inválidos.
- **Regras:** cada recusa da regra de negócio, repetição dentro do arquivo e repetição com o banco.
- **Fluxo:** enviar e conferir não gravam; cancelar não grava e apaga o arquivo; com erros, sem escolha é recusado; ignorar grava só as prontas; **mudança entre conferir e confirmar desfaz tudo**; fora do período; perfil sem acesso.
- `Storage::fake('local')` e verifique que nenhum arquivo sobra.
- O **modelo para baixar** (`docs/exemplos/*.csv`) também é importado num teste: o modelo nunca pode estar quebrado.
