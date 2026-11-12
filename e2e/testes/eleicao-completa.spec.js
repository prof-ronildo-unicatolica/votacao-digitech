// Uma eleição inteira pelo navegador (D28): a comissão prepara, os alunos inscrevem chapas, a
// comissão homologa, a mesa libera, os eleitores votam em dois terminais, a votação encerra e
// sai o resultado. Cinco navegadores separados, como cinco computadores: comissão, aluno, mesa
// e as duas urnas. O relógio da aplicação é adiantado entre as fases (apoio/ambiente.js).
const path = require('node:path');
const { test, expect } = require('@playwright/test');
const { artisan, adiantarRelogio } = require('../apoio/ambiente');
const t = require('../apoio/telas');

const ADMIN = { email: 'admin@e2e.local', senha: 'admin-e2e-123' };
const MESARIO = { nome: 'Paulo Henrique Sousa', email: 'mesario@e2e.local', senha: 'mesario-e2e-123' };
const TITULO = 'Eleição do Centro Acadêmico 2026';
const CHAPAS = [
  { numero: 1, nome: 'Chapa Integração', presidente: '2025007', vice: '2025008' },
  { numero: 2, nome: 'Chapa Renovação', presidente: '2025009', vice: '2025010' },
  { numero: 3, nome: 'Chapa Sem Documentos', presidente: '2025001', vice: '2025002' },
];

// Linha do tempo, em minutos a partir do início do teste (o relógio só anda para a frente):
//   inscrições [-10, 10) · homologação [10, 20) · votação [20, 90) · encerrada depois de 90.
const FASE = { homologacao: 12, votacao: 25, expirou: 25 + 31, encerrada: 100 };

