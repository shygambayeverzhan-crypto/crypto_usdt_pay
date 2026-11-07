<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

const canvasRef = ref<HTMLCanvasElement | null>(null);

let ctx: CanvasRenderingContext2D | null = null;
let rafId = 0;
let resizeObserver: ResizeObserver | null = null;

const settings = {
    blockSize: 17,
    gap: 3,
    speed: 0.675,
    baseHue: 145,
    hueShift: 5,
    maxDpr: 2,
    lift: 13.5,
    lightDir: { x: -0.35, y: -0.45, z: 0.82 },
    gradOffset: 0.12,
};

const energyAt = (nx: number, ny: number, t: number) => {
    const wave = Math.sin(nx * Math.PI * 6 + t * 1.4) + Math.cos(ny * Math.PI * 5 - t * 1.2);
    const ripple = Math.sin(Math.hypot(nx, ny) * 14 - t * 1.8);
    return wave * 0.55 + ripple * 0.45;
};

const resizeCanvas = () => {
    const canvas = canvasRef.value;
    if (!canvas || !ctx) return;

    const { width, height } = canvas.getBoundingClientRect();
    const dpr = Math.min(window.devicePixelRatio || 1, settings.maxDpr);

    canvas.width = Math.max(1, Math.floor(width * dpr));
    canvas.height = Math.max(1, Math.floor(height * dpr));

    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
};

    const render = () => {
    const canvas = canvasRef.value;
    if (!canvas || !ctx) return;

    const dpr = Math.min(window.devicePixelRatio || 1, settings.maxDpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        const viewWidth = canvas.width / dpr;
        const viewHeight = canvas.height / dpr;

        ctx.clearRect(0, 0, viewWidth, viewHeight);

        const cols = Math.ceil(viewWidth / settings.blockSize) + 1;
        const rows = Math.ceil(viewHeight / settings.blockSize) + 1;
        const t = (performance.now() * 0.001) * settings.speed;

        for (let y = 0; y < rows; y++) {
            for (let x = 0; x < cols; x++) {
                const normX = (x - cols / 2) / cols;
                const normY = (y - rows / 2) / rows;
                const radial = Math.hypot(normX, normY);

                const energy = energyAt(normX, normY, t);

                // Локальные производные для псевдо-нормали (эффект объёма)
                const delta = settings.gradOffset;
                const energyX = energyAt(normX + delta, normY, t) - energy;
                const energyY = energyAt(normX, normY + delta, t) - energy;
                const nx = -energyX;
                const ny = -energyY;
                const nz = 1;
                const normLen = Math.hypot(nx, ny, nz) || 1;
                const normalX = nx / normLen;
                const normalY = ny / normLen;
                const normalZ = nz / normLen;
                const light = settings.lightDir;
                const lightLen = Math.hypot(light.x, light.y, light.z) || 1;
                const lx = light.x / lightLen;
                const ly = light.y / lightLen;
                const lz = light.z / lightLen;
                const lambert = Math.max(0, normalX * lx + normalY * ly + normalZ * lz);

                const height = (energy + 1) * 0.5; // 0..1
                const lift = (height - 0.5) * settings.lift;

                const luminanceBoost = Math.max(0, 0.45 - radial) * 22;
                const heightLuma = height * 42; // подчёркиваем высоту: выше — светлее
                const lightness = Math.max(
                    6,
                    Math.min(78, 10 + heightLuma + lambert * 18 + luminanceBoost * 0.55),
                );
                const hue = settings.baseHue + height * 14 + lambert * 3 + energy * settings.hueShift;
                const alpha = Math.min(0.9, Math.max(0.2, 0.26 + height * 0.3));

                const yShift =
                    Math.sin(t * 0.9 + normX * 4) * 4.5 +
                    Math.cos(t * 0.6 + normY * 5) * 3 -
                    lift;
                const px = x * settings.blockSize;
                const py = y * settings.blockSize + yShift;

                const size = settings.blockSize - settings.gap;

                ctx.save();
                ctx.shadowColor = 'rgba(24,16,48,0.3)';
                ctx.shadowBlur = 12;
                ctx.shadowOffsetY = 4;

                ctx.fillStyle = `hsla(${hue.toFixed(2)}, 82%, ${lightness.toFixed(2)}%, ${alpha.toFixed(2)})`;
                ctx.fillRect(px, py, size, size);

                const grad = ctx.createLinearGradient(px, py, px + size, py + size);
                grad.addColorStop(0, 'rgba(255,255,255,0.12)');
                grad.addColorStop(0.45, 'rgba(255,255,255,0.03)');
                grad.addColorStop(1, 'rgba(0,0,0,0.18)');
                ctx.fillStyle = grad;
                ctx.fillRect(px, py, size, size);

                ctx.restore();
            }
        }

        rafId = requestAnimationFrame(render);
    };

onMounted(() => {
    const canvas = canvasRef.value;
    if (!canvas) return;

    ctx = canvas.getContext('2d');
    if (!ctx) return;

    resizeObserver = new ResizeObserver(resizeCanvas);
    resizeObserver.observe(canvas);

    resizeCanvas();
    rafId = requestAnimationFrame(render);
});

onBeforeUnmount(() => {
    cancelAnimationFrame(rafId);
    resizeObserver?.disconnect();
    resizeObserver = null;
    ctx = null;
});
</script>

<template>
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <canvas ref="canvasRef" class="h-full w-full opacity-90" />
        <div class="absolute inset-0 bg-gradient-to-b from-emerald-950/80 via-slate-950/45 to-black/88" />
    </div>
</template>

