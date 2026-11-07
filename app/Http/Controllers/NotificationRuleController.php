<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Money\MoneyServiceContract;
use App\Http\Requests\NotificationRuleRequest;
use App\Models\NotificationRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class NotificationRuleController extends Controller
{
    public function store(NotificationRuleRequest $request, MoneyServiceContract $money): RedirectResponse
    {
        $this->persist(new NotificationRule(), $request, $money);

        return back()->with('success', __('messages.notifications.rule_created'));
    }

    public function update(NotificationRuleRequest $request, NotificationRule $notificationRule, MoneyServiceContract $money): RedirectResponse
    {
        $this->assertOwnsRule($notificationRule);
        $this->persist($notificationRule, $request, $money);

        return back()->with('success', __('messages.notifications.rule_updated'));
    }

    public function destroy(NotificationRule $notificationRule): RedirectResponse
    {
        $this->assertOwnsRule($notificationRule);
        $notificationRule->delete();

        return back()->with('success', __('messages.notifications.rule_deleted'));
    }

    private function persist(NotificationRule $rule, NotificationRuleRequest $request, MoneyServiceContract $money): void
    {
        $minAmount = $request->toMoneyAmount($money);

        $rule->user_id = Auth::id();
        $rule->event = $request->input('event');
        $rule->currency = $request->toCurrencyEnum();
        $rule->statuses = $request->statuses();
        $rule->channels = $request->channels();
        $rule->min_amount_minor = $money->toMinor($minAmount);
        $rule->enabled = $request->isEnabled();
        $rule->save();
    }

    private function assertOwnsRule(NotificationRule $rule): void
    {
        abort_if($rule->user_id !== Auth::id(), 403);
    }
}

