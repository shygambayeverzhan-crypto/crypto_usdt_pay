<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\NotificationDeliveryStatus;
use App\Models\Notification;
use App\Services\Notification\Channels\NotificationChannelFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $notificationId,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(NotificationChannelFactory $channelFactory): void
    {
        $notification = Notification::query()->find($this->notificationId);
        if (!$notification) {
            return;
        }

        try {
            $channel = $channelFactory->make($notification->channel);
            $channel->send($notification);

            $notification->status = NotificationDeliveryStatus::DELIVERED;
            $notification->delivered_at = now();
            $notification->error_message = null;
        } catch (\Throwable $throwable) {
            Log::warning('Notification sending failed', [
                'notification_id' => $notification->id,
                'error' => $throwable->getMessage(),
            ]);

            $notification->status = NotificationDeliveryStatus::FAILED;
            $notification->error_message = $throwable->getMessage();
        }

        $notification->save();
    }
}

