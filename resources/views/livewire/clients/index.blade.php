<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-stone-500">Directory</p>
            <h1 class="font-display text-3xl">Clients</h1>
        </div>
        <a href="{{ route('clients.create') }}" class="btn btn-primary" wire:navigate>Add client</a>
    </div>
    <div class="mb-4">
        <input wire:model.live.debounce.300ms="search" class="field max-w-md" placeholder="Search name, TRN, phone">
    </div>
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-sand/60 text-left text-xs uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">TRN</th>
                        <th class="px-4 py-3">Emirate</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clients as $client)
                        <tr class="border-t border-stone-100 hover:bg-sand/30">
                            <td class="px-4 py-3 font-medium">{{ $client->name }}</td>
                            <td class="px-4 py-3">{{ $client->trn }}</td>
                            <td class="px-4 py-3">{{ $client->emirate }}</td>
                            <td class="px-4 py-3">{{ $client->phone }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('clients.edit', $client) }}" class="text-zenora hover:underline" wire:navigate>Edit</a>
                                <button wire:click="delete({{ $client->id }})" wire:confirm="Delete this client?" class="ml-3 text-red-600 hover:underline">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-stone-500">No clients yet. TRN is required for every client.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-stone-100 px-4 py-3">{{ $clients->links() }}</div>
    </div>
</div>
