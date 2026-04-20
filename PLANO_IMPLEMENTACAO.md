# Plano de Implementação — DIGITECH Votação Digital

> Tarefas ordenadas por dependência. Cada item só deve ser iniciado após suas dependências estarem concluídas.

---

## FASE 1 — Fundação

---

### 1. Configuração do Repositório Git

- [ ] **1.1** Criar `.gitignore` na raiz
  - Ignorar `.env`, `vendor/`, `*.log`, `.DS_Store`, `Thumbs.db`
- [ ] **1.2** Criar `.env.example` na raiz
  - Conter as chaves: `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`
- [ ] **1.3** Criar `.env` local (não versionado)
  - Preencher com credenciais do ambiente local (XAMPP)
- [ ] **1.4** Inicializar repositório Git com branches `main` e `develop`

**Dependências:** Nenhuma

---

### 2. Refatorar `config.php` para ler `.env`

- [ ] **2.1** Implementar função de parsing do `.env` em `config.php`
  - Ler o arquivo `.env` da raiz linha a linha
  - Ignorar comentários (`#`) e linhas vazias
  - Definir cada par `CHAVE=VALOR` como constante via `define()`
- [ ] **2.2** Substituir constantes hardcoded por valores lidos do `.env`
- [ ] **2.3** Manter a função `conectarBanco(): PDO` existente, usando as constantes do `.env`

**Dependências:** 1.2, 1.3

---

### 3. Criar estrutura de pastas do projeto

- [ ] **3.1** Criar pasta `assets/css/` e arquivo `styles.css`
  - Definir variáveis CSS (`:root`) para cores, fontes e espaçamentos
  - Definir estilos base reutilizáveis (botões, cards, alertas, formulários)
- [ ] **3.2** Criar pasta `assets/js/` e arquivo `main.js`
  - Arquivo vazio por enquanto, será populado nas fases seguintes
- [ ] **3.3** Criar pasta `assets/img/` para logo e imagens
- [ ] **3.4** Criar pasta `api/` (vazia, será populada na Fase 2)
- [ ] **3.5** Criar pasta `admin/` (vazia, será populada na Fase 3)

**Dependências:** Nenhuma

---

### 4. Implementar proteção CSRF

> **Arquivo:** `classes/Csrf.php`

- [ ] **4.1** Criar classe `Csrf`

```php
class Csrf
{
    /**
     * Inicia sessão PHP se ainda não estiver ativa.
     * Gera um token CSRF e armazena em $_SESSION['csrf_token'].
     * Retorna o token gerado.
     */
    public static function gerarToken(): string

    /**
     * Retorna um campo HTML <input type="hidden"> com o token atual.
     * Deve ser chamado dentro de cada <form>.
     */
    public static function campoHidden(): string

    /**
     * Compara o token recebido via POST com o da sessão.
     * Regenera o token após validação (para single-use).
     * Retorna true se válido, false se inválido.
     */
    public static function validarToken(string $tokenRecebido): bool
}
```

- [ ] **4.2** Integrar `Csrf::campoHidden()` em todos os formulários (`mesario/index.php`, `votante/index.php`)
- [ ] **4.3** Validar token CSRF em todos os blocos `if ($_SERVER['REQUEST_METHOD'] === 'POST')` antes de processar qualquer dado

**Dependências:** Nenhuma

---

### 5. Implementar validação e sanitização de entradas

> **Arquivo:** `classes/Validador.php`

- [ ] **5.1** Criar classe `Validador`

```php
class Validador
{
    /**
     * Valida matrícula: deve ser string numérica, entre 5 e 20 caracteres.
     * Retorna a matrícula sanitizada ou null se inválida.
     */
    public static function validarMatricula(string $matricula): ?string

    /**
     * Valida ID inteiro positivo (usado para terminal_id, chapa_id, etc.).
     * Retorna o inteiro ou null se inválido.
     */
    public static function validarIdPositivo(mixed $valor): ?int

    /**
     * Valida que o tipo do aluno permite votar (apenas 'votante').
     * Retorna true se o aluno pode votar, false caso contrário.
     */
    public static function podeVotar(string $tipoAluno): bool

    /**
     * Sanitiza string para exibição em HTML (wrapper de htmlspecialchars).
     */
    public static function sanitizarSaida(string $valor): string
}
```

