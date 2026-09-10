<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class Toast extends Component
{
    public bool $show = false;

    public string $message = '';

    public string $type = 'success';

    public function mount(): void
    {
        $toast = session('toast');
        if (! is_array($toast) && session('success')) {
            $toast = ['message' => (string) session('success'), 'type' => 'success'];
        }
        if (! is_array($toast) && session('error')) {
            $toast = ['message' => (string) session('error'), 'type' => 'error'];
        }

        if (is_array($toast) && ! empty($toast['message'] ?? $toast['title'] ?? null)) {
            $this->present($toast);
        }
    }

    #[On('app-toast')]
    public function onToast(string $message = '', string $type = 'success'): void
    {
        $this->present([
            'message' => $message,
            'type' => $type,
        ]);
    }

    public function dismiss(): void
    {
        $this->show = false;
    }

    /**
     * @param  array{message?: string, title?: string, type?: string}  $toast
     */
    private function present(array $toast): void
    {
        $this->message = (string) ($toast['message'] ?? $toast['title'] ?? '');
        $this->type = ($toast['type'] ?? 'success') === 'error' ? 'error' : 'success';
        $this->show = $this->message !== '';
    }

    public function render()
    {
        return view('livewire.toast');
    }
}
