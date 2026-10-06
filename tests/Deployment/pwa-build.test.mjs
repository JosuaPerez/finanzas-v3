import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync, existsSync } from 'node:fs';

const root = new URL('../../', import.meta.url);
const read = (path) => readFileSync(new URL(path, root), 'utf8');

test('production PWA paths match Laravel build output and never precache private HTML', () => {
    const manifest = JSON.parse(read('public/build/manifest.json'));
    const registerAsset = Object.values(manifest).find(asset => asset.file.includes('virtual_pwa-register'));
    assert.ok(registerAsset, 'Run npm run build before this test');
    const registration = read(`public/build/${registerAsset.file}`);
    assert.match(registration, /"\/sw\.js"/);
    assert.doesNotMatch(registration, /\/build\/sw\.js/);
    assert.match(read('resources/views/app.blade.php'), /href="\/build\/manifest\.webmanifest"/);
    const worker = read('public/sw.js');
    const urls = [...worker.matchAll(/url:"([^"]+)"/g)].map(match => match[1]);
    assert.ok(urls.length > 0);
    for (const url of urls) {
        assert.ok(url.startsWith('/build/assets/') || url === '/build/manifest.webmanifest', url);
        assert.ok(existsSync(new URL(`public${url}`, root)), url);
        assert.doesNotMatch(url, /\.html/);
    }
    const pwa = JSON.parse(read('public/build/manifest.webmanifest'));
    for (const icon of pwa.icons) {
        const bytes = readFileSync(new URL(`public${icon.src}`, root));
        assert.equal(bytes.subarray(0, 8).toString('hex'), '89504e470d0a1a0a');
        const [width, height] = icon.sizes.split('x').map(Number);
        assert.equal(bytes.readUInt32BE(16), width);
        assert.equal(bytes.readUInt32BE(20), height);
    }
});
