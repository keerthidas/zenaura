<div class="mx-auto max-w-5xl">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('leads.index') }}" class="text-sm text-zenora hover:underline" wire:navigate>← Sales CRM</a>
            <p class="mt-3 text-xs uppercase tracking-[0.2em] text-stone-500">In-app reminders</p>
            <h1 class="font-display text-3xl">Follow-ups</h1>
        </div>
        <span class="rounded-full bg-zenora/10 px-3 py-1.5 text-sm text-zenora">{{ $reminders->count() }} open</span>
    </div>
    <div class="space-y-3">
        @forelse ($reminders as $reminder)
            <div class="card flex flex-wrap items-center justify-between gap-4 p-4 {{ $reminder->isOverdue() ? 'border-red-200' : '' }}">
                <div class="min-w-48 flex-1">
                    <a href="{{ route('leads.show', $reminder->lead) }}" class="font-medium text-zenora hover:underline" wire:navigate>{{ $reminder->lead->company ?: $reminder->lead->name }}</a>
                    <p class="mt-1 text-sm">{{ $reminder->subject }}</p>
                    <p class="mt-1 text-xs {{ $reminder->isOverdue() ? 'font-medium text-red-700' : 'text-stone-500' }}">
                        {{ $reminder->isOverdue() ? 'Overdue · ' : 'Due · ' }}{{ $reminder->reminder_at->format('D, d M Y · H:i') }}
                    </p>
                    @if ($reminder->notes)
                        <p class="mt-2 whitespace-pre-line text-sm text-stone-600">{{ $reminder->notes }}</p>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <form wire:submit="reschedule({{ $reminder->id }})" class="flex gap-2">
                        <input type="datetime-local" wire:model="rescheduleAt.{{ $reminder->id }}" class="field-compact">
                        <button class="btn btn-ghost px-3 py-1.5 text-xs">Reschedule</button>
                    </form>
                    <button wire:click="complete({{ $reminder->id }})" class="btn btn-primary px-3 py-1.5 text-xs">Complete</button>
                </div>
            </div>
            @error("rescheduleAt.{$reminder->id}") <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        @empty
            <div class="card p-12 text-center text-sm text-stone-500">You’re all caught up. No open follow-ups.</div>
        @endforelse
    </div>
</div>
