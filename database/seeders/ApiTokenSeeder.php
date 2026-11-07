<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ApiTokenSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->orderBy('id')->first();
        if (!$user) {
            return;
        }

        $exists = $user->apiTokens()->exists();
        if ($exists) {
            return;
        }

        $user->apiTokens()->create([
            'name' => 'Default',
            'token' => $this->generateUniqueToken(),
        ]);
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (ApiToken::query()->where('token', $token)->exists());

        return $token;
    }
}


