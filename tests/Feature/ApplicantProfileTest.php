<?php

namespace Tests\Feature;

use App\Models\Applicant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicantProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_visiting_profile(): void
    {
        $this->get(route('applicant.profile'))
            ->assertRedirect(route('applicant.login'));
    }

    public function test_applicant_can_update_name_and_email(): void
    {
        $applicant = Applicant::create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'password' => 'password123',
        ]);

        $response = $this->actingAs($applicant, 'applicant')
            ->put(route('applicant.profile.update'), [
                'name' => 'New Name',
                'email' => 'new@example.com',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('applicants', [
            'id' => $applicant->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_applicant_cannot_update_email_to_one_already_taken(): void
    {
        Applicant::create([
            'name' => 'Someone Else',
            'email' => 'taken@example.com',
            'password' => 'password123',
        ]);

        $applicant = Applicant::create([
            'name' => 'Me',
            'email' => 'me@example.com',
            'password' => 'password123',
        ]);

        $response = $this->actingAs($applicant, 'applicant')
            ->put(route('applicant.profile.update'), [
                'name' => 'Me',
                'email' => 'taken@example.com',
            ]);

        $response->assertSessionHasErrorsIn('updateProfile', ['email']);

        $this->assertDatabaseHas('applicants', [
            'id' => $applicant->id,
            'email' => 'me@example.com',
        ]);
    }

    public function test_applicant_can_change_password_with_correct_current_password(): void
    {
        $applicant = Applicant::create([
            'name' => 'Test Applicant',
            'email' => 'applicant@example.com',
            'password' => 'oldpassword123',
        ]);

        $response = $this->actingAs($applicant, 'applicant')
            ->put(route('applicant.profile.password'), [
                'current_password' => 'oldpassword123',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertTrue(\Hash::check('newpassword123', $applicant->fresh()->password));
    }

    public function test_applicant_cannot_change_password_with_wrong_current_password(): void
    {
        $applicant = Applicant::create([
            'name' => 'Test Applicant',
            'email' => 'applicant@example.com',
            'password' => 'oldpassword123',
        ]);

        $response = $this->actingAs($applicant, 'applicant')
            ->put(route('applicant.profile.password'), [
                'current_password' => 'wrongpassword',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertSessionHasErrorsIn('updatePassword', ['current_password']);

        $this->assertTrue(\Hash::check('oldpassword123', $applicant->fresh()->password));
    }
}
