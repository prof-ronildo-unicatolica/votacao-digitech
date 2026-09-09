# Testes

## Dois tipos, dois exemplos no repositório

| Tipo                  | Exemplo                            | O que testa                                        | Precisa de banco? |
| --------------------- | ---------------------------------- | -------------------------------------------------- | ----------------- |
| **Unitário**          | `tests/Unit/ApuracaoTest.php`      | Uma classe isolada (`App\Support\Apuracao`)        | Não               |
| **Integração** (Feature) | `tests/Feature/*Test.php`       | Rotas HTTP, controllers, models e o schema real     | Sim (SQLite em memória) |

Regra prática: **regra de negócio que dá para expressar como função pura vai para `app/Support` e ganha teste unitário**. Tudo que passa por rota, banco ou sessão ganha teste de integração.

## Rodando

```bash
php artisan test                       # tudo
php artisan test --testsuite=Unit      # só unitários (rápidos)
php artisan test --filter=ApiStatus    # um arquivo/teste
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
- **Regra que o banco garante** (quando houver): `SigiloDoVotoTest` mostra como testar constraints (unique, cascade, restrict).

## Convenções

- Nome do método em português, descritivo: `test_eleitor_que_ja_votou_nao_pode_ser_liberado`.
- Um `assert` principal por teste; vários asserts só se verificam a mesma ação.
- Factories em `database/factories/`. Ao adicionar atributos numa migration, atualize a factory correspondente. Estados recorrentes viram *states* (ex.: `Eleicao::factory()->encerrada()`).
- Não use o seeder nos testes; use factories.

## CI

`.github/workflows/tests.yml` roda `php artisan test` em cada push e PR. PR com CI vermelho não é mergeado.
