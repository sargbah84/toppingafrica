<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Admin\Blog\ContentCalendar;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContentCalendarCreatorDiscoveryToggleTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['is_staff' => true]);
    }

    public function test_toggle_persists_to_setting(): void
    {
        Livewire::actingAs($this->staff())
            ->test(ContentCalendar::class)
            ->set('creatorDiscoveryEnabled', false)
            ->call('saveAgentSettings');

        $this->assertFalse(
            filter_var(Setting::get('creator_discovery_enabled'), FILTER_VALIDATE_BOOLEAN)
        );
    }

    public function test_existing_setting_hydrates_into_component(): void
    {
        Setting::set('creator_discovery_enabled', false);

        Livewire::actingAs($this->staff())
            ->test(ContentCalendar::class)
            ->assertSet('creatorDiscoveryEnabled', false);
    }
}
