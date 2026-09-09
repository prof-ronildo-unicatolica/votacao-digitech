# Instalação no Windows com XAMPP

Tempo estimado: 30 minutos. Ao final você terá o sistema rodando em `http://localhost:8000` e os testes passando.

## 1. Instalar o XAMPP (Apache + MariaDB + PHP 8.2)

1. Baixe o **XAMPP 8.2.12** em <https://www.apachefriends.org/download.html>.
   Precisa ser a versão **8.2.x**: o Laravel 12 exige PHP 8.2 ou superior.
2. Instale em `C:\xampp` (padrão). Pode desmarcar Mercury, Tomcat, Perl e FileZilla.
3. Abra o **XAMPP Control Panel** e clique em **Start** em **Apache** e **MySQL**.
   Confira em `http://localhost/phpmyadmin` que o phpMyAdmin abre.

## 2. Colocar o PHP no PATH e habilitar extensões

1. Windows → pesquisar "variáveis de ambiente" → **Editar as variáveis de ambiente do sistema** → **Variáveis de Ambiente** → em `Path` clique **Editar** → **Novo** → `C:\xampp\php` → OK em tudo.
2. Feche e reabra o terminal (PowerShell ou Prompt). Teste:

   ```powershell
   php -v
   ```

   Deve mostrar `PHP 8.2.12`.
3. Abra `C:\xampp\php\php.ini` no Bloco de Notas e garanta que estas linhas existem **sem** `;` na frente:

   ```ini
   extension=curl
   extension=fileinfo
   extension=mbstring
   extension=openssl
   extension=pdo_mysql
   extension=pdo_sqlite
   extension=zip
   ```

   No XAMPP 8.2 todas já vêm habilitadas; só confira. Se mudou algo, reinicie o Apache.

## 3. Instalar o Composer

1. Baixe o **Composer-Setup.exe** em <https://getcomposer.org/download/>.
2. Na instalação, quando pedir o PHP, escolha `C:\xampp\php\php.exe`.
3. Reabra o terminal e teste:

   ```powershell
   composer --version
   ```

## 4. Instalar o Git

Baixe em <https://git-scm.com/download/win>, instale com as opções padrão e teste `git --version`.
Configure seu nome e e-mail (aparecem nos commits):

```powershell
git config --global user.name "Seu Nome"
git config --global user.email "seu@email.com"
```

## 5. Clonar o projeto e instalar dependências

Pode clonar em qualquer pasta (ex.: `C:\projetos`). **Não precisa** ser dentro de `htdocs`, porque vamos usar o servidor embutido do Laravel.

```powershell
cd C:\projetos
git clone https://github.com/prof-ronildo-unicatolica/votacao-digitech.git
cd votacao-digitech
composer install
copy .env.example .env
php artisan key:generate
```

## 6. Criar o banco de dados

Opção A — phpMyAdmin: abra `http://localhost/phpmyadmin` → aba **Bancos de dados** → nome `votacao_digitech`, collation `utf8mb4_unicode_ci` → **Criar**.

Opção B — terminal:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE votacao_digitech CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

O `.env` já vem configurado para o padrão do XAMPP (usuário `root`, sem senha). Se você definiu senha no MySQL, edite `DB_PASSWORD`.

## 7. Criar as tabelas e os dados de teste

```powershell
php artisan migrate --seed
```

Confira no phpMyAdmin: o banco deve ter as tabelas `eleicoes`, `chapas`, `eleitores`, `terminais`, `sessoes_votacao`, `votos`, além das tabelas padrão do Laravel.

## 8. Rodar

```powershell
php artisan serve
```

Abra <http://localhost:8000>. Você deve ver "Scaffold funcionando", o nome do banco, e as 3 chapas do seed.
Teste também <http://localhost:8000/api/health> (deve responder `"banco":"ok"`).

Para parar: `Ctrl + C` no terminal.

## 9. Rodar os testes

```powershell
php artisan test
```

Esperado: `Tests: 15 passed`. Os testes usam SQLite em memória, por isso a extensão `pdo_sqlite` precisa estar habilitada (passo 2).

## Problemas comuns

| Sintoma                                                        | Causa / solução                                                                                 |
| -------------------------------------------------------------- | ----------------------------------------------------------------------------------------------- |
| `php` não é reconhecido                                        | PATH não atualizado. Refaça o passo 2 e **reabra o terminal**.                                  |
| `SQLSTATE[HY000] [2002]` ao migrar                             | MySQL parado no XAMPP Control Panel, ou porta diferente de 3306.                                |
| `Unknown database 'votacao_digitech'`                          | Passo 6 não executado.                                                                          |
| `could not find driver` nos testes                             | `extension=pdo_sqlite` comentada no `php.ini`.                                                  |
| `The zip extension and unzip/7z commands are both missing`     | Habilite `extension=zip` no `php.ini`.                                                          |
| Porta 3306 ocupada (MySQL não inicia)                          | Outro MySQL instalado (ex.: Workbench). Pare o serviço no Windows ou mude a porta no XAMPP.     |
| Porta 80 ocupada (Apache não inicia)                           | Skype/IIS. Para este projeto o Apache não é necessário: só o MySQL precisa estar rodando.       |

## Opcional: servir pelo Apache do XAMPP

Não é necessário para desenvolver. Se quiser `http://votacao.local` em vez de `localhost:8000`:

1. Em `C:\xampp\apache\conf\extra\httpd-vhosts.conf` adicione:

   ```apache
   <VirtualHost *:80>
       ServerName votacao.local
       DocumentRoot "C:/projetos/votacao-digitech/public"
       <Directory "C:/projetos/votacao-digitech/public">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

2. Em `C:\Windows\System32\drivers\etc\hosts` (abrir o Bloco de Notas como administrador) adicione `127.0.0.1 votacao.local`.
3. Reinicie o Apache. A pasta `public/` é a única exposta; o restante do projeto fica fora do alcance do navegador, que é exatamente o que o Laravel espera.

## Alternativa moderna: Laravel Herd

Se preferir não usar XAMPP, o [Laravel Herd](https://herd.laravel.com) (gratuito, Windows/macOS) instala PHP 8.3+, Composer e Nginx em um clique. Ele **não** traz MySQL na versão gratuita; use o MySQL do XAMPP ou troque o `.env` para SQLite (`DB_CONNECTION=sqlite`, comentando as outras linhas `DB_`).
