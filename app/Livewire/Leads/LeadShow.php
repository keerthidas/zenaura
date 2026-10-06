<?php

namespace App\Livewire\Leads;

use App\Models\Client;
use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class LeadShow extends Component
{
    public Lead $lead;

    public string $activityType = 'call';

    public string $activitySubject = '';

    public string $activityNotes = '';

    public string $occurredAt = '';

    public string $reminderAt = '';

    public array $rescheduleAt = [];

    public function mount(Lead $lead): void
    {
        $this->lead = $lead;
        $this->occurredAt = now()->format('Y-m-d\TH:i');
    }

    public function addActivity(): void
    {
        $data = $this->validate([
            'activityType' => ['required', Rule::in(LeadActivity::TYPES)],
            'activitySubject' => ['required', 'string', 'max:255'],
            'activityNotes' => ['nullable', 'string', 'max:10000'],
            'occurredAt' => ['required', 'date'],
            'reminderAt' => ['nullable', 'date'],
        ]);

        $this->lead->activities()->create([
            'created_by' => auth()->id(),
            'type' => $data['activityType'],
            'subject' => $data['activitySubject'],
            'notes' => $data['activityNotes'],
            'occurred_at' => $data['occurredAt'],
            'reminder_at' => $data['reminderAt'] ?: null,
        ]);

        $this->reset('activitySubject', 'activityNotes', 'reminderAt');
        $this->activityType = 'call';
        $this->occurredAt = now()->format('Y-m-d\TH:i');
        session()->flash('success', 'Activity added to the lead timeline.');
    }

    public function completeReminder(int $activityId): void
    {
        $activity = $this->lead->reminders()->findOrFail($activityId);
        $activity->update(['completed_at' => now()]);

        session()->flash('success', 'Follow-up marked complete.');
    }

    public function rescheduleReminder(int $activityId): void
    {
        $activity = $this->lead->reminders()->findOrFail($activityId);
        $date = $this->rescheduleAt[$activityId] ?? null;
        validator(
            ['rescheduleAt' => [$activityId => $date]],
            ["rescheduleAt.{$activityId}" => ['required', 'date', 'after:now']],
        )->validate();

        $activity->update(['reminder_at' => $date]);
        unset($this->rescheduleAt[$activityId]);
        session()->flash('success', 'Follow-up rescheduled.');
    }

    public function convertToClient(): void
    {
        $client = DB::transaction(function () {
            $lead = Lead::query()->lockForUpdate()->findOrFail($this->lead->id);

            if ($lead->client_id) {
                return $lead->client;
            }

            $client = Client::create([
                'name' => $lead->company ?: $lead->name,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'address' => $lead->address,
                'emirate' => $lead->emirate,
                'trn' => null,
                'notes' => $lead->notes,
            ]);

            $lead->update([
                'client_id' => $client->id,
                'converted_at' => now(),
                'status' => 'won',
            ]);
            $lead->activities()->create([
                'created_by' => auth()->id(),
                'type' => 'note',
                'subject' => 'Lead converted to client',
                'notes' => 'Client record created: '.$client->name,
                'occurred_at' => now(),
            ]);

            return $client;
        });

        $this->lead->refresh();
        session()->flash('success', 'Lead converted to a client.');
        $this->redirect(route('clients.edit', $client), navigate: true);
    }

    public function render()
    {
        $this->lead->loadMissing('client');
        $activities = $this->lead->activities()->get();
        $reminders = $this->lead->reminders()->orderBy('reminder_at')->get();

        foreach ($reminders as $reminder) {
            $this->rescheduleAt[$reminder->id] ??= $reminder->reminder_at->format('Y-m-d\TH:i');
        }

        return view('livewire.leads.show', compact('activities', 'reminders'))
            ->title(($this->lead->company ?: $this->lead->name).' — Sales CRM — Zenaura');
    }
}
