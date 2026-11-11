---
name: implementar-historia
description: Roteiro para implementar uma história de usuário (H1–H15) do votacao-digitech, da branch até a PR. Use quando pedirem "vamos pra hN", "implementa a história X" ou para continuar uma história.
---

# Implementar uma história

O backlog está em `docs/HISTORIAS_USUARIO.md` (critérios com `[ ]`/`[x]`); cada história tem uma issue no GitHub (`gh issue list --search "H15"`). Decisões em `docs/DECISOES.md` (D1…), modelo em `docs/MODELO_DADOS.md`, diagramas em `docs/DIAGRAMAS.md`.

## 1. Branch

- Parta de `develop`: `git checkout -b feature/<issue>-<nome-curto>` (ex.: `feature/51-importar-chapas`).
- **Se o app estiver no ar para alguém olhar** (`php artisan serve` na pasta principal), trabalhe num **git worktree** (`git worktree add ../votacao-digitech-hN -b feature/...  develop`), com `vendor` **copiado** (`cp -a`, não link simbólico: o autoload do Composer resolveria as classes da pasta original) e `composer dump-autoload`. O banco MySQL é o mesmo: avise antes de rodar uma migration nele.
- **Dependência de PR ainda aberta:** faça merge da branch da qual depende (não rebase, não mude a base da PR). A PR continua contra `develop`, e a descrição diz qual commit revisar. Mudar a base de uma PR descarta as aprovações.

## 2. Implementar (convenções do projeto)

- Laravel 12, Blade + Bootstrap 5, **sem Node** (D2). CSS em `public/css/digitech.css`, com tokens `--dt-*`.
- Chaves UUID (`HasUuids`) nas tabelas do domínio; `users` é `bigint`.
- Uma migration nova por mudança (`2026_11_11_0000NN_...`), com docblock explicando o porquê; nunca edite migration já mesclada.
- Regras de negócio em `app/Support/*` (ex.: `InscricaoDeChapa`, `CargosDaEleicao`), reaproveitadas por formulário e CSV. O controller só traduz HTTP.
- Concorrência: quem mexe em cargos ou chapas trava a linha da eleição (`lockForUpdate`) dentro de `DB::transaction` (D13).
- Componentes Blade: `x-botao`, `x-campo`, `x-cartao`, `x-alerta`, `x-selo`, `x-indicador`, `x-estado-vazio`. Admin usa `layouts.painel` (menu em `resources/views/layouts/painel.blade.php`: `[rótulo, ícone, rota|null, história]`).
- Textos da interface e mensagens de erro em português, dizendo **o que fazer**, não só o que deu errado.
- Importação de arquivo: siga a skill `importacao-csv`.

## 3. Testes

- `php artisan test` (SQLite em memória). Um `tests/Feature/<Assunto>Test.php` por história, cobrindo **cada critério** da história; restrições de banco em `ConstraintsTest`.
- Arquivos de exemplo em `tests/Fixtures/<assunto>/` (o `.gitattributes` guarda esses arquivos byte a byte).
- Em redirecionamento, use `assertRedirect(<url exata>)`, não só `assertRedirect()`: o teste não envia cabeçalhos de verdade, e um `Location` quebrado (ex.: concatenar texto numa `RedirectResponse` em vez de na URL) só aparece no navegador.
- `vendor/bin/pint` antes de commitar.

## 4. Documentação (na mesma PR)

- `HISTORIAS_USUARIO.md`: marque `[x]` o que foi feito e anote o que ficou para outra história.
- `DECISOES.md`: decisão nova (`D<n>`) quando escolher algo que a equipe precisa saber.
- `MODELO_DADOS.md`: tabela de atributos e diagrama ER (`erDiagram`) quando mudar o banco.
- `DIAGRAMAS.md`: caso de uso (classe `feito`/`revisao`/`afazer`) e diagrama de sequência da história.
- **Mermaid:** sem `;` no texto dos diagramas de sequência (quebra o parser). Valide renderizando com mermaid@10.9.1 no Chrome headless antes de commitar.

## 5. Ver funcionando

- `php artisan migrate` no MySQL local, `php artisan serve --port=8765`, login com `admin@digitech.local` / `admin123` (seed).
- Pela linha de comando: `curl` com cookie jar para logar e navegar; screenshot com `google-chrome --headless=new --screenshot` em 1440 px e 390 px (celular).
- Para matar o servidor: `pkill -f "artisan serve --port=876[5]"` (os colchetes evitam que o `pkill` mate o próprio shell).
- Teste as ações de verdade (POST com o `_token` da página), não só as telas.
- Apague do banco local os dados de teste que criou, ou devolva-os ao estado anterior.

## 6. PR

- Commit `feat: <o que faz> (Hn)`; push; PR para `develop` com `closes #<issue>`.
- **Revisores: todos os colaboradores** do repositório (`gh api repos/{owner}/{repo}/collaborators`).
- Use `GH_TOKEN=$(gh auth token --user prof-ronildo-unicatolica)` sem trocar a conta ativa do `gh`.
- `develop` exige 1 aprovação e o check `phpunit`. Merge com bypass de admin **só com autorização explícita, a cada vez**.
