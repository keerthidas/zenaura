<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-stone-500">Directory</p>
            <h1 class="font-display text-3xl">Suppliers</h1>
        </div>
        <a href="{{ route('suppliers.create') }}" class="btn btn-primary" wire:navigate>Add supplier</a>
    </div>
    <div class="mb-4">
        <input wire:model.live.debounce.300ms="search" class="field max-w-md" placeholder="Search name, TRN, phone, email">
    </div>
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-sand/60 text-left text-xs uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Contact</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3">VAT no.</th>
                        <th class="px-4 py-3 text-right">Active LPOs</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr class="border-t border-stone-100 hover:bg-sand/30">
                            <td class="px-4 py-3 font-medium">{{ $supplier->name }}</td>
                            <td class="px-4 py-3">{{ $supplier->contact_person }}</td>
                            <td class="px-4 py-3">{{ $supplier->phone }}</td>
                            <td class="px-4 py-3">{{ $supplier->trn }}</td>
                            <td class="px-4 py-3 text-right">{{ $supplier->purchase_orders_count }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('suppliers.edit', $supplier) }}" class="text-zenora hover:underline" wire:navigate>Edit</a>
                                <button wire:click="delete({{ $supplier->id }})" wire:confirm="Delete this supplier?" class="ml-3 text-red-600 hover:underline">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-stone-500">No suppliers yet. Add one before raising an LPO.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-stone-100 px-4 py-3">{{ $suppliers->links() }}</div>
    </div>
</div>
