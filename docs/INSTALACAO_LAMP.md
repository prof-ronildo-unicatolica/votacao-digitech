# Instalação no Linux (LAMP)

Testado em Ubuntu 24.04 / Debian 12. Para outras distribuições, adapte os nomes dos pacotes.
Tempo estimado: 20 minutos.

## 1. Instalar PHP, extensões, MariaDB, Composer e Git

```bash
sudo apt update
sudo apt install -y php php-cli php-mysql php-sqlite3 php-xml php-mbstring php-curl php-zip php-bcmath \
                    mariadb-server git unzip
php -v        # precisa ser 8.2 ou superior
```

Composer (a versão do `apt` costuma ser antiga; prefira a oficial):

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

> Apache não é necessário para desenvolver: usamos `php artisan serve`. Se quiser o Apache mesmo assim, veja o final deste documento.

## 2. Configurar o banco

No Ubuntu, o `root` do MariaDB autentica pelo socket do sistema, então crie um usuário próprio para o projeto:

```bash
sudo mysql <<'SQL'
CREATE DATABASE votacao_digitech CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'votacao'@'localhost' IDENTIFIED BY 'votacao';
GRANT ALL PRIVILEGES ON votacao_digitech.* TO 'votacao'@'localhost';
FLUSH PRIVILEGES;
SQL
```

## 3. Clonar e configurar

```bash
git clone https://github.com/prof-ronildo-unicatolica/votacao-digitech.git
cd votacao-digitech
composer install
cp .env.example .env
php artisan key:generate
```

Edite o `.env` com o usuário criado acima:

```env
DB_USERNAME=votacao
DB_PASSWORD=votacao
```

## 4. Migrar, popular e rodar

```bash
php artisan migrate --seed
php artisan serve
```

Abra <http://localhost:8000> e <http://localhost:8000/api/health>.

## 5. Testes

```bash
php artisan test
```

Esperado: `Tests: 9 passed`. Usa SQLite em memória (pacote `php-sqlite3`).

## Problemas comuns

| Sintoma                                               | Solução                                                                                     |
| ----------------------------------------------------- | ------------------------------------------------------------------------------------------- |
| `Access denied for user 'root'@'localhost'`           | Use o usuário `votacao` criado no passo 2, não o `root`.                                    |
| `could not find driver`                               | Falta `php-mysql` (MySQL) ou `php-sqlite3` (testes). Instale e rode de novo.                |
| `Please provide a valid cache path` / erro de permissão | `chmod -R ug+rw storage bootstrap/cache`                                                   |
| `php` é 8.1 ou menor                                  | Ubuntu 22.04 traz 8.1. Use o PPA `ppa:ondrej/php` e instale `php8.2` ou superior.           |

## Opcional: Apache com VirtualHost

```bash
sudo apt install -y apache2 libapache2-mod-php
sudo a2enmod rewrite
sudo tee /etc/apache2/sites-available/votacao.conf >/dev/null <<'CONF'
<VirtualHost *:80>
    ServerName votacao.local
    DocumentRoot /caminho/para/votacao-digitech/public
    <Directory /caminho/para/votacao-digitech/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
CONF
sudo a2ensite votacao && sudo systemctl reload apache2
echo "127.0.0.1 votacao.local" | sudo tee -a /etc/hosts
```

O usuário do Apache (`www-data`) precisa escrever em `storage/` e `bootstrap/cache/`:

```bash
sudo chgrp -R www-data storage bootstrap/cache && sudo chmod -R g+w storage bootstrap/cache
```

## Alternativa: Docker (sem instalar nada no sistema)

Se a máquina tiver Docker, dá para rodar os testes sem PHP instalado:

```bash
docker run --rm -v "$PWD":/app -w /app composer:latest composer install
docker run --rm -v "$PWD":/app -w /app php:8.2-cli sh -c "cp -n .env.example .env; php artisan key:generate; php artisan test"
```
