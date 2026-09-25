<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Admin\ManageCreators;
use App\Models\Creator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ManageCreatorsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('creator', 'web');
        Role::findOrCreate('regular', 'web');
    }

    private function staff(): User
    {
        return User::factory()->create(['is_staff' => true]);
    }

    private function makeCreator(array $attributes): Creator
    {
        return Creator::create($attributes + ['bio' => 'Test bio.', 'country' => 'Nigeria', 'category' => 'Tech']);
    }

    public function test_unpublish_moves_user_submitted_creator_back_to_pending_and_demotes_owner(): void
    {
        $owner = User::factory()->create();
        $owner->syncRoles(['creator']);

        $creator = $this->makeCreator([
            'user_id' => $owner->id,
            'name' => 'Suspicious Profile',
            'status' => 'published',
            'claim_token' => 'abc',
            'claim_token_expires_at' => now()->addDay(),
        ]);

        Livewire::actingAs($this->staff())
            ->test(ManageCreators::class)
            ->call('unpublish', $creator->id);

        $creator->refresh();
        $this->assertSame('pending', $creator->status);
        $this->assertNull($creator->claim_token);
        $this->assertTrue($owner->fresh()->hasRole('regular'));
        $this->assertFalse($owner->fresh()->hasRole('creator'));
    }

    public function test_unpublish_keeps_creator_role_when_owner_has_another_live_profile(): void
    {
        $owner = User::factory()->create();
        $owner->syncRoles(['creator']);

        $target = $this->makeCreator(['user_id' => $owner->id, 'name' => 'First One', 'status' => 'published']);
        $this->makeCreator(['user_id' => $owner->id, 'name' => 'Second One', 'status' => 'claimed']);

        Livewire::actingAs($this->staff())
            ->test(ManageCreators::class)
            ->call('unpublish', $target->id);

        $this->assertSame('pending', $target->fresh()->status);
        $this->assertTrue($owner->fresh()->hasRole('creator'));
    }

    public function test_preview_shows_pending_submission_with_owner_details(): void
    {
        $owner = User::factory()->create(['name' => 'Submitter Person', 'email' => 'submitter@example.com']);
        $creator = $this->makeCreator(['user_id' => $owner->id, 'name' => 'Pending Hopeful', 'status' => 'pending']);

        Livewire::actingAs($this->staff())
            ->test(ManageCreators::class)
            ->assertDontSee('Owner account')
            ->call('openPreview', $creator->id)
            ->assertSet('previewingCreatorId', $creator->id)
            ->assertSee('Owner account')
            ->assertSee('submitter@example.com')
            ->assertSee('Test bio.')
            ->assertSee('Re-pull')
            ->call('closePreview')
            ->assertSet('previewingCreatorId', null)
            ->assertDontSee('Owner account');
    }

    public function test_editing_from_preview_closes_the_preview(): void
    {
        $creator = $this->makeCreator(['name' => 'Editable One', 'status' => 'published']);

        Livewire::actingAs($this->staff())
            ->test(ManageCreators::class)
            ->call('openPreview', $creator->id)
            ->call('edit', $creator->id)
            ->assertSet('previewingCreatorId', null)
            ->assertSet('showEditModal', true);
    }

    public function test_unpublish_ignores_pending_creators(): void
    {
        $creator = $this->makeCreator(['name' => 'Already Pending', 'status' => 'pending']);

        Livewire::actingAs($this->staff())
            ->test(ManageCreators::class)
            ->call('unpublish', $creator->id);

        $this->assertSame('pending', $creator->fresh()->status);
    }
}
