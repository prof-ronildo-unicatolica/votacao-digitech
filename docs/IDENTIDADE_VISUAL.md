# Identidade visual

A Votação DigiTech é um produto **da UniCatólica**: usa a marca da instituição sem alterá-la e acrescenta só o que um sistema de votação precisa (estados, acessibilidade, neutralidade entre chapas).

Tudo o que está aqui tem uma fonte única de verdade:

| O quê                         | Onde                                   | Quem usa                         |
| ----------------------------- | -------------------------------------- | -------------------------------- |
| Tokens (cores, fontes, espaços) | `design/tokens/tokens.json`          | Figma (Tokens Studio) e o gerador de CSS |
| Variáveis CSS geradas         | `public/css/tokens.css`                | Telas Blade                      |
| Arquivos da marca (alta resolução) | `design/marca/`                   | Figma, apresentações, impressos  |
| Arquivos da marca (web)       | `public/img/marca/`                    | Telas Blade                      |

**Mudou uma cor?** Edite `tokens.json`, rode `php design/tokens/gerar-css.php` e reimporte no Figma. Nunca edite `tokens.css` à mão: o teste `TokensTest` falha.

## Princípios

1. **Institucional, não festivo.** Uma eleição precisa transmitir seriedade. Laranja é acento e ação; a base é grafite e branco, como no logotipo.
2. **Neutro entre chapas.** Todas as chapas têm o mesmo peso visual na urna, na ordem do número. O laranja da marca **nunca** é cor de chapa, para nenhuma parecer "a oficial".
3. **Legível em pé, a um metro.** A urna é usada por pessoas nervosas, às vezes sem óculos: fonte mínima de 24px, alvos de 88px de altura.
4. **Estado nunca só por cor.** Livre, liberado, votando, erro: sempre cor + ícone + palavra.
5. **Contraste AA verificado por teste**, não no olho.

## Marca

| Arquivo (`design/marca/`)            | Quando usar                                       |
| ------------------------------------ | ------------------------------------------------- |
| `unicatolica-logo-cor.png`           | Padrão, sobre fundo branco ou claro               |
| `unicatolica-logo-preto.png`         | Impressão em uma cor, documentos (ata, PDF)       |
| `unicatolica-logo-branco.png`        | Sobre grafite 900 ou fotos escuras                |
| `unicatolica-simbolo-laranja.png`    | Ícone do sistema, favicon, espaço reduzido        |
| `unicatolica-simbolo-branco.png`     | Símbolo sobre fundo escuro ou laranja 700         |
| `unicatolica-simbolo-grafite.png`    | Símbolo em documentos monocromáticos              |

Os arquivos originais (fornecidos pela instituição) estão em `_raw/`, fora do repositório. As versões branca e grafite foram geradas recolorindo os originais, sem redesenhar.

**Não faça:** esticar, girar, recolorir em outras cores, aplicar sombra ou contorno, colocar o logo colorido sobre o laranja, ou redesenhar o símbolo. Deixe em volta do logo uma área livre igual à altura do "u" de *uni*. Tamanho mínimo: 120px de largura para o logo, 24px para o símbolo.

**Assinatura do produto:** símbolo + "Votação" (Poppins 600) + "DigiTech" (Poppins 600, laranja 700 no claro e laranja 500 no escuro), com "UniCatólica" em texto secundário abaixo. É montada em HTML/CSS (componente `x-marca`), não é uma imagem.

## Cores

### Marca

| Token          | Hex       | Uso                                                       |
| -------------- | --------- | --------------------------------------------------------- |
| `laranja.500`  | `#F5821F` | O laranja do logotipo. Detalhes, ícones, barra ativa, áreas grandes. **Não use para texto sobre branco** (2.6:1). |
| `laranja.600`  | `#EB681C` | O laranja do símbolo. Estado "liberado" no painel.        |
| `laranja.700`  | `#B5480A` | **Ação primária** (botões), links, texto em laranja. Branco sobre ele: 5.4:1. |
| `laranja.800`  | `#963A0A` | Hover/pressionado da ação primária.                       |
| `laranja.50`   | `#FFF4EB` | Fundo de destaque (chapa selecionada, aviso de marca).    |
| `grafite.900`  | `#18181A` | O preto de *uni*. Texto principal, barra superior, menu lateral. |
| `grafite.600`  | `#58585A` | O cinza do subtítulo do logotipo. Texto secundário.       |
| `grafite.50`   | `#F7F7F8` | Fundo de página.                                          |

