/**
 * Calcul de l'atelier portrait, sans accès au DOM : découpe aux ciseaux, deux tons, liseré.
 * Coordonnées en pixels de l'image finale (SIZE × SIZE).
 */

export const SIZE = 600;
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

export function defaultSettings(palette) {
    return {
        version: 1,
        crop: null,
        cuts: [],
        brightness: 0,
        contrast: 0,
        threshold: 128,
        halo: { enabled: false, width: 8 },
        palette: { ...palette },
    };
}

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

export function toneValue(r, g, b, { brightness, contrast }) {
    const gray = 0.299 * r + 0.587 * g + 0.114 * b;
    const value = (gray - 128) * (1 + contrast / 100) + 128 + brightness * 1.28;

    return Math.min(Math.max(value, 0), 255);
}

/**
 * Efface du masque (1 = gardé) les pixels dont le centre est dans le polygone.
 */
function clearPolygon(mask, polygon) {
    for (let y = 0; y < SIZE; y++) {
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
            const to = Math.min(Math.floor(crossings[k + 1] - 0.5), SIZE - 1);
            mask.fill(0, y * SIZE + from, y * SIZE + to + 1);
        }
    }
}

/**
 * Masque dilaté par un disque : chaque pixel gardé au bord du masque y imprime un disque.
 */
export function dilate(mask, radius) {
    const result = mask.slice();
    const offsets = [];
    for (let dy = -radius; dy <= radius; dy++) {
        for (let dx = -radius; dx <= radius; dx++) {
            if (dx * dx + dy * dy <= radius * radius) offsets.push([dx, dy]);
        }
    }
    const kept = (x, y) => x >= 0 && y >= 0 && x < SIZE && y < SIZE && mask[y * SIZE + x] === 1;

    for (let y = 0; y < SIZE; y++) {
        for (let x = 0; x < SIZE; x++) {
            if (!kept(x, y) || (kept(x - 1, y) && kept(x + 1, y) && kept(x, y - 1) && kept(x, y + 1))) continue;
            for (const [dx, dy] of offsets) {
                const nx = x + dx;
                const ny = y + dy;
                if (nx >= 0 && ny >= 0 && nx < SIZE && ny < SIZE) result[ny * SIZE + nx] = 1;
            }
        }
    }

    return result;
}

const rgb = (hex) => [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16));

/**
 * Image finale à partir de la photo recadrée (SIZE × SIZE) et des réglages.
 */
export function renderPortrait(source, settings) {
    const mask = new Uint8Array(SIZE * SIZE).fill(1);
    settings.cuts.forEach((cut) => {
        const polygon = discardedPolygon(cut);
        if (polygon !== null) clearPolygon(mask, polygon);
    });
    const halo = settings.halo.enabled ? dilate(mask, settings.halo.width) : null;

    const dark = rgb(settings.palette.dark);
    const light = rgb(settings.palette.light);
    const haloColor = rgb(settings.palette.halo);
    const data = new Uint8ClampedArray(SIZE * SIZE * 4);

    for (let i = 0; i < SIZE * SIZE; i++) {
        const o = i * 4;
        if (mask[i] === 1) {
            const value = toneValue(source.data[o], source.data[o + 1], source.data[o + 2], settings);
            data.set([...(value < settings.threshold ? dark : light), 255], o);
        } else if (halo !== null && halo[i] === 1) {
            data.set([...haloColor, 255], o);
        }
    }

    return typeof ImageData === 'function' ? new ImageData(data, SIZE, SIZE) : { width: SIZE, height: SIZE, data };
}