- [ ] **5.2** Aplicar `Validador::validarMatricula()` em `mesario/index.php` antes de buscar aluno
- [ ] **5.3** Aplicar `Validador::validarIdPositivo()` para `terminal_id` e `chapa_id` em `votante/index.php`
- [ ] **5.4** Aplicar `Validador::podeVotar()` em `SessaoVotacao::criarSessao()` — rejeitar alunos do tipo `candidato` ou `mesario`

**Dependências:** Nenhuma

---

### 6. Implementar autenticação do mesário

> **Arquivo:** `classes/AutenticacaoMesario.php`

- [ ] **6.1** Adicionar coluna `senha_hash` na tabela `alunos` (alterar `schema.sql`)
  - `ALTER TABLE alunos ADD COLUMN senha_hash VARCHAR(255) NULL;`
  - Apenas alunos com `tipo = 'mesario'` terão senha preenchida
- [ ] **6.2** Atualizar `dados-teste.sql` para incluir senhas hash dos mesários (usar `password_hash()` com `PASSWORD_DEFAULT`)
- [ ] **6.3** Criar classe `AutenticacaoMesario`

```php
class AutenticacaoMesario
{
    private PDO $pdo;

    public function __construct(PDO $pdo)

    /**
     * Busca o aluno pela matrícula e verifica se é mesário.
     * Compara a senha informada com o hash armazenado via password_verify().
     * Se válido, inicia sessão PHP e armazena id e matrícula em $_SESSION.
     * Retorna true se autenticou, false caso contrário.
     */
    public function login(string $matricula, string $senha): bool

    /**
     * Destrói a sessão PHP completamente.
     * Limpa cookies de sessão.
     */
    public function logout(): void

    /**
     * Verifica se existe sessão ativa de mesário.
     * Retorna true se logado, false caso contrário.
     */
    public static function estaLogado(): bool

    /**
     * Retorna os dados do mesário logado da sessão.
     * Retorna null se não estiver logado.
     */
    public static function obterMesarioLogado(): ?array
}
```

- [ ] **6.4** Criar `mesario/login.php` — tela de login com campos matrícula + senha
  - Formulário POST com token CSRF
  - Após login bem-sucedido, redirecionar para `mesario/index.php`
- [ ] **6.5** Proteger `mesario/index.php` — redirecionar para `login.php` se não autenticado
- [ ] **6.6** Adicionar botão/link de logout no painel do mesário
  - Chamar `AutenticacaoMesario::logout()` e redirecionar para `login.php`

**Dependências:** 2, 4

---

### 7. Refatorar `mesario/index.php`

- [ ] **7.1** Remover CSS inline — substituir por link para `assets/css/styles.css`
- [ ] **7.2** Adicionar seleção de terminal (dropdown) no formulário do mesário
  - Usar `SessaoVotacao::listarTerminais()` para popular o dropdown
- [ ] **7.3** Ajustar chamada a `SessaoVotacao::criarSessao()` para receber `$terminalId` (já é o comportamento atual da classe)
- [ ] **7.4** Remover exibição de token — o fluxo correto é por terminal (polling), não por token
- [ ] **7.5** Adicionar guard de autenticação no topo (`AutenticacaoMesario::estaLogado()`)
- [ ] **7.6** Adicionar token CSRF no formulário
- [ ] **7.7** Usar `Validador` para sanitizar entrada da matrícula

**Dependências:** 3.1, 4, 5, 6

---

### 8. Refatorar `votante/index.php`

- [ ] **8.1** Remover CSS inline — substituir por link para `assets/css/styles.css`
- [ ] **8.2** Validar `terminal_id` com `Validador::validarIdPositivo()`
- [ ] **8.3** Adicionar token CSRF no formulário de voto
- [ ] **8.4** Validar CSRF no POST antes de registrar voto
- [ ] **8.5** Validar `chapa_id` com `Validador::validarIdPositivo()` e verificar se existe no banco