A escala completa (50 a 900 de cada) está em `tokens.json`.

### Semânticas

| Papel     | Texto     | Fundo     | Quando                                  |
| --------- | --------- | --------- | --------------------------------------- |
| sucesso   | `#1E7A4C` | `#E7F5EE` | Voto registrado, terminal livre, importação ok |
| aviso     | `#8A5A00` | `#FFF5D6` | Sessão perto de expirar, eleição não iniciada |
| perigo    | `#B42318` | `#FDECEA` | Eleitor já votou, erro, cancelar sessão |
| info      | `#1D5FA8` | `#E8F1FB` | Terminal votando, mensagens neutras     |

O amarelo de aviso foi puxado para o mostarda de propósito: um aviso não pode ser confundido com o laranja da marca.

### Estados do terminal (H5, H9)

| Estado    | Cor             | Ícone (Bootstrap Icons) | Rótulo      |
| --------- | --------------- | ----------------------- | ----------- |
| livre     | sucesso         | `bi-check-circle`       | Livre       |
| liberado  | `laranja.600`   | `bi-unlock`             | Liberado    |
| votando   | info            | `bi-hourglass-split`    | Votando     |
| inativo   | `grafite.400`   | `bi-dash-circle`        | Inativo     |

### Dados (gráfico da apuração, H10)

| Chapa (pela ordem do número) | 1 | 2 | 3 | 4 | 5 | 6 | Brancos/nulos |
| ---------------------------- | - | - | - | - | - | - | ------------- |
| Cor | `#2A78D6` | `#1BAF7A` | `#6250D6` | `#EDA100` | `#E87BA4` | `#008300` | `#9E9EA4` |

- A cor segue a **chapa**, nunca a posição no resultado: a chapa 1 é azul antes e depois da apuração.
- Paleta validada para os três tipos de daltonismo. Algumas cores ficam abaixo de 3:1 sobre branco, então o gráfico **sempre** vem com a tabela e com rótulos (nome + número) — já é critério da H10.
- Mais de 6 chapas: as excedentes entram como "Outras" no gráfico; a tabela mostra todas.

## Tipografia

| Família         | Uso                                         | Por quê                         |
| --------------- | ------------------------------------------- | ------------------------------- |
| **Poppins**     | Títulos, números grandes, assinatura        | É a fonte do site da UniCatólica |
| **Inter**       | Texto, formulários, tabelas                 | Feita para tela; números tabulares alinham a apuração |
| **JetBrains Mono** | Matrícula, UUID                          | Diferencia 0/O e 1/l na conferência |

Todas do Google Fonts, carregadas no layout. Pesos: 400, 500, 600, 700.

| Token  | Tamanho | Uso                                        |
| ------ | ------- | ------------------------------------------ |
| `xs`   | 12px    | Legendas                                   |
| `sm`   | 14px    | Texto auxiliar, tabelas densas             |
| `md`   | 16px    | Texto padrão                               |
| `xl`   | 20px    | Título de cartão                           |
| `2xl`  | 24px    | Título de seção; **mínimo na urna**        |
| `3xl`  | 32px    | Título de página; texto das opções da urna |
| `4xl`  | 40px    | Título da urna                             |
| `5xl`  | 56px    | Número da chapa, número de destaque        |

## Espaço, forma e profundidade

