import test from "node:test";
import assert from "node:assert/strict";
import { formatCurrency, parseAmount } from "../../resources/js/utils.js";
import { cleanNum } from "../../resources/js/composables/useDebtUtils.js";

test("decimal keyboards accept comma and point without interpreting thousands as decimals", () => {
    assert.equal(parseAmount("25,50"), 25.5);
    assert.equal(parseAmount("25.50"), 25.5);
    assert.equal(parseAmount("1000"), 1000);
    for (const invalid of [
        "",
        "0",
        "-1",
        "1,234.56",
        "1.234,56",
        "1,234",
        "1.234",
        "1e3",
        "12 euros",
        ".5",
    ]) {
        assert.equal(parseAmount(invalid), null, invalid);
    }
});

test('budget goal and debt inputs preserve decimal commas and reject ambiguous or malformed amounts', () => {
    assert.equal(cleanNum('25,50'), 25.5);
    assert.equal(cleanNum('25.50'), 25.5);
    assert.equal(cleanNum('0'), 0);
    assert.equal(cleanNum(''), 0);
    assert.equal(cleanNum(1000), 1000);
    for (const invalid of ['1,234', '1.234,56', 'abc', '-1', '12e3', '0.001']) {
        assert.ok(Number.isNaN(cleanNum(invalid)), invalid);
    }
});

test("regional formatting preserves record currency and fractional amounts", () => {
    const mexico = formatCurrency(1234.56, "MXN", "es-MX");
    const spain = formatCurrency(1234.56, "EUR", "es-ES");
    assert.match(mexico, /MXN/);
    assert.match(mexico, /1,234\.56/);
    assert.match(spain, /EUR/);
    assert.match(spain, /1234,56|1\.234,56/);
    assert.match(formatCurrency(25.5, "CLP", "es-CL"), /25,50/);
});
