<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SupportController extends Controller
{
    /**
     * Display a listing of support tickets.
     */
    public function index(Request $request): JsonResponse
    {
        $subdomain = $request->header('X-Subdomain') ?? $request->get('subdomain');
        
        $query = SupportTicket::query()
            ->when($subdomain, fn($q) => $q->forSubdomain($subdomain));

        // Apply filters
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('priority')) {
            $query->byPriority($request->priority);
        }

        if ($request->has('email')) {
            $query->where('email', $request->email);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $tickets = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $tickets,
        ]);
    }

    /**
     * Handle contact form submissions (explicit endpoint).
     */
    public function contact(Request $request): JsonResponse
    {
        $subdomain = $request->header('X-Subdomain') ?? $request->get('subdomain');

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:500',
            'message' => 'required|string|max:5000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'string|max:500',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ticket = SupportTicket::create([
            'subdomain' => $subdomain,
            'type' => 'contact',
            'name' => $request->name,
            'email' => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
            'priority' => 'medium',
            'category' => null,
            'browser' => null,
            'os' => null,
            'url' => null,
            'attachments' => $request->attachments,
            'metadata' => $request->metadata,
        ]);

        // TODO: Send notification emails for contact form

        return response()->json([
            'success' => true,
            'message' => 'Contact request submitted successfully.',
            'data' => [
                'ticket_id' => $ticket->ticket_id,
                'id' => $ticket->id,
                'created_at' => $ticket->created_at,
            ],
        ], 201);
    }

    /**
     * Store a new support ticket.
     */
    public function store(Request $request): JsonResponse
    {
        $subdomain = $request->header('X-Subdomain') ?? $request->get('subdomain');
        
        $validator = Validator::make($request->all(), [
            'type' => ['required', Rule::in(['bug_report', 'feature_request', 'contact', 'general_support'])],
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:500',
            'message' => 'required|string|max:5000',
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'category' => 'nullable|string|max:100',
            'browser' => 'nullable|string|max:200',
            'os' => 'nullable|string|max:200',
            'url' => 'nullable|url|max:500',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'string|max:500', // URLs or file paths
            'metadata' => 'nullable|array',
            'company' => 'nullable|string',
            'receive_newsletter' => 'nullable|boolean', // New field for newsletter subscription
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ticket = SupportTicket::create([
            'subdomain' => $subdomain,
            'type' => $request->type,
            'name' => $request->name,
            'email' => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
            'priority' => $request->priority ?? 'medium',
            'category' => $request->category,
            'browser' => $request->browser,
            'os' => $request->os,
            'url' => $request->url,
            'attachments' => $request->attachments,
            'metadata' => $request->metadata,
            'company' => $request->company,
            'receive_newsletter' => $request->receive_newsletter ?? false, // Default to
        ]);

        // TODO: Send notification emails
        // Mail::to(config('support.notification_email'))->send(new NewSupportTicket($ticket));
        // Mail::to($ticket->email)->send(new SupportTicketCreated($ticket));

        return response()->json([
            'success' => true,
            'message' => 'Support ticket created successfully.',
            'data' => [
                'ticket_id' => $ticket->ticket_id,
                'id' => $ticket->id,
                'type' => $ticket->type,
                'status' => $ticket->status,
                'created_at' => $ticket->created_at,
            ],
        ], 201);
    }

    /**
     * Display the specified support ticket.
     */
    public function show(Request $request, string $ticketId): JsonResponse
    {
        $subdomain = $request->header('X-Subdomain') ?? $request->get('subdomain');
        
        $ticket = SupportTicket::where('ticket_id', $ticketId)
            ->when($subdomain, fn($q) => $q->forSubdomain($subdomain))
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Support ticket not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $ticket,
        ]);
    }

    /**
     * Update the specified support ticket.
     */
    public function update(Request $request, string $ticketId): JsonResponse
    {
        $subdomain = $request->header('X-Subdomain') ?? $request->get('subdomain');
        
        $ticket = SupportTicket::where('ticket_id', $ticketId)
            ->when($subdomain, fn($q) => $q->forSubdomain($subdomain))
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Support ticket not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['nullable', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'category' => 'nullable|string|max:100',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updateData = $request->only(['status', 'priority', 'category', 'metadata']);
        
        // If status is being changed to resolved, set resolved_at
        if (isset($updateData['status']) && $updateData['status'] === 'resolved' && !$ticket->resolved_at) {
            $updateData['resolved_at'] = now();
        }

        $ticket->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Support ticket updated successfully.',
            'data' => $ticket->fresh(),
        ]);
    }

    /**
     * Get tickets by email address.
     */
    public function getByEmail(Request $request, string $email): JsonResponse
    {
        $subdomain = $request->header('X-Subdomain') ?? $request->get('subdomain');
        
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email address.',
            ], 422);
        }

        $tickets = SupportTicket::where('email', $email)
            ->when($subdomain, fn($q) => $q->forSubdomain($subdomain))
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $tickets,
        ]);
    }

    /**
     * Get statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $subdomain = $request->header('X-Subdomain') ?? $request->get('subdomain');
        
        $query = SupportTicket::query()
            ->when($subdomain, fn($q) => $q->forSubdomain($subdomain));

        $stats = [
            'total_tickets' => (clone $query)->count(),
            'open_tickets' => (clone $query)->open()->count(),
            'resolved_tickets' => (clone $query)->byStatus('resolved')->count(),
            'by_type' => (clone $query)->selectRaw('type, count(*) as count')->groupBy('type')->pluck('count', 'type'),
            'by_priority' => (clone $query)->selectRaw('priority, count(*) as count')->groupBy('priority')->pluck('count', 'priority'),
            'by_status' => (clone $query)->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Remove the specified support ticket.
     */
    public function destroy(Request $request, string $ticketId): JsonResponse
    {
        $subdomain = $request->header('X-Subdomain') ?? $request->get('subdomain');
        
        $ticket = SupportTicket::where('ticket_id', $ticketId)
            ->when($subdomain, fn($q) => $q->forSubdomain($subdomain))
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Support ticket not found.',
            ], 404);
        }

        $ticket->delete();

        return response()->json([
            'success' => true,
            'message' => 'Support ticket deleted successfully.',
        ]);
    }
}
