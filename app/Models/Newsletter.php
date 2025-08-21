<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Newsletter extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'subdomain',
        'name',
        'source',
        'is_active',
        'verified_at',
        'verification_token',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Scope to filter by subdomain
     */
    public function scopeForSubdomain(Builder $query, ?string $subdomain): Builder
    {
        return $query->where('subdomain', $subdomain);
    }

    /**
     * Scope to get active subscribers
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get verified subscribers
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('verified_at');
    }

    /**
     * Check if subscriber is verified
     */
    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    /**
     * Mark subscriber as verified
     */
    public function markAsVerified(): bool
    {
        return $this->update([
            'verified_at' => now(),
            'verification_token' => null,
        ]);
    }
}
