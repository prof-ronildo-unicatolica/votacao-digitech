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

Um sistema web, simples de operar no dia da votação, com três papéis:

| Papel       | O que faz                                                                 |
| ----------- | ------------------------------------------------------------------------- |
| **Admin**   | Cadastra a eleição, as chapas, os eleitores aptos e os terminais           |
| **Mesário** | Confere a matrícula do eleitor e libera o voto em um terminal específico   |
| **Eleitor** | Na urna do terminal liberado, escolhe a chapa (ou branco/nulo) e confirma  |

O fluxo reproduz a urna presencial: o eleitor não faz login; quem o identifica é o mesário, e a urna só aceita um voto por liberação. Ao fim do período, o admin vê a apuração com participação, votos por chapa, brancos e nulos.

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

Voto remoto/on-line sem mesário, biometria, integração com o sistema acadêmico e eleições com mais de um cargo por cédula. Podem virar histórias futuras.

### Roadmap

Cinco sprints de duas semanas. O detalhe, por perfil, está em **[docs/ROADMAP.md](docs/ROADMAP.md)**. O trabalho é acompanhado na aba **Issues** (uma por história, com etiqueta de perfil e milestone da sprint) e no quadro em **Projects**.

| Sprint | Objetivo                                 | Histórias      |
| ------ | ---------------------------------------- | -------------- |
| 0      | Ambiente, Trello, Figma, git flow        | —              |
| 1      | Admin monta uma eleição                  | H1, H2, H3, H5 |
| 2      | Dia da votação de ponta a ponta          | H4, H6, H7, H8 |
| 3      | Resultado, painel da mesa e auditoria    | H9, H10, H11   |
| 4      | Produção na Hostinger                    | H12            |

## Stack

| Camada     | Tecnologia                                        |
| ---------- | ------------------------------------------------- |
| Back-end   | PHP 8.2+ · Laravel 12                             |
| Front-end  | Blade + Bootstrap 5.3 via CDN (já no layout, sem Node) |
| Banco      | MySQL / MariaDB 10.4+ (XAMPP) · SQLite nos testes |
| Testes     | PHPUnit (unitários e de integração)               |
| Local      | XAMPP (Windows) ou LAMP (Linux)                   |
| Produção   | Hostinger (hospedagem compartilhada)              |
| Gestão     | GitHub Issues + Projects · Git Flow · Figma       |

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

Abra <http://localhost:8000>. A página inicial mostra a conexão com o banco, a contagem de cada tabela e as chapas do seed.

## O que o scaffold entrega

| Item                           | Onde                                          |
| ------------------------------ | --------------------------------------------- |
| Modelo de dados: só chaves e constraints (6 migrations) | `database/migrations/2026_09_09_*` |
| Models Eloquent com relações   | `app/Models/`                                 |
| Dados de desenvolvimento       | `database/seeders/DatabaseSeeder.php`         |
| Página inicial funcional       | `app/Http/Controllers/HomeController.php`     |
| API de saúde e status da urna  | `routes/api.php`                              |
| Telas placeholder              | `/mesario`, `/urna/{terminal}`, `/admin`      |
| Classe pura de exemplo (alvo de teste unitário) | `app/Support/Apuracao.php`   |
| 2 testes unitários de exemplo  | `tests/Unit/ApuracaoTest.php`                 |
| 2 testes de integração de exemplo + 5 testes das constraints | `tests/Feature/`  |
| CI (roda os testes em cada PR) | `.github/workflows/tests.yml`                 |

As tabelas do domínio têm **apenas chaves e constraints**; os atributos são definidos pela equipe (ver `docs/MODELO_DADOS.md`). Usuário do seed: `mesario@digitech.local` / `mesario123` (login é a história H1).

## Rodando os testes

```bash
php artisan test
```

Esperado: `9 passed`. Os testes usam SQLite em memória (ver `phpunit.xml`), então não tocam no seu banco MySQL. Detalhes em [docs/TESTES.md](docs/TESTES.md).

## Documentação

| Documento                                              | Conteúdo                                              |
| ------------------------------------------------------ | ----------------------------------------------------- |
| [docs/DECISOES.md](docs/DECISOES.md)                   | Decisões de arquitetura e respostas às dúvidas iniciais |
| [docs/MODELO_DADOS.md](docs/MODELO_DADOS.md)           | Diagrama ER e regras do banco (sigilo do voto)        |
| [docs/HISTORIAS_USUARIO.md](docs/HISTORIAS_USUARIO.md) | Histórias de usuário (espelhadas nas Issues)          |
| [docs/ROADMAP.md](docs/ROADMAP.md)                     | Sprints e ordem de implementação                      |
| [docs/GIT_FLOW.md](docs/GIT_FLOW.md)                   | Branches, commits e Pull Requests (terminal)          |
| [docs/GITHUB_DESKTOP.md](docs/GITHUB_DESKTOP.md)       | O mesmo fluxo no GitHub Desktop e no site, para iniciantes |
| [docs/TESTES.md](docs/TESTES.md)                       | Como escrever e rodar testes                          |
| [docs/FIGMA.md](docs/FIGMA.md)                         | O que prototipar antes de codar                       |
| [docs/DEPLOY_HOSTINGER.md](docs/DEPLOY_HOSTINGER.md)   | Publicação em produção                                |
| [docs/PLAYLISTS.md](docs/PLAYLISTS.md)                 | Playlists de estudo da stack                          |

## Estrutura (o que importa para a equipe)

```
app/
├── Http/Controllers/     ← recebem a requisição e devolvem view ou JSON
├── Models/               ← Eleicao, Chapa, Eleitor, Terminal, SessaoVotacao, Voto, User
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

## Licença

Projeto acadêmico do laboratório DIGITECH — UniCatólica. Uso educacional.
