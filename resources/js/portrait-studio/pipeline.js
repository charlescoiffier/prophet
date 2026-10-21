/**
 * Calcul de l'atelier portrait, sans accès au DOM : découpe aux ciseaux, deux tons, liseré.
 * Les coupes et l'épaisseur du liseré sont notées dans un espace de travail de WORK_SIZE × WORK_SIZE ;
 * l'image finale fait settings.size de côté (taille donnée par resources/portrait/studio.json).
 */

export const WORK_SIZE = 600;
const SIZE = WORK_SIZE;
export const SNAP_DISTANCE = 12;
export const MAX_FILE_BYTES = 10 * 1024 * 1024;

const PERIMETER = 4 * SIZE;
const CORNERS = [
    { t: 0, point: [0, 0] },
    { t: SIZE, point: [SIZE, 0] },
    { t: 2 * SIZE, point: [SIZE, SIZE] },
    { t: 3 * SIZE, point: [0, SIZE] },
];
const ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

export function defaultSettings(studio) {
    return {
        version: 1,
        size: studio.size,
        shades: studio.shades ?? 0,
        spread: 40,
        crop: null,
        cuts: [],
        brightness: 0,
        contrast: 0,
        threshold: 128,
        halo: { enabled: false, width: 8 },
        palette: { ...studio.palette },
    };
}

/** Taille de l'image finale ; les réglages d'avant ce paramètre valent 600. */
export const outputSize = (settings) => settings.size ?? WORK_SIZE;

export function checkFile(file) {
    if (!ACCEPTED_TYPES.includes(file.type)) {
        return 'Format non pris en charge : choisissez une photo JPEG, PNG ou WebP.';
    }
    if (file.size > MAX_FILE_BYTES) {
        return 'Photo trop lourde (10 Mo au plus).';
    }

    return null;
}

/**
 * Cadre carré tiré par un coin : le coin opposé (anchor) reste fixe, le carré suit le pointeur
 * sans sortir de la photo ni descendre sous min. Coordonnées en pixels de la photo d'origine.
 */
export function resizeCrop({ anchor: [ax, ay], pointer: [px, py], width, height, min }) {
    const towardRight = px >= ax;
    const towardBottom = py >= ay;
    const room = Math.min(towardRight ? width - ax : ax, towardBottom ? height - ay : ay);
    const size = Math.round(Math.min(Math.max(Math.abs(px - ax), Math.abs(py - ay), min), room));

    return {
        x: Math.round(towardRight ? ax : ax - size),
        y: Math.round(towardBottom ? ay : ay - size),
        size,
    };
}

/**
 * Projette le clic sur le côté le plus proche s'il en est assez près ; sinon null.
 */
export function snapToEdge([x, y]) {
    x = Math.min(Math.max(x, 0), SIZE);
    y = Math.min(Math.max(y, 0), SIZE);
    const sides = [
        { distance: y, point: [x, 0] },
        { distance: SIZE - x, point: [SIZE, y] },
        { distance: SIZE - y, point: [x, SIZE] },
        { distance: x, point: [0, y] },
    ];
    const nearest = sides.reduce((best, side) => (side.distance < best.distance ? side : best));

    return nearest.distance <= SNAP_DISTANCE ? nearest.point : null;
}

/**
 * Position d'un point du pourtour, dans le sens horaire depuis le coin haut gauche.
 */
function perimeterPosition([x, y]) {
    if (y === 0) return x;
    if (x === SIZE) return SIZE + y;
    if (y === SIZE) return 2 * SIZE + (SIZE - x);
    if (x === 0) return 3 * SIZE + (SIZE - y);

    return null;
}

const modulo = (value) => ((value % PERIMETER) + PERIMETER) % PERIMETER;

function area(polygon) {
    return Math.abs(polygon.reduce((sum, [x, y], i) => {
        const [nx, ny] = polygon[(i + 1) % polygon.length];

        return sum + x * ny - nx * y;
    }, 0)) / 2;
}

/**
 * Les deux morceaux d'une coupe : la ligne brisée, refermée par le pourtour dans un sens ou dans l'autre.
 */
export function splitByCut(points) {
    if (points.length < 2) return null;
    const start = perimeterPosition(points[0]);
    const end = perimeterPosition(points[points.length - 1]);
    if (start === null || end === null) return null;

    const clockwise = CORNERS
        .map((corner) => ({ ...corner, distance: modulo(corner.t - end) }))
        .filter(({ distance }) => distance > 0 && distance < modulo(start - end))
        .sort((a, b) => a.distance - b.distance)
        .map(({ point }) => point);
    const counterClockwise = CORNERS
        .map((corner) => ({ ...corner, distance: modulo(end - corner.t) }))
        .filter(({ distance }) => distance > 0 && distance < modulo(end - start))
        .sort((a, b) => a.distance - b.distance)
        .map(({ point }) => point);

    const pieces = [[...points, ...clockwise], [...points, ...counterClockwise]];

    return pieces.every((piece) => area(piece) >= 1) ? pieces : null;
}

function distanceToSegment([px, py], [ax, ay], [bx, by]) {
    const dx = bx - ax;
    const dy = by - ay;
    const length = dx * dx + dy * dy;
    const t = length === 0 ? 0 : Math.min(Math.max(((px - ax) * dx + (py - ay) * dy) / length, 0), 1);

    return Math.hypot(px - (ax + t * dx), py - (ay + t * dy));
}

