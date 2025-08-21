<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NewsletterController extends Controller
{
    /**
     * Display a listing of newsletter subscriptions.
     */
    public function index(Request $request): JsonResponse
    {
        $subdomain = $request->header('X-Subdomain') ?? $request->get('subdomain');
        
        $newsletters = Newsletter::query()
            ->when($subdomain, fn($query) => $query->forSubdomain($subdomain))
            ->active()
            ->latest()
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $newsletters,
        ]);
    }

    /**
     * Store a new newsletter subscription.
     */
    public function store(Request $request): JsonResponse
    {
        $subdomain = $request->header('X-Subdomain') ?? $request->get('subdomain');
        
        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('newsletters')->where(function ($query) use ($subdomain) {
                    return $query->where('subdomain', $subdomain);
                }),
            ],
            'name' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:100',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $verificationToken = Str::random(64);

        $newsletter = Newsletter::create([
            'email' => $request->email,
            'subdomain' => $subdomain,
            'name' => $request->name,
            'source' => $request->source ?? 'api',
            'verification_token' => $verificationToken,
            'metadata' => $request->metadata,
        ]);

        // TODO: Send verification email
        // Mail::to($newsletter->email)->send(new NewsletterVerification($newsletter));

        return response()->json([
            'success' => true,
            'message' => 'Successfully subscribed to newsletter. Please check your email for verification.',
            'data' => [
                'id' => $newsletter->id,
                'email' => $newsletter->email,
                'verification_required' => true,
            ],
        ], 201);
    }

    /**
     * Verify newsletter subscription.
     */
    public function verify(Request $request, $token): JsonResponse
    {
        $newsletter = Newsletter::where('verification_token', $token)->first();

        if (!$newsletter) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification token.',
            ], 404);
        }

        if ($newsletter->isVerified()) {
            return response()->json([
                'success' => true,
                'message' => 'Email already verified.',
                'data' => ['verified_at' => $newsletter->verified_at],
            ]);
        }

        $newsletter->markAsVerified();

        return response()->json([
            'success' => true,
            'message' => 'Email successfully verified.',
            'data' => ['verified_at' => $newsletter->verified_at],
        ]);
    }

    /**
     * Display the specified newsletter subscription.
     */
    public function show(Request $request, Newsletter $newsletter): JsonResponse
    {
        $subdomain = $request->header('X-Subdomain') ?? $request->get('subdomain');
        
        // Check if newsletter belongs to the subdomain
        if ($newsletter->subdomain !== $subdomain) {
            return response()->json([
                'success' => false,
                'message' => 'Newsletter subscription not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $newsletter,
        ]);
    }

    /**
     * Update the specified newsletter subscription.
     */
    public function update(Request $request, Newsletter $newsletter): JsonResponse
    {
        $subdomain = $request->header('X-Subdomain') ?? $request->get('subdomain');
        
        // Check if newsletter belongs to the subdomain
        if ($newsletter->subdomain !== $subdomain) {
            return response()->json([
                'success' => false,
                'message' => 'Newsletter subscription not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $newsletter->update($request->only(['name', 'is_active', 'metadata']));

        return response()->json([
            'success' => true,
            'message' => 'Newsletter subscription updated successfully.',
            'data' => $newsletter->fresh(),
        ]);
    }

    /**
     * Unsubscribe from newsletter.
     */
    public function unsubscribe(Request $request, $email): JsonResponse
    {
        $subdomain = $request->header('X-Subdomain') ?? $request->get('subdomain');
        
        $newsletter = Newsletter::where('email', $email)
            ->forSubdomain($subdomain)
            ->first();

        if (!$newsletter) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found.',
            ], 404);
        }

        $newsletter->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Successfully unsubscribed from newsletter.',
        ]);
    }

    /**
     * Remove the specified newsletter subscription.
     */
    public function destroy(Request $request, Newsletter $newsletter): JsonResponse
    {
        $subdomain = $request->header('X-Subdomain') ?? $request->get('subdomain');
        
        // Check if newsletter belongs to the subdomain
        if ($newsletter->subdomain !== $subdomain) {
            return response()->json([
                'success' => false,
                'message' => 'Newsletter subscription not found.',
            ], 404);
        }

        $newsletter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Newsletter subscription deleted successfully.',
        ]);
    }
}
