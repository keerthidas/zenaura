<?php

namespace App\Livewire\Leads;

use App\Models\Lead;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class LeadForm extends Component
{
    public ?Lead $lead = null;

    public string $name = '';

    public string $company = '';

    public string $phone = '';

    public string $email = '';

    public string $address = '';

    public string $emirate = '';

    public string $source = '';

    public string $interested_in = '';

    public string $estimated_value = '';

    public string $expected_close_date = '';

    public string $status = 'new';

    public string $lost_reason = '';

    public string $notes = '';

    public function mount(?Lead $lead = null): void
    {
        if ($lead?->exists) {
            if ($lead->isConverted()) {
                $this->redirect(route('leads.show', $lead), navigate: true);

                return;
            }

            $this->lead = $lead;
            $this->fill($lead->only([
                'name',
                'company',
                'phone',
                'email',
                'address',
                'emirate',
                'source',
                'interested_in',
                'estimated_value',
                'status',
                'lost_reason',
                'notes',
            ]));
            $this->expected_close_date = $lead->expected_close_date?->toDateString() ?? '';
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'emirate' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:100'],
            'interested_in' => ['nullable', 'string', 'max:2000'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'expected_close_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_diff(Lead::STATUSES, ['won']))],
            'lost_reason' => ['nullable', 'string', 'max:255', Rule::requiredIf($this->status === 'lost')],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $data['created_by'] = $this->lead?->created_by ?? auth()->id();
        $lead = $this->lead?->exists
            ? tap($this->lead)->update($data)
            : Lead::create($data);

        session()->flash('success', 'Lead saved.');
        $this->redirect(route('leads.show', $lead), navigate: true);
    }

    public function render()
    {
        return view('livewire.leads.lead-form')
            ->title(($this->lead?->exists ? 'Edit lead' : 'New lead').' — Sales CRM — Zenaura');
    }
}
