<?php

namespace Tests\Feature;

use App\Models\Newsletter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_subscribe_to_newsletter(): void
    {
        $response = $this->postJson('/api/newsletter', [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'source' => 'test'
        ], [
            'X-Subdomain' => 'testapp'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Successfully subscribed to newsletter. Please check your email for verification.',
            ]);

        $this->assertDatabaseHas('newsletters', [
            'email' => 'test@example.com',
            'subdomain' => 'testapp',
            'name' => 'Test User',
        ]);
    }

    public function test_cannot_subscribe_with_duplicate_email_in_same_subdomain(): void
    {
        Newsletter::create([
            'email' => 'test@example.com',
            'subdomain' => 'testapp',
        ]);

        $response = $this->postJson('/api/newsletter', [
            'email' => 'test@example.com',
        ], [
            'X-Subdomain' => 'testapp'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ]);
    }

    public function test_can_subscribe_with_same_email_in_different_subdomains(): void
    {
        Newsletter::create([
            'email' => 'test@example.com',
            'subdomain' => 'testapp1',
        ]);

        $response = $this->postJson('/api/newsletter', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], [
            'X-Subdomain' => 'testapp2'
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseCount('newsletters', 2);
    }

    public function test_can_unsubscribe_from_newsletter(): void
    {
        Newsletter::create([
            'email' => 'test@example.com',
            'subdomain' => 'testapp',
            'is_active' => true,
        ]);

        $response = $this->deleteJson('/api/newsletter/unsubscribe/test@example.com', [], [
            'X-Subdomain' => 'testapp'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Successfully unsubscribed from newsletter.',
            ]);

        $this->assertDatabaseHas('newsletters', [
            'email' => 'test@example.com',
            'subdomain' => 'testapp',
            'is_active' => false,
        ]);
    }
}
