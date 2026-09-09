# Votação DigiTech

> Sistema de votação digital para colegiado universitário — laboratório DIGITECH, UniCatólica.
> Este repositório contém o **scaffold mínimo funcional** (Laravel 12 + MySQL) e a documentação do projeto.
> A implementação das telas é feita pela equipe seguindo as histórias em `docs/HISTORIAS_USUARIO.md`.

## Stack

| Camada     | Tecnologia                                        |
| ---------- | ------------------------------------------------- |
| Back-end   | PHP 8.2+ · Laravel 12                             |
| Front-end  | Blade + Bootstrap 5 (CDN, sem Node no scaffold)   |
| Banco      | MySQL / MariaDB 10.4+ (XAMPP) · SQLite nos testes |
| Testes     | PHPUnit (unitários e de integração)               |
| Local      | XAMPP (Windows) ou LAMP (Linux)                   |
| Produção   | Hostinger (hospedagem compartilhada)              |
| Gestão     | Trello · Git Flow · Figma                         |

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

Crie o banco `votacao_digitech` (phpMyAdmin ou `mysql -u root -e "CREATE DATABASE votacao_digitech"`), depois:

```bash
php artisan migrate --seed
php artisan serve
```

Abra <http://localhost:8000>. A página inicial mostra a conexão com o banco, a eleição ativa e as chapas do seed.

## O que o scaffold entrega

| Item                           | Onde                                          |
| ------------------------------ | --------------------------------------------- |
| Modelo de dados (7 migrations) | `database/migrations/2026_09_09_*`            |
| Models Eloquent com relações   | `app/Models/`                                 |
| Dados de desenvolvimento       | `database/seeders/DatabaseSeeder.php`         |
| Página inicial funcional       | `app/Http/Controllers/HomeController.php`     |
| API de saúde e status da urna  | `routes/api.php`                              |
| Telas placeholder              | `/mesario`, `/urna/{terminal}`, `/admin`      |
| Regra de apuração pura         | `app/Support/Apuracao.php`                    |
| 5 testes unitários             | `tests/Unit/ApuracaoTest.php`                 |
| 10 testes de integração        | `tests/Feature/`                              |
| CI (roda os testes em cada PR) | `.github/workflows/tests.yml`                 |

Usuários do seed: `admin@digitech.local` / `admin123` e `mesario@digitech.local` / `mesario123` (login ainda não implementado — história H3).

## Rodando os testes

```bash
php artisan test
```

Os testes usam SQLite em memória (ver `phpunit.xml`), então não tocam no seu banco MySQL. Detalhes em [docs/TESTES.md](docs/TESTES.md).

## Documentação

| Documento                                              | Conteúdo                                              |
| ------------------------------------------------------ | ----------------------------------------------------- |
| [docs/DECISOES.md](docs/DECISOES.md)                   | Decisões de arquitetura e respostas às dúvidas iniciais |
| [docs/MODELO_DADOS.md](docs/MODELO_DADOS.md)           | Diagrama ER e regras do banco (sigilo do voto)        |
| [docs/HISTORIAS_USUARIO.md](docs/HISTORIAS_USUARIO.md) | Histórias prontas para virar cartões no Trello        |
| [docs/ROADMAP.md](docs/ROADMAP.md)                     | Sprints e ordem de implementação                      |
| [docs/GIT_FLOW.md](docs/GIT_FLOW.md)                   | Branches, commits e Pull Requests                     |
| [docs/TESTES.md](docs/TESTES.md)                       | Como escrever e rodar testes                          |
| [docs/FIGMA.md](docs/FIGMA.md)                         | O que prototipar antes de codar                       |
| [docs/DEPLOY_HOSTINGER.md](docs/DEPLOY_HOSTINGER.md)   | Publicação em produção                                |
| [docs/PLAYLISTS.md](docs/PLAYLISTS.md)                 | Playlists de estudo da stack                          |

## Estrutura (o que importa para a equipe)

```
app/
├── Http/Controllers/     ← recebem a requisição e devolvem view ou JSON
├── Models/               ← Eleicao, Chapa, Eleitor, Terminal, SessaoVotacao, Voto, User
└── Support/Apuracao.php  ← regras puras (sem banco)
database/
├── migrations/           ← estrutura das tabelas (versionada)
├── factories/            ← geram dados falsos para os testes
└── seeders/              ← dados de desenvolvimento
resources/views/          ← telas Blade (layouts/, placeholders/, home)
routes/web.php            ← rotas de páginas · routes/api.php ← rotas JSON
tests/Unit · tests/Feature
docs/                     ← toda a documentação
```

## Licença

Projeto acadêmico do laboratório DIGITECH — UniCatólica. Uso educacional.
