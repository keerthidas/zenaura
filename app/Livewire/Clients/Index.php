<?php

namespace App\Livewire\Clients;

use App\Models\Client;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Clients — Zenaura')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $client = Client::findOrFail($id);

        if ($client->quotations()->exists()) {
            $client->delete();
            session()->flash('success', 'Client archived because quotations exist.');

            return;
        }

        $client->forceDelete();
        session()->flash('success', 'Client deleted.');
    }

    public function render()
    {
        $clients = Client::query()
            ->when($this->search, function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('trn', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.clients.index', compact('clients'));
    }
}
