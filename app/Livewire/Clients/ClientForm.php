<?php

namespace App\Livewire\Clients;

use App\Models\Client;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ClientForm extends Component
{
    public ?Client $client = null;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $address = '';

    public string $emirate = '';

    public string $trn = '';

    public string $notes = '';

    public function mount(?Client $client = null): void
    {
        if ($client?->exists) {
            $this->client = $client;
            $this->fill($client->only(['name', 'phone', 'email', 'address', 'emirate', 'trn', 'notes']));
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'emirate' => ['nullable', 'string', 'max:100'],
            'trn' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        Client::updateOrCreate(['id' => $this->client?->id], $data);

        session()->flash('success', 'Client saved.');
        $this->redirect(route('clients.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.clients.client-form')
            ->title(($this->client?->exists ? 'Edit client' : 'New client').' — Zenaura');
    }
}
