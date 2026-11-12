// Antes do servidor subir (é o próprio comando do webServer, que roda antes do globalSetup):
// banco SQLite vazio, todas as migrations e só o admin (E2eSeeder), relógio no presente.
const fs = require('node:fs');
const { BANCO, artisan, zerarRelogio } = require('./ambiente');

zerarRelogio();
for (const arquivo of [BANCO, `${BANCO}-wal`, `${BANCO}-shm`]) fs.rmSync(arquivo, { force: true });
fs.writeFileSync(BANCO, '');
artisan('migrate', '--force', '--seed', '--seeder=E2eSeeder');
