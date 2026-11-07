<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationEvent;
use App\Contracts\Telegram\TelegramServiceContract;
use App\Http\Requests\NotificationFilterRequest;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\NotificationRuleResource;
use App\Http\Resources\TelegramAccountResource;
use App\Models\Notification;
use App\Models\NotificationRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(NotificationFilterRequest $request): Response
    {
        $filters = $request->filters();
        $userId = Auth::id();
        $user = Auth::user();
        $telegramService = app(TelegramServiceContract::class);
        $telegramAccount = $user ? $telegramService->getOrCreateForUser($user) : null;

        $notifications = Notification::query()
            ->where('user_id', $userId)
            ->where('channel', NotificationChannel::IN_APP)
            ->when($filters['event'], fn ($query, string $event) => $query->where('event', $event))
            ->when($filters['delivery_status'], fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['only_unread'], fn ($query) => $query->whereNull('read_at'))
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($notification) => (new NotificationResource($notification))->resolve());

        $rules = NotificationRule::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->get()
            ->map(fn ($rule) => (new NotificationRuleResource($rule))->resolve());

        return $this->inertia('notifications/Index', [
            'notifications' => $notifications,
            'rules' => $rules,
            'filters' => $filters,
            'telegram' => $telegramAccount ? (new TelegramAccountResource($telegramAccount))->resolve() : null,
            'events' => collect(NotificationEvent::cases())->map(fn ($event) => [
                'value' => $event->value,
                'label' => $event->label(),
            ]),
            'channels' => collect(NotificationChannel::cases())->map(fn ($channel) => [
                'value' => $channel->value,
                'label' => $channel->label(),
            ]),
            'invoice_statuses' => collect(InvoiceStatus::cases())->map(fn ($status) => [
                'value' => $status->value,
                'label' => $status->value,
            ]),
            'currencies' => collect(Currency::cases())->map(fn ($currency) => [
                'value' => $currency->value,
                'label' => $currency->value,
            ]),
            'delivery_statuses' => collect(NotificationDeliveryStatus::cases())->map(fn ($status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
        ]);
    }

    public function markRead(Notification $notification): RedirectResponse
    {
        $this->assertOwnsNotification($notification);
        $notification->markRead();

        return back()->with('success', __('messages.notifications.marked_read'));
    }

    public function markUnread(Notification $notification): RedirectResponse
    {
        $this->assertOwnsNotification($notification);
        $notification->read_at = null;
        $notification->save();

        return back()->with('success', __('messages.notifications.marked_unread'));
    }

    public function markAllRead(): RedirectResponse
    {
        $userId = Auth::id();
        Notification::query()
            ->where('user_id', $userId)
            ->where('channel', NotificationChannel::IN_APP)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', __('messages.notifications.all_read'));
    }

    private function assertOwnsNotification(Notification $notification): void
    {
        abort_if($notification->user_id !== Auth::id(), 403);
    }
}

