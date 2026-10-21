/**
 * Atelier portrait (modération) : recadrer, découper aux ciseaux, passer en deux tons.
 * Le calcul est dans pipeline.js ; ici, l'affichage et les clics.
 */
import {
    WORK_SIZE as SIZE,
    checkFile,
    defaultSettings,
    discardedPolygon,
    outputSize,
    renderPortrait,
    resizeCrop,
    snapToEdge,
    splitByCut,
} from './pipeline.js';

const INK = '#20212B';

const STEPS = ['crop', 'cut', 'tones'];

/** Demi-côté des poignées du cadre, en pixels du canevas. */
const HANDLE = 9;

/** Copie simple : structuredClone refuse les objets réactifs d'Alpine. */
const copy = (value) => JSON.parse(JSON.stringify(value));

function checkerPattern(context) {
    const tile = document.createElement('canvas');
    tile.width = tile.height = 16;
    const tileContext = tile.getContext('2d');
    tileContext.fillStyle = '#ffffff';
    tileContext.fillRect(0, 0, 16, 16);
    tileContext.fillStyle = '#d4d4d4';
    tileContext.fillRect(0, 0, 8, 8);
    tileContext.fillRect(8, 8, 8, 8);

    return context.createPattern(tile, 'repeat');
}

function loadImage(source) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => resolve(image);
        image.onerror = reject;
        image.src = source;
    });
}

function upload($wire, name, file) {
    return new Promise((resolve, reject) => $wire.upload(name, file, resolve, reject));
}

