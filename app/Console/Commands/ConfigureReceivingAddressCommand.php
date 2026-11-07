<?php

namespace App\Console\Commands;

use App\Models\Address;
use App\Models\User;
use App\Services\Blockchain\BlockchainService;
use App\Services\Blockchain\TronAddress;
use Illuminate\Console\Command;

class ConfigureReceivingAddressCommand extends Command
{
    protected $signature = 'payments:configure-address {owner_email}';

    protected $description = 'Register only the public Trust Wallet receive address from environment';

    public function handle(BlockchainService $blockchain): int
    {
        $owner = User::query()->where('email', $this->argument('owner_email'))->firstOrFail();
        if (! $owner->hasRole('admin')) {
            $this->error('The receiving address must belong to the owner role.');

            return self::FAILURE;
        }
        $blockchain->paymentConfiguration();
        $address = (string) config('payments.receive_address');
        TronAddress::hex($address);
        $existing = Address::query()->where('network', 'tron')->where('currency', 'usdt')->where('address', $address)->first();
        if ($existing && $existing->user_id !== $owner->id) {
            $this->error('Address is assigned to another account. Review ownership before changing it.');

            return self::FAILURE;
        }
        Address::query()->firstOrCreate(['network' => 'tron', 'currency' => 'usdt', 'address' => $address],
            ['user_id' => $owner->id, 'is_active' => true, 'balance' => '0']);
        $this->info('Public receiving address registered. No wallet secrets were used.');

        return self::SUCCESS;
    }
}
