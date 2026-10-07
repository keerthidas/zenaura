<?php

namespace App\Livewire\Leads;

use App\Models\LeadActivity;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Follow-ups — Sales CRM — Zenaura')]
class Reminders extends Component
{
    public array $rescheduleAt = [];

    public function complete(int $activityId): void
    {
        $activity = LeadActivity::query()->whereNotNull('reminder_at')->findOrFail($activityId);
        $activity->update(['completed_at' => now()]);

        session()->flash('success', 'Follow-up marked complete.');
    }

    public function reschedule(int $activityId): void
    {
        $activity = LeadActivity::query()
            ->whereNotNull('reminder_at')
            ->whereNull('completed_at')
            ->findOrFail($activityId);
        $date = $this->rescheduleAt[$activityId] ?? null;
        validator(
            ['rescheduleAt' => [$activityId => $date]],
            ["rescheduleAt.{$activityId}" => ['required', 'date', 'after:now']],
        )->validate();

        $activity->update(['reminder_at' => $date]);
        unset($this->rescheduleAt[$activityId]);
        session()->flash('success', 'Follow-up rescheduled.');
    }

    public function render()
    {
        $reminders = LeadActivity::query()
            ->with('lead')
            ->whereHas('lead')
            ->whereNotNull('reminder_at')
            ->whereNull('completed_at')
            ->orderBy('reminder_at')
            ->get();

        foreach ($reminders as $reminder) {
            $this->rescheduleAt[$reminder->id] ??= $reminder->reminder_at->format('Y-m-d\TH:i');
        }

        return view('livewire.leads.reminders', compact('reminders'));
    }
}