**Dependências:** 3.1, 4, 5

---

## FASE 2 — Core: Funcionalidades Essenciais

---

### 9. Criar tabela e lógica de controle de período de votação

- [ ] **9.1** Criar tabela `eleicoes` no `schema.sql`

```sql
CREATE TABLE eleicoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(200) NOT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NOT NULL,
    ativa BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

- [ ] **9.2** Adicionar dados de teste para `eleicoes` em `dados-teste.sql`

> **Arquivo:** `classes/Eleicao.php`

- [ ] **9.3** Criar classe `Eleicao`

```php
class Eleicao
{
    private PDO $pdo;

    public function __construct(PDO $pdo)

    /**
     * Busca a eleição ativa atual.
     * Retorna array com dados da eleição ou null se não houver.
     */
    public function obterEleicaoAtiva(): ?array

    /**
     * Verifica se a data/hora atual está dentro do período da eleição ativa.
     * Retorna true se a votação está aberta, false caso contrário.
     */
    public function votacaoAberta(): bool

    /**
     * Verifica se a eleição ativa já encerrou (data_fim < NOW()).
     * Retorna true se encerrou.
     */
    public function votacaoEncerrada(): bool
}
```

- [ ] **9.4** Integrar verificação de período no `mesario/index.php` — bloquear liberação se votação não estiver aberta
- [ ] **9.5** Integrar verificação de período no `votante/index.php` — exibir "Votação encerrada" se fora do período

**Dependências:** 2

---

### 10. Criar API JSON de status do terminal

> **Arquivo:** `api/status.php`

- [ ] **10.1** Criar endpoint `api/status.php`
  - Receber parâmetro GET `terminal` (ID do terminal)
  - Validar `terminal` com `Validador::validarIdPositivo()`
  - Chamar `SessaoVotacao::obterSessaoDoTerminal($terminalId)`
  - Retornar JSON: `{ "liberada": bool, "nome": string|null, "matricula": string|null }`
  - Definir header `Content-Type: application/json`
  - Tratar erros retornando JSON com campo `"erro"`

**Dependências:** 5, 9

---

### 11. Implementar polling na urna via API JSON

- [ ] **11.1** Criar função JavaScript em `assets/js/main.js` para polling
  - Fazer `fetch()` para `api/status.php?terminal=X` a cada 3-5 segundos
  - Quando `liberada === true`, atualizar a interface da urna com dados do votante e exibir chapas
  - Quando `liberada === false`, exibir tela de espera ("Aguardando liberação do mesário")
- [ ] **11.2** Atualizar `votante/index.php` para usar polling JS em vez de recarregamento PHP
  - Remover lógica PHP de verificação de sessão na carga da página
  - A página carrega sempre na tela de espera; o JS assume o controle de estado

**Dependências:** 10, 3.2

---

### 12. Criar tabela e lógica de auditoria

- [ ] **12.1** Criar tabela `audit_log` no `schema.sql`

```sql
CREATE TABLE audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    acao VARCHAR(100) NOT NULL,
    usuario_id INT NULL,
    ip VARCHAR(45) NOT NULL,
    detalhes TEXT,
    data_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES alunos(id) ON DELETE SET NULL
);
```

> **Arquivo:** `classes/AuditLog.php`

- [ ] **12.2** Criar classe `AuditLog`

```php
class AuditLog
{
    private PDO $pdo;

    public function __construct(PDO $pdo)

    /**
     * Registra uma ação no log de auditoria.
     * Captura o IP do cliente via $_SERVER['REMOTE_ADDR'].
     * $acao: string descritiva (ex: 'LOGIN_MESARIO', 'VOTO_REGISTRADO', 'SESSAO_CRIADA')
     * $usuarioId: ID do aluno que realizou a ação (ou null para ações de sistema)
     * $detalhes: informações adicionais em texto livre
     */
    public function registrar(string $acao, ?int $usuarioId, string $detalhes = ''): void

