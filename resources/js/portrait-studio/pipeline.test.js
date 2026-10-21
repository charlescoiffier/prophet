import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import {
    WORK_SIZE as SIZE,
    checkFile,
    defaultSettings,
    discardedPolygon,
    renderPortrait,
    resizeCrop,
    snapToEdge,
    splitByCut,
    toneColors,
    toneThresholds,
    toneValue,
} from './pipeline.js';

const studio = JSON.parse(readFileSync(new URL('../../portrait/studio.json', import.meta.url)));
const palette = studio.palette;

const area = (polygon) =>
    Math.abs(polygon.reduce((sum, [x, y], i) => {
        const [nx, ny] = polygon[(i + 1) % polygon.length];
        return sum + x * ny - nx * y;
    }, 0)) / 2;

const grayImage = (value, size = SIZE) => {
    const data = new Uint8ClampedArray(size * size * 4);
    for (let i = 0; i < data.length; i += 4) {
        data.set([value, value, value, 255], i);
    }
    return { width: size, height: size, data };
};

const pixel = (image, x, y) => Array.from(image.data.slice((y * image.width + x) * 4, (y * image.width + x) * 4 + 4));

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
    const settings = { ...defaultSettings(studio), shades: 0, cuts: [horizontalCut] };

    const dark = renderPortrait(grayImage(100), { ...settings, threshold: 128 });
    assert.equal(pixel(dark, 300, 100)[3], 0);
    assert.deepEqual(pixel(dark, 300, 500), rgba(palette.dark));

    const light = renderPortrait(grayImage(100), { ...settings, threshold: 50 });
    assert.deepEqual(pixel(light, 300, 500), rgba(palette.light));
});

test('renderPortrait trace le liseré le long de la coupe', () => {
    const settings = { ...defaultSettings(studio), cuts: [horizontalCut], halo: { enabled: true, width: 8 } };
    const image = renderPortrait(grayImage(100), settings);

    assert.deepEqual(pixel(image, 300, 296), rgba(palette.halo));
    assert.equal(pixel(image, 300, 280)[3], 0);
});

test('studio.json donne la taille finale, les nuances et trois couleurs #RRGGBB', () => {
    assert.ok(Number.isInteger(studio.size) && studio.size >= 100);
    assert.ok([0, 1, 2].includes(studio.shades));
    assert.deepEqual(Object.keys(studio.palette).sort(), ['dark', 'halo', 'light']);
    Object.values(studio.palette).forEach((color) => assert.match(color, /^#[0-9A-Fa-f]{6}$/));
});

test('defaultSettings reprend la taille, les couleurs et les nuances du fichier', () => {
    const settings = defaultSettings({ size: 800, shades: 2, palette });
    assert.equal(settings.size, 800);
    assert.equal(settings.shades, 2);
    assert.deepEqual(settings.palette, palette);
});

test('toneColors ajoute les nuances en dégradé entre le ton foncé et le ton clair', () => {
    const blackWhite = { dark: '#000000', light: '#FFFFFF', halo: '#FFFFFF' };
    assert.deepEqual(toneColors(blackWhite, 0), [[0, 0, 0], [255, 255, 255]]);
    assert.deepEqual(toneColors(blackWhite, 1), [[0, 0, 0], [128, 128, 128], [255, 255, 255]]);
    assert.deepEqual(toneColors(blackWhite, 2), [[0, 0, 0], [85, 85, 85], [170, 170, 170], [255, 255, 255]]);
});

test('toneThresholds espace les seuils autour du seuil principal', () => {
    assert.deepEqual(toneThresholds({ threshold: 128, spread: 40, shades: 0 }), [128]);
    assert.deepEqual(toneThresholds({ threshold: 128, spread: 40, shades: 1 }), [108, 148]);
    assert.deepEqual(toneThresholds({ threshold: 128, spread: 40, shades: 2 }), [88, 128, 168]);
    assert.deepEqual(toneThresholds({ threshold: 128 }), [128]);
});

test('renderPortrait place un gris moyen dans la nuance intermédiaire', () => {
    const settings = { ...defaultSettings({ size: SIZE, shades: 1, palette }), spread: 40 };
    const image = renderPortrait(grayImage(128), settings);

    assert.deepEqual(pixel(image, 300, 300), [...toneColors(palette, 1)[1], 255]);
});

test('renderPortrait rend à la taille demandée, coupes notées sur 600', () => {
    const settings = { ...defaultSettings({ size: 300, palette }), cuts: [horizontalCut] };
    const image = renderPortrait(grayImage(100, 300), settings);

    assert.equal(image.width, 300);
    assert.equal(pixel(image, 150, 50)[3], 0);
    assert.deepEqual(pixel(image, 150, 250), rgba(palette.dark));
});

test('checkFile refuse un fichier trop lourd ou illisible', () => {
    assert.equal(typeof checkFile({ size: 11 * 1024 * 1024, type: 'image/jpeg' }), 'string');
    assert.equal(typeof checkFile({ size: 1000, type: 'image/heic' }), 'string');
    assert.equal(checkFile({ size: 1000, type: 'image/png' }), null);
});

test('resizeCrop garde le coin opposé fixe, un carré dans la photo', () => {
    const photo = { width: 1200, height: 900, min: 50 };
    assert.deepEqual(resizeCrop({ ...photo, anchor: [100, 100], pointer: [400, 300] }), { x: 100, y: 100, size: 300 });
    assert.deepEqual(resizeCrop({ ...photo, anchor: [100, 100], pointer: [0, 0] }), { x: 0, y: 0, size: 100 });
    assert.deepEqual(resizeCrop({ ...photo, anchor: [100, 100], pointer: [1500, 120] }), { x: 100, y: 100, size: 800 });
    assert.deepEqual(resizeCrop({ ...photo, anchor: [100, 100], pointer: [110, 105] }), { x: 100, y: 100, size: 50 });
});