- **Grade de 4px**: `espaco.1` (4px) a `espaco.8` (64px).
- **Raios**: 6px (campos, selos), 10px (botões, cartões), 16px (painéis da urna, modais).
- **Sombras**: discretas; `sm` em cartões, `md` em elementos flutuantes, `lg` só na urna.
- **Foco**: contorno de 3px em `laranja.700`, afastado 2px. Nunca remova o foco.
- **Alvo mínimo**: 48px em qualquer tela; 88px nas opções da urna.

## Componentes

Componentes Blade em `resources/views/components/`, estilos em `public/css/digitech.css` (só variáveis `--dt-*`, nada de cor solta). A vitrine com todos eles fica em **`/estilo`** (só fora de produção), e as telas de referência em `/estilo/telas/...`. Cada componente deve ter um componente com o mesmo nome no Figma.

| Componente               | Uso                                                        | Exemplo |
| ------------------------ | ---------------------------------------------------------- | ------- |
| `<x-marca>`              | Assinatura do produto (`tema="escuro"`, `tamanho="grande"`) | topo de todas as telas |
| `<x-botao>`              | `variante`: primario (uma por tela), secundario, contorno, fantasma, perigo; `tamanho`: grande, pequeno; `icone`; `href` | `<x-botao icone="unlock">Liberar</x-botao>` |
| `<x-campo>`              | Rótulo + campo + ajuda + erro de validação do Laravel; `mono` para matrícula | `<x-campo name="matricula" label="Matrícula" mono autofocus />` |
| `<x-alerta>`             | `tipo`: info, sucesso, aviso, perigo; `titulo`             | eleitor já votou |
| `<x-cartao>`             | Bloco com `titulo`, `subtitulo`, slot `acoes`, `sem-padding` para tabelas | listas do admin |
| `<x-selo>`               | Situação curta: neutro, sucesso, aviso, perigo, info, marca | "Em andamento" |
| `<x-indicador>`          | Número de destaque com `rotulo`, `detalhe` e `progresso` (%) | comparecimento |
| `<x-status-terminal>`    | `estado`: livre, liberado, votando, inativo; slot para ações | painel do mesário |
| `<x-opcao-chapa>`        | Rádio grande da urna; `:neutra="true"` para branco/nulo    | urna |
| `<x-estado-vazio>`       | Lista sem itens, com chamada para ação                     | "Nenhum eleitor" |

Tabelas usam `<table class="table dt-tabela">`; números de destaque, `dt-numeros`; matrícula e UUID, `dt-mono`.

**Layouts** (`resources/views/layouts/`):

| Layout    | Para                          | Seções                                               |
| --------- | ----------------------------- | ---------------------------------------------------- |
| `app`     | Telas simples, início         | `titulo`, `conteudo`                                 |
| `painel`  | Admin, com menu lateral       | `titulo`, `sobretitulo`, `acoes`, `menu` (item ativo), `conteudo` |
| `urna`    | Urna em modo quiosque         | `titulo`, `topo-direita`, `conteudo`                 |
| `base`    | Esqueleto dos três (fontes, CSS) | `corpo`, pilhas `head` e `scripts`                |

**Telas de referência** (dados fictícios, a partir dos rascunhos da equipe): urna aguardando, escolha, confirmação e voto registrado; painel do mesário; resultado; login. Elas mostram como compor os componentes. As telas reais (histórias H1 a H11) devem ser construídas com os mesmos componentes.



Detalhes em `FIGMA.md`.

1. **Tokens** → plugin *Tokens Studio for Figma* → *Load from file* → `design/tokens/tokens.json` → *Create variables* / *Create styles*. As cores, fontes e espaços viram variáveis do arquivo.
2. **Componentes e telas** → extensão do Chrome *html.to.design* em `http://localhost:8000/estilo` e em cada `/estilo/telas/...` → cada página vira um frame editável, com as camadas, cores e fontes. Depois, transforme cada bloco em componente do Figma com o mesmo nome do Blade.
3. **Marca** → arraste os PNG de `design/marca/` para o arquivo.

Sombras (efeitos) podem não importar pelo Tokens Studio dependendo da versão; nesse caso crie os 3 estilos de efeito à mão com os valores de `tokens.json`.
