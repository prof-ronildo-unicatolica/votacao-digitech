// Ambiente do teste de ponta a ponta (D28). O servidor e os comandos artisan do teste usam as
// mesmas variáveis: banco SQLite próprio (e2e/banco.sqlite), nunca o MySQL de desenvolvimento.
const path = require('node:path');
const fs = require('node:fs');
const { execFileSync } = require('node:child_process');

const RAIZ = path.resolve(__dirname, '..', '..');
const PORTA = Number(process.env.E2E_PORTA || 8766);
const URL_BASE = `http://127.0.0.1:${PORTA}`;
const BANCO = path.join(RAIZ, 'e2e', 'banco.sqlite');
const RELOGIO = path.join(RAIZ, 'storage', 'framework', 'e2e-relogio');

const VARIAVEIS = {
  APP_ENV: 'e2e',
  APP_DEBUG: 'true',
  APP_URL: URL_BASE,
  APP_KEY: 'base64:ZTJlLWRpZ2l0ZWNoLWNoYXZlLXNvLWRlLXRlc3RlISE=',
  DB_CONNECTION: 'sqlite',
  DB_URL: '',
  DB_DATABASE: BANCO,
  DB_BUSY_TIMEOUT: '10000',
  DB_JOURNAL_MODE: 'wal',
  SESSION_DRIVER: 'database',
  SESSION_SECURE_COOKIE: 'false',
  CACHE_STORE: 'database',
  QUEUE_CONNECTION: 'sync',
  MAIL_MAILER: 'array',
  LOG_CHANNEL: 'single',
};

const ambiente = () => ({ ...process.env, ...VARIAVEIS });

/** Roda `php artisan ...` no ambiente do teste e devolve a saída. */
function artisan(...argumentos) {
  return execFileSync('php', ['artisan', ...argumentos], { cwd: RAIZ, env: ambiente(), encoding: 'utf8' });
}

/** Adianta o relógio da aplicação (AppServiceProvider lê o arquivo, só com APP_ENV=e2e). */
function adiantarRelogio(minutos) {
  fs.writeFileSync(RELOGIO, String(Math.round(minutos * 60)));
}

function zerarRelogio() {
  fs.rmSync(RELOGIO, { force: true });
}

module.exports = { RAIZ, PORTA, URL_BASE, BANCO, ambiente, artisan, adiantarRelogio, zerarRelogio };
