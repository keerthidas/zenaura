<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Product;
use App\Models\Quotation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard — Zenaura')]
class Dashboard extends Component
{
    public function render()
    {
        $monthStart = now()->startOfMonth();

        return view('livewire.dashboard', [
            'productCount' => Product::count(),
            'clientCount' => Client::count(),
            'monthQuotes' => Quotation::where('quote_date', '>=', $monthStart)->count(),
            'draftQuotes' => Quotation::where('status', 'draft')->count(),
            'recentQuotes' => Quotation::with('client')->latest()->limit(8)->get(),
            'leadCount' => Lead::whereNotIn('status', ['won', 'lost'])->count(),
            'pipelineValue' => Lead::whereNotIn('status', ['won', 'lost'])->sum('estimated_value'),
            'openReminderCount' => LeadActivity::whereHas('lead')
                ->whereNotNull('reminder_at')
                ->whereNull('completed_at')
                ->count(),
            'upcomingReminders' => LeadActivity::with('lead')
                ->whereHas('lead')
                ->whereNotNull('reminder_at')
                ->whereNull('completed_at')
                ->where('reminder_at', '<=', now()->addDays(7))
                ->orderBy('reminder_at')
                ->limit(8)
                ->get(),
        ]);
    }
}
