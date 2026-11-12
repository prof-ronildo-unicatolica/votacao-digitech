// Teste de ponta a ponta (D28): `cd e2e && npm ci && npx playwright install chromium && npm test`.
const path = require('node:path');
const { defineConfig, devices } = require('@playwright/test');
const { RAIZ, PORTA, URL_BASE, ambiente } = require('./apoio/ambiente');

module.exports = defineConfig({
  testDir: './testes',
  // Uma eleição é uma história só, com o relógio compartilhado: nada em paralelo.
  workers: 1,
  fullyParallel: false,
  timeout: 5 * 60 * 1000,
  expect: { timeout: 15 * 1000 },
  retries: 0,
  reporter: [['list'], ['html', { open: 'never' }]],
  globalTeardown: require.resolve('./apoio/encerrar'),
  use: {
    baseURL: URL_BASE,
    locale: 'pt-BR',
    timezoneId: 'America/Fortaleza',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    ...devices['Desktop Chrome'],
  },
  webServer: {
    // O servidor embutido do PHP, com UM processo: as requisições da mesa, das urnas e da
    // comissão entram em fila. Com vários, o SQLite recusaria escritas simultâneas (no PHP < 8.4 o
    // Laravel não abre transação IMMEDIATE), e o erro seria do ambiente de teste, não da aplicação.
    // O roteador do Laravel (o mesmo do `artisan serve`) procura o index.php na pasta atual.
    command: `node ${path.join(__dirname, 'apoio/preparar.js')} && php -S 127.0.0.1:${PORTA} ${path.join(RAIZ, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')}`,
    cwd: path.join(RAIZ, 'public'),
    env: ambiente(),
    url: `${URL_BASE}/api/health`,
    reuseExistingServer: false,
    timeout: 60 * 1000,
    stdout: 'ignore',
    stderr: 'pipe',
  },
});
