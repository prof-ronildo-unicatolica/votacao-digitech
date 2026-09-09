# Deploy na Hostinger (hospedagem compartilhada)

> Confirmar na Sprint 0 qual plano foi contratado. Os passos abaixo assumem um plano com **SSH** e **PHP 8.2+** selecionável no hPanel (planos Premium/Business têm os dois). Sem SSH, use a seção "Sem SSH" no final.

## Antes do primeiro deploy

1. hPanel → **Avançado → Configuração PHP**: escolher PHP **8.2** ou superior e habilitar `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `zip`.
2. hPanel → **Bancos de dados → Gerenciamento**: criar banco e usuário. Anotar `nome_do_banco`, `usuario`, `senha`, `host` (na Hostinger o host costuma ser `localhost`).
3. hPanel → **Segurança → SSL**: ativar o certificado gratuito e forçar HTTPS.
4. hPanel → **Avançado → SSH**: ativar e anotar host, porta e usuário.

## Deploy pela primeira vez (via SSH)

```bash
ssh -p PORTA usuario@host

cd ~/domains/SEU_DOMINIO         # ajuste ao caminho mostrado no hPanel
git clone https://github.com/prof-ronildo-unicatolica/votacao-digitech.git app
cd app
composer install --no-dev --optimize-autoloader
cp .env.example .env
nano .env
```

No `.env` de produção:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://SEU_DOMINIO
DB_DATABASE=nome_do_banco
DB_USERNAME=usuario
DB_PASSWORD=senha
SESSION_DRIVER=database
CACHE_STORE=file
QUEUE_CONNECTION=sync
LOG_LEVEL=warning
```

```bash
php artisan key:generate
php artisan migrate --force         # SEM --seed em produção
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

Teste: `https://SEU_DOMINIO/api/health` deve responder `"banco":"ok"`.

Se o painel não permitir o link simbólico, alternativa: copiar o conteúdo de `app/public/` para `public_html/` e editar `public_html/index.php` trocando `__DIR__.'/../vendor/autoload.php'` por `__DIR__.'/../app/vendor/autoload.php'` (e o mesmo para `bootstrap/app.php`).

## Criar o primeiro admin em produção

```bash
php artisan tinker --execute="App\Models\User::create(['name'=>'Admin','email'=>'admin@SEU_DOMINIO','password'=>'TROQUE-ESTA-SENHA']);"
```

## Deploys seguintes (a cada merge em `main`)

```bash
ssh -p PORTA usuario@host
cd ~/domains/SEU_DOMINIO/app
php artisan down
git pull origin main
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

1. Rode `composer install --no-dev` **localmente**, gere o `.env` de produção e as caches.
2. Envie tudo (inclusive `vendor/`) via **Gerenciador de arquivos** ou FTP para `~/domains/SEU_DOMINIO/app`.
3. Faça o ajuste de `public_html` descrito acima (versão "copiar `public/`").
4. Rode as migrations importando o SQL: `php artisan schema:dump` local gera `database/schema/mysql-schema.sql`, importável no phpMyAdmin do hPanel.

É trabalhoso; se a disciplina puder, o plano com SSH compensa.

## Smoke test pós-deploy (H12)

- [ ] `https://SEU_DOMINIO/` abre com HTTPS e sem aviso de debug.
- [ ] `/api/health` → `"banco":"ok"`.
- [ ] Login do admin funciona.
- [ ] `/mesario` exige login.
- [ ] `storage/logs/laravel.log` sem erros após navegar.

## Restrições da hospedagem compartilhada e como o projeto lida

| Restrição                          | Resposta do projeto                              |
| ---------------------------------- | ------------------------------------------------ |
| Sem processo em background (queue worker) | `QUEUE_CONNECTION=sync`                   |
| Sem Redis                          | sessão em banco, cache em arquivo                |
| Sem WebSocket                      | urna usa polling HTTP (3 s)                      |
| Sem Node no servidor               | Bootstrap via CDN; se adotarem Vite, fazer `npm run build` local e commitar `public/build` só na branch `main` |