    /**
     * Lista os registros do log, opcionalmente filtrados por ação.
     * Retorna array de registros ordenado por data descendente.
     * $limite: número máximo de registros a retornar.
     */
    public function listar(?string $acao = null, int $limite = 100): array
}
```

- [ ] **12.3** Adicionar chamadas ao `AuditLog` nos pontos críticos:
  - Login do mesário (sucesso e falha)
  - Liberação de sessão de votação
  - Registro de voto
  - Logout do mesário

**Dependências:** 2

---

### 13. Criar endpoint de resultados

> **Arquivo:** `api/resultados.php`

- [ ] **13.1** Criar endpoint `api/resultados.php`
  - Verificar se a eleição está encerrada via `Eleicao::votacaoEncerrada()`
  - Se não encerrada, retornar JSON com `"erro": "Votação ainda em andamento"`
  - Se encerrada, retornar JSON com:
    - Array de chapas com `numero`, `nome`, `total_votos`
    - `total_votantes` (quantos votaram)
    - `total_eleitores_aptos` (quantos poderiam votar)
    - `taxa_participacao` (percentual)

**Dependências:** 9

---

### 14. Criar página de dashboard de resultados

> **Arquivo:** `admin/dashboard.php`

- [ ] **14.1** Criar página `admin/dashboard.php`
  - Se a votação não encerrou: exibir mensagem "Votação em andamento" com contagem de votos parcial (sem revelar por chapa)
  - Se a votação encerrou: exibir resultados completos
- [ ] **14.2** Integrar biblioteca Chart.js via CDN
  - Gráfico de barras com total de votos por chapa
  - Gráfico de pizza com percentual de cada chapa
- [ ] **14.3** Exibir indicadores numéricos:
  - Total de votos computados
  - Total de eleitores aptos
  - Taxa de participação (%)
  - Chapa vencedora (destaque visual)
- [ ] **14.4** Usar CSS de `assets/css/styles.css` (sem CSS inline)

**Dependências:** 13, 3.1

---

## FASE 3 — Polimento: UX, Responsividade e Admin

---

### 15. Implementar painel administrativo (CRUD)

> **Arquivo:** `classes/AdminController.php`

- [ ] **15.1** Criar classe `AdminController`

```php
class AdminController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)

    // --- ALUNOS ---

    /**
     * Lista todos os alunos com paginação.
     * $pagina: número da página (1-based).
     * $porPagina: registros por página.
     * Retorna array com 'dados' e 'total'.
     */
    public function listarAlunos(int $pagina = 1, int $porPagina = 20): array

    /**
     * Busca um aluno pelo ID.
     */
    public function obterAluno(int $id): ?array

    /**
     * Cria um novo aluno. Valida unicidade da matrícula.
     * Se tipo = 'mesario', exige $senha para gerar hash.
     * Retorna o ID do aluno criado.
     */
    public function criarAluno(string $matricula, string $nome, string $email, string $turma, string $tipo, ?string $senha = null): int

    /**
     * Atualiza dados de um aluno existente.
     */
    public function atualizarAluno(int $id, string $nome, string $email, string $turma, string $tipo): bool

    /**
     * Remove um aluno pelo ID (e registros dependentes via CASCADE).
     */
    public function removerAluno(int $id): bool

    // --- CHAPAS ---

    /**
     * Lista todas as chapas com contagem de candidatos.
     */
    public function listarChapas(): array

    /**
     * Cria uma nova chapa. Valida unicidade do número.
     * Retorna o ID da chapa criada.
     */
    public function criarChapa(int $numero, string $nome, string $descricao): int

    /**
     * Atualiza dados de uma chapa.
     */
    public function atualizarChapa(int $id, string $nome, string $descricao): bool

    /**
     * Remove uma chapa (e candidatos associados via CASCADE).
     */
    public function removerChapa(int $id): bool

    // --- CANDIDATOS ---

    /**
     * Associa um aluno a uma chapa com uma posição.
     * Valida que o aluno tem tipo = 'candidato'.
     * Valida unicidade aluno+chapa.
     */
    public function associarCandidato(int $alunoId, int $chapaId, string $posicao): int

    /**
     * Remove associação de candidato.
     */
    public function removerCandidato(int $id): bool

    // --- TERMINAIS ---

    /**
     * Lista todos os terminais.
     */
    public function listarTerminais(): array

    /**
     * Cria um terminal. Valida unicidade do número.
     */
    public function criarTerminal(int $numero, string $nome): int

    /**
     * Remove um terminal.
     */
    public function removerTerminal(int $id): bool

    // --- ELEIÇÕES ---

    /**
     * Cria ou atualiza configuração de eleição (título, data_inicio, data_fim).
     */
    public function configurarEleicao(string $titulo, string $dataInicio, string $dataFim): int
}
```

- [ ] **15.2** Criar `admin/index.php` — página principal do painel administrativo
  - Proteger com autenticação (admin ou mesário com permissão elevada)
  - Navegação entre seções: Alunos, Chapas, Terminais, Eleição, Logs
- [ ] **15.3** Criar `admin/alunos.php` — listagem, criação, edição e remoção de alunos
- [ ] **15.4** Criar `admin/chapas.php` — listagem, criação, edição e remoção de chapas + associação de candidatos
- [ ] **15.5** Criar `admin/terminais.php` — listagem, criação e remoção de terminais
- [ ] **15.6** Criar `admin/eleicao.php` — formulário de configuração do período de votação
- [ ] **15.7** Criar `admin/logs.php` — visualização do `audit_log` com filtros por ação

**Dependências:** 6, 9, 12

---

### 16. Implementar models de domínio efetivamente

- [ ] **16.1** Atualizar `models.php` — classe `Chapa`
  - Implementar `Chapa::totalEleitores()` para contar alunos com `tipo = 'votante'` no banco
  - Receber `PDO` como dependência (parâmetro estático ou via setter)
- [ ] **16.2** Atualizar `models.php` — classe `Aluno`
  - Adicionar propriedade `senhaHash` (nullable)
  - Adicionar método estático `fromArray(array $dados): Aluno` para criar instância a partir de resultado do banco
- [ ] **16.3** Atualizar `models.php` — classe `Voto`
  - Adicionar método estático `fromArray(array $dados): Voto`
- [ ] **16.4** Utilizar os models nas classes de negócio (`SessaoVotacao`, `AdminController`) em vez de arrays brutos, nos pontos onde fizer sentido

**Dependências:** 15

---

### 17. CSS centralizado e identidade visual

- [ ] **17.1** Definir variáveis CSS em `assets/css/styles.css`
  - Paleta de cores primárias/secundárias
  - Tipografia (família, tamanhos, pesos)
  - Espaçamentos padrão
  - Cores de feedback (sucesso, erro, alerta, info)
- [ ] **17.2** Criar classes utilitárias e componentes reutilizáveis
  - `.container-painel` — wrapper padrão para painéis
  - `.card-info` — card de informação de aluno
  - `.btn-primario`, `.btn-secundario` — botões padronizados
  - `.alerta-*` — mensagens de feedback
  - `.urna-*` — estilos específicos da urna
- [ ] **17.3** Remover todo CSS inline restante de `mesario/index.php` e `votante/index.php`
  - Substituir por classes definidas em `styles.css`

**Dependências:** 3.1

---

### 18. Responsividade

- [ ] **18.1** Adicionar media queries em `assets/css/styles.css`
  - Breakpoint mobile: `max-width: 576px`
  - Breakpoint tablet: `max-width: 768px`
  - Breakpoint desktop: `min-width: 992px`
- [ ] **18.2** Garantir que cards de chapas na urna empilhem verticalmente no mobile
- [ ] **18.3** Garantir que botão de voto tenha tamanho adequado para touch (mínimo 48x48px)
- [ ] **18.4** Garantir que painel do mesário seja funcional em tablet

**Dependências:** 17

---

### 19. Acessibilidade (a11y)

- [ ] **19.1** Adicionar `aria-label` em todos os botões e elementos interativos
- [ ] **19.2** Garantir navegação completa por teclado (Tab + Enter) na urna e no mesário
- [ ] **19.3** Validar contraste de cores conforme WCAG AA (mínimo 4.5:1 para texto normal)
- [ ] **19.4** Adicionar roles ARIA em elementos dinâmicos (tela de espera, alertas, etc.)

**Dependências:** 17

---

## FASE 4 — Qualidade: Testes e Endurecimento

---

### 20. Rate limiting no painel do mesário

> **Arquivo:** `classes/RateLimiter.php`

- [ ] **20.1** Criar classe `RateLimiter`

```php
class RateLimiter
{
    private PDO $pdo;

