import test from 'node:test';
import assert from 'node:assert/strict';
import { mkdtempSync, readFileSync, writeFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { spawnSync } from 'node:child_process';

function runStartup(failMigration = false) {
    const dir = mkdtempSync(join(tmpdir(), 'finance-startup-'));
    try {
        const source = readFileSync(new URL('../../docker/start.sh', import.meta.url), 'utf8');
        writeFileSync(join(dir, 'ports.conf'), 'Listen 80\n');
        writeFileSync(join(dir, 'site.conf'), '<VirtualHost *:80>\n');
        writeFileSync(join(dir, 'start.sh'), source
            .replaceAll('/etc/apache2/ports.conf', join(dir, 'ports.conf'))
            .replaceAll('/etc/apache2/sites-available/000-default.conf', join(dir, 'site.conf')));
        writeFileSync(join(dir, 'php'), `#!/bin/sh
set -eu
[ "$APP_ENV" = production ] && [ "$APP_DEBUG" = false ]
[ "$APP_KEY" = test-existing-key ] || exit 90
echo "$*" >> "$STARTUP_LOG"
if [ "$*" = 'artisan migrate --force' ] && [ "$FAIL_MIGRATION" = 1 ]; then exit 42; fi
`, { mode: 0o755 });
        writeFileSync(join(dir, 'apache2-foreground'), '#!/bin/sh\necho apache >> "$STARTUP_LOG"\n', { mode: 0o755 });
        const result = spawnSync('sh', [join(dir, 'start.sh')], {
            encoding: 'utf8',
            env: { ...process.env, PATH: `${dir}:${process.env.PATH}`, APP_ENV: 'local',
                APP_DEBUG: 'true', APP_KEY: 'test-existing-key', PORT: '10000',
                STARTUP_LOG: join(dir, 'log'), FAIL_MIGRATION: failMigration ? '1' : '0' },
        });
        return { ...result, commands: readFileSync(join(dir, 'log'), 'utf8').trim().split('\n'),
            ports: readFileSync(join(dir, 'ports.conf'), 'utf8') };
    } finally {
        rmSync(dir, { recursive: true, force: true });
    }
}

test('production startup disables debug, preserves the key and migrates before serving', {
    skip: process.platform === 'win32' ? 'Docker entrypoint requires a POSIX shell' : false,
}, () => {
    const result = runStartup();
    assert.equal(result.status, 0, result.stderr);
    assert.equal(result.ports, 'Listen 10000\n');
    assert.deepEqual(result.commands, ['artisan config:clear', 'artisan migrate --force',
        'artisan config:cache', 'artisan view:cache', 'apache']);
});

test('a failed migration prevents configuration caching and HTTP startup', {
    skip: process.platform === 'win32' ? 'Docker entrypoint requires a POSIX shell' : false,
}, () => {
    const result = runStartup(true);
    assert.equal(result.status, 42);
    assert.deepEqual(result.commands, ['artisan config:clear', 'artisan migrate --force']);
});
