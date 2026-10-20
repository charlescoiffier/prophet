/**
 * Régénère les captures du README (docs/images/) à partir du site local et des données de démonstration.
 *
 * Usage : npm run capture:readme
 * Prérequis : base locale configurée (.env), assets construits (npm run build), Node 22 ou plus récent,
 *             Google Chrome (ou CHROME_PATH=/chemin/vers/chrome).
 * Attention : recharge la base locale avec les données de démonstration (migrate:fresh --seed).
 *
 * Chrome est piloté par son protocole de débogage (WebSocket natif de Node, sans dépendance) : c'est le seul
 * moyen d'émuler un vrai téléphone, le mode « --screenshot » imposant une fenêtre d'au moins 500 px.
 */
import { execFileSync, spawn } from 'node:child_process';
import { mkdirSync, mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = fileURLToPath(new URL('..', import.meta.url));
const OUT = join(ROOT, 'docs', 'images');
const PORT = 8099;
const DEBUG_PORT = 9333;
const BASE = `http://127.0.0.1:${PORT}`;
const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

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

/** Petit client CDP : une commande, une réponse. */
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

execFileSync('php', ['artisan', 'migrate:fresh', '--seed', '--no-interaction'], { cwd: ROOT, stdio: 'ignore' });
const card = execFileSync('php', ['artisan', 'tinker', '--execute',
    'echo App\\Models\\Card::where("quote", "like", "%10 000 places%")->value("id");'], { cwd: ROOT }).toString().trim();

// Serveur intégré de PHP (il transmet l'environnement, contrairement à « artisan serve ») : sans bandeau de test.
// Il se lance depuis public/, où le routeur de Laravel cherche index.php.
const server = spawn('php', ['-S', `127.0.0.1:${PORT}`, join(ROOT, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],
    { cwd: join(ROOT, 'public'), env: { ...process.env, PROPHET_TEST_NOTICE: 'false' }, stdio: 'ignore' });
const profile = mkdtempSync(join(tmpdir(), 'prophet-chrome-'));
const chrome = spawn(CHROME, ['--headless=new', '--disable-gpu', '--hide-scrollbars', '--no-first-run',
    `--remote-debugging-port=${DEBUG_PORT}`, `--user-data-dir=${profile}`, 'about:blank'], { stdio: 'ignore' });

try {
    const home = await (await waitFor(`${BASE}/`)).text();
    if (!home.includes('Les paroles passent')) throw new Error("La page d'accueil ne s'affiche pas correctement.");
    const targets = await (await waitFor(`http://127.0.0.1:${DEBUG_PORT}/json/list`)).json();
    const page = await connect(targets.find((target) => target.type === 'page').webSocketDebuggerUrl);
    await page.send('Page.enable');
    mkdirSync(OUT, { recursive: true });

    const shots = [
        ['accueil.png', '/', 1440, 900, 1, false],
        ['tranchees.png', '/tranchees', 1440, 900, 1, false],
        ['fiche.png', `/cartes/${card}`, 1280, 900, 1, false],
        ['proposer.png', '/proposer', 1280, 860, 1, false],
        ['mobile.png', '/', 390, 844, 2, true],
        ['mobile-fiche.png', `/cartes/${card}`, 390, 844, 2, true],
    ];

    for (const [file, path, width, height, scale, mobile] of shots) {
        await page.send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: scale, mobile });
        await page.send('Page.navigate', { url: BASE + path });
        await sleep(2500); // polices, Livewire et widget anti-robot
        const { data } = await page.send('Page.captureScreenshot', { format: 'png' });
        writeFileSync(join(OUT, file), Buffer.from(data, 'base64'));
        console.log(`→ docs/images/${file}`);
    }

    page.close();
} finally {
    chrome.kill();
    server.kill();
    await sleep(300);
    rmSync(profile, { recursive: true, force: true });
}
