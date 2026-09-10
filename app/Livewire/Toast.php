<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class Toast extends Component
{
    public bool $show = false;

    public string $eyebrow = 'Done';

    public string $title = '';

    public ?string $body = null;

    public function mount(): void
    {
        $toast = session('toast');
        if (! is_array($toast) && session('success')) {
            $toast = [
                'eyebrow' => 'Done',
                'title' => (string) session('success'),
                'body' => null,
            ];
        }

        if (is_array($toast) && ! empty($toast['title'])) {
            $this->present($toast);
        }
    }

    #[On('app-toast')]
    public function onToast(string $eyebrow = 'Done', string $title = '', ?string $body = null): void
    {
        $this->present([
            'eyebrow' => $eyebrow,
            'title' => $title,
            'body' => $body,
        ]);
    }

    public function dismiss(): void
    {
        $this->show = false;
    }

    /**
     * @param  array{eyebrow?: string, title?: string, body?: string|null}  $toast
     */
    private function present(array $toast): void
    {
        $this->eyebrow = (string) ($toast['eyebrow'] ?? 'Done');
        $this->title = (string) ($toast['title'] ?? '');
        $this->body = isset($toast['body']) ? (string) $toast['body'] : null;
        $this->show = $this->title !== '';
    }

    public function render()
    {
        return view('livewire.toast');
    }
}