test('uma eleição do começo ao fim', async ({ browser }) => {
  const base = new Date(Math.floor(Date.now() / 60_000) * 60_000);
  const novo = async () => (await browser.newContext()).newPage();
  const [comissao, aluno, mesa, urna1, urna2] = await Promise.all([novo(), novo(), novo(), novo(), novo()]);

  await test.step('a comissão cadastra a eleição, os cargos, os eleitores, os terminais e o mesário', async () => {
    await t.entrar(comissao, ADMIN, 'login');

    await comissao.goto('/admin/eleicoes/create');
    await comissao.getByLabel('Título').fill(TITULO);
    await comissao.locator('#campo-inscricao_abre_em').fill(t.dataHora(base, -10));
    await comissao.locator('#campo-inscricao_fecha_em').fill(t.dataHora(base, 10));
    await comissao.locator('#campo-votacao_abre_em').fill(t.dataHora(base, 20));
    await comissao.locator('#campo-votacao_fecha_em').fill(t.dataHora(base, 90));
    await t.foto(comissao, 'eleicao-nova', { inteira: true });
    await comissao.getByRole('button', { name: 'Cadastrar eleição' }).click();
    const linha = comissao.getByRole('row').filter({ hasText: TITULO });
    await expect(linha).toContainText('Inscrições abertas'); // a primeira eleição já nasce ativa
    await t.foto(comissao, 'eleicoes');

    await linha.getByRole('link', { name: 'Cargos' }).click();
    for (const cargo of ['Presidente', 'Vice-presidente']) {
      await comissao.locator('#campo-titulo').fill(cargo);
      await comissao.getByRole('button', { name: 'Adicionar' }).click();
      await expect(comissao.locator(`input[value="${cargo}"]`)).toBeVisible();
    }
    await t.foto(comissao, 'cargos', { inteira: true });

    await comissao.goto('/admin/eleitores/importar');
    await comissao.locator('#campo-arquivo').setInputFiles(path.join(__dirname, '..', 'dados', 'eleitores.csv'));
    await t.foto(comissao, 'eleitores-importar', { inteira: true });
    await comissao.getByRole('button', { name: 'Enviar para conferência' }).click();
    await t.foto(comissao, 'eleitores-conferir', { inteira: true });
    await comissao.getByRole('button', { name: /^Confirmar importação de/ }).click();
    await comissao.goto('/admin/eleitores');
    await expect(comissao.getByText('João Pedro Melo')).toBeVisible();
    await t.foto(comissao, 'eleitores');

    for (const [numero, local] of [[1, 'Laboratório 1'], [2, 'Laboratório 2']]) {
      await comissao.goto('/admin/terminais/create');
      await comissao.locator('#campo-numero').fill(String(numero));
      await comissao.locator('#campo-local').fill(local);
      await comissao.getByRole('button', { name: 'Cadastrar terminal' }).click();
      await expect(comissao.getByText(local)).toBeVisible();
    }
    await t.foto(comissao, 'terminais');

    await comissao.goto('/admin/usuarios/create');
    await comissao.locator('#campo-name').fill(MESARIO.nome);
    await comissao.locator('#campo-email').fill(MESARIO.email);
    await comissao.locator('#campo-perfil').selectOption('mesario');
    await comissao.locator('#campo-password').fill(MESARIO.senha);
    await comissao.locator('#campo-password_confirmation').fill(MESARIO.senha);
    await t.foto(comissao, 'usuario-novo', { inteira: true });
    await comissao.getByRole('button', { name: 'Cadastrar usuário' }).click();
    await expect(comissao.getByText(MESARIO.email)).toBeVisible();
    await t.foto(comissao, 'usuarios');
  });

  await test.step('os alunos inscrevem três chapas pelo formulário público', async () => {
    await aluno.goto('/');
    await expect(aluno.getByRole('heading', { name: 'Inscrições de chapas abertas' })).toBeVisible();
    await t.foto(aluno, 'inicio', { inteira: true });
    for (const chapa of CHAPAS) {
      await aluno.goto('/inscricao');
      await aluno.getByLabel('Nome da chapa').fill(chapa.nome);
      await aluno.getByLabel('E-mail do responsável').fill(`contato${chapa.numero}@aluno.edu.br`);
      await aluno.locator('#matricula-1').fill(chapa.presidente);
      await aluno.locator('#matricula-2').fill(chapa.vice);
      await aluno.locator('#matricula-2').blur(); // o nome do aluno aparece ao lado da matrícula
      if (chapa.numero === 1) await t.foto(aluno, 'inscricao', { inteira: true });
      await aluno.getByRole('button', { name: 'Enviar inscrição' }).click();
      await expect(aluno.getByRole('heading', { name: 'Inscrição recebida' })).toBeVisible();
      await expect(aluno.getByText(chapa.nome)).toBeVisible();
      if (chapa.numero === 1) await t.foto(aluno, 'inscricao-comprovante', { inteira: true });
    }

    await comissao.goto('/admin/chapas');
    await t.foto(comissao, 'chapas-inscritas', { inteira: true });
  });

  await test.step('inscrições fecham, a comissão homologa duas chapas e indefere uma', async () => {
    adiantarRelogio(FASE.homologacao);
    await aluno.goto('/inscricao');
    await expect(aluno.getByText('Inscrições fechadas')).toBeVisible();
    await t.foto(aluno, 'inscricao-fechada');

    await comissao.goto('/admin/chapas');
    for (const numero of [1, 2]) {
      const cartao = comissao.locator(`#chapa-${numero}`);
      await cartao.getByText('Homologar', { exact: true }).click();
      if (numero === 1) await t.foto(comissao, 'chapas-homologar');
      await cartao.getByRole('button', { name: 'Confirmar homologação' }).click();
      await expect(comissao.locator(`#chapa-${numero}`)).toContainText('Homologada');
    }
    const terceira = comissao.locator('#chapa-3');
    await terceira.getByText('Indeferir', { exact: true }).click();
    await terceira.getByRole('textbox').fill('Faltou a declaração de matrícula dos candidatos.');
    await t.foto(comissao, 'chapas-indeferir');
    await terceira.getByRole('button', { name: 'Confirmar indeferimento' }).click();
    await expect(comissao.locator('#chapa-3')).toContainText('Faltou a declaração de matrícula');
    await comissao.goto('/admin');
    await t.foto(comissao, 'painel', { inteira: true });
  });

  await test.step('a votação abre: dois computadores viram urnas e a mesa começa a liberar', async () => {
    adiantarRelogio(FASE.votacao);
    await urna1.goto('/urna');
    await t.foto(urna1, 'urna-nao-ativada');
    await t.ativarUrna(urna1, MESARIO, 1, { fotos: true });
    await t.ativarUrna(urna2, MESARIO, 2);

    await t.entrar(mesa, MESARIO);
    await expect(mesa).toHaveURL(/\/mesario/);
    await expect(t.indicador(mesa, 'Comparecimento')).toHaveText('0');
    await expect(t.indicador(mesa, 'Terminais livres')).toHaveText('2');
    await t.foto(mesa, 'mesa', { inteira: true });

    await comissao.goto('/admin/resultado');
    await expect(comissao.getByText('O resultado sai quando a votação encerrar')).toBeVisible();
    await t.foto(comissao, 'resultado-parcial');
  });

  await test.step('dois eleitores votam ao mesmo tempo, em terminais diferentes', async () => {
    await t.liberar(mesa, '2025001', 1, { fotos: true });
    await t.foto(mesa, 'mesa-liberado');
    await t.liberar(mesa, '2025002', 2);

    // Só as chapas homologadas aparecem, mais branco e nulo.
    await t.esperarCedula(urna1, 'Ana Beatriz Lima');
    await expect(urna1.locator('label.dt-opcao')).toHaveCount(4);
    await expect(t.opcao(urna1, 'Chapa Sem Documentos')).toHaveCount(0);

    // O painel da mesa (atualiza a cada 5 s) mostra os dois ocupados, sem saber em quem votam.
    await expect(t.indicador(mesa, 'Terminais livres')).toHaveText('0', { timeout: 15_000 });
    await expect(t.terminalNaMesa(mesa, 1)).toHaveAttribute('data-estado', 'votando');
    await t.foto(mesa, 'mesa-votando', { inteira: true });

    await Promise.all([
      t.votar(urna1, 'Ana Beatriz Lima', 'Chapa Integração', { fotos: true }),
      t.votar(urna2, 'Bruno Carvalho', 'Chapa Renovação'),
    ]);
    await expect(t.indicador(mesa, 'Comparecimento')).toHaveText('2', { timeout: 15_000 });
    await expect(mesa.getByText('Chapa Integração')).toHaveCount(0);
  });

  await test.step('quem já votou não é liberado de novo', async () => {
    await t.conferir(mesa, '2025001');
    await expect(mesa.getByText(/Ana Beatriz Lima já votou nesta eleição/)).toBeVisible();
    await expect(mesa.getByRole('button', { name: /^Liberar / })).toHaveCount(0);
    await t.foto(mesa, 'mesa-ja-votou');
  });

  await test.step('um eleitor corrige a escolha antes de confirmar e vota em branco; outro anula', async () => {
    await t.liberar(mesa, '2025003', 1);
    await t.esperarCedula(urna1, 'Carla Souza');
    await t.opcao(urna1, 'Chapa Renovação').click();
    await urna1.getByRole('button', { name: 'Continuar' }).click();
    await urna1.getByRole('link', { name: 'Corrigir' }).click();
    await t.votar(urna1, 'Carla Souza', 'Branco');

    await t.liberar(mesa, '2025004', 2);
    await t.votar(urna2, 'Daniel Rocha', 'Nulo');
  });

  await test.step('o mesário cancela uma sessão e libera de novo', async () => {
    await t.liberar(mesa, '2025005', 1);
    await t.esperarCedula(urna1, 'Eva Martins');
    await t.cancelarSessao(mesa, 1, { fotos: true });
    await t.esperarEspera(urna1); // a urna volta sozinha para a espera

    await t.conferir(mesa, '2025005');
    await expect(mesa.getByText(/Sessão anterior cancelada/)).toBeVisible();
    await t.liberar(mesa, '2025005', 1);
    await t.votar(urna1, 'Eva Martins', 'Chapa Integração');
  });

  await test.step('uma liberação expira depois de 30 minutos sem voto e é refeita', async () => {
    await t.liberar(mesa, '2025006', 2);
    await t.esperarCedula(urna2, 'Fábio Nunes');

    adiantarRelogio(FASE.expirou);
    await t.esperarEspera(urna2);
    await mesa.goto('/mesario');
    await expect(t.terminalNaMesa(mesa, 2)).toHaveAttribute('data-estado', 'livre');

    await t.conferir(mesa, '2025006');
    await expect(mesa.getByText(/Sessão anterior expirada/)).toBeVisible();
    await t.foto(mesa, 'mesa-expirada');
    await t.liberar(mesa, '2025006', 2);
    await t.votar(urna2, 'Fábio Nunes', 'Chapa Renovação');

    await t.liberar(mesa, '2025007', 1);
    await t.votar(urna1, 'Gabriela Prado', 'Chapa Integração');

    await mesa.goto('/mesario');
    await expect(t.indicador(mesa, 'Comparecimento')).toHaveText('7');
    await expect(mesa.getByText('de 10 aptos · 70%')).toBeVisible();
  });

  await test.step('a votação encerra: a mesa não libera mais e as urnas ficam em espera', async () => {
    adiantarRelogio(FASE.encerrada);
    await mesa.goto('/mesario');
    await expect(mesa.getByText('Liberação indisponível')).toBeVisible();
    await expect(mesa.locator('#campo-matricula')).toHaveCount(0);
    await aluno.goto('/');
    await expect(aluno.getByRole('heading', { name: 'Votação encerrada' })).toBeVisible();
    await t.foto(mesa, 'mesa-encerrada');
    await urna1.reload();
    await expect(urna1.getByRole('heading', { name: 'Aguardando liberação' })).toBeVisible();
  });

  await test.step('a comissão vê o resultado, os eleitos e a ata', async () => {
    await comissao.goto('/admin/resultado');
    await expect(t.indicador(comissao, 'Votos registrados')).toHaveText('7');
    await expect(t.indicador(comissao, 'Brancos e nulos')).toHaveText('2');
    await expect(comissao.getByText('1 branco · 1 nulo')).toBeVisible();
    await expect(comissao.getByText('7 de 10 aptos')).toBeVisible();
    await expect(t.indicador(comissao, 'Chapa vencedora')).toContainText('1 · Chapa Integração');
    const eleitos = comissao.locator('.dt-cartao').filter({ hasText: 'Eleitos · chapa 1' });
    await expect(eleitos.getByRole('row', { name: /Presidente Gabriela Prado/ })).toBeVisible();
    await expect(eleitos.getByRole('row', { name: /Vice-presidente Heitor Alves/ })).toBeVisible();
    await t.foto(comissao, 'resultado', { inteira: true });

    const [ata] = await Promise.all([comissao.context().waitForEvent('page'), comissao.getByRole('link', { name: /Ata para imprimir/ }).click()]);
    await expect(ata.getByText('Chapa Integração').first()).toBeVisible();
    await expect(ata.getByText(/eventos na corrente/)).toBeVisible();
    await t.foto(ata, 'ata', { inteira: true });
  });

  await test.step('a auditoria registrou tudo e a corrente de hashes confere', async () => {
    // Cada ação filtrada pela URL, com o total exato do que aconteceu na história acima.
    const esperado = { 'sessao.votou': '7 eventos', 'sessao.cancelada': '1 evento', 'chapa.homologada': '2 eventos', 'chapa.indeferida': '1 evento' };
    for (const [acao, total] of Object.entries(esperado)) {
      await comissao.goto(`/admin/auditoria?acao=${acao}`);
      await expect(comissao.getByRole('heading', { name: total, exact: true })).toBeVisible();
    }
    await comissao.goto('/admin/auditoria');
    await t.foto(comissao, 'auditoria');
    expect(artisan('digitech:auditoria:verificar')).toContain('A auditoria confere');
  });
});
