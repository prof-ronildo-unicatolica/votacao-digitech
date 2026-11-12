// O que cada pessoa faz nas telas, escrito uma vez só: login, liberar, votar, cancelar.
const { expect } = require('@playwright/test');

/** "YYYY-MM-DDTHH:mm" no fuso da aplicação (America/Fortaleza, UTC-3 o ano todo), para campo datetime-local. */
function dataHora(base, minutos) {
  return new Date(base.getTime() + minutos * 60_000 - 3 * 3_600_000).toISOString().slice(0, 16);
}

async function entrar(page, { email, senha }) {
  await page.goto('/login');
  await page.getByLabel('E-mail').fill(email);
  await page.getByLabel('Senha', { exact: true }).fill(senha);
  await page.getByRole('button', { name: 'Entrar' }).click();
  await expect(page).not.toHaveURL(/\/login/);
}

/** Valor grande de um <x-indicador> pelo rótulo (ex.: "Comparecimento"). */
function indicador(page, rotulo) {
  return page.locator('.dt-indicador').filter({ has: page.locator('.dt-indicador-rotulo', { hasText: rotulo }) }).locator('.dt-indicador-valor');
}

/** Cartão do terminal no painel da mesa. */
function terminalNaMesa(mesa, numero) {
  return mesa.locator(`article.dt-terminal[aria-label^="Terminal ${numero}:"]`);
}

/** No computador da urna: alguém da mesa entra, escolhe o terminal, e o navegador vira urna. */
async function ativarUrna(urna, mesario, numero) {
  await entrar(urna, mesario);
  await urna.goto('/terminal/ativar');
  await urna.locator(`#terminal-${numero}`).check();
  await urna.getByRole('button', { name: 'Ativar e abrir a urna' }).click();
  await expect(urna.getByRole('heading', { name: 'Aguardando liberação' })).toBeVisible();
}

/** Mesário: confere a matrícula e devolve o alerta com o nome (e o motivo, se não puder votar). */
async function conferir(mesa, matricula) {
  await mesa.goto('/mesario');
  await mesa.locator('#campo-matricula').fill(matricula);
  await mesa.getByRole('button', { name: 'Conferir' }).click();
}

async function liberar(mesa, matricula, terminal) {
  await conferir(mesa, matricula);
  const opcao = mesa.locator('#campo-terminal option', { hasText: new RegExp(`^\\s*Terminal ${terminal} `) });
  await mesa.locator('#campo-terminal').selectOption(await opcao.getAttribute('value'));
  await mesa.getByRole('button', { name: /^Liberar / }).click();
  await expect(mesa.getByText(`Terminal ${terminal} liberado para`)).toBeVisible();
}

/** O mesário cancela a sessão de um terminal pelo painel. */
async function cancelarSessao(mesa, terminal) {
  await mesa.goto('/mesario');
  const cartao = terminalNaMesa(mesa, terminal);
  await cartao.getByText('Cancelar sessão').click();
  await cartao.getByRole('button', { name: 'Confirmar cancelamento' }).click();
  await expect(mesa.getByText(/cancelada\. A liberação pode ser feita de novo/)).toBeVisible();
}

/** Uma opção da cédula pelo nome exato (chapa, "Branco" ou "Nulo"). */
function opcao(urna, nome) {
  return urna.locator('label.dt-opcao').filter({ has: urna.locator('.dt-opcao-nome', { hasText: new RegExp(`^${nome}$`) }) });
}

/** A urna troca sozinha (polling de 3 s) para a cédula do eleitor liberado. */
async function esperarCedula(urna, nome) {
  await expect(urna.getByRole('heading', { name: `Olá, ${nome}` })).toBeVisible({ timeout: 20_000 });
}

async function esperarEspera(urna) {
  await expect(urna.getByRole('heading', { name: 'Aguardando liberação' })).toBeVisible({ timeout: 20_000 });
}

/** O eleitor escolhe, confere e confirma. A urna diz "Voto registrado" e volta para a espera. */
async function votar(urna, nome, escolha) {
  await esperarCedula(urna, nome);
  await opcao(urna, escolha).click();
  await urna.getByRole('button', { name: 'Continuar' }).click();
  await expect(urna.getByRole('heading', { name: 'Confirme seu voto' })).toBeVisible();
  await expect(urna.locator('.dt-opcao--escolhida .dt-opcao-nome')).toHaveText(escolha);
  await urna.getByRole('button', { name: 'Confirmar voto' }).click();
  await expect(urna.getByRole('heading', { name: 'Voto registrado' })).toBeVisible();
  await expect(urna.getByText(escolha)).toHaveCount(0); // a tela final não diz em quem votou
  await esperarEspera(urna);
}

module.exports = { dataHora, entrar, indicador, terminalNaMesa, ativarUrna, conferir, liberar, cancelarSessao, opcao, esperarCedula, esperarEspera, votar };
