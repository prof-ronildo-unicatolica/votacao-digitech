# Votação DigiTech

> Sistema de votação digital para eleições de colegiado da UniCatólica, desenvolvido pelos alunos do laboratório DIGITECH.
> Este repositório contém o **scaffold mínimo funcional** (Laravel 12 + MySQL) e a documentação do projeto.
> A implementação das telas é feita pela equipe seguindo as histórias em `docs/HISTORIAS_USUARIO.md` e o plano em `docs/ROADMAP.md`.

## O caso

### Contexto

A UniCatólica realiza periodicamente eleições de colegiado: representantes discentes, coordenações e comissões são escolhidos pelo voto dos alunos de cada curso. O processo envolve uma comissão eleitoral, mesários que conferem a identidade de quem vota e a apuração ao final do período.

<!-- Ajustar ao cenário real da instituição: como a eleição é feita hoje, quantos eleitores, quantos cursos/colegiados, quem organiza. -->

### Problema

Feito em papel ou com formulários genéricos, o processo tem custos conhecidos:

- **Conferência manual** de quem já votou, com risco de voto duplo ou de eleitor apto ser barrado.
- **Apuração lenta e sujeita a erro**, feita à mão e sem trilha de verificação.
- **Sigilo frágil**: cédulas e listas de presença circulam juntas; formulários on-line costumam identificar o respondente.
- **Sem histórico**: cada eleição recomeça do zero, sem registro estruturado das anteriores.

### Proposta

Um sistema web que cobre a eleição inteira, da inscrição das chapas à apuração:

| Papel                      | O que faz                                                                 |
| -------------------------- | ------------------------------------------------------------------------- |
| **Admin** (comissão)       | Cadastra a eleição com os períodos de inscrição e de votação, os cargos das chapas, os eleitores e os terminais; importa chapas por CSV e homologa as inscrições |
| **Representante de chapa** | No período de inscrição, inscreve a chapa num formulário público: nome e a matrícula de um aluno para cada cargo (Presidente, Vice, 1º e 2º Secretário, Tesoureiro...) |
| **Mesário**                | Confere a matrícula do eleitor e libera o voto em um terminal específico   |
| **Eleitor**                | Na urna do terminal liberado, escolhe a chapa (ou branco/nulo) e confirma  |

Cada eleição passa por duas fases com datas próprias: **inscrição** (chapas entram como pendentes e a comissão homologa) e **votação** (só chapas homologadas aparecem na urna). Todo aluno, eleitor ou candidato, é identificado pela matrícula. Fluxo completo em `docs/MODELO_DADOS.md`, "Inscrição de chapas".

Na votação, o fluxo reproduz a urna presencial: o eleitor não faz login; quem o identifica é o mesário, e a urna só aceita um voto por liberação. Ao fim do período, o admin vê a apuração com participação, votos por chapa, brancos e nulos.

### Princípios que o sistema garante

1. **Sigilo**: o banco não tem nenhuma ligação entre o voto e quem votou (ver `docs/MODELO_DADOS.md`).
2. **Voto único**: um eleitor tem uma única sessão por eleição, garantido por constraint no banco.
3. **Auditabilidade**: quem liberou quem, quando, e quem alterou cadastros fica registrado; em quem alguém votou, nunca.
4. **Simplicidade operacional**: roda em hospedagem compartilhada, sem instalação nos terminais (só um navegador).

### Finalidade

O projeto tem dupla finalidade:

- **Institucional**: entregar à UniCatólica uma ferramenta própria para suas eleições de colegiado, reutilizável a cada semestre.
- **Formativa**: ser o projeto integrador da equipe do DIGITECH. Os alunos praticam levantamento de requisitos (histórias de usuário), modelagem de dados, prototipação (Figma), desenvolvimento em framework (Laravel), testes automatizados, Git Flow com revisão por pares, gestão ágil (GitHub Issues e Projects) e publicação em produção (Hostinger).

### Fora do escopo (nesta versão)

Voto remoto/on-line sem mesário, biometria, integração com o sistema acadêmico e voto separado por cargo (a chapa tem vários cargos, mas o eleitor vota na chapa inteira, D13). Podem virar histórias futuras.

### Roadmap

