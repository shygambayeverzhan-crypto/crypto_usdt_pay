<template>
    <div class="relative inline-flex h-60 w-60 items-center justify-center">
        <svg viewBox="0 0 200 200" class="h-full w-full">
            <defs>
                <linearGradient id="diamond-stroke" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#22d3ee" />
                    <stop offset="100%" stop-color="#34d399" />
                </linearGradient>
                <linearGradient id="cube-top" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#22c55e" stop-opacity="0.95" />
                    <stop offset="100%" stop-color="#0ea5e9" stop-opacity="0.85" />
                </linearGradient>
                <linearGradient id="cube-line" x1="0%" y1="0%" x2="120%" y2="100%">
                    <stop offset="0%" stop-color="#34d399" stop-opacity="0.95" />
                    <stop offset="60%" stop-color="#22d3ee" stop-opacity="0.9" />
                    <stop offset="100%" stop-color="#a7f3d0" stop-opacity="0.7" />
                </linearGradient>
                <linearGradient id="cube-side" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#0b5e45" stop-opacity="0.9" />
                    <stop offset="100%" stop-color="#0f172a" stop-opacity="0.9" />
                </linearGradient>
                <radialGradient id="orb" cx="50%" cy="45%" r="60%">
                    <stop offset="0%" stop-color="#a7f3d0" stop-opacity="1" />
                    <stop offset="60%" stop-color="#34d399" stop-opacity="0.95" />
                    <stop offset="100%" stop-color="#0ea5e9" stop-opacity="0.15" />
                </radialGradient>
                <filter id="glow" x="-50%" y="-50%" width="200%" height="200%">
                    <feGaussianBlur stdDeviation="8" result="blur" />
                    <feMerge>
                        <feMergeNode in="blur" />
                        <feMergeNode in="SourceGraphic" />
                    </feMerge>
                </filter>
                <filter id="shadow" x="-50%" y="-50%" width="200%" height="200%">
                    <feDropShadow dx="0" dy="10" stdDeviation="12" flood-color="#0c4a31" flood-opacity="0.35" />
                </filter>
            </defs>

            <!-- Diamond frame -->
            <g transform="translate(100 100) rotate(45)">
                <rect
                    x="-78"
                    y="-78"
                    width="156"
                    height="156"
                    rx="18"
                    fill="none"
                    stroke="url(#diamond-stroke)"
                    stroke-width="6"
                    filter="url(#shadow)"
                />
            </g>

            <!-- Cube body (simple isometric) -->
            <g transform="translate(100 100)">
                <!-- top face -->
                <polygon
                    points="0,-38 34,-18 0,2 -34,-18"
                    fill="none"
                    stroke="url(#cube-line)"
                    stroke-width="3"
                    stroke-linejoin="round"
                    opacity="0.95"
                />
                <!-- right face -->
                <polygon
                    points="34,-18 34,22 0,42 0,2"
                    fill="none"
                    stroke="url(#cube-line)"
                    stroke-width="3"
                    stroke-linejoin="round"
                    opacity="0.9"
                />
                <!-- left face -->
                <polygon
                    points="-34,-18 0,2 0,42 -34,22"
                    fill="none"
                    stroke="url(#cube-line)"
                    stroke-width="3"
                    stroke-linejoin="round"
                    opacity="0.9"
                />
            </g>

            <!-- Glowing orb -->
            <g transform="translate(100 100)">
                <circle r="20" fill="url(#orb)" filter="url(#glow)" />
            </g>
        </svg>
    </div>
</template>

