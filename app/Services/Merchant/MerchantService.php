<?php

declare(strict_types=1);

namespace App\Services\Merchant;

use App\Contracts\Merchant\MerchantServiceContract;
use App\Exceptions\Merchant\MerchantLogoInvalidException;
use App\Exceptions\Merchant\MerchantLogoNotSquareException;
use App\Exceptions\Merchant\MerchantLogoTooLargeException;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class MerchantService implements MerchantServiceContract
{
    private const MAX_LOGO_SIZE = 500;
    private const LOGO_DIRECTORY = 'merchant-logos';

    public function create(
        User $user,
        string $name,
        ?string $description,
        string $initials,
        ?string $backUrl,
        bool $whiteLabelEnabled,
        int $invoiceExpiresInMinutes,
        ?UploadedFile $logo = null
    ): Merchant {
        $logoPath = $this->storeLogo($logo);

        return Merchant::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'description' => $description,
            'initials' => $initials,
            'logo_path' => $logoPath,
            'back_url' => $backUrl,
            'white_label_enabled' => $whiteLabelEnabled,
            'invoice_expires_in_minutes' => $invoiceExpiresInMinutes,
        ]);
    }

    public function update(
        Merchant $merchant,
        string $name,
        ?string $description,
        string $initials,
        ?string $backUrl,
        bool $whiteLabelEnabled,
        int $invoiceExpiresInMinutes,
        ?UploadedFile $logo = null
    ): Merchant {
        $logoPath = $logo ? $this->replaceLogo($merchant, $logo) : $merchant->logo_path;

        $merchant->fill([
            'name' => $name,
            'description' => $description,
            'initials' => $initials,
            'logo_path' => $logoPath,
            'back_url' => $backUrl,
            'white_label_enabled' => $whiteLabelEnabled,
            'invoice_expires_in_minutes' => $invoiceExpiresInMinutes,
        ]);

        $merchant->save();

        return $merchant->refresh();
    }

    private function replaceLogo(Merchant $merchant, UploadedFile $logo): string
    {
        $logoPath = $this->storeLogo($logo);

        if ($merchant->logo_path) {
            Storage::disk('public')->delete($merchant->logo_path);
        }

        return $logoPath;
    }

    private function storeLogo(?UploadedFile $logo): ?string
    {
        if (!$logo) {
            return null;
        }

        $this->assertLogoDimensions($logo);

        $extension = $logo->getClientOriginalExtension() ?: $logo->guessExtension() ?: 'png';
        $filename = Str::uuid()->toString().'.'.$extension;

        return $logo->storeAs(self::LOGO_DIRECTORY, $filename, 'public');
    }

    private function assertLogoDimensions(UploadedFile $logo): void
    {
        $imageSize = getimagesize($logo->getPathname());

        if (!$imageSize || count($imageSize) < 2) {
            throw new MerchantLogoInvalidException('Не удалось определить параметры логотипа.');
        }

        [$width, $height] = $imageSize;

        if ($width !== $height) {
            throw new MerchantLogoNotSquareException('Логотип должен быть квадратным.');
        }

        if ($width > self::MAX_LOGO_SIZE || $height > self::MAX_LOGO_SIZE) {
            throw new MerchantLogoTooLargeException('Размер логотипа не должен превышать 500x500 пикселей.');
        }
    }
}

