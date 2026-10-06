<?php

namespace App\Livewire\Leads;

use App\Models\Lead;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Sales CRM — Zenaura')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $stage = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStage(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $leads = Lead::query()
            ->withCount(['activities', 'reminders'])
            ->when($this->search, function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('company', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when(in_array($this->stage, Lead::STATUSES, true), fn ($query) => $query->where('status', $this->stage))
            ->latest()
            ->paginate(15);

        $pipelineCounts = Lead::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('livewire.leads.index', [
            'leads' => $leads,
            'pipelineCounts' => $pipelineCounts,
        ]);
    }
}
