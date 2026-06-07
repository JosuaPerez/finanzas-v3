/**
 * useDebtUtils.js — Composable for shared debt/finance utility functions.
 *
 * Extracted from Deudas.vue to eliminate prop-drilling of utility functions
 * into BossCard.vue. Any component can import directly from here.
 *
 * Previously these were defined inline in Deudas.vue and passed as function
 * props (:formatMoney="formatMoney"), which is an anti-pattern in Vue 3.
 * Now BossCard.vue imports this composable directly, making it self-contained.
 */

/**
 * Format a numeric amount as a locale string with 2 decimal places.
 * e.g. 1234567.8 → "1,234,567.80"
 *
 * @param {number|string} amount
 * @returns {string}
 */
export const formatMoney = (amount) =>
    Number(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

/**
 * Return the currency symbol for a given currency code.
 *
 * @param {'DOP'|'USD'} currency
 * @returns {'RD$'|'US$'}
 */
export const getSymbol = (currency) => (currency === 'USD' ? 'US$' : 'RD$');

/**
 * Parse a formatted money string (e.g. "1,234.56") into a plain float.
 * Returns 0 for null/undefined/empty.
 *
 * @param {string|number|null|undefined} val
 * @returns {number}
 */
export const cleanNum = (val) => {
    if (val === null || val === undefined || val === '') return 0;
    return parseFloat(String(val).replace(/,/g, '')) || 0;
};

/**
 * Calculate HP bar stats for a debt (RPG metaphor).
 *
 * Rules:
 *  - Loans      → maxHP = original_amount (or current balance if unset)
 *  - Credit cards → maxHP = credit_limit × (1 + overdraft_percentage / 100)
 *  - If balance has grown beyond maxHP (e.g. due to interest), maxHP is raised
 *    to match so the bar never overflows.
 *  - isCritical: HP% > 80% means the boss is nearly at full strength → danger.
 *
 * @param {Object} debt
 * @param {number} debt.balance
 * @param {'loan'|'credit_card'} debt.type
 * @param {number} [debt.original_amount]
 * @param {number} [debt.credit_limit]
 * @param {number} [debt.overdraft_percentage]
 * @returns {{ current: number, max: number, percent: number, isCritical: boolean }}
 */
export const getHPStats = (debt) => {
    const balance = Number(debt.balance) || 0;

    let maxHP = balance; // Default: maxHP equals current balance

    if (debt.type === 'loan' && Number(debt.original_amount) > 0) {
        maxHP = Number(debt.original_amount);
    } else if (debt.type === 'credit_card' && Number(debt.credit_limit) > 0) {
        const overdraft = Number(debt.overdraft_percentage) || 0;
        maxHP = Number(debt.credit_limit) * (1 + overdraft / 100);
    }

    // Guard: interest may push balance beyond the computed maxHP
    if (balance > maxHP) maxHP = balance;

    const percent = maxHP > 0 ? (balance / maxHP) * 100 : 100;

    return {
        current: balance,
        max: maxHP,
        percent: Math.min(100, percent),
        isCritical: percent > 80,
    };
};

/**
 * Vue custom directive: live currency formatting for <input> elements.
 * Formats input as comma-separated thousands while the user types.
 * Attach with v-money on any text input bound to a numeric model.
 *
 * Usage: const { vMoney } = useMoneyDirective()
 */
export const vMoney = {
    mounted: (el) => {
        el.addEventListener('input', (e) => {
            // Skip programmatic events to avoid infinite loops
            if (!e.isTrusted) return;

            let cursorPosition = el.selectionStart;
            const oldLength = el.value.length;

            // Strip everything except digits and the decimal point
            let val = el.value.replace(/[^\d.]/g, '');
            const parts = val.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            const formatted = parts.join('.');

            if (el.value !== formatted) {
                el.value = formatted;
                // Nudge cursor so it doesn't jump to end when a comma appears
                cursorPosition += formatted.length - oldLength;
                el.setSelectionRange(cursorPosition, cursorPosition);
                el.dispatchEvent(new Event('input'));
            }
        });
    },
};
