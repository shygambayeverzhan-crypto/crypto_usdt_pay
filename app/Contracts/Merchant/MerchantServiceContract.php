<?php

declare(strict_types=1);

namespace App\Contracts\Merchant;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\UploadedFile;

interface MerchantServiceContract
{
    public function create(
        User $user,
        string $name,
        ?string $description,
        string $initials,
        ?string $backUrl,
        bool $whiteLabelEnabled,
        int $invoiceExpiresInMinutes,
        ?UploadedFile $logo = null
    ): Merchant;

    public function update(
        Merchant $merchant,
        string $name,
        ?string $description,
        string $initials,
        ?string $backUrl,
        bool $whiteLabelEnabled,
        int $invoiceExpiresInMinutes,
        ?UploadedFile $logo = null
    ): Merchant;
}

