{{-- ============================================================
     Mesin coretan tangan (data-sketch), port vanilla dari SketchFrame.tsx.
     Setiap [data-sketch] diukur, goresannya diskalakan ke piksel, lalu "digambar" sekali saat terlihat di layar.
     Atribut: data-sketch (bentuk), data-delay, data-duration, data-stagger, data-scale, data-threshold.
     Elemen yang ditambahkan belakangan (x-if Alpine, dsb.) ikut ditangani lewat MutationObserver.
     ============================================================ --}}
<script>
    (() => {
        const SVG_NS = 'http://www.w3.org/2000/svg';
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

        // Koordinat goresan dalam kotak viewBox; hanya perintah absolut M / C / S supaya angkanya selalu berpasangan x y
        const shapes = {
            // Tiap sisi dua goresan: tebal lalu tipis sedikit bergeser
            corner: { viewBox: [140, 56], strokes: [
                ['M3 5C35 3 80 6 136 2', 3, 0],
                ['M4 3C3 20 6 36 4 54', 3, 0],
                ['M9 9C45 7 88 10 122 7', 1.5, 1],
                ['M8 8C7 22 9 36 7 47', 1.5, 1],
            ] },
            // Satu putaran penuh lalu kebablasan melewati titik awal, ditambah goresan tipis di sisi kanan
            circle: { viewBox: [200, 64], strokes: [
                ['M170 10C130 0 55 1 22 14C2 22 3 44 34 54C80 66 160 62 188 46C204 36 196 14 150 7C128 4 104 5 86 8', 2.75, 0],
                ['M182 20C190 30 178 44 150 52', 1.5, 1],
            ] },
            // Arah goresan bolak-balik seperti tangan yang mencoret berulang kali
            underline: { viewBox: [300, 20], strokes: [
                ['M6 10C70 6 150 9 220 6S285 5 296 4', 3.5, 0],
                ['M298 8C280 9 220 10 150 11S40 13 2 13', 2, 1],
                ['M20 16C90 13 170 15 262 11', 1.5, 2],
                ['M120 7C80 8 44 7 10 8', 1.25, 3],
            ] },
            // Memancar dari pojok kiri bawah: ke atas, serong, lalu ke kanan
            sparks: { viewBox: [40, 40], strokes: [
                ['M10 18C9 12 9 8 10 3', 2.75, 0],
                ['M18 23C22 18 27 13 33 8', 2.75, 1],
                ['M23 32C28 31 32 31 37 31', 2.75, 2],
            ] },
            // Batang sedikit bergelombang + goresan tipis, lalu dua sisi kepala panah
            arrow: { viewBox: [48, 24], strokes: [
                ['M3 13C12 11 22 13 31 11S41 11 45 12', 2.75, 0],
                ['M9 15C18 14 27 15 37 13', 1.25, 1],
                ['M34 4C38 7 42 10 46 12', 2.75, 1],
                ['M46 11C42 15 38 18 33 21', 2.75, 2],
            ] },
            // Panah kanan yang dicerminkan pada garis diagonal, jadi ujungnya menghadap ke bawah
            arrowDown: { viewBox: [24, 48], strokes: [
                ['M13 3C11 12 13 22 11 31S11 41 12 45', 2.75, 0],
                ['M15 9C14 18 15 27 13 37', 1.25, 1],
                ['M4 34C7 38 10 42 12 46', 2.75, 1],
                ['M11 46C15 42 18 38 21 33', 2.75, 2],
            ] },
            line: { viewBox: [300, 12], strokes: [
                ['M2 5C70 3 160 7 298 4', 3, 0],
                ['M12 9C90 7 190 10 284 7', 1.5, 1],
            ] },
            lineDown: { viewBox: [12, 300], strokes: [
                ['M5 2C3 70 7 160 4 298', 3, 0],
                ['M9 12C7 90 10 190 7 284', 1.5, 1],
            ] },
            // Garis tipis pemisah baris/kolom tabel
            rule: { viewBox: [300, 12], strokes: [
                ['M2 6C60 4 140 8 210 5S280 6 298 5', 1.25, 0],
            ] },
            ruleDown: { viewBox: [12, 300], strokes: [
                ['M6 2C4 60 8 140 5 210S6 280 5 298', 1.25, 0],
            ] },
        };

        // Bawaan durasi per bentuk, sama dengan komponen React-nya
        const defaultDuration = { circle: 900, underline: 450, sparks: 300, arrow: 350, arrowDown: 350, line: 900, lineDown: 900, rule: 900, ruleDown: 900 };

        const states = new WeakMap();

        // Skalakan setiap pasangan angka x y dari kotak viewBox ke ukuran piksel
        function scalePath(d, sx, sy) {
            let i = 0;
            return d.replace(/-?\d*\.?\d+/g, (n) => (parseFloat(n) * (i++ % 2 === 0 ? sx : sy)).toFixed(1));
        }

        function num(value, fallback) {
            const parsed = parseFloat(value);
            return Number.isFinite(parsed) ? parsed : fallback;
        }

        // Ukur elemen (offsetWidth/Height = ukuran sebelum rotate, jadi siku yang diputar tetap benar), lalu gambar ulang path
        function render(el, state) {
            const w = el.offsetWidth;
            const h = el.offsetHeight;
            if (!w || !h || (w === state.w && h === state.h)) return;
            state.w = w;
            state.h = h;

            const [vbW, vbH] = state.shape.viewBox;
            if (!state.svg) {
                const svg = document.createElementNS(SVG_NS, 'svg');
                svg.setAttribute('fill', 'none');
                svg.setAttribute('stroke', 'currentColor');
                svg.setAttribute('stroke-linecap', 'round');
                svg.setAttribute('stroke-linejoin', 'round');
                svg.setAttribute('aria-hidden', 'true');
                svg.style.display = 'block';
                svg.style.overflow = 'visible';

                state.paths = state.shape.strokes.map(([, width, order]) => {
                    const path = document.createElementNS(SVG_NS, 'path');
                    // pathLength 1 + dasharray 1: dashoffset 1 = belum tergambar, 0 = tergambar penuh
                    path.setAttribute('pathLength', '1');
                    path.setAttribute('stroke-width', String(width * state.scale));
                    path.style.strokeDasharray = '1';
                    path.style.strokeDashoffset = state.drawn || reducedMotion.matches ? '0' : '1';
                    path.style.transitionProperty = 'stroke-dashoffset';
                    path.style.transitionTimingFunction = 'ease-out';
                    path.style.transitionDuration = reducedMotion.matches ? '0ms' : `${state.duration}ms`;
                    path.style.transitionDelay = reducedMotion.matches ? '0ms' : `${state.delay + order * state.stagger}ms`;
                    svg.appendChild(path);
                    return path;
                });

                state.svg = svg;
                el.replaceChildren(svg);
            }

            state.svg.setAttribute('width', String(w));
            state.svg.setAttribute('height', String(h));
            state.svg.setAttribute('viewBox', `0 0 ${w} ${h}`);
            state.shape.strokes.forEach(([d], index) => {
                state.paths[index].setAttribute('d', scalePath(d, w / vbW, h / vbH));
            });

            maybeDraw(state);
        }

        // Tunggu dua frame dalam keadaan belum tergambar, baru mulai; kalau langsung, browser melewatkan transisinya
        function maybeDraw(state) {
            if (!state.visible || !state.svg || state.drawn || state.pending) return;
            state.pending = true;
            requestAnimationFrame(() => requestAnimationFrame(() => {
                state.pending = false;
                state.drawn = true;
                state.paths.forEach((path) => { path.style.strokeDashoffset = '0'; });
            }));
        }

        function setup(el) {
            if (states.has(el)) return;
            const shape = shapes[el.dataset.sketch];
            if (!shape) return;

            const state = {
                shape,
                delay: num(el.dataset.delay, 0),
                duration: num(el.dataset.duration, defaultDuration[el.dataset.sketch] ?? 700),
                stagger: num(el.dataset.stagger, 200),
                scale: num(el.dataset.scale, 1),
                threshold: num(el.dataset.threshold, 0.6),
                w: 0,
                h: 0,
                svg: null,
                paths: [],
                visible: false,
                drawn: false,
                pending: false,
            };
            states.set(el, state);

            state.resizeObserver = new ResizeObserver(() => render(el, state));
            state.resizeObserver.observe(el);

            state.visibleObserver = new IntersectionObserver(([entry]) => {
                if (!entry?.isIntersecting) return;
                state.visible = true;
                state.visibleObserver.disconnect();
                render(el, state);
                maybeDraw(state);
            }, { threshold: state.threshold });
            state.visibleObserver.observe(el);
        }

        function teardown(el) {
            const state = states.get(el);
            if (!state) return;
            state.resizeObserver.disconnect();
            state.visibleObserver.disconnect();
            states.delete(el);
        }

        function eachSketch(node, callback) {
            if (node.nodeType !== Node.ELEMENT_NODE) return;
            if (node.matches('[data-sketch]')) callback(node);
            node.querySelectorAll('[data-sketch]').forEach(callback);
        }

        function start() {
            document.querySelectorAll('[data-sketch]').forEach(setup);

            new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => eachSketch(node, setup));
                    mutation.removedNodes.forEach((node) => eachSketch(node, (el) => {
                        if (!el.isConnected) teardown(el);
                    }));
                });
            }).observe(document.body, { childList: true, subtree: true });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', start);
        } else {
            start();
        }
    })();
</script>
