<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_persists_the_member_in_the_database(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test Member',
            'email' => 'member@example.test',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', '註冊完成，請登入。');
        $this->assertDatabaseHas('users', [
            'name' => 'Test Member',
            'email' => 'member@example.test',
        ]);
    }

    public function test_login_distinguishes_an_existing_account_with_a_wrong_password(): void
    {
        User::create([
            'name' => 'Test Member',
            'email' => 'member@example.test',
            'password' => Hash::make('correct-horse-battery'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'member@example.test',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors([
            'password' => '資料庫中有此帳號，但密碼不正確。',
        ]);
    }

    public function test_existing_member_can_log_in_with_the_correct_password(): void
    {
        $user = User::create([
            'name' => 'Test Member',
            'email' => 'member@example.test',
            'password' => Hash::make('correct-horse-battery'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'member@example.test',
            'password' => 'correct-horse-battery',
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_reports_when_the_account_is_not_in_the_database(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'missing@example.test',
            'password' => 'some-password',
        ]);

        $response->assertSessionHasErrors([
            'email' => '資料庫中找不到此電子郵件，請先註冊。',
        ]);
    }
}
