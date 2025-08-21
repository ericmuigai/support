<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'subdomain',
        'type',
        'name',
        'email',
        'subject',
        'message',
        'priority',
        'status',
        'category',
        'browser',
        'os',
        'url',
        'attachments',
        'metadata',
        'resolved_at',
        'company',
        'receive_newsletter',
    ];

    protected $casts = [
        'attachments' => 'array',
        'metadata' => 'array',
        'resolved_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_id)) {
                $ticket->ticket_id = $ticket->generateTicketId();
            }
        });
    }

    /**
     * Generate a unique ticket ID
     */
    protected function generateTicketId(): string
    {
        do {
            $ticketId = 'TKT-' . strtoupper(Str::random(8));
        } while (static::where('ticket_id', $ticketId)->exists());

        return $ticketId;
    }

    /**
     * Scope to filter by subdomain
     */
    public function scopeForSubdomain(Builder $query, ?string $subdomain): Builder
    {
        return $query->where('subdomain', $subdomain);
    }

    /**
     * Scope to filter by type
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get open tickets
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }

    /**
     * Scope to filter by priority
     */
    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    /**
     * Mark ticket as resolved
     */
    public function markAsResolved(): bool
    {
        return $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    /**
     * Check if ticket is open
     */
    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'in_progress']);
    }

    /**
     * Get ticket type labels
     */
    public static function getTypeLabels(): array
    {
        return [
            'bug_report' => 'Bug Report',
            'feature_request' => 'Feature Request',
            'contact' => 'Contact',
            'general_support' => 'General Support',
        ];
    }

    /**
     * Get priority labels
     */
    public static function getPriorityLabels(): array
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent',
        ];
    }

    /**
     * Get status labels
     */
    public static function getStatusLabels(): array
    {
        return [
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
        ];
    }
}
