const assert = require('node:assert/strict');
const { calculate } = require('../sections/cross-blocks/cross-blocks.js');

assert.deepEqual(calculate(100, 80, 'buy', { buy: 2, sell: 5 }), { base: 8000, fee: 160, total: 8160 });
assert.deepEqual(calculate(100, 80, 'sell', { buy: 2, sell: 5 }), { base: 8000, fee: 400, total: 7600 });
assert.equal(calculate(0, 80, 'buy', { buy: 2, sell: 5 }), null);
assert.equal(calculate(100, NaN, 'buy', { buy: 2, sell: 5 }), null);
assert.equal(calculate(100, 80, 'sell', { buy: 2, sell: 101 }), null);
console.log('Exchange calculation checks passed.');
