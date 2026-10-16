# Conectando o banco ao DBeaver

O [DBeaver](https://dbeaver.io/) é um cliente gráfico gratuito para bancos de dados. Com ele você vê as tabelas, roda consultas SQL e confere o que as migrations e o seed criaram, sem depender do phpMyAdmin.

> Pré-requisito: o banco `votacao_digitech` já existe e `php artisan migrate --seed` rodou sem erro (ver [README](../README.md#começando-em-5-comandos)).

## 1. Instalar o DBeaver

Baixe a versão **Community** (gratuita) em <https://dbeaver.io/download/>.

| Sistema | Como instalar |
| ------- | ------------- |
| Windows | Instalador `.exe` (pode marcar "incluir Java") |
| Ubuntu/Debian | `sudo snap install dbeaver-ce` ou o pacote `.deb` do site |
| Fedora | `flatpak install flathub io.dbeaver.DBeaverCommunity` |
| macOS | `brew install --cask dbeaver-community` |

## 2. Descobrir os dados de conexão

Use **os mesmos valores do seu `.env`**. O DBeaver e o Laravel se conectam ao mesmo servidor.

```dotenv
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=votacao_digitech
DB_USERNAME=root      # XAMPP (Windows): root sem senha
DB_PASSWORD=          # LAMP (Linux): normalmente votacao / votacao
```

> **Linux:** o `root` do MySQL/MariaDB costuma autenticar só via `sudo` (erro `1698 Access denied`). Use o usuário criado no README (`votacao`/`votacao`), não o `root`.

## 3. Criar a conexão

1. Abra o DBeaver e clique em **Nova conexão** (ícone de tomada com `+`, no canto superior esquerdo), ou use `Ctrl+Shift+N`.
2. Escolha **MySQL** (XAMPP usa MariaDB, mas o driver MySQL funciona; também pode escolher **MariaDB**). Clique em **Avançar**.
3. Na aba **Principal**, preencha:

   | Campo | Valor |
   | ----- | ----- |
   | Server Host | `127.0.0.1` |
   | Port | `3306` |
   | Database | `votacao_digitech` |
   | Nome de usuário | `root` (XAMPP) ou `votacao` (Linux) |
   | Senha | vazia (XAMPP) ou `votacao` (Linux) |
   | Salvar senha | marcado |

4. Clique em **Testar conexão**. Na primeira vez o DBeaver pede para **baixar o driver**: aceite.
5. Se aparecer **Conectado**, clique em **Concluir**.

### Ajuste comum: `Public Key Retrieval is not allowed`

Se o teste falhar com essa mensagem (MySQL 8):

1. Edite a conexão → aba **Propriedades do driver**.
2. Defina `allowPublicKeyRetrieval` = `true` e `useSSL` = `false`.
3. Teste de novo.

## 4. Explorar o banco

No painel **Navegador de banco de dados**, expanda:

```text
votacao_digitech
└── Databases
    └── votacao_digitech
        └── Tables   ← tabelas criadas pelas migrations
```

- **Ver os dados:** duplo clique na tabela → aba **Dados**.
- **Ver colunas, chaves e constraints:** aba **Propriedades** → **Colunas**, **Chaves**, **Chaves estrangeiras**.
- **Diagrama ER:** clique com o botão direito em `votacao_digitech` → **Visualizar diagrama**. Compare com [MODELO_DADOS.md](MODELO_DADOS.md).
- **Rodar SQL:** `Ctrl+]` (ou **Editor SQL → Novo script**) e execute com `Ctrl+Enter`:

```sql
SHOW TABLES;
SELECT * FROM users;
SELECT * FROM migrations;
```

> Depois de `php artisan migrate:fresh --seed`, aperte **F5** no navegador do DBeaver para atualizar a lista de tabelas.

## 5. Boas práticas

- **Não altere a estrutura pelo DBeaver.** Toda mudança de tabela vira uma *migration* no código, senão o banco dos colegas e o de produção ficam diferentes do seu.
- Use o DBeaver para **consultar e conferir**; para dados de exemplo, edite o `DatabaseSeeder`.
- **Sigilo do voto:** ao inspecionar votos, confira que não existe nenhuma coluna ligando o voto ao eleitor (ver [MODELO_DADOS.md](MODELO_DADOS.md)).
- Nunca conecte o DBeaver ao banco de **produção** (Hostinger) com usuário de escrita para "testar".

## Problemas comuns

| Erro | Causa provável | Solução |
| ---- | -------------- | ------- |
| `Communications link failure` / `Connection refused` | MySQL parado | Inicie o MySQL no painel do XAMPP ou `sudo systemctl start mysql` (ou `mariadb`) |
| `Access denied for user 'root'@'localhost'` (1698) | `root` via socket no Linux | Use o usuário `votacao` (ver README) |
| `Access denied ... (using password: YES)` (1045) | Usuário/senha errados | Confira com o `.env` |
| `Unknown database 'votacao_digitech'` | Banco não criado | Crie o banco (ver README) |
| Lista de tabelas vazia | Migrations não rodaram ou navegador desatualizado | `php artisan migrate --seed` e **F5** |
