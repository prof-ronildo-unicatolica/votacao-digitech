# Roadmap

Sprints de 2 semanas. Cada sprint fecha com PR `develop → main` e demo. Ajuste as datas ao calendário da disciplina.

| Sprint | Objetivo                                   | Histórias           | Entrega visível                                             |
| ------ | ------------------------------------------ | ------------------- | ----------------------------------------------------------- |
| **0**  | Ambiente e alinhamento                     | —                   | Todos rodando o scaffold local com testes verdes; Trello montado; wireframes das 3 telas no Figma |
| **1**  | Admin consegue montar uma eleição          | H1, H2, H3, H5      | Login funcionando; eleição, chapas e terminais cadastrados pela interface |
| **2**  | Dia da votação funciona de ponta a ponta   | H4, H6, H7, H8      | Demo: mesário libera, urna acorda, eleitor vota, não vota duas vezes |
| **3**  | Resultado, controle e polimento            | H9, H10, H11        | Painel da mesa ao vivo; resultado com gráfico; auditoria    |
| **4**  | Produção                                   | H12 + correções     | Sistema na Hostinger com HTTPS; simulação de eleição com a turma |

## Sprint 0 em detalhe (semana 1)

| Tarefa                                                       | Responsável   | Doc                    |
| ------------------------------------------------------------ | ------------- | ---------------------- |
| Instalar XAMPP/LAMP, Composer, Git; rodar `php artisan test` | todos         | `INSTALACAO_*.md`      |
| Ler `GIT_FLOW.md` e abrir um PR de treino (ex.: adicionar o próprio nome no README) | todos | `GIT_FLOW.md` |
| Criar quadro no Trello e importar as 12 histórias            | líder         | `HISTORIAS_USUARIO.md` |
| Wireframes: urna, painel do mesário, admin (lista + formulário) | design      | `FIGMA.md`             |
| Assistir as playlists de PHP/Laravel básico                  | todos         | `PLAYLISTS.md`         |

## Ordem de dependência das histórias

```
H1 login ─┬─▶ H2 eleição ─▶ H3 chapas ─┐
          ├─▶ H5 terminais ────────────┼─▶ H6 liberar ─▶ H7 urna aguarda ─▶ H8 votar ─▶ H10 resultado
          └─▶ H4 eleitores ────────────┘                                   └─▶ H9 painel da mesa
H11 auditoria: pode começar após H1 e crescer junto com as demais
H12 deploy: após H8 (dá para publicar antes, como teste)
```

## O que o Laravel já resolve (não criar cartão para isso)

| Necessidade do plano antigo        | Solução pronta no Laravel                                   |
| ---------------------------------- | ----------------------------------------------------------- |
| Parser de `.env`                   | nativo                                                      |
| Classe `Csrf`                      | middleware `VerifyCsrfToken` + `@csrf` no formulário        |
| Classe `Validador`                 | `$request->validate([...])` ou Form Request                 |
| `AutenticacaoMesario`              | `Auth` + middleware `auth` + Gate/Policy por `perfil`       |
| `RateLimiter`                      | middleware `throttle:5,1`                                   |
| Paginação                          | `Model::paginate(20)` + `{{ $itens->links() }}`             |
| Conexão PDO manual                 | Eloquent / `DB`                                             |
| `schema.sql` / `seed.sql`          | migrations / seeders / factories                            |

## Riscos e mitigação

| Risco                                              | Mitigação                                                        |
| -------------------------------------------------- | ---------------------------------------------------------------- |
| Equipe travar na instalação                        | Sprint 0 inteira dedicada; par com quem já conseguiu             |
| PHP 8.2 do XAMPP ficar defasado                    | Laravel 12 segue com segurança até 02/2027; migrar para Herd se preciso |
| Hostinger sem SSH no plano contratado              | Confirmar plano na Sprint 0; alternativa é upload via FTP/Git do hPanel |
| Quebra de sigilo por descuido em nova migration    | `SigiloDoVotoTest` roda no CI                                    |
