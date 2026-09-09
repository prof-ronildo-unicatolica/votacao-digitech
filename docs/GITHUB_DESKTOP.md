# GitHub e GitHub Desktop, passo a passo

Para quem nunca usou Git. Faz o mesmo fluxo de `GIT_FLOW.md`, sem terminal. Quem preferir terminal usa aquele documento; o resultado no GitHub é idêntico.

## 1. Conta e acesso ao repositório

1. Crie uma conta em <https://github.com> com o e-mail institucional (dá direito ao [GitHub Student Pack](https://education.github.com/pack)).
2. Envie seu usuário ao professor. Ele adiciona você em **Settings → Collaborators** do repositório `prof-ronildo-unicatolica/votacao-digitech`.
3. Aceite o convite que chega por e-mail.

## 2. Instalar o GitHub Desktop

1. Baixe em <https://desktop.github.com> (Windows e macOS; no Linux use o terminal ou o [fork da comunidade](https://github.com/shiftkey/desktop)).
2. Instale e faça login: **File → Options → Accounts → Sign in**.
3. Em **Options → Git**, confira nome e e-mail. Eles aparecem em cada commit seu.

## 3. Clonar o projeto

1. **File → Clone repository → GitHub.com**, escolha `votacao-digitech`.
2. **Local path**: uma pasta sem espaços nem acento, por exemplo `C:\projetos`.
3. **Clone**. Depois siga `INSTALACAO_XAMPP.md` ou `INSTALACAO_LAMP.md` a partir do passo "instalar dependências".

## 4. Começar uma história (branch)

Antes: na aba **Issues** do site, abra a issue da história, clique em **Assignees → assign yourself** e leia os critérios de aceite.

1. No topo, em **Current branch**, escolha `develop`.
2. **Fetch origin** e, se aparecer, **Pull origin**, para trazer o que a equipe já subiu.
3. **Current branch → New branch**. Nome no padrão: `feature/12-liberar-votacao` (12 = número da issue). Confirme que ela é criada **a partir de `develop`**.
4. **Publish branch** (botão azul) para ela existir no GitHub.

Nunca trabalhe direto em `main` ou `develop`. Se o **Current branch** mostrar uma dessas, crie a branch antes de editar. `main` é só do professor: o GitHub recusa push e merge de qualquer outra pessoa.

## 5. Salvar o trabalho (commit)

1. Edite os arquivos no VS Code. O GitHub Desktop lista o que mudou em **Changes**.
2. Marque só os arquivos da história (desmarque `.env` ou arquivos de teste locais, se aparecerem).
3. Em **Summary**, escreva no padrão `tipo: descrição`, por exemplo `feat: buscar eleitor por matrícula`.
4. **Commit to feature/12-...**.
5. **Push origin** para enviar ao GitHub. Faça isso no fim de cada sessão de trabalho, mesmo que não tenha terminado.

Commits pequenos e frequentes. Se a descrição precisa de "e", são dois commits.

## 6. Abrir o Pull Request

1. Antes: rode `php artisan test` no terminal do VS Code. Se falhar, corrija antes de abrir o PR.
2. No GitHub Desktop, **Branch → Create pull request** (ou o botão **Preview Pull Request**). Abre o site.
3. Confira: **base: `develop`** ← **compare: `feature/12-...`**. Se a base vier como `main`, troque.
4. Título igual ao commit principal. Na descrição: `closes #12` (fecha a issue no merge), o que foi feito e **como testar**.
5. **Reviewers**: escolha alguém de outro perfil (ver `ROADMAP.md`). **Create pull request**.
6. No quadro (aba **Projects**), a issue vai para **Em revisão**.

O CI roda sozinho. Uma marca verde ao lado do último commit significa testes passando; uma vermelha, clique em **Details** para ver o que quebrou, corrija, faça commit e push: o PR atualiza sozinho.

## 7. Revisar o PR de um colega

1. No site, aba **Pull requests**, abra o PR. Aba **Files changed**.
2. Para comentar uma linha, passe o mouse sobre ela e clique no **+**.
3. Baixe a branch para testar: no GitHub Desktop, **Current branch → escolha a branch do colega** e rode o projeto.
4. **Review changes** (botão verde no canto): **Approve** se está ok, **Request changes** se precisa ajustar. Diga o quê.
5. Quem aprovou clica **Merge pull request → Confirm merge** e depois **Delete branch**.

## 8. Depois do merge

1. No GitHub Desktop, **Current branch → `develop`**, depois **Fetch origin** e **Pull origin**.
2. A branch da história pode ser apagada: **Branch → Delete**.
3. A issue fecha sozinha por causa do `closes #12`.

## 9. Conflitos

Aparecem quando duas pessoas mudaram a mesma linha. O GitHub Desktop avisa ao fazer **Pull** ou ao abrir o PR.

1. Na sua branch, **Branch → Update from develop** (ou **Merge into current branch → develop**).
2. O Desktop lista os arquivos em conflito. Abra cada um no VS Code: ele mostra **Current change** (seu) e **Incoming change** (do colega) com botões para escolher um, outro ou os dois.
3. Salve, volte ao Desktop, faça commit (`chore: resolver conflito com develop`) e push.

Se travar, não force nada: chame quem fez a outra alteração e resolvam juntos.

## 10. Erros comuns

| Situação                                                     | O que fazer                                                                                     |
| ------------------------------------------------------------ | ----------------------------------------------------------------------------------------------- |
| Editei em `develop` sem querer                               | Antes de commitar: **New branch** com as mudanças; o Desktop pergunta se quer levá-las para a nova branch. Diga sim. |
| Commitei em `develop` sem querer (ainda sem push)            | **Branch → New branch**, depois em `develop`: **History → clique direito no commit → Undo**.    |
| O `.env` apareceu em Changes                                 | Não marque. Ele está no `.gitignore`; se aparece, avise o professor.                            |
| PR aponta para `main`                                        | No PR, **Edit** ao lado do título → troque a base para `develop`.                               |
| "This branch is out-of-date with the base branch"            | **Branch → Update from develop** no Desktop, resolva conflitos se houver, push.                 |
| Push recusado (`rejected`)                                   | Alguém subiu na mesma branch. **Pull** primeiro, depois **Push**.                               |

## Vocabulário

| Termo         | Significa                                                                 |
| ------------- | ------------------------------------------------------------------------- |
| Repositório   | A pasta do projeto com todo o histórico                                   |
| Clone         | Sua cópia local do repositório                                            |
| Branch        | Uma linha de trabalho paralela; aqui, uma história                        |
| Commit        | Um "salvar" com mensagem, que fica no histórico                           |
| Push / Pull   | Enviar seus commits para o GitHub / trazer os dos outros                  |
| Pull Request  | Pedido para juntar sua branch em `develop`, com revisão                   |
| Merge         | A junção em si                                                            |
| CI            | Robô que roda os testes a cada push (`.github/workflows/tests.yml`)       |
