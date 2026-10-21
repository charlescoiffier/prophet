import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import {
    SIZE,
    checkFile,
    defaultSettings,
    discardedPolygon,
    renderPortrait,
    snapToEdge,
    splitByCut,
    toneValue,
} from './pipeline.js';

const palette = JSON.parse(readFileSync(new URL('../../portrait/palette.json', import.meta.url)));

const area = (polygon) =>
    Math.abs(polygon.reduce((sum, [x, y], i) => {
        const [nx, ny] = polygon[(i + 1) % polygon.length];
        return sum + x * ny - nx * y;
    }, 0)) / 2;

const grayImage = (value) => {
    const data = new Uint8ClampedArray(SIZE * SIZE * 4);
    for (let i = 0; i < data.length; i += 4) {
        data.set([value, value, value, 255], i);
    }
    return { width: SIZE, height: SIZE, data };
};

const pixel = (image, x, y) => Array.from(image.data.slice((y * SIZE + x) * 4, (y * SIZE + x) * 4 + 4));

const rgba = (hex) => [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16)).concat(255);

const horizontalCut = { points: [[0, 300], [SIZE, 300]], discard: [300, 50] };

test('snapToEdge aimante un clic proche du bord et ignore les autres', () => {
    assert.deepEqual(snapToEdge([5, 300]), [0, 300]);
    assert.deepEqual(snapToEdge([300, 595]), [300, SIZE]);
    assert.equal(snapToEdge([300, 300]), null);
});

test('splitByCut partage l’image en deux morceaux', () => {
    const [a, b] = splitByCut([[0, 300], [SIZE, 300]]);
    assert.equal(area(a), SIZE * 300);
    assert.equal(area(b), SIZE * 300);
});

test('splitByCut ignore une coupe le long d’un seul côté', () => {
    assert.equal(splitByCut([[0, 100], [0, 400]]), null);
});

test('discardedPolygon renvoie le morceau cliqué, rien si le clic est sur la ligne', () => {
    const top = discardedPolygon(horizontalCut);
    assert.ok(top.every(([, y]) => y <= 300));
    assert.equal(discardedPolygon({ ...horizontalCut, discard: [300, 300] }), null);
});

test('toneValue applique luminosité et contraste', () => {
    assert.equal(toneValue(255, 255, 255, { brightness: 0, contrast: 0 }), 255);
    assert.equal(toneValue(128, 128, 128, { brightness: 50, contrast: 0 }), 192);
});

test('renderPortrait rend le morceau jeté transparent et le reste en deux tons', () => {
    const settings = { ...defaultSettings(palette), cuts: [horizontalCut] };

    const dark = renderPortrait(grayImage(100), { ...settings, threshold: 128 });
    assert.equal(pixel(dark, 300, 100)[3], 0);
    assert.deepEqual(pixel(dark, 300, 500), rgba(palette.dark));

    const light = renderPortrait(grayImage(100), { ...settings, threshold: 50 });
    assert.deepEqual(pixel(light, 300, 500), rgba(palette.light));
});

test('renderPortrait trace le liseré le long de la coupe', () => {
    const settings = { ...defaultSettings(palette), cuts: [horizontalCut], halo: { enabled: true, width: 8 } };
    const image = renderPortrait(grayImage(100), settings);

    assert.deepEqual(pixel(image, 300, 296), rgba(palette.halo));
    assert.equal(pixel(image, 300, 280)[3], 0);
});

test('palette.json donne trois couleurs #RRGGBB', () => {
    assert.deepEqual(Object.keys(palette).sort(), ['dark', 'halo', 'light']);
    Object.values(palette).forEach((color) => assert.match(color, /^#[0-9A-Fa-f]{6}$/));
});

test('checkFile refuse un fichier trop lourd ou illisible', () => {
    assert.equal(typeof checkFile({ size: 11 * 1024 * 1024, type: 'image/jpeg' }), 'string');
    assert.equal(typeof checkFile({ size: 1000, type: 'image/heic' }), 'string');
    assert.equal(checkFile({ size: 1000, type: 'image/png' }), null);
});
