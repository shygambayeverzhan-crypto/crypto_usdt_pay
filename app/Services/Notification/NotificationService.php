<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Contracts\Notification\NotificationServiceContract;
use App\Enums\Currency;
use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationEvent;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Models\NotificationRule;
use App\Services\Money\MoneyAmount;
use App\Contracts\Money\MoneyServiceContract;
use App\Services\Notification\Events\NotificationEventInterface;
use App\Services\Notification\Templates\NotificationTemplateResolver;

final class NotificationService implements NotificationServiceContract
{
    public function __construct(
        private readonly MoneyServiceContract $money,
        private readonly NotificationTemplateResolver $templates,
    ) {
    }

    public function dispatch(NotificationEventInterface $event): void
    {
        $user = $event->user();
        if (!$user) {
            return;
        }

        $rules = NotificationRule::query()
            ->where('user_id', $user->id)
            ->where('event', $event->type()->value)
            ->where('enabled', true)
            ->get();

        foreach ($rules as $rule) {
            if (!$this->matchesRule($rule, $event)) {
                continue;
            }

            $channels = $rule->channels ?? [];
            foreach ($channels as $channelCode) {
                $channel = NotificationChannel::tryFrom((string) $channelCode);
                if ($channel === null) {
                    continue;
                }

                $content = $this->templates->build($event, $channel);

                $notification = Notification::query()->create([
                    'user_id' => $user->id,
                    'event' => $event->type()->value,
                    'channel' => $channel,
                    'status' => NotificationDeliveryStatus::PENDING,
                    'title' => $content->title,
                    'body' => $content->body,
                    'payload' => $content->payload,
                ]);

                SendNotificationJob::dispatch($notification->id)->onQueue('notifications');
            }
        }
    }

    private function matchesRule(NotificationRule $rule, NotificationEventInterface $event): bool
    {
        $currency = $event->currency();
        $ruleCurrency = $rule->currency;
        if ($currency !== null && $ruleCurrency !== null && $currency !== $ruleCurrency) {
            return false;
        }

        $amount = $event->amount();
        if ($amount instanceof MoneyAmount) {
            $thresholdCurrency = $currency ?? $ruleCurrency ?? Currency::USDT;
            $threshold = $this->money->fromMinor($rule->min_amount_minor, $thresholdCurrency);
            if ($this->money->compare($amount, $threshold) < 0) {
                return false;
            }
        }

        if ($event->type() === NotificationEvent::INVOICE_STATUS_CHANGED) {
            $statuses = $rule->statuses ?? [];
            if (!empty($statuses) && $event->status() !== null && !in_array($event->status(), $statuses, true)) {
                return false;
            }
        }

        return true;
    }
}

