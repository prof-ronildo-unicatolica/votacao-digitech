# Deploy na Hostinger (hospedagem compartilhada)

Dois ambientes, um por branch:

| Branch    | Ambiente    | Sugestão de endereço          | Banco                    |
| --------- | ----------- | ----------------------------- | ------------------------ |
| `staging` | Homologação | `homolog.SEU_DOMINIO`         | `votacao_homolog`        |
| `main`    | Produção    | `SEU_DOMINIO`                 | `votacao_producao`       |

Os passos abaixo valem para os dois: repita com o subdomínio, a pasta e o banco de cada um. Em homologação pode rodar o seed; em produção, nunca.

> Confirmar na Sprint 0 qual plano foi contratado. Os passos abaixo assumem um plano com **SSH** e **PHP 8.2+** selecionável no hPanel (planos Premium/Business têm os dois). Sem SSH, use a seção "Sem SSH" no final.

## Antes do primeiro deploy

### No GitHub: ter código na branch do ambiente

`staging` e `main` nasceram vazias (só o commit "raiz vazia", ver `GIT_FLOW.md`). Antes do primeiro deploy de cada ambiente, mescle o código nela:

1. Homologação: abrir e mesclar o PR `staging` ← `develop`.
2. Produção: depois que a homologação aprovar, abrir e mesclar o PR `main` ← `staging`.

Clonar uma branch vazia gera um site sem nada; clonar sem `-b` traz a `develop` (a branch padrão), que não é release.

### No hPanel

Nesta ordem (o subdomínio vem primeiro porque PHP e SSL são configurados por site):

1. Homologação: **Domínios → Subdomínios**: criar `homolog.SEU_DOMINIO`. Anote a **pasta** que o hPanel mostra para ele (ex.: `~/domains/SEU_DOMINIO/public_html/homolog` ou `~/domains/homolog.SEU_DOMINIO/public_html`): é ela que vira o link simbólico em "Apontar o domínio para `public/`".
2. **Avançado → Configuração PHP** (do domínio ou do subdomínio): PHP **8.2** ou superior, com `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `zip` habilitados.
3. **Bancos de dados → Gerenciamento**: criar banco e usuário (`votacao_homolog` ou `votacao_producao`). A Hostinger acrescenta um prefixo da conta (ex.: `u123456789_votacao_homolog`): copie o nome **completo** do banco e do usuário para o `.env`. O host costuma ser `localhost`.
4. **Segurança → SSL**: ativar o certificado gratuito e forçar HTTPS, para o domínio **e** para o subdomínio.
5. **Avançado → SSH**: ativar e anotar host, porta e usuário. A senha vai para o gerenciador de senhas, não para o projeto.
6. Se o repositório for **privado**: configurar a deploy key (seção "Repositório privado" abaixo) antes do `git clone`. Hoje ele é público e não precisa.

### Checklist da primeira homologação

- [x] PR `staging` ← `develop` mesclado (#79).
- [ ] hPanel: subdomínio, PHP, banco, SSL e SSH (lista acima).
- [ ] SSH: `git clone -b staging`, `composer install`, `.env` preenchido no servidor.
- [ ] `key:generate`, `migrate --force`, `digitech:admin`, `storage:link`, caches.
- [ ] Pasta do subdomínio trocada pelo link para `app/public`.
- [ ] Smoke test (final deste guia).
- [ ] Equipe testa; aprovado, PR `main` ← `staging` e repetir para produção.

## Deploy pela primeira vez (via SSH)

```bash
ssh -p PORTA usuario@host

cd ~/domains/SEU_DOMINIO         # ajuste ao caminho mostrado no hPanel
# produção: -b main, pasta app | homologação: -b staging, pasta homolog-app (troque "app" abaixo)
git clone -b main https://github.com/prof-ronildo-unicatolica/votacao-digitech.git app
cd app
composer install --no-dev --optimize-autoloader
cp .env.production.example .env
nano .env
```

O modelo `.env.production.example` já vem com os valores de produção (`APP_ENV=production`, `APP_DEBUG=false`, log diário em nível `warning`, cookies de sessão só por HTTPS e criptografados, cache em arquivo, fila `sync`). Preencha **só o que está marcado `TROCAR`**: `APP_URL`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (copiados do hPanel) e o remetente de e-mail.

Regras do `.env` de produção:

- Nunca reaproveite o `APP_KEY` local: gere outro no servidor (comando abaixo). Trocar o `APP_KEY` depois invalida sessões abertas, então gere uma vez só.
- `APP_DEBUG=true` em produção expõe senhas e código na tela de erro. O smoke test abaixo confere.
- O `.env` guarda **só o que o Laravel lê**. Login do hPanel, FTP e SSH ficam num gerenciador de senhas, nunca em arquivo do projeto (linha fora do formato `CHAVE=valor` também quebra o Laravel: "Failed to parse dotenv file").
- Homologação usa o mesmo modelo, com `APP_URL`, banco e usuário próprios.

```bash
php artisan key:generate
php artisan migrate --force         # SEM --seed em produção (o seed se recusa a rodar)
php artisan digitech:admin          # cria o primeiro admin: pergunta e-mail, nome e senha
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### Apontar o domínio para `public/`

O Laravel só pode expor a pasta `public/`. Na hospedagem compartilhada o domínio aponta para `public_html`, então troque `public_html` por um link simbólico:

```bash
cd ~/domains/SEU_DOMINIO
mv public_html public_html_antigo      # ou rm -rf, se estiver vazio
ln -s "$PWD/app/public" public_html
```