Cinco sprints de duas semanas. O detalhe, por perfil, está em **[docs/ROADMAP.md](docs/ROADMAP.md)**. O trabalho é acompanhado na aba **Issues** (uma por história, com etiqueta de perfil e milestone da sprint) e no [quadro do projeto](https://github.com/users/prof-ronildo-unicatolica/projects/1).

| Sprint | Objetivo                                 | Histórias            |
| ------ | ---------------------------------------- | -------------------- |
| 0      | Ambiente, Issues, Figma, git flow        | —                    |
| 1      | Admin monta a eleição                    | H1, H2, H13, H4, H5  |
| 2      | Inscrição de chapas (vai ao ar primeiro) | H14, H15, H3         |
| 3      | Dia da votação de ponta a ponta          | H6, H7, H8, H9       |
| 4      | Resultado, auditoria e produção          | H10, H11, H12        |

## Stack

| Camada     | Tecnologia                                        |
| ---------- | ------------------------------------------------- |
| Back-end   | PHP 8.2+ · Laravel 12                             |
| Front-end  | Blade + Bootstrap 5.3 via CDN (já no layout, sem Node) |
| Banco      | MySQL / MariaDB 10.4+ (XAMPP) · SQLite nos testes |
| Testes     | PHPUnit (unitários e de integração)               |
| Local      | XAMPP (Windows) ou LAMP (Linux)                   |
| Produção   | Hostinger (hospedagem compartilhada)              |
| Gestão     | GitHub Issues + Projects · Git Flow (`develop` → `staging` → `main`) · Figma |

## Começando em 5 comandos

Pré-requisitos: PHP 8.2+, Composer e MySQL rodando. Tutorial passo a passo:
**[docs/INSTALACAO_XAMPP.md](docs/INSTALACAO_XAMPP.md)** (Windows) ou **[docs/INSTALACAO_LAMP.md](docs/INSTALACAO_LAMP.md)** (Linux).

```bash
git clone https://github.com/prof-ronildo-unicatolica/votacao-digitech.git
cd votacao-digitech
composer install
copy .env.example .env      # Linux/macOS: cp .env.example .env
php artisan key:generate
```

Crie o banco `votacao_digitech` (phpMyAdmin ou `mysql -u root -e "CREATE DATABASE votacao_digitech"`).

> **Linux (LAMP):** no MySQL/MariaDB do Linux o `root` costuma autenticar via socket (`ERROR 1698: Access denied for user 'root'@'localhost'`), e o Laravel não consegue entrar com `root` sem senha. Crie um usuário próprio e ajuste `DB_USERNAME`/`DB_PASSWORD` no `.env`:
>
> ```bash
> sudo mysql -e "CREATE DATABASE IF NOT EXISTS votacao_digitech CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
>   CREATE USER IF NOT EXISTS 'votacao'@'localhost' IDENTIFIED BY 'votacao';
>   CREATE USER IF NOT EXISTS 'votacao'@'127.0.0.1' IDENTIFIED BY 'votacao';
>   GRANT ALL PRIVILEGES ON votacao_digitech.* TO 'votacao'@'localhost';
>   GRANT ALL PRIVILEGES ON votacao_digitech.* TO 'votacao'@'127.0.0.1';
>   FLUSH PRIVILEGES;"
> ```
>
> No `.env`: `DB_USERNAME=votacao` e `DB_PASSWORD=votacao`.

Depois:

```bash
php artisan migrate --seed
php artisan serve
```

Abra <http://localhost:8000>. Com `APP_ENV=local`, a página **recarrega sozinha** quando você salva uma view, CSS, controller ou rota (sem Node: o layout consulta `/_dev/recarregar` a cada segundo). A página inicial mostra a conexão com o banco, a contagem de cada tabela e as chapas do seed com sua composição (5 cargos, 3 chapas completas).

Já tinha o banco de uma versão anterior? Rode `php artisan migrate`. Se der erro de chave estrangeira (`errno: 150`), seu banco foi criado com migrations antigas: em desenvolvimento, `php artisan migrate:fresh --seed` recria tudo (apaga os dados locais).

## O que o scaffold entrega

| Item                           | Onde                                          |
| ------------------------------ | --------------------------------------------- |
| Modelo de dados: só chaves e constraints (8 migrations, incluindo cargos e candidatos) | `database/migrations/2026_*` |
| Modelo de CSV de chapas (uma linha por chapa, uma coluna por cargo, H15) | `docs/exemplos/chapas.csv` |
| Skills do Claude Code: roteiro de história e padrão de importação CSV | `.claude/skills/` |
| Modelos de `.env` (local e produção) | `.env.example`, `.env.production.example` |
| Models Eloquent com relações   | `app/Models/`                                 |
| Dados de desenvolvimento       | `database/seeders/DatabaseSeeder.php`         |
| Página inicial funcional       | `app/Http/Controllers/HomeController.php`     |
| API de saúde                   | `routes/api.php`                              |
| Login, perfis e usuários (H1) | `/login`, `/admin/usuarios`, `app/Http/Controllers/Auth/` |
| Eleições com os dois períodos e a fase atual (H2) | `/admin`, `/admin/eleicoes`, `app/Enums/FaseEleicao.php` |
| Terminais e pareamento por cookie (H5, D16) | `/admin/terminais`, `/terminal/ativar`, `app/Http/Middleware/TerminalPareado.php` |
| Tela da urna com a identidade (sem lógica ainda) | `/urna` e `/urna/status`, só no computador pareado (H7) |
| Tela placeholder               | `/mesario` (H6, H9)                           |
| Componentes Blade, layouts e vitrine `/estilo` | `resources/views/components/`, `public/css/digitech.css` |
| Classe pura de exemplo (alvo de teste unitário) | `app/Support/Apuracao.php`   |
| 2 testes unitários de exemplo  | `tests/Unit/ApuracaoTest.php`                 |
| Identidade visual: tokens, marca e testes de contraste | `design/`, `public/css/tokens.css`, `tests/Unit/TokensTest.php` |
| 6 testes de integração de exemplo + 24 testes das constraints | `tests/Feature/`  |
| CI (roda os testes em cada PR) | `.github/workflows/tests.yml`                 |

As tabelas do domínio têm **apenas chaves e constraints**; os atributos são definidos pela equipe, história a história (ver `docs/MODELO_DADOS.md`). Já implementados: **H1** (login, perfis, usuários) e **H2** (eleições com período de inscrição e de votação).

Usuários do seed (só desenvolvimento):

| Perfil  | E-mail                   | Senha        | Entra em   |
| ------- | ------------------------ | ------------ | ---------- |
| Admin   | `admin@digitech.local`   | `admin123`   | `/admin`   |
| Mesário | `mesario@digitech.local` | `mesario123` | `/mesario` |

Para criar um admin com outro e-mail (e o **primeiro admin em produção**, onde o seed não roda): `php artisan digitech:admin`. O comando pergunta e-mail, nome e senha; se o e-mail já existe, promove o usuário a admin.

## Rodando os testes

```bash
php artisan test
```

Os testes usam SQLite em memória (ver `phpunit.xml`), então não tocam no seu banco MySQL.

Há também um teste **de ponta a ponta** que faz uma eleição inteira no navegador (Playwright): `cd e2e && npm ci && npx playwright install chromium && npm test`. Detalhes dos dois em [docs/TESTES.md](docs/TESTES.md).

## Documentação

| Documento                                              | Conteúdo                                              |
| ------------------------------------------------------ | ----------------------------------------------------- |
| [docs/DECISOES.md](docs/DECISOES.md)                   | Decisões de arquitetura e respostas às dúvidas iniciais |
| [docs/MODELO_DADOS.md](docs/MODELO_DADOS.md)           | Diagrama ER e regras do banco (sigilo do voto)        |
| [docs/DIAGRAMAS.md](docs/DIAGRAMAS.md)                 | Casos de uso e diagramas de sequência (UML)           |
| [docs/AUDITORIA.md](docs/AUDITORIA.md)                 | Plano de auditoria: eventos, tabela, fases (H11)      |
| [docs/DBEAVER.md](docs/DBEAVER.md)                     | Como conectar e inspecionar o banco no DBeaver        |
| [docs/HISTORIAS_USUARIO.md](docs/HISTORIAS_USUARIO.md) | Histórias de usuário (espelhadas nas Issues)          |
| [docs/ROADMAP.md](docs/ROADMAP.md)                     | Sprints e ordem de implementação                      |
| [docs/GIT_FLOW.md](docs/GIT_FLOW.md)                   | Branches, commits e Pull Requests (terminal)          |
| [docs/GITHUB_DESKTOP.md](docs/GITHUB_DESKTOP.md)       | O mesmo fluxo no GitHub Desktop e no site, para iniciantes |
| [docs/TESTES.md](docs/TESTES.md)                       | Como escrever e rodar testes                          |
| [docs/IDENTIDADE_VISUAL.md](docs/IDENTIDADE_VISUAL.md) | Marca, cores, tipografia e como importar no Figma     |
| [docs/FIGMA.md](docs/FIGMA.md)                         | O que prototipar antes de codar                       |
| [docs/DEPLOY_HOSTINGER.md](docs/DEPLOY_HOSTINGER.md)   | Publicação em produção                                |
| [docs/PLAYLISTS.md](docs/PLAYLISTS.md)                 | Playlists de estudo da stack                          |

## Estrutura (o que importa para a equipe)

```
app/
├── Http/Controllers/     ← recebem a requisição e devolvem view ou JSON
├── Models/               ← Eleicao, Cargo, Chapa, Candidato, Eleitor, Terminal, SessaoVotacao, Voto, User
└── Support/Apuracao.php  ← exemplo de classe pura (alvo de teste unitário)
database/
├── migrations/           ← estrutura das tabelas (versionada)
├── factories/            ← geram dados falsos para os testes
└── seeders/              ← dados de desenvolvimento
resources/views/          ← telas Blade (layouts/, placeholders/, home)
routes/web.php            ← rotas de páginas · routes/api.php ← rotas JSON
tests/Unit · tests/Feature
docs/                     ← toda a documentação
```

## Equipe

| Nome                              | GitHub              | Perfil            |
| --------------------------------- | ------------------- | ----------------- |
| Prof. Ronildo Silva               | prof-ronildo-unicatolica | Orientação   |
| Isabely Xavier Mancuso Carmona    | XavierIsa24         | Front, UI/UX      |
| Patricya I. Magalhães             | pmagalhaes-code     | Back, QA          |
| Álvaro da Silva de Moura          | alvin-silvaM        | Back, Testes      |
| Lucas Queiroz                     | Lucas-Queiroz1      | Front             |

O perfil **Dados** (migrations e modelo) ainda não tem dono: definir na Sprint 0 (issue #18). No PR de treino, cada membro confere sua linha.

## Licença

Projeto acadêmico do laboratório DIGITECH — UniCatólica. Uso educacional.
