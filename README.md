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

## Telas da aplicação

Com `php artisan serve` rodando, a aplicação fica em <http://localhost:8000>. Usuários do seed (só em desenvolvimento): **admin** `admin@digitech.local` / `admin123` e **mesário** `mesario@digitech.local` / `mesario123`.

| Tela | Endereço | Quem usa | Login |
| ---- | -------- | -------- | ----- |
| Início (eleição ativa, fase, calendário e chapas homologadas) | <http://localhost:8000> | qualquer pessoa | — |
| Inscrição de chapa | <http://localhost:8000/inscricao> | alunos, com as inscrições abertas | — |
| Entrar | <http://localhost:8000/login> | comissão e mesários | — |
| Painel da comissão | <http://localhost:8000/admin> | comissão eleitoral | admin |
| Eleições e cargos | <http://localhost:8000/admin/eleicoes> | comissão | admin |
| Chapas (homologar, indeferir, corrigir, importar CSV) | <http://localhost:8000/admin/chapas> | comissão | admin |
| Eleitores (cadastro e importação CSV) | <http://localhost:8000/admin/eleitores> | comissão | admin |
| Terminais | <http://localhost:8000/admin/terminais> | comissão | admin |
| Usuários | <http://localhost:8000/admin/usuarios> | comissão | admin |
| Resultado, ata e CSV | <http://localhost:8000/admin/resultado> | comissão, depois do fim da votação | admin |
| Auditoria | <http://localhost:8000/admin/auditoria> | comissão | admin |
| Mesa receptora (liberar eleitor, painel dos terminais) | <http://localhost:8000/mesario> | mesários | mesário ou admin |
| Ativar este computador como urna | <http://localhost:8000/terminal/ativar> | mesário, no computador da urna | mesário ou admin (sai sozinho depois) |
| Urna | <http://localhost:8000/urna> | eleitor, no computador ativado | — (só no computador ativado) |
| Vitrine de componentes | <http://localhost:8000/estilo> | equipe de desenvolvimento | — |
| Saúde (banco ok?) | <http://localhost:8000/api/health> | quem publica | — |

**Ordem de uma eleição:** a comissão cadastra a eleição, os cargos, os eleitores e os terminais. Os alunos inscrevem as chapas e a comissão homologa. Na votação, cada computador de urna é ativado uma vez, e a mesa libera um eleitor por vez. Quando a votação acaba, o resultado sai em `/admin/resultado`. O teste de ponta a ponta (`e2e/`, ver [docs/TESTES.md](docs/TESTES.md)) percorre exatamente esse caminho.

## O que já está pronto

Histórias implementadas (detalhes e critérios em [docs/HISTORIAS_USUARIO.md](docs/HISTORIAS_USUARIO.md)):

| História | O quê |
| -------- | ----- |
| H1 | Login, perfis (admin e mesário) e usuários |
| H2 | Eleições com período de inscrição e de votação, uma ativa por vez |
| H3 | Homologar, indeferir (com motivo) e reabrir chapas |
| H4 | Eleitores: cadastro e importação CSV com conferência |
| H5 | Terminais e ativação do computador da urna por cookie |
| H6 | Mesa receptora: conferir a matrícula, liberar, cancelar |
| H7 | Urna: espera, cédula com as chapas homologadas |
| H8 | Votar: escolher, confirmar, voto secreto |
| H9 | Painel da mesa que se atualiza sozinho |
| H10 | Resultado, eleitos por cargo, ata para imprimir e CSV |
| H11 | Auditoria de todas as ações, tela com filtros e corrente de hashes |
| H13 | Cargos da eleição |
| H14 | Inscrição de chapa pelo formulário público |
| H15 | Importação de chapas por CSV |

Falta a **H12** (publicar na Hostinger), com o roteiro em [docs/DEPLOY_HOSTINGER.md](docs/DEPLOY_HOSTINGER.md).

Onde fica cada coisa no código:

| O quê | Onde |
| ----- | ---- |
| Regras de negócio (inscrição, homologação, liberação, voto, apuração, auditoria) | `app/Support/` |
| Controllers (só traduzem HTTP) | `app/Http/Controllers/` |
| Telas e componentes Blade | `resources/views/`, `resources/views/components/` |
| CSS e identidade visual | `public/css/digitech.css`, `public/css/tokens.css`, `design/` |
| Migrations e dados de desenvolvimento | `database/migrations/`, `database/seeders/DatabaseSeeder.php` |
| Modelos de CSV | `docs/exemplos/`, e o link "Baixar o modelo" em cada importação |
| Testes unitários e de integração | `tests/Unit/`, `tests/Feature/` |
| Teste de ponta a ponta (Playwright) | `e2e/` |
| CI (roda tudo em cada PR) | `.github/workflows/tests.yml` |
| Skills do Claude Code (roteiro de história, importação CSV) | `.claude/skills/` |
| Modelos de `.env` (local e produção) | `.env.example`, `.env.production.example` |

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
| [docs/GUIA_USUARIO.md](docs/GUIA_USUARIO.md)           | Guia do usuário: comissão, alunos, mesário e eleitor, com as telas |
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
