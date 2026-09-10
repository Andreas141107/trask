<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamJoinTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_join_a_team_with_an_invite_code(): void
    {
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@trask.test',
            'password' => 'password',
        ]);
        $team = Team::create([
            'owner_id' => $owner->id,
            'name' => 'Tim Owner',
            'invite_code' => 'JOIN1234',
        ]);
        $owner->teams()->attach($team->id, ['role' => 'owner']);

        $user = User::create([
            'name' => 'Member',
            'email' => 'member@trask.test',
            'password' => 'password',
        ]);

        $response = $this->actingAs($user)->post('/teams/join', ['invite_code' => 'join1234']);

        $response->assertRedirect(route('teams.index'));
        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => 'member',
        ]);
        $this->assertSame($team->id, $user->fresh()->current_team_id);
    }

    public function test_invalid_invite_code_does_not_join_a_team(): void
    {
        $user = User::create([
            'name' => 'Member',
            'email' => 'member-invalid@trask.test',
            'password' => 'password',
        ]);

        $response = $this->actingAs($user)->post('/teams/join', ['invite_code' => 'BADCODE1']);

        $response->assertSessionHasErrors('invite_code');
        $this->assertDatabaseCount('team_user', 0);
    }
}
