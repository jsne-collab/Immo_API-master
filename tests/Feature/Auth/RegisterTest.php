<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\EmailVerificationOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_as_owner(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'phone' => '+22890000001',
            'password' => 'Password!234',
            'password_confirmation' => 'Password!234',
            'role' => 'owner',
            'terms_accepted' => true,
            'privacy_accepted' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role', 'owner')
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'phone', 'role'], 'token']]);

        $this->assertDatabaseHas('users', [
            'email' => 'jean@example.com',
            'role' => 'owner',
        ]);

        $user = User::where('email', 'jean@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('Password!234', $user->password));

        Notification::assertSentTo($user, EmailVerificationOtp::class);
    }

    public function test_registration_does_not_crash_when_the_verification_email_is_actually_rendered(): void
    {
        // Regression test: Notification::fake() in the other tests never
        // calls toMail(), so it could not have caught the real bug where
        // registering triggered Laravel's default MustVerifyEmail
        // listener, which built a link for a web route this API-only app
        // never registers ("Route [verification.verify] not defined.").
        // This test exercises the real notification pipeline instead
        // (still captured by the array mail driver, never actually sent).
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Real Mail Path',
            'email' => 'real-mail@example.com',
            'phone' => '+22890000009',
            'password' => 'Password!234',
            'password_confirmation' => 'Password!234',
            'role' => 'tenant',
            'terms_accepted' => true,
            'privacy_accepted' => true,
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
    }

    public function test_a_user_can_register_as_tenant(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Awa Koffi',
            'email' => 'awa@example.com',
            'phone' => '+22890000002',
            'password' => 'Password!234',
            'password_confirmation' => 'Password!234',
            'role' => 'tenant',
            'terms_accepted' => true,
            'privacy_accepted' => true,
        ]);

        $response->assertCreated()->assertJsonPath('data.user.role', 'tenant');
    }

    public function test_registration_fails_with_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'password', 'role', 'terms_accepted', 'privacy_accepted']);
    }

    public function test_registration_fails_without_accepting_terms_and_privacy(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Someone',
            'email' => 'no-terms@example.com',
            'phone' => '+22890000010',
            'password' => 'Password!234',
            'password_confirmation' => 'Password!234',
            'role' => 'tenant',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['terms_accepted', 'privacy_accepted']);
        $this->assertDatabaseMissing('users', ['email' => 'no-terms@example.com']);
    }

    public function test_registration_records_terms_and_privacy_acceptance_timestamps(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jean Dupont',
            'email' => 'accept@example.com',
            'phone' => '+22890000011',
            'password' => 'Password!234',
            'password_confirmation' => 'Password!234',
            'role' => 'tenant',
            'terms_accepted' => true,
            'privacy_accepted' => true,
        ]);

        $response->assertCreated();

        $user = User::where('email', 'accept@example.com')->firstOrFail();
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertNotNull($user->privacy_accepted_at);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'duplicate@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Someone',
            'email' => 'duplicate@example.com',
            'phone' => '+22890000003',
            'password' => 'Password!234',
            'password_confirmation' => 'Password!234',
            'role' => 'tenant',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_registration_fails_with_duplicate_phone(): void
    {
        User::factory()->create(['phone' => '+22890000004']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Someone',
            'email' => 'unique@example.com',
            'phone' => '+22890000004',
            'password' => 'Password!234',
            'password_confirmation' => 'Password!234',
            'role' => 'tenant',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['phone']);
    }

    public function test_registration_rejects_admin_role(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Someone',
            'email' => 'admin-try@example.com',
            'phone' => '+22890000005',
            'password' => 'Password!234',
            'password_confirmation' => 'Password!234',
            'role' => 'admin',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['role']);
    }

    public function test_registration_fails_when_password_confirmation_does_not_match(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Someone',
            'email' => 'mismatch@example.com',
            'phone' => '+22890000006',
            'password' => 'Password!234',
            'password_confirmation' => 'Different!234',
            'role' => 'tenant',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }
}
