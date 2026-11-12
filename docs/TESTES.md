# Testes

## Dois tipos, dois exemplos no repositório

| Tipo                  | Exemplo                            | O que testa                                        | Precisa de banco? |
| --------------------- | ---------------------------------- | -------------------------------------------------- | ----------------- |
| **Unitário**          | `tests/Unit/ApuracaoTest.php`      | Uma classe isolada (`App\Support\Apuracao`), exemplo | Não             |
| **Integração** (Feature) | `tests/Feature/PaginaInicialTest.php` | Rotas HTTP e banco (exemplo)                   | Sim (SQLite em memória) |
| **Constraints**       | `tests/Feature/ConstraintsTest.php` | O que o schema garante (unique, cascade, sigilo)  | Sim               |
| **Ponta a ponta** (E2E) | `e2e/testes/eleicao-completa.spec.js` | Uma eleição inteira num navegador de verdade (D28) | Sim (SQLite próprio, `e2e/banco.sqlite`) |

Regra prática: **regra de negócio que dá para expressar como função pura vai para `app/Support` e ganha teste unitário**. Tudo que passa por rota, banco ou sessão ganha teste de integração.

## Rodando

```bash
php artisan test                       # tudo
php artisan test --testsuite=Unit      # só unitários (rápidos)
php artisan test --filter=ConstraintsTest  # um arquivo/teste
php artisan test --coverage            # cobertura (precisa de Xdebug ou PCOV)
```

O `phpunit.xml` força `DB_CONNECTION=sqlite` + `:memory:`, então os testes **nunca** tocam no seu MySQL.

## Anatomia de um teste de integração

```php
class LiberarVotacaoTest extends TestCase
{
    use RefreshDatabase;                // recria o banco em memória a cada teste

    public function test_mesario_libera_eleitor_apto(): void
    {
        // Arrange — montar o cenário com factories
        $mesario  = User::factory()->create();
        $eleicao  = Eleicao::factory()->create();
        $eleitor  = Eleitor::factory()->create();
        $terminal = Terminal::factory()->create();

        // Act — agir como o usuário
        $resposta = $this->actingAs($mesario)
            ->post('/mesario/liberar', ['matricula' => $eleitor->matricula, 'terminal_id' => $terminal->id]);

        // Assert — verificar resposta e banco
        $resposta->assertRedirect('/mesario');
        $this->assertDatabaseHas('sessoes_votacao', ['eleitor_id' => $eleitor->id, 'terminal_id' => $terminal->id]);
    }
}
```

## O que cada história precisa cobrir (mínimo)

- **Caminho feliz**: o usuário faz o que a história descreve e dá certo.
- **Um caminho triste**: dado inválido, sem permissão, ou regra violada (ex.: eleitor já votou).
- **Regra que o banco garante** (quando houver): `ConstraintsTest` mostra como testar constraints (unique, cascade, restrict).

## Ponta a ponta (Playwright, D28)

Um teste só, `e2e/testes/eleicao-completa.spec.js`, faz uma eleição inteira pelas telas, em **cinco navegadores separados** (como cinco computadores): comissão, aluno, mesa e duas urnas.

1. A comissão cadastra a eleição, os cargos, importa os eleitores (`e2e/dados/eleitores.csv`), cadastra dois terminais e o mesário.
2. Os alunos inscrevem três chapas pelo formulário público.
3. As inscrições fecham. A comissão homologa duas chapas e indefere uma.
4. A votação abre. Dois computadores viram urnas e a mesa libera:
   - dois eleitores votam ao mesmo tempo, em terminais diferentes;
   - a cédula só mostra as chapas homologadas;
   - quem já votou não é liberado de novo;
   - um eleitor corrige a escolha e vota em branco, outro anula;
   - o mesário cancela uma sessão e libera de novo;
   - uma liberação expira (30 min sem voto) e é refeita.
5. A votação encerra: a mesa não libera mais e as urnas ficam em espera.
6. Sai o resultado (votos, participação, vencedora, eleitos, ata), a auditoria tem cada ação e `digitech:auditoria:verificar` confere a corrente de hashes.

**Relógio:** em vez de esperar horas entre as fases, o teste adianta o relógio da aplicação. Ele grava os segundos em `storage/framework/e2e-relogio`, e o `AppServiceProvider` aplica (só com `APP_ENV=e2e`).

**O que precisa instalado** (só para este teste; a aplicação continua sem Node):

| O quê | Como conferir | Se faltar |
| ----- | ------------- | --------- |
| **Node 20 ou mais novo** | `node --version` | Baixe a versão LTS em [nodejs.org](https://nodejs.org). No Linux, também dá pelo [nvm](https://github.com/nvm-sh/nvm). |
| **PHP com `pdo_sqlite`** (o teste usa um banco SQLite próprio) | `php -m` deve listar `pdo_sqlite` e `sqlite3` | **XAMPP (Windows):** em `C:\xampp\php\php.ini`, tire o `;` do começo das linhas `extension=pdo_sqlite` e `extension=sqlite3`, e reinicie o terminal. **Linux:** `sudo apt install php-sqlite3` (ou `php8.2-sqlite3`, na versão do seu PHP). |
| **Composer já rodado** na raiz (`vendor/` existe) | `ls vendor` | `composer install` na raiz do projeto |

O `npx playwright install chromium` baixa um Chromium só para os testes (cerca de 150 MB, uma vez). No Linux, se ele reclamar de bibliotecas do sistema, rode `npx playwright install --with-deps chromium`.

**Rodando:**

```bash
cd e2e
npm ci                           # uma vez
npx playwright install chromium  # uma vez
npm test                         # sobe o servidor na porta 8766, roda e derruba (cerca de 2 min)
npm run test:ver                 # o mesmo, com os navegadores abertos na tela
npm run relatorio                # relatório HTML do último resultado
npm run guia                     # roda o teste e atualiza as fotos do guia do usuário (docs/guia/)
```

O servidor de teste usa um banco SQLite próprio (`e2e/banco.sqlite`), recriado a cada execução: **não toca no seu MySQL**. Se falhar, `e2e/test-results/` guarda foto, vídeo e trace (`npx playwright show-trace <arquivo>`).

**Quando mexer:** mudou um texto de botão, um rótulo ou um fluxo que o teste percorre? Ajuste `e2e/apoio/telas.js` (as ações de cada pessoa ficam escritas uma vez só ali).

Mudou o visual de uma tela que aparece em [GUIA_USUARIO.md](GUIA_USUARIO.md)? Rode `npm run guia` e faça commit das fotos novas.

## Convenções

- Nome do método em português, descritivo: `test_eleitor_que_ja_votou_nao_pode_ser_liberado`.
- Um `assert` principal por teste; vários asserts só se verificam a mesma ação.
- Factories em `database/factories/`. Ao adicionar atributos numa migration, atualize a factory correspondente. Estados recorrentes viram *states* (ex.: `Eleicao::factory()->encerrada()`).
- Não use o seeder nos testes; use factories.

## CI

`.github/workflows/tests.yml` roda `php artisan test` (job `phpunit`) e a eleição de ponta a ponta (job `e2e`) em cada push e PR. Se o `e2e` falhar, o relatório, os vídeos e o trace ficam nos artefatos da execução. PR com CI vermelho não é mergeado.
