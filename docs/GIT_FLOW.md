# Git Flow do projeto

> Sem terminal? O mesmo fluxo, com GitHub Desktop e o site, está em `GITHUB_DESKTOP.md`.

Três regras, aplicadas pelo próprio GitHub (rulesets do repositório), não só combinadas:

1. **`main` e `staging` são do professor.** Só ele faz push ou merge nelas. Alunos nunca as tocam, nem por PR.
2. **Ninguém commita direto em `develop`.** Todo código entra por Pull Request.
3. **PR para `develop` só é mergeado com o CI verde** (check `phpunit`) **e pelo menos uma aprovação** de outro membro. Push forçado e exclusão das duas branches estão bloqueados.

## Branches

| Branch      | Papel                                                             |
| ----------- | ----------------------------------------------------------------- |
| `main`      | Produção na Hostinger. Só o professor mexe; recebe merge de `staging` quando a homologação aprova. |
| `staging`   | Homologação. Só o professor mexe; recebe merge de `develop` ao fim da sprint para a equipe testar como se fosse produção. |
| `develop`   | Integração dos alunos. Branch padrão do repositório. Tudo que está pronto e testado. |
| `feature/*` | Uma história de usuário (ou parte dela). Nasce e morre em `develop`. |
| `fix/*`     | Correção de bug encontrado em `develop`.                          |
| `hotfix/*`  | Correção urgente em produção. Nasce de `main`, volta para `main` **e** `develop`. |

Nome da branch = tipo + número da issue + descrição curta:

```
feature/12-liberar-votacao
fix/18-duplo-clique-no-voto
```

## Fluxo completo de uma história

```bash
# 1. Atualizar develop
git checkout develop
git pull origin develop

# 2. Criar a branch da história
git checkout -b feature/12-liberar-votacao

# 3. Trabalhar em commits pequenos
git add .
git commit -m "feat: formulário de busca de eleitor por matrícula"
git commit -m "feat: liberar sessão de votação no terminal escolhido"
git commit -m "test: cobrir liberação de sessão e eleitor já votou"

# 4. Rodar os testes ANTES de subir
php artisan test

# 5. Subir e abrir o PR (base: develop)
git push -u origin feature/12-liberar-votacao
```

No GitHub: **Pull requests → New** → base `develop` ← compare `feature/12-...`.
No título use o mesmo padrão dos commits; na descrição escreva `closes #12` (fecha a issue no merge) e diga **como testar**.

Depois do merge:

```bash
git checkout develop
git pull origin develop
git branch -d feature/12-liberar-votacao
```

## Pipeline (develop → staging → main)

```
feature/* ──PR──▶ develop ──PR──▶ staging ──PR──▶ main
 (alunos)        (integração)   (homologação)   (produção)
```

1. Ao fim da sprint, **o professor** abre um PR `staging` ← `develop` e mescla. Isso publica no ambiente de homologação (ver `DEPLOY_HOSTINGER.md`).
2. QA e a equipe testam em homologação. Bug encontrado vira issue `bug`, corrigida em `fix/*` a partir de `develop`, e o ciclo repete.
3. Aprovada a homologação, **o professor** abre um PR `main` ← `staging` e mescla. Isso publica em produção.

Alunos não têm permissão para mesclar em `staging` nem em `main`; o GitHub bloqueia. `staging` e `main` nasceram vazias: só recebem conteúdo por esses PRs, então o histórico delas é a lista de releases.

## Padrão de commits (Conventional Commits)

```
<tipo>: <descrição curta no imperativo, sem ponto final>
```

| Tipo       | Quando usar                                       |
| ---------- | ------------------------------------------------- |
| `feat`     | Nova funcionalidade                               |
| `fix`      | Correção de bug                                   |
| `test`     | Adiciona ou corrige testes                        |
| `refactor` | Mudança interna sem alterar comportamento         |
| `style`    | Formatação, CSS, sem mudança de lógica            |
| `docs`     | Documentação                                      |
| `chore`    | Configuração, dependências, CI                    |

Commit pequeno é commit bom. Se a descrição precisa de "e", provavelmente são dois commits.

## Resolvendo conflito

```bash
git checkout feature/minha-branch
git pull origin develop          # traz o que mudou em develop
# resolver os arquivos marcados com <<<<<<< / >>>>>>>
git add .
git commit -m "chore: resolver conflito com develop"
git push
```

## Resumo visual

```
main    ─────────────────────────────●─────────────────────●──── (produção)
                                    ↑ PR                   ↑ PR
staging ────────────────────────●───●──────────────────●───●──── (homologação)
                               ↑ PR                    ↑ PR
develop ───●─────●──────●──────●──────●────────●───────●──────── (integração)
           ↑     ↑      ↑             ↑
feature/12 ●──●──┘      │             │
feature/13 ●──●──●──────┘             │
fix/18-... ●──●───────────────────────┘
```
