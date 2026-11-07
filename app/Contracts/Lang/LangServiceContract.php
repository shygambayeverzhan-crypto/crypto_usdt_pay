<?php

declare(strict_types=1);

namespace App\Contracts\Lang;

interface LangServiceContract
{
    public function load(string $file): array;

    public function getFile(string|array $files): array;

    public function getLoaded(): array;

    public function availableFiles(?string $locale = null): array;
}