No subdomínio de homologação, troque do mesmo jeito a pasta anotada no passo 1 do hPanel. Exemplo, se ela for `public_html/homolog` e o clone estiver em `~/domains/SEU_DOMINIO/homolog-app`:

```bash
cd ~/domains/SEU_DOMINIO/public_html
mv homolog homolog_antigo
ln -s ~/domains/SEU_DOMINIO/homolog-app/public homolog
```

Homologação e produção ficam em clones separados (`app` e `homolog-app`, cada um com seu `.env`), nunca no mesmo.

Teste: `https://SEU_DOMINIO/api/health` deve responder `"banco":"ok"`.

Se o painel não permitir o link simbólico, alternativa: copiar o conteúdo de `app/public/` para `public_html/` e editar `public_html/index.php` trocando `__DIR__.'/../vendor/autoload.php'` por `__DIR__.'/../app/vendor/autoload.php'` (e o mesmo para `bootstrap/app.php`).

O primeiro admin é criado só com `php artisan digitech:admin` (passo acima): ele pergunta a senha sem ecoar. Não crie usuário com `tinker` passando a senha no comando, porque ela fica no histórico do shell do servidor.

## Repositório privado: deploy key

Com o repositório público, o `git clone` por HTTPS funciona sem senha. Se ele for privado, o servidor precisa de uma chave só de leitura:

1. hPanel → **Avançado → Git** (ou via SSH: `ssh-keygen -t ed25519 -C hostinger-votacao` e `cat ~/.ssh/id_ed25519.pub`): copiar a chave pública.
2. GitHub → repositório → **Settings → Deploy keys → Add deploy key**: colar a chave, título `Hostinger`, **sem** marcar "Allow write access".
3. Clonar pelo endereço SSH em vez do HTTPS:

   ```bash
   git clone -b main git@github.com:prof-ronildo-unicatolica/votacao-digitech.git app
   ```

   Se o clone já existir por HTTPS: `git remote set-url origin git@github.com:prof-ronildo-unicatolica/votacao-digitech.git`.

A chave fica só no servidor: não copie a chave privada para o projeto nem para o `.env`.

## Deploys seguintes (a cada merge em `staging` ou `main`)

```bash
ssh -p PORTA usuario@host
cd ~/domains/SEU_DOMINIO/app
php artisan down
git pull origin main      # ou staging, no ambiente de homologação
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

Salve isso como `deploy.sh` no servidor (fora do repositório) para rodar com um comando. O hPanel também tem **Avançado → Git** para fazer o `pull` automático a cada push em `main`; os comandos do `composer`/`artisan` continuam precisando de SSH ou do cron.

## Cron (se alguma história precisar de agendamento)

hPanel → **Avançado → Cron Jobs**, a cada minuto:

```
cd ~/domains/SEU_DOMINIO/app && php artisan schedule:run >> /dev/null 2>&1
```

## Sem SSH (plano básico)

1. Rode `composer install --no-dev` **localmente**, gere o `.env` de produção a partir de `.env.production.example` e as caches.
2. Envie tudo (inclusive `vendor/`) via **Gerenciador de arquivos** ou FTP para `~/domains/SEU_DOMINIO/app`.
3. Faça o ajuste de `public_html` descrito acima (versão "copiar `public/`").
4. Rode as migrations importando o SQL: `php artisan schema:dump` local gera `database/schema/mysql-schema.sql`, importável no phpMyAdmin do hPanel.

É trabalhoso; se a disciplina puder, o plano com SSH compensa.

## Smoke test pós-deploy (H12)

- [ ] `https://SEU_DOMINIO/` abre com HTTPS e sem aviso de debug.
- [ ] `/api/health` → `"banco":"ok"`.
- [ ] Login do admin criado com `php artisan digitech:admin` funciona; `admin@digitech.local` **não** existe.
- [ ] `/mesario` exige login.
- [ ] `storage/logs/laravel-AAAA-MM-DD.log` (log diário) sem erros após navegar.
- [ ] `php artisan about` mostra `Environment: production` e `Debug Mode: OFF`.
- [ ] `php artisan digitech:auditoria:verificar` → "A auditoria confere" (D27). Rode de novo depois da votação, antes de assinar a ata, e confira se o último hash é o mesmo impresso nela.

## Auditoria só-acrescenta no banco (opcional, D27)

A corrente de hashes **detecta** alteração na tabela `auditoria`. Para **impedir**, o usuário do banco da aplicação não deveria ter `UPDATE` nem `DELETE` nela. Se o painel da Hostinger permitir escolher privilégios por tabela (ou se houver acesso a `GRANT`):

```sql
-- Exemplo: o usuário da aplicação lê e acrescenta, mas não altera nem apaga a auditoria.
REVOKE UPDATE, DELETE ON SEU_BANCO.auditoria FROM 'SEU_USUARIO'@'%';
```

As migrations precisam de um usuário com todos os privilégios: rode-as com um usuário administrativo, ou aplique o `REVOKE` depois delas. Se a hospedagem não permitir, a corrente de hashes e o hash impresso na ata continuam valendo.

## Restrições da hospedagem compartilhada e como o projeto lida

| Restrição                          | Resposta do projeto                              |
| ---------------------------------- | ------------------------------------------------ |
| Sem processo em background (queue worker) | `QUEUE_CONNECTION=sync`                   |
| Sem Redis                          | sessão em banco, cache em arquivo                |
| Sem WebSocket                      | urna usa polling HTTP (3 s)                      |
| Sem Node no servidor               | Bootstrap via CDN; se adotarem Vite, fazer `npm run build` local e commitar `public/build` só na branch `main` |
