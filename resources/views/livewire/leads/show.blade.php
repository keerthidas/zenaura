<div class="mx-auto max-w-6xl">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('leads.index') }}" class="text-sm text-zenora hover:underline" wire:navigate>← Leads</a>
            <p class="mt-3 text-xs uppercase tracking-[0.2em] text-stone-500">Sales lead · {{ ucfirst($lead->status) }}</p>
            <h1 class="font-display text-3xl">{{ $lead->company ?: $lead->name }}</h1>
            @if ($lead->company && $lead->name !== $lead->company)
                <p class="text-stone-500">{{ $lead->name }}</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($lead->client)
                <a href="{{ route('quotations.create', ['client' => $lead->client_id]) }}" class="btn btn-copper" wire:navigate>New quotation</a>
                <a href="{{ route('clients.edit', $lead->client) }}" class="btn btn-ghost" wire:navigate>Open client</a>
            @else
                <button wire:click="convertToClient" wire:confirm="Convert this lead into a client?" class="btn btn-copper">Convert to client</button>
            @endif
            <a href="{{ route('leads.edit', $lead) }}" class="btn btn-ghost" wire:navigate>Edit lead</a>
        </div>
    </div>

    @if ($lead->client)
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
            Converted to client <a class="font-medium underline" href="{{ route('clients.edit', $lead->client) }}" wire:navigate>{{ $lead->client->name }}</a>
            {{ $lead->converted_at ? 'on '.$lead->converted_at->format('d M Y') : '' }}.
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
        <section class="space-y-5">
            <div class="card p-5">
                <h2 class="mb-4 font-medium">Lead details</h2>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-4 text-sm">
                    <div><dt class="text-xs uppercase text-stone-500">Phone</dt><dd class="mt-1">{{ $lead->phone ?: '—' }}</dd></div>
                    <div><dt class="text-xs uppercase text-stone-500">Email</dt><dd class="mt-1 break-all">{{ $lead->email ?: '—' }}</dd></div>
                    <div><dt class="text-xs uppercase text-stone-500">Source</dt><dd class="mt-1">{{ $lead->source ?: '—' }}</dd></div>
                    <div><dt class="text-xs uppercase text-stone-500">Emirate</dt><dd class="mt-1">{{ $lead->emirate ?: '—' }}</dd></div>
                    <div><dt class="text-xs uppercase text-stone-500">Est. value</dt><dd class="mt-1">{{ $lead->estimated_value !== null ? aed($lead->estimated_value) : '—' }}</dd></div>
                    <div><dt class="text-xs uppercase text-stone-500">Expected close</dt><dd class="mt-1">{{ $lead->expected_close_date?->format('d M Y') ?? '—' }}</dd></div>
                </dl>
                @if ($lead->address)
                    <p class="mt-4 border-t border-stone-100 pt-3 text-sm">{{ $lead->address }}</p>
                @endif
                @if ($lead->interested_in)
                    <div class="mt-4 border-t border-stone-100 pt-3">
                        <p class="text-xs uppercase text-stone-500">Products / requirements</p>
                        <p class="mt-1 whitespace-pre-line text-sm">{{ $lead->interested_in }}</p>
                    </div>
                @endif
                @if ($lead->notes)
                    <div class="mt-4 border-t border-stone-100 pt-3">
                        <p class="text-xs uppercase text-stone-500">Lead notes</p>
                        <p class="mt-1 whitespace-pre-line text-sm">{{ $lead->notes }}</p>
                    </div>
                @endif
                @if ($lead->status === 'lost' && $lead->lost_reason)
                    <p class="mt-4 text-sm text-red-700"><span class="font-medium">Reason lost:</span> {{ $lead->lost_reason }}</p>
                @endif
            </div>

            <div class="card p-5">
                <h2 class="mb-4 font-medium">Log an activity</h2>
                <form wire:submit="addActivity" class="space-y-4">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="label">Type</label>
                            <select wire:model="activityType" class="field">
                                @foreach (['call', 'meeting', 'email', 'visit', 'note', 'other'] as $type)
                                    <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Activity date</label>
                            <input type="datetime-local" wire:model="occurredAt" class="field">
                            @error('occurredAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="label">Subject *</label>
                        <input wire:model="activitySubject" class="field" placeholder="Call with purchasing manager">
                        @error('activitySubject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Minutes / notes</label>
                        <textarea wire:model="activityNotes" rows="3" class="field" placeholder="Discussion summary, decisions, and next steps"></textarea>
                    </div>
                    <div>
                        <label class="label">Follow-up reminder (optional)</label>
                        <input type="datetime-local" wire:model="reminderAt" class="field">
                        @error('reminderAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button class="btn btn-primary">Add to timeline</button>
                </form>
            </div>
        </section>

        <section class="space-y-5">
            <div class="card p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="font-medium">Open follow-ups</h2>
                    <a href="{{ route('leads.reminders') }}" class="text-sm text-zenora hover:underline" wire:navigate>All reminders</a>
                </div>
                <div class="space-y-3">
                    @forelse ($reminders as $reminder)
                        <div class="rounded-xl border {{ $reminder->isOverdue() ? 'border-red-200 bg-red-50' : 'border-stone-200 bg-white' }} p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-medium">{{ $reminder->subject }}</p>
                                    <p class="mt-1 text-xs {{ $reminder->isOverdue() ? 'text-red-700' : 'text-stone-500' }}">
                                        {{ $reminder->isOverdue() ? 'Overdue · ' : 'Due · ' }}{{ $reminder->reminder_at->format('d M Y, H:i') }}
                                    </p>
                                </div>
                                <button wire:click="completeReminder({{ $reminder->id }})" class="text-xs font-medium text-zenora hover:underline">Complete</button>
                            </div>
                            <form wire:submit="rescheduleReminder({{ $reminder->id }})" class="mt-3 flex gap-2">
                                <input type="datetime-local" wire:model="rescheduleAt.{{ $reminder->id }}" class="field-compact">
                                <button class="btn btn-ghost px-3 py-1 text-xs">Reschedule</button>
                            </form>
                            @error("rescheduleAt.{$reminder->id}") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @empty
                        <p class="py-5 text-center text-sm text-stone-500">No open reminders for this lead.</p>
                    @endforelse
                </div>
            </div>

            <div class="card p-5">
                <h2 class="mb-4 font-medium">Activity timeline</h2>
                <div class="relative ml-1 space-y-6 border-l-2 border-zenora/20 pl-5">
                    @forelse ($activities as $activity)
                        <article class="relative">
                            <span class="absolute -left-[27px] top-1 h-3 w-3 rounded-full border-2 border-white bg-zenora ring-2 ring-zenora/20"></span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-semibold uppercase tracking-wide text-zenora">{{ $activity->type }}</span>
                                <span class="text-xs text-stone-500">{{ $activity->occurred_at->format('d M Y, H:i') }}</span>
                            </div>
                            <h3 class="mt-1 text-sm font-medium">{{ $activity->subject }}</h3>
                            @if ($activity->notes)
                                <p class="mt-1 whitespace-pre-line text-sm text-stone-600">{{ $activity->notes }}</p>
                            @endif
                            @if ($activity->reminder_at)
                                <p class="mt-1 text-xs {{ $activity->completed_at ? 'text-emerald-700' : ($activity->isOverdue() ? 'text-red-700' : 'text-stone-500') }}">
                                    {{ $activity->completed_at ? 'Follow-up completed' : 'Follow-up: '.$activity->reminder_at->format('d M Y, H:i') }}
                                </p>
                            @endif
                        </article>
                    @empty
                        <p class="py-5 text-center text-sm text-stone-500">No activity logged yet.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</div>
