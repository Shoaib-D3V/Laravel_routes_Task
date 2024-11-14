<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_screen_shows_welcome()
    {
        $response = $this->get('/');

        $response->assertViewIs('welcome');
        $response->assertViewHas('pageTitle', 'Homepage');
    }

    public function test_user_page_existing_user_found()
    {
        $user = User::factory()->create();
        $response = $this->get('/user/' . $user->name);

        $response->assertOk();
        $response->assertViewIs('users.show');
    }

    // public function test_user_page_nonexisting_user_not_found()
    // {
    //     $response = $this->get('/user/sometotallynonexistinguser');
    //     $response->assertStatus(404);
    //     $response->assertViewIs('users.notfound');
    // }

    public function test_about_page_is_loaded()
    {
        $response = $this->get('/about');

        $response->assertViewIs('pages.about');
    }

    public function test_auth_middleware_is_working()
    {
        $response = $this->get('/app/dashboard');
        $response->assertRedirect('/login');

        $response = $this->get('/app/tasks');
        $response->assertRedirect('/login');
    }

    public function test_task_crud_is_working()
    {
        $user = User::factory()->create();

        // Test Task list retrieval
        $response = $this->actingAs($user)->get('/app/tasks');
        $response->assertOk();

        // Test Task creation
        $response = $this->actingAs($user)->post('/app/tasks', ['name' => 'Test Task']);
        $response->assertRedirect('/app/tasks');
        $this->assertDatabaseHas('tasks', ['name' => 'Test Task']);

        // Test Task update
        $task = Task::factory()->create(['name' => 'Old Task Name']);
        $response = $this->actingAs($user)->put('/app/tasks/' . $task->id, ['name' => 'Updated Task Name']);
        $response->assertRedirect('/app/tasks');
        $this->assertDatabaseHas('tasks', ['name' => 'Updated Task Name']);

        // Test Task deletion
        $response = $this->actingAs($user)->delete('/app/tasks/' . $task->id);
        $response->assertRedirect('/app/tasks');
        $this->assertDatabaseMissing('tasks', ['name' => 'Updated Task Name']);
    }

    public function test_task_api_crud_is_working()
    {
        $user = User::factory()->create();

        // Test API Task list retrieval
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/tasks');
        $response->assertOk();

        // Test API Task creation
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tasks', ['name' => 'API Task']);
        $response->assertCreated();
        $this->assertDatabaseHas('tasks', ['name' => 'API Task']);

        // Test API Task update
        $task = Task::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/tasks/{$task->id}", ['name' => 'Updated API Task']);
        $response->assertOk();
        $this->assertDatabaseHas('tasks', ['name' => 'Updated API Task']);

        // Test API Task deletion
        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/tasks/{$task->id}");
        $response->assertNoContent();
        $this->assertDatabaseMissing('tasks', ['name' => 'Updated API Task']);
    }

    public function test_is_admin_middleware_is_working()
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');

        $response = $this->get('/admin/stats');
        $response->assertRedirect('/login');

        // Test that a non-admin user is forbidden
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertStatus(403);

        $response = $this->actingAs($user)->get('/admin/stats');
        $response->assertStatus(403);

        // Test that an admin user has access
        $admin = User::factory()->create(['is_admin' => 1]);
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertOk();
        $response->assertViewIs('admin.dashboard');

        $response = $this->actingAs($admin)->get('/admin/stats');
        $response->assertOk();
        $response->assertViewIs('admin.stats');
    }
}