    public function __construct(PDO $pdo)

    /**
     * Verifica se o IP já excedeu o limite de tentativas.
     * $ip: endereço IP do cliente.
     * $maxTentativas: número máximo de ações permitidas no intervalo.
     * $intervaloSegundos: janela de tempo em segundos.
     * Retorna true se está dentro do limite, false se bloqueado.
     */
    public function permitir(string $ip, int $maxTentativas = 10, int $intervaloSegundos = 60): bool

    /**
     * Registra uma tentativa para o IP informado.
     */
    public function registrarTentativa(string $ip): void
}
```

- [ ] **20.2** Criar tabela `rate_limit` no `schema.sql`

```sql
CREATE TABLE rate_limit (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip VARCHAR(45) NOT NULL,
    data_tentativa TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rate_ip_data (ip, data_tentativa)
);
```

- [ ] **20.3** Integrar `RateLimiter` no `mesario/index.php` — verificar antes de processar busca de matrícula
  - Se bloqueado, exibir mensagem: "Muitas tentativas. Aguarde 5 minutos."

**Dependências:** 7

---

### 21. Voto em branco / nulo

- [ ] **21.1** Adicionar opções "Voto em Branco" e "Voto Nulo" na urna
  - Criar registros especiais na tabela `chapas` com números reservados (ex: 0 para branco, -1 para nulo) **OU** adicionar coluna `tipo_voto ENUM('valido','branco','nulo')` na tabela `votos`
- [ ] **21.2** Atualizar `SessaoVotacao::marcarVotacao()` para aceitar votos brancos/nulos
- [ ] **21.3** Atualizar dashboard para exibir contagem separada de votos brancos e nulos

**Dependências:** 14

---

### 22. Testes manuais e correção de bugs

- [ ] **22.1** Executar testes de segurança:
  - SQL Injection em campos de matrícula, terminal e chapa
  - XSS em campos de entrada
  - CSRF — enviar POST sem token válido
  - Manipulação de `terminal_id` via DevTools
  - Tentativa de voto duplo via manipulação de requests
- [ ] **22.2** Executar testes de fluxo completo (happy path):
  - Mesário faz login → busca aluno → seleciona terminal → libera sessão
  - Urna detecta sessão via polling → exibe chapas → votante seleciona e confirma
  - Voto registrado → sessão encerrada → dashboard atualiza
- [ ] **22.3** Executar testes de edge cases:
  - Sessão expirada (após 30 minutos)
  - Duplo clique no botão de voto
  - Navegador fechado durante votação
  - Votante tenta acessar urna sem sessão liberada
  - Mesário tenta liberar aluno que já votou
- [ ] **22.4** Testar em Chrome, Firefox e Edge
- [ ] **22.5** Testar responsividade em dispositivos mobile (via DevTools)

**Dependências:** Todas as tarefas anteriores

---

## FASE 5 — Produção: Deploy

---

### 23. Preparação para deploy

- [ ] **23.1** Criar `.env` de produção com credenciais da Hostinger
- [ ] **23.2** Configurar domínio/subdomínio na Hostinger
- [ ] **23.3** Configurar SSL/HTTPS
- [ ] **23.4** Executar `schema.sql` no MySQL de produção
- [ ] **23.5** Fazer upload dos arquivos do projeto (exceto `.env`, `dados-teste.sql`)
- [ ] **23.6** Executar smoke test em produção:
  - Login do mesário funciona
  - Liberação de votação funciona
  - Voto é registrado
  - Dashboard exibe resultados

**Dependências:** 22

---

## Resumo de Classes a Criar

| Classe                | Arquivo                           | Fase |
| --------------------- | --------------------------------- | ---- |
| `Csrf`                | `classes/Csrf.php`                | 1    |
| `Validador`           | `classes/Validador.php`           | 1    |
| `AutenticacaoMesario` | `classes/AutenticacaoMesario.php` | 1    |
| `Eleicao`             | `classes/Eleicao.php`             | 2    |
| `AuditLog`            | `classes/AuditLog.php`            | 2    |
| `AdminController`     | `classes/AdminController.php`     | 3    |
| `RateLimiter`         | `classes/RateLimiter.php`         | 4    |

## Classes Existentes (a refatorar)

| Classe          | Arquivo                     | O que alterar                                         |
| --------------- | --------------------------- | ----------------------------------------------------- |
| `SessaoVotacao` | `classes/SessaoVotacao.php` | Integrar validação, auditoria, verificação de período |
| `Aluno`         | `models.php`                | Adicionar `senhaHash`, `fromArray()`                  |
| `Chapa`         | `models.php`                | Implementar `totalEleitores()` com PDO                |
| `Voto`          | `models.php`                | Adicionar `fromArray()`                               |
| `Mesario`       | `models.php`                | Sem alterações significativas                         |

## Páginas/Endpoints a Criar

| Arquivo               | Tipo                         | Fase |
| --------------------- | ---------------------------- | ---- |
| `mesario/login.php`   | Página HTML + PHP            | 1    |
| `api/status.php`      | Endpoint JSON                | 2    |
| `api/resultados.php`  | Endpoint JSON                | 2    |
| `admin/index.php`     | Página HTML + PHP            | 3    |
| `admin/dashboard.php` | Página HTML + PHP + Chart.js | 2    |
| `admin/alunos.php`    | Página HTML + PHP            | 3    |
| `admin/chapas.php`    | Página HTML + PHP            | 3    |
| `admin/terminais.php` | Página HTML + PHP            | 3    |
| `admin/eleicao.php`   | Página HTML + PHP            | 3    |
| `admin/logs.php`      | Página HTML + PHP            | 3    |

## Arquivos de Configuração a Criar

| Arquivo                 | Fase              |
| ----------------------- | ----------------- |
| `.gitignore`            | 1                 |
| `.env.example`          | 1                 |
| `.env`                  | 1 (não versionar) |
| `assets/css/styles.css` | 1                 |
| `assets/js/main.js`     | 2                 |

---

## Diagrama de Dependências

```
1. Git/Repo ──────────────────────────────────────┐
2. config.php + .env ─────────┬───────────────────┤
3. Estrutura de pastas ───────┤                   │
4. CSRF ──────────────────────┤                   │
5. Validador ─────────────────┤                   │
                              ▼                   │
6. Autenticação Mesário ──────┤                   │
                              ▼                   │
7. Refatorar mesario/ ────────┤                   │
8. Refatorar votante/ ────────┤                   │
                              ▼                   │
9. Controle de Período ───────┤                   │
10. API status.php ───────────┤                   │
11. Polling JS ───────────────┤                   │
12. Audit Log ────────────────┤                   │
13. API resultados.php ───────┤                   │
14. Dashboard ────────────────┤                   │
                              ▼                   │
15. Admin CRUD ───────────────┤                   │
16. Models de domínio ────────┤                   │
17. CSS centralizado ─────────┤                   │
18. Responsividade ───────────┤                   │
19. Acessibilidade ───────────┤                   │
                              ▼                   │
20. Rate Limiting ────────────┤                   │
21. Voto branco/nulo ─────────┤                   │
22. Testes ───────────────────┤                   │
                              ▼                   │
23. Deploy ───────────────────┘
```
