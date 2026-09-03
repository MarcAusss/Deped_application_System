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
