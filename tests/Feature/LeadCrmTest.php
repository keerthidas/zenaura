<?php

namespace Tests\Feature;

use App\Livewire\Leads\LeadForm;
use App\Livewire\Leads\Index as LeadsIndex;
use App\Livewire\Leads\LeadShow;
use App\Livewire\Leads\Reminders;
use App\Livewire\Clients\ClientForm;
use App\Livewire\Quotations\QuotationEditor;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeadCrmTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_lead_can_be_created_with_pipeline_details(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(LeadForm::class)
            ->set('name', 'Alex Prospect')
            ->set('company', 'Prospect Interiors')
            ->set('email', 'alex@example.com')
            ->set('source', 'Referral')
            ->set('status', 'qualified')
            ->set('estimated_value', '12500')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('leads', [
            'name' => 'Alex Prospect',
            'company' => 'Prospect Interiors',
            'source' => 'Referral',
            'status' => 'qualified',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('leads.index'))
            ->assertOk()
            ->assertSee('Leads', false);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Active leads')
            ->assertSee('Open follow-ups');
    }

    public function test_a_lead_can_be_deleted_from_the_pipeline(): void
    {
        $user = User::factory()->create();
        $lead = Lead::create(['name' => 'Remove This Lead', 'status' => 'new']);
        $remainingLead = Lead::create(['name' => 'Keep This Lead', 'status' => 'new']);

        Livewire::actingAs($user)
            ->test(LeadsIndex::class)
            ->call('delete', $lead->id)
            ->assertHasNoErrors()
            ->assertDontSee($lead->name)
            ->assertSee($remainingLead->name);

        $this->assertSoftDeleted('leads', ['id' => $lead->id]);
        $this->assertSame(1, Lead::where('status', 'new')->count());
    }

    public function test_lead_activities_support_minutes_reminders_rescheduling_and_completion(): void
    {
        $user = User::factory()->create();
        $lead = Lead::create([
            'name' => 'Alex Prospect',
            'status' => 'new',
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(LeadShow::class, ['lead' => $lead])
            ->set('activityType', 'meeting')
            ->set('activitySubject', 'Showroom project meeting')
            ->set('activityNotes', 'Discussed the bathroom package and next steps.')
            ->set('occurredAt', now()->subHour()->format('Y-m-d\TH:i'))
            ->set('reminderAt', now()->subMinute()->format('Y-m-d\TH:i'))
            ->call('addActivity')
            ->assertHasNoErrors();

        $activity = LeadActivity::query()->where('lead_id', $lead->id)->firstOrFail();
        $this->assertSame('meeting', $activity->type);
        $this->assertSame('Discussed the bathroom package and next steps.', $activity->notes);
        $this->assertTrue($activity->isOverdue());

        $newDate = now()->addDays(2)->format('Y-m-d\TH:i');
        Livewire::actingAs($user)
            ->test(LeadShow::class, ['lead' => $lead])
            ->set('rescheduleAt.'.$activity->id, $newDate)
            ->call('rescheduleReminder', $activity->id)
            ->assertHasNoErrors();

        $this->assertEqualsWithDelta(now()->addDays(2)->timestamp, $activity->fresh()->reminder_at->timestamp, 60);

        Livewire::actingAs($user)
            ->test(Reminders::class)
            ->call('complete', $activity->id);

        $this->assertNotNull($activity->fresh()->completed_at);
    }

    public function test_a_lead_can_be_converted_once_and_prefilled_into_a_quotation(): void
    {
        $user = User::factory()->create();
        $lead = Lead::create([
            'name' => 'Alex Prospect',
            'company' => 'Prospect Interiors',
            'phone' => '+971500000000',
            'email' => 'alex@example.com',
            'emirate' => 'Dubai',
            'address' => 'Business Bay',
            'notes' => 'Interested in a full bathroom package.',
            'status' => 'qualified',
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(LeadShow::class, ['lead' => $lead])
            ->call('convertToClient')
            ->assertHasNoErrors();

        $lead->refresh();
        $client = Client::findOrFail($lead->client_id);

        $this->assertSame('won', $lead->status);
        $this->assertNotNull($lead->converted_at);
        $this->assertSame('Prospect Interiors', $client->name);
        $this->assertSame('+971500000000', $client->phone);
        $this->assertNull($client->trn);
        Livewire::actingAs($user)
            ->test(ClientForm::class, ['client' => $client])
            ->assertSet('trn', '');
        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'subject' => 'Lead converted to client',
        ]);

        Livewire::actingAs($user)
            ->test(LeadShow::class, ['lead' => $lead])
            ->call('convertToClient');

        $this->assertSame(1, Client::count());
        $this->assertSame(1, Lead::whereNotNull('client_id')->count());

        CompanySetting::flushCache();
        Livewire::actingAs($user)
            ->withQueryParams(['client' => $client->id])
            ->test(QuotationEditor::class)
            ->assertSet('client_id', $client->id)
            ->assertSet('client_query', $client->name);
    }
}
