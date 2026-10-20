/**
 * Rend public/logo.svg en icônes PNG (onglet, favoris, écran d'accueil) et en favicon.ico.
 *
 * Usage : npm run build:favicons      Prérequis : Google Chrome (ou CHROME_PATH), Node 22 ou plus récent.
 * Chrome est piloté par son protocole de débogage (WebSocket natif de Node, sans dépendance).
 */
import { spawn } from 'node:child_process';
import { mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = fileURLToPath(new URL('..', import.meta.url));
const PUBLIC = join(ROOT, 'public');
const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const DEBUG_PORT = 9334;
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function waitFor(url) {
    for (let i = 0; i < 60; i++) {
        try {
            const response = await fetch(url);
            if (response.ok) return response;
        } catch {}
        await sleep(250);
    }
    throw new Error(`${url} ne répond pas.`);
}

function connect(webSocketUrl) {
    const socket = new WebSocket(webSocketUrl);
    let id = 0;
    const pending = new Map();
    socket.addEventListener('message', (event) => {
        const message = JSON.parse(event.data);
        if (message.id && pending.has(message.id)) {
            pending.get(message.id)(message);
            pending.delete(message.id);
        }
    });
    const send = (method, params = {}) => new Promise((resolve, reject) => {
        const messageId = ++id;
        pending.set(messageId, (message) => (message.error ? reject(new Error(message.error.message)) : resolve(message.result)));
        socket.send(JSON.stringify({ id: messageId, method, params }));
    });
    return new Promise((resolve) => socket.addEventListener('open', () => resolve({ send, close: () => socket.close() })));
}

/** ICO contenant des images PNG (accepté par tous les navigateurs actuels). */
function ico(pngs) {
    const header = Buffer.alloc(6 + 16 * pngs.length);
    header.writeUInt16LE(0, 0);
    header.writeUInt16LE(1, 2);
    header.writeUInt16LE(pngs.length, 4);
    let offset = header.length;
    pngs.forEach(({ size, data }, index) => {
        const entry = 6 + 16 * index;
        header.writeUInt8(size >= 256 ? 0 : size, entry);
        header.writeUInt8(size >= 256 ? 0 : size, entry + 1);
        header.writeUInt16LE(1, entry + 4);
        header.writeUInt16LE(32, entry + 6);
        header.writeUInt32LE(data.length, entry + 8);
        header.writeUInt32LE(offset, entry + 12);
        offset += data.length;
    });
    return Buffer.concat([header, ...pngs.map(({ data }) => data)]);
}

const svg = readFileSync(join(PUBLIC, 'logo.svg'), 'utf8');
const profile = mkdtempSync(join(tmpdir(), 'prophet-chrome-'));
const chrome = spawn(CHROME, ['--headless=new', '--disable-gpu', '--hide-scrollbars', '--no-first-run',
    `--remote-debugging-port=${DEBUG_PORT}`, `--user-data-dir=${profile}`, 'about:blank'], { stdio: 'ignore' });

try {
    const targets = await (await waitFor(`http://127.0.0.1:${DEBUG_PORT}/json/list`)).json();
    const page = await connect(targets.find((target) => target.type === 'page').webSocketDebuggerUrl);
    const render = async (size, background = 'transparent', padding = 0) => {
        await page.send('Emulation.setDeviceMetricsOverride', { width: size, height: size, deviceScaleFactor: 1, mobile: false });
        await page.send('Emulation.setDefaultBackgroundColorOverride', { color: { r: 0, g: 0, b: 0, a: 0 } });
        const html = `<html><body style="margin:0;background:${background}"><div style="box-sizing:border-box;width:${size}px;height:${size}px;padding:${padding}px">`
            + svg.replace('<svg ', `<svg width="${size - 2 * padding}" height="${size - 2 * padding}" `) + '</div></body></html>';
        await page.send('Page.navigate', { url: 'data:text/html;base64,' + Buffer.from(html).toString('base64') });
        await sleep(300);
        const { data } = await page.send('Page.captureScreenshot', { format: 'png', omitBackground: background === 'transparent' });
        return Buffer.from(data, 'base64');
    };

    const favicon32 = await render(32);
    const favicon16 = await render(16);
    writeFileSync(join(PUBLIC, 'favicon-32.png'), favicon32);
    writeFileSync(join(PUBLIC, 'favicon.ico'), ico([{ size: 16, data: favicon16 }, { size: 32, data: favicon32 }]));
    // Écran d'accueil : fond papier plein, le système arrondit lui-même les angles.
    writeFileSync(join(PUBLIC, 'apple-touch-icon.png'), await render(180, '#F7F4EA', 18));
    writeFileSync(join(PUBLIC, 'icon-192.png'), await render(192, '#F7F4EA', 19));
    writeFileSync(join(PUBLIC, 'icon-512.png'), await render(512, '#F7F4EA', 51));
    console.log('→ public/favicon.ico, favicon-32.png, apple-touch-icon.png, icon-192.png, icon-512.png');
    page.close();
} finally {
    chrome.kill();
    await sleep(300);
    rmSync(profile, { recursive: true, force: true });
}