function portraitStudio({ statePath, originalUrl, studio }) {
    return {
        open: false,
        step: 'crop',
        /** Dernière étape atteinte : on ne valide qu'après être passé par les trois. */
        reached: 0,
        error: null,
        hint: null,
        busy: false,
        source: null,
        originalFile: null,
        settings: defaultSettings(studio),
        currentCut: [],
        pendingCut: null,
        pointer: null,
        dragging: null,
        cursor: 'default',
        sourceData: null,
        frame: null,

        get canvas() {
            return this.$refs.canvas;
        },

        get minSide() {
            return this.source ? Math.min(this.source.width, this.source.height) : 0;
        },

        get lowResolution() {
            return this.settings.crop !== null && this.settings.crop.size < outputSize(this.settings);
        },

        init() {
            this.$watch('settings', () => this.draw(), { deep: true });
        },

        chooseFile() {
            this.$refs.file.value = '';
            this.$refs.file.click();
        },

        async fileChosen(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.error = checkFile(file);
            if (this.error) return;

            try {
                this.source = await createImageBitmap(file);
            } catch {
                this.error = 'Impossible de lire cette photo : essayez un fichier JPEG ou PNG.';
                return;
            }

            this.originalFile = file;
            this.settings = defaultSettings(studio);
            const size = this.minSide;
            this.settings.crop = {
                x: Math.round((this.source.width - size) / 2),
                y: Math.round((this.source.height - size) / 2),
                size,
            };
            this.reached = 0;
            this.show('crop');
        },

        async retouch() {
            this.error = null;
            const state = this.$wire.get(statePath);
            if (!this.source) {
                try {
                    this.source = await loadImage(originalUrl);
                } catch {
                    this.error = 'Photo d’origine introuvable, choisissez un nouveau fichier.';
                    return;
                }
            }
            // Réglages d'avant les nuances : deux tons, comme à l'enregistrement.
            this.settings = { shades: 0, spread: 40, ...copy(state.settings) };
            // Photo déjà passée par les trois étapes : toutes restent accessibles.
            this.reached = STEPS.length - 1;
            this.show('cut');
        },

        remove() {
            this.source = null;
            this.originalFile = null;
            this.$wire.set(statePath, { path: null, original_path: null, settings: null, image: null, original: null });
        },

        show(step) {
            this.open = true;
            this.goTo(step);
        },

        canOpen(step) {
            return STEPS.indexOf(step) <= this.reached;
        },

        get isLastStep() {
            return this.step === STEPS[STEPS.length - 1];
        },

        /** Bouton principal : étape suivante, puis Valider à la dernière. */
        next() {
            if (this.isLastStep) {
                this.validate();
                return;
            }
            const index = STEPS.indexOf(this.step) + 1;
            this.reached = Math.max(this.reached, index);
            this.goTo(STEPS[index]);
        },

        goTo(step) {
            if (!this.canOpen(step)) return;
            this.step = step;
            this.currentCut = [];
            this.pendingCut = null;
            this.hint = step === 'cut' ? 'Commencez sur le bord de l’image.' : null;
            if (step !== 'crop') this.captureCrop();
            this.$nextTick(() => this.draw());
        },

        cancel() {
            this.open = false;
            if (this.originalFile) {
                this.source = null;
                this.originalFile = null;
            }
        },

        /**
         * Photo recadrée, à la taille finale, en couleurs : base des découpes et des tons.
         */
        captureCrop() {
            const output = outputSize(this.settings);
            const buffer = document.createElement('canvas');
            buffer.width = buffer.height = output;
            const context = buffer.getContext('2d', { willReadFrequently: true });
            const { x, y, size } = this.settings.crop;
            context.drawImage(this.source, x, y, size, size, 0, 0, output, output);
            this.cropped = buffer;
            this.sourceData = context.getImageData(0, 0, output, output);
        },

        /** Position du pointeur dans les coordonnées du canevas (0 à 600). */
        position(event) {
            const box = this.canvas.getBoundingClientRect();

            return [(event.clientX - box.left) * (SIZE / box.width), (event.clientY - box.top) * (SIZE / box.height)];
        },

        /** Placement de la photo d'origine entière dans le canevas, pour le recadrage. */
        fit() {
            const scale = SIZE / Math.max(this.source.width, this.source.height);

            return {
                scale,
                left: (SIZE - this.source.width * scale) / 2,
                top: (SIZE - this.source.height * scale) / 2,
            };
        },

        /** Point du canevas ramené en pixels de la photo d'origine. */
        toSource([x, y]) {
            const { scale, left, top } = this.fit();

            return [(x - left) / scale, (y - top) / scale];
        },

        /** Coins du cadre, en pixels du canevas, avec le coin opposé de chacun. */
        cropCorners() {
            const { scale, left, top } = this.fit();
            const { x, y, size } = this.settings.crop;
            const x1 = left + x * scale;
            const y1 = top + y * scale;
            const x2 = x1 + size * scale;
            const y2 = y1 + size * scale;

            return [
                { point: [x1, y1], opposite: [x + size, y + size], cursor: 'nwse-resize' },
                { point: [x2, y1], opposite: [x, y + size], cursor: 'nesw-resize' },
                { point: [x2, y2], opposite: [x, y], cursor: 'nwse-resize' },
                { point: [x1, y2], opposite: [x + size, y], cursor: 'nesw-resize' },
            ];
        },

        /** Ce que le pointeur saisit dans le cadre : un coin, le cadre entier, ou rien. */
        cropTarget(point) {
            const corner = this.cropCorners()
                .find(({ point: [cx, cy] }) => Math.abs(point[0] - cx) <= HANDLE + 4 && Math.abs(point[1] - cy) <= HANDLE + 4);
            if (corner) return { mode: 'resize', ...corner };

            const [x1, y1] = this.cropCorners()[0].point;
            const [x2, y2] = this.cropCorners()[2].point;
            const inside = point[0] >= x1 && point[0] <= x2 && point[1] >= y1 && point[1] <= y2;

            return inside ? { mode: 'move', cursor: 'move' } : null;
        },

        moveCrop(x, y, size = this.settings.crop.size) {
            this.settings.crop = {
                size: Math.round(size),
                x: Math.round(Math.min(Math.max(x, 0), this.source.width - size)),
                y: Math.round(Math.min(Math.max(y, 0), this.source.height - size)),
            };
        },

        pointerDown(event) {
            const point = this.position(event);
            if (this.step === 'crop') {
                const target = this.cropTarget(point);
                this.dragging = target && { ...target, point, crop: { ...this.settings.crop } };
            } else if (this.step === 'cut') {
                this.cutClick(point);
            }
        },

        pointerMove(event) {
            const point = this.position(event);
            if (this.step === 'crop' && this.dragging?.mode === 'resize') {
                this.settings.crop = resizeCrop({
                    anchor: this.dragging.opposite,
                    pointer: this.toSource(point),
                    width: this.source.width,
                    height: this.source.height,
                    min: Math.round(this.minSide * 0.2),
                });
            } else if (this.step === 'crop' && this.dragging) {
                const { scale } = this.fit();
                this.moveCrop(
                    this.dragging.crop.x + (point[0] - this.dragging.point[0]) / scale,
                    this.dragging.crop.y + (point[1] - this.dragging.point[1]) / scale,
                );
            } else if (this.step === 'crop') {
                this.cursor = this.cropTarget(point)?.cursor ?? 'default';
            } else if (this.step === 'cut') {
                this.pointer = point;
                this.draw();
            }
        },

        pointerUp() {
            this.dragging = null;
        },

        cutClick(point) {
            if (this.pendingCut) {
                // Un clic juste au-delà du bord vise le morceau qui touche ce bord.
                const discard = point.map((value) => Math.round(Math.min(Math.max(value, 1), SIZE - 1)));
                const cut = { points: this.pendingCut, discard };
                if (discardedPolygon(cut) === null) {
                    this.hint = 'Cliquez à l’intérieur du morceau à jeter.';
                    return;
                }
                this.settings.cuts.push(cut);
                this.pendingCut = null;
                this.hint = 'Coupe faite. Une autre coupe commence sur le bord de l’image.';
                return;
            }

            const onEdge = snapToEdge(point);
            if (this.currentCut.length === 0) {
                if (onEdge === null) {
                    this.hint = 'Commencez sur le bord de l’image.';
                    return;
                }
                this.currentCut = [onEdge];
                this.hint = 'Posez les angles de la coupe, puis finissez sur le bord.';
            } else if (onEdge !== null) {
                const points = [...this.currentCut, onEdge].map((p) => p.map(Math.round));
                this.currentCut = [];
                if (splitByCut(points) === null) {
                    this.hint = 'Cette coupe ne sépare rien : recommencez sur le bord de l’image.';
                } else {
                    this.pendingCut = points;
                    this.hint = 'Cliquez sur le morceau à jeter.';
                }
            } else {
                this.currentCut.push(point);
            }
            this.draw();
        },

        keyDown(event) {
            if (!this.open || this.step !== 'cut') return;
            if (event.key === 'Backspace' && this.currentCut.length > 0) {
                event.preventDefault();
                this.currentCut.pop();
                this.draw();
            } else if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                this.currentCut = [];
                this.pendingCut = null;
                this.hint = 'Commencez sur le bord de l’image.';
                this.draw();
            }
        },

        /** Abandonne la coupe en cours s'il y en a une, sinon retire la dernière coupe faite. */
        undoCut() {
            if (this.pendingCut || this.currentCut.length > 0) {
                this.currentCut = [];
                this.pendingCut = null;
                this.hint = 'Commencez sur le bord de l’image.';
                this.draw();
            } else {
                this.settings.cuts.pop();
            }
        },

        resetCuts() {
            this.settings.cuts = [];
            this.currentCut = [];
            this.pendingCut = null;
        },

        draw() {
            if (!this.open || !this.canvas || !this.source) return;
            cancelAnimationFrame(this.frame);
            this.frame = requestAnimationFrame(() => {
                const context = this.canvas.getContext('2d');
                context.clearRect(0, 0, SIZE, SIZE);
                if (this.step === 'crop') this.drawCrop(context);
                if (this.step === 'cut') this.drawCuts(context);
                if (this.step === 'tones') context.drawImage(this.renderCanvas(), 0, 0, SIZE, SIZE);
            });
        },

        drawCrop(context) {
            const { scale, left, top } = this.fit();
            const { x, y, size } = this.settings.crop;
            context.drawImage(this.source, left, top, this.source.width * scale, this.source.height * scale);
            context.fillStyle = 'rgba(32, 33, 43, 0.6)';
            context.beginPath();
            context.rect(0, 0, SIZE, SIZE);
            context.rect(left + x * scale, top + y * scale, size * scale, size * scale);
            context.fill('evenodd');
            context.strokeStyle = '#ffffff';
            context.lineWidth = 2;
            context.strokeRect(left + x * scale, top + y * scale, size * scale, size * scale);
            this.cropCorners().forEach(({ point: [cx, cy] }) => {
                context.fillStyle = '#ffffff';
                context.fillRect(cx - HANDLE, cy - HANDLE, HANDLE * 2, HANDLE * 2);
                context.strokeStyle = INK;
                context.lineWidth = 1.5;
                context.strokeRect(cx - HANDLE, cy - HANDLE, HANDLE * 2, HANDLE * 2);
            });
        },

        /** Image finale, à sa taille, dans un canevas. */
        renderCanvas() {
            const image = renderPortrait(this.sourceData, this.settings);
            const canvas = document.createElement('canvas');
            canvas.width = canvas.height = image.width;
            canvas.getContext('2d').putImageData(image, 0, 0);

            return canvas;
        },

        drawCuts(context) {
            context.drawImage(this.cropped, 0, 0, SIZE, SIZE);
            context.fillStyle = checkerPattern(context);
            this.settings.cuts.forEach((cut) => {
                const polygon = discardedPolygon(cut);
                if (polygon === null) return;
                context.beginPath();
                polygon.forEach(([x, y], i) => (i === 0 ? context.moveTo(x, y) : context.lineTo(x, y)));
                context.closePath();
                context.fill();
            });

            const line = [...(this.pendingCut ?? this.currentCut)];
            if (!this.pendingCut && line.length > 0 && this.pointer) line.push(snapToEdge(this.pointer) ?? this.pointer);
            if (line.length === 0) return;
            context.strokeStyle = '#ffffff';
            context.lineWidth = 4;
            context.lineJoin = 'miter';
            this.strokeLine(context, line);
            context.strokeStyle = INK;
            context.lineWidth = 2;
            this.strokeLine(context, line);
        },

        strokeLine(context, points) {
            context.beginPath();
            points.forEach(([x, y], i) => (i === 0 ? context.moveTo(x, y) : context.lineTo(x, y)));
            context.stroke();
        },

        async validate() {
            this.busy = true;
            this.error = null;
            try {
                // Depuis l'onglet Recadrer, la photo recadrée n'est pas encore prête (ou date d'un ancien cadre).
                this.captureCrop();
                const blob = await new Promise((resolve) => this.renderCanvas().toBlob(resolve, 'image/png'));

                if (this.originalFile) {
                    await upload(this.$wire, `${statePath}.original`, this.originalFile);
                }
                await this.$wire.set(`${statePath}.settings`, copy(this.settings));
                await upload(this.$wire, `${statePath}.image`, new File([blob], 'portrait.png', { type: 'image/png' }));
                this.originalFile = null;
                this.open = false;
            } catch (exception) {
                console.error(exception);
                this.error = 'L’envoi de la photo a échoué : réessayez.';
            } finally {
                this.busy = false;
            }
        },
    };
}

function register() {
    window.Alpine.data('portraitStudio', portraitStudio);
}

if (window.Alpine) {
    register();
} else {
    document.addEventListener('alpine:init', register);
}
