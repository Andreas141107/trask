<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_switch_team_updates_current_team_and_redirects_dashboard(): void
    {
        $user = User::factory()->create();

        $teamA = Team::create(['owner_id' => $user->id, 'name' => 'Tim A', 'invite_code' => 'TEAMA123']);
        $teamB = Team::create(['owner_id' => $user->id, 'name' => 'Tim B', 'invite_code' => 'TEAMB123']);

        $user->teams()->attach($teamA->id, ['role' => 'owner']);
        $user->teams()->attach($teamB->id, ['role' => 'member']);
        $user->update(['current_team_id' => $teamA->id]);

        $response = $this->actingAs($user)->post(route('teams.switch', $teamB));

        $response->assertRedirect(route('dashboard'));
        $this->assertSame($teamB->id, $user->fresh()->current_team_id);
    }

    public function test_switch_team_rejected_when_not_member(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();

        $team = Team::create(['owner_id' => $owner->id, 'name' => 'Tim Orang', 'invite_code' => 'ORANG123']);
        $owner->teams()->attach($team->id, ['role' => 'owner']);

        $ownTeam = Team::create(['owner_id' => $user->id, 'name' => 'Tim Saya', 'invite_code' => 'SAYA1234']);
        $user->teams()->attach($ownTeam->id, ['role' => 'owner']);
        $user->update(['current_team_id' => $ownTeam->id]);

        $response = $this->actingAs($user)->post(route('teams.switch', $team));

        $response->assertRedirect();
        $this->assertSame($ownTeam->id, $user->fresh()->current_team_id);
        $this->assertDatabaseMissing('team_user', ['user_id' => $user->id, 'team_id' => $team->id]);
    }

    public function test_register_with_valid_invite_code_joins_team_as_member(): void
    {
        $owner = User::factory()->create();
        $team = Team::create(['owner_id' => $owner->id, 'name' => 'Tim Kedai', 'invite_code' => 'KEDAI123']);
        $owner->teams()->attach($team->id, ['role' => 'owner']);

        $response = $this->post('/register', [
            'name' => 'Anggota Baru',
            'email' => 'anggota@baru.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'invite_code' => 'KEDAI123',
        ]);

        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'anggota@baru.test')->first();
        $this->assertNotNull($user);
        $this->assertSame($team->id, $user->current_team_id);
        $this->assertDatabaseHas('team_user', ['user_id' => $user->id, 'team_id' => $team->id, 'role' => 'member']);
        $this->assertDatabaseMissing('teams', ['owner_id' => $user->id]);
    }

    public function test_register_with_invalid_invite_code_fails_validation(): void
    {
        $response = $this->post('/register', [
            'name' => 'Gagal Gabung',
            'email' => 'gagal@gabung.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'invite_code' => 'SALAH123',
        ]);

        $response->assertSessionHasErrors('invite_code');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'gagal@gabung.test']);
    }

    public function test_teams_page_lists_all_teams_for_switching(): void
    {
        $user = User::factory()->create();
        $teamA = Team::create(['owner_id' => $user->id, 'name' => 'Tim A', 'invite_code' => 'TEAMA123']);
        $teamB = Team::create(['owner_id' => $user->id, 'name' => 'Tim B', 'invite_code' => 'TEAMB123']);
        $user->teams()->attach($teamA->id, ['role' => 'owner']);
        $user->teams()->attach($teamB->id, ['role' => 'member']);
        $user->update(['current_team_id' => $teamA->id]);

        $response = $this->actingAs($user)->get('/teams');

        $response->assertOk();
        $response->assertSee('Daftar Tim Anda');
        $response->assertSee('Tim A');
        $response->assertSee('Tim B');
    }
}
