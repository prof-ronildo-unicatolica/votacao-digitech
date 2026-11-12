// Depois de tudo: o relógio volta ao normal (o arquivo só vale com APP_ENV=e2e, mas não fica lixo).
const { zerarRelogio } = require('./ambiente');

module.exports = async () => zerarRelogio();
