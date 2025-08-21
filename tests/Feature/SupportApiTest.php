<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_bug_report_ticket(): void
    {
        $ticketData = [
            'type' => 'bug_report',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Login issue',
            'message' => 'Cannot login to my account',
            'priority' => 'high',
            'browser' => 'Chrome 118',
            'os' => 'Windows 11',
        ];

        $response = $this->postJson('/api/support', $ticketData, [
            'X-Subdomain' => 'myapp'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Support ticket created successfully.',
            ])
            ->assertJsonStructure([
                'data' => ['ticket_id', 'id', 'type', 'status', 'created_at']
            ]);

        $this->assertDatabaseHas('support_tickets', [
            'type' => 'bug_report',
            'email' => 'john@example.com',
            'subdomain' => 'myapp',
            'status' => 'open',
        ]);
    }

    public function test_can_create_feature_request_ticket(): void
    {
        $ticketData = [
            'type' => 'feature_request',
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'subject' => 'Dark mode support',
            'message' => 'Please add dark mode to the application',
            'priority' => 'medium',
        ];

        $response = $this->postJson('/api/support', $ticketData, [
            'X-Subdomain' => 'myapp'
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('support_tickets', [
            'type' => 'feature_request',
            'email' => 'jane@example.com',
            'priority' => 'medium',
        ]);
    }

    public function test_ticket_gets_unique_ticket_id(): void
    {
        $response = $this->postJson('/api/support', [
            'type' => 'contact',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Test',
            'message' => 'Test message',
        ]);

        $response->assertStatus(201);
        
        $data = $response->json('data');
        $this->assertMatchesRegularExpression('/^TKT-[A-Z0-9]{8}$/', $data['ticket_id']);
    }

    public function test_can_get_tickets_by_email(): void
    {
        SupportTicket::create([
            'ticket_id' => 'TKT-TEST001',
            'type' => 'bug_report',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Test Subject',
            'message' => 'Test Message',
            'subdomain' => 'myapp',
        ]);

        $response = $this->getJson('/api/support/email/test@example.com', [
            'X-Subdomain' => 'myapp'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(1, 'data.data');
    }

    public function test_can_update_ticket_status(): void
    {
        $ticket = SupportTicket::create([
            'ticket_id' => 'TKT-TEST001',
            'type' => 'bug_report',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Test Subject',
            'message' => 'Test Message',
            'subdomain' => 'myapp',
            'status' => 'open',
        ]);

        $response = $this->putJson('/api/support/' . $ticket->ticket_id, [
            'status' => 'resolved',
        ], [
            'X-Subdomain' => 'myapp'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('support_tickets', [
            'ticket_id' => 'TKT-TEST001',
            'status' => 'resolved',
        ]);
    }

    public function test_different_subdomains_are_isolated(): void
    {
        $ticket1 = SupportTicket::create([
            'ticket_id' => 'TKT-TEST001',
            'type' => 'bug_report',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Test Subject',
            'message' => 'Test Message',
            'subdomain' => 'app1',
        ]);

        $ticket2 = SupportTicket::create([
            'ticket_id' => 'TKT-TEST002',
            'type' => 'bug_report',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Test Subject',
            'message' => 'Test Message',
            'subdomain' => 'app2',
        ]);

        // Should only see ticket for app1
        $response = $this->getJson('/api/support', [
            'X-Subdomain' => 'app1'
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data');
    }
}