function contains(polygon, [x, y]) {
    let inside = false;
    for (let i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
        const [xi, yi] = polygon[i];
        const [xj, yj] = polygon[j];
        if ((yi > y) !== (yj > y) && x < ((xj - xi) * (y - yi)) / (yj - yi) + xi) {
            inside = !inside;
        }
    }

    return inside;
}

/**
 * Le morceau jeté : celui qui contient le point cliqué. Rien si le clic tombe sur la ligne.
 */
export function discardedPolygon(cut) {
    const pieces = splitByCut(cut.points);
    if (pieces === null) return null;
    const onLine = cut.points.some((point, i) => i > 0 && distanceToSegment(cut.discard, cut.points[i - 1], point) < 1);
    if (onLine) return null;

    return pieces.find((piece) => contains(piece, cut.discard)) ?? null;
}

/**
 * Couleurs de l'image, du foncé au clair : ton foncé, nuances en dégradé (0 à 2), ton clair.
 */
export function toneColors(palette, shades = 0) {
    const dark = rgb(palette.dark);
    const light = rgb(palette.light);

    return Array.from({ length: shades + 2 }, (_, i) => {
        const t = i / (shades + 1);

        return dark.map((channel, c) => Math.round(channel + (light[c] - channel) * t));
    });
}

/**
 * Seuils entre les couleurs, espacés de spread autour du seuil principal.
 * Les réglages d'avant les nuances n'ont qu'un seuil.
 */
export function toneThresholds({ threshold, spread = 40, shades = 0 }) {
    return Array.from({ length: shades + 1 }, (_, i) => threshold + (i - shades / 2) * spread);
}

export function toneValue(r, g, b, { brightness, contrast }) {
    const gray = 0.299 * r + 0.587 * g + 0.114 * b;
    const value = (gray - 128) * (1 + contrast / 100) + 128 + brightness * 1.28;

    return Math.min(Math.max(value, 0), 255);
}

/**
 * Efface du masque (1 = gardé) les pixels dont le centre est dans le polygone.
 */
function clearPolygon(mask, polygon, size) {
    for (let y = 0; y < size; y++) {
        const center = y + 0.5;
        const crossings = [];
        for (let i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
            const [xi, yi] = polygon[i];
            const [xj, yj] = polygon[j];
            if ((yi > center) !== (yj > center)) {
                crossings.push(((xj - xi) * (center - yi)) / (yj - yi) + xi);
            }
        }
        crossings.sort((a, b) => a - b);
        for (let k = 0; k + 1 < crossings.length; k += 2) {
            const from = Math.max(Math.ceil(crossings[k] - 0.5), 0);
            const to = Math.min(Math.floor(crossings[k + 1] - 0.5), size - 1);
            mask.fill(0, y * size + from, y * size + to + 1);
        }
    }
}

/**
 * Masque dilaté par un disque : chaque pixel gardé au bord du masque y imprime un disque.
 */
export function dilate(mask, radius, size = SIZE) {
    const result = mask.slice();
    const offsets = [];
    for (let dy = -radius; dy <= radius; dy++) {
        for (let dx = -radius; dx <= radius; dx++) {
            if (dx * dx + dy * dy <= radius * radius) offsets.push([dx, dy]);
        }
    }
    const kept = (x, y) => x >= 0 && y >= 0 && x < size && y < size && mask[y * size + x] === 1;

    for (let y = 0; y < size; y++) {
        for (let x = 0; x < size; x++) {
            if (!kept(x, y) || (kept(x - 1, y) && kept(x + 1, y) && kept(x, y - 1) && kept(x, y + 1))) continue;
            for (const [dx, dy] of offsets) {
                const nx = x + dx;
                const ny = y + dy;
                if (nx >= 0 && ny >= 0 && nx < size && ny < size) result[ny * size + nx] = 1;
            }
        }
    }

    return result;
}

const rgb = (hex) => [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16));

/**
 * Image finale à partir de la photo recadrée (settings.size de côté) et des réglages.
 */
export function renderPortrait(source, settings) {
    const size = outputSize(settings);
    const scale = size / WORK_SIZE;
    const mask = new Uint8Array(size * size).fill(1);
    settings.cuts.forEach((cut) => {
        const polygon = discardedPolygon(cut);
        if (polygon !== null) clearPolygon(mask, polygon.map(([x, y]) => [x * scale, y * scale]), size);
    });
    const halo = settings.halo.enabled ? dilate(mask, Math.round(settings.halo.width * scale), size) : null;

    const colors = toneColors(settings.palette, settings.shades ?? 0);
    const thresholds = toneThresholds(settings);
    const haloColor = rgb(settings.palette.halo);
    const data = new Uint8ClampedArray(size * size * 4);

    for (let i = 0; i < size * size; i++) {
        const o = i * 4;
        if (mask[i] === 1) {
            const value = toneValue(source.data[o], source.data[o + 1], source.data[o + 2], settings);
            const level = thresholds.findIndex((threshold) => value < threshold);
            data.set([...colors[level === -1 ? colors.length - 1 : level], 255], o);
        } else if (halo !== null && halo[i] === 1) {
            data.set([...haloColor, 255], o);
        }
    }

    return typeof ImageData === 'function' ? new ImageData(data, size, size) : { width: size, height: size, data };
}
