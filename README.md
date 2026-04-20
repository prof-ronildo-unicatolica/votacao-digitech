# Sistema de Votação Digital — DIGITECH

> Sistema de votação digital para colegiado universitário, desenvolvido pelo laboratório DIGITECH da UniCatólica.

---

## Pré-requisitos

| Software                                | Versão mínima                          |
| --------------------------------------- | -------------------------------------- |
| [XAMPP](https://www.apachefriends.org/) | 8.2+ (Apache + MySQL + PHP)            |
| Navegador                               | Chrome, Firefox ou Edge (versão atual) |

---

## Instalação

### 1. Clonar o repositório

```bash
cd C:\xampp\htdocs
git clone <url-do-repositorio> votacao-digitech
```

### 2. Configurar variáveis de ambiente

Copie o arquivo de exemplo e preencha com suas credenciais:

```bash
cd votacao-digitech
copy .env.example .env
```

Edite o `.env`:

```env
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=votacao_digitech
```

### 3. Criar o banco de dados

Abra o phpMyAdmin (`http://localhost/phpmyadmin`) e execute:

```sql
CREATE DATABASE votacao_digitech CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Criar as tabelas

No phpMyAdmin, selecione o banco `votacao_digitech`, vá em **Importar** e selecione o arquivo `schema.sql`.

Ou via terminal:

```bash
C:\xampp\mysql\bin\mysql.exe -u root votacao_digitech < schema.sql
```

### 5. Inserir dados de teste (opcional)

```bash
C:\xampp\mysql\bin\mysql.exe -u root votacao_digitech < dados-teste.sql
```

### 6. Iniciar o XAMPP

1. Abra o **XAMPP Control Panel**
2. Inicie **Apache** e **MySQL**
3. Acesse `http://localhost/votacao-digitech/`

---

## Estrutura do Projeto

```
votacao-digitech/
├── config.php                   ← Conexão com banco (lê .env)
├── models.php                   ← Classes de domínio (Aluno, Chapa, Voto, Mesario)
├── schema.sql                   ← DDL — criação das tabelas
├── dados-teste.sql              ← Seed de dados para desenvolvimento
├── .env                         ← Credenciais locais (NÃO versionar)
├── .env.example                 ← Template do .env
├── .gitignore
│
├── assets/
│   ├── css/styles.css           ← CSS centralizado
│   ├── js/main.js               ← JavaScript compartilhado
│   └── img/                     ← Imagens e logo
│
├── classes/
│   ├── SessaoVotacao.php        ← Lógica de sessões e votação
│   ├── Csrf.php                 ← Proteção CSRF
│   ├── Validador.php            ← Validação de entradas
│   ├── AutenticacaoMesario.php  ← Login/logout do mesário
│   ├── Eleicao.php              ← Controle de período
│   ├── AuditLog.php             ← Log de auditoria
│   ├── AdminController.php      ← CRUD administrativo
│   └── RateLimiter.php          ← Limitação de tentativas
│
├── api/
│   ├── status.php               ← Polling JSON (estado do terminal)
│   └── resultados.php           ← Resultados da eleição (JSON)
│
├── admin/
│   ├── index.php                ← Painel administrativo
│   ├── dashboard.php            ← Dashboard de resultados
│   ├── alunos.php               ← CRUD de alunos
│   ├── chapas.php               ← CRUD de chapas
│   ├── terminais.php            ← CRUD de terminais
│   ├── eleicao.php              ← Configuração de período
│   └── logs.php                 ← Log de auditoria
│
├── mesario/
│   ├── login.php                ← Login do mesário
│   └── index.php                ← Painel do mesário
│
├── votante/
│   └── index.php                ← Urna de votação
│
└── docs/                        ← Documentação do projeto
```

---

## Fluxo de Uso

### 1. Mesário faz login

1. Acesse `http://localhost/votacao-digitech/mesario/login.php`
2. Informe matrícula e senha do mesário

### 2. Mesário libera votação

1. No painel, digite a matrícula do votante
2. Selecione o terminal de votação
3. Clique em **Liberar Votação**

### 3. Votante vota

1. A urna no terminal selecionado detecta a liberação automaticamente (polling)
2. O votante seleciona uma chapa e confirma o voto
3. O sistema registra o voto e encerra a sessão

### 4. Apuração

1. Após o encerramento do período de votação, acesse o dashboard
2. Os resultados são exibidos com gráficos e percentuais

---

## Dados de Teste

### Votantes

| Matrícula | Nome           |
| --------- | -------------- |
| 2024007   | Lucas Ferreira |
| 2024008   | Beatriz Lima   |
| 2024009   | Gabriel Rocha  |
| 2024010   | Camila Souza   |
| 2024011   | Rafael Torres  |
| 2024012   | Isabela Gomes  |

### Mesários

| Matrícula | Nome             |
| --------- | ---------------- |
| 2024013   | Bruno Castro     |
| 2024014   | Fernanda Ribeiro |

### Chapas

| Número | Nome          | Presidente   | Vice          |
| ------ | ------------- | ------------ | ------------- |
| 1      | Transformação | João Silva   | Maria Santos  |
| 2      | Movimento     | Carlos Costa | Ana Oliveira  |
| 3      | Integração    | Pedro Alves  | Sofia Martins |

### Terminais

| Número | Local             |
| ------ | ----------------- |
| 1      | Lab Informática A |
| 2      | Lab Informática B |
| 3      | Sala de Aula 101  |
| 4      | Biblioteca        |

---

## Solução de Problemas

### "Erro ao conectar ao banco"

- Verifique se Apache e MySQL estão rodando no XAMPP
- Confirme as credenciais no arquivo `.env`
- Verifique se o banco `votacao_digitech` existe no phpMyAdmin

### "Aluno não encontrado"

- Use uma matrícula válida do `dados-teste.sql` (2024007 a 2024012 para votantes)

### "Nenhuma sessão liberada"

- O mesário precisa liberar a votação antes do votante acessar a urna
- Verifique se o terminal selecionado no mesário é o mesmo da urna

---

## Stack Tecnológica

| Camada         | Tecnologia                          |
| -------------- | ----------------------------------- |
| Linguagem      | PHP 8.2 (estrutural, sem framework) |
| Front-end      | HTML5, Bootstrap, CSS3 customizado  |
| Banco de Dados | MySQL (charset utf8mb4)             |
| Ambiente Local | XAMPP                               |
| Hospedagem     | Hostinger                           |

---

## Documentação

A documentação completa do projeto está na pasta `docs/`:

---

## Licença

Projeto acadêmico do laboratório DIGITECH — UniCatólica.
