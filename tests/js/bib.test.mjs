// node --test tests/js — kasus yang SAMA dengan tests/Unit/BibTest.php (PHP).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { normalizeBib, normalizeRegistrationNumber } from '../../resources/js/scanner/bib.js';

const { cases } = JSON.parse(readFileSync(new URL('../Fixtures/bib-normalize-cases.json', import.meta.url), 'utf8'));

for (const { input, expected } of cases) {
    test(`normalizeBib(${JSON.stringify(input)}) → ${JSON.stringify(expected)}`, () => {
        assert.equal(normalizeBib(input), expected);
    });
}

test('normalizeRegistrationNumber', () => {
    assert.equal(normalizeRegistrationNumber(' gtr202600123 '), 'GTR202600123');
    assert.equal(normalizeRegistrationNumber('7001'), null);
    assert.equal(normalizeRegistrationNumber('GTR-2026'), null);
});
