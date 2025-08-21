<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('newsletters', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('subdomain')->nullable(); // For multi-subdomain support
            $table->string('name')->nullable();
            $table->string('source')->nullable(); // Where they subscribed from
            $table->boolean('is_active')->default(true);
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_token')->nullable();
            $table->json('metadata')->nullable(); // Additional data like preferences
            $table->timestamps();
            
            $table->unique(['email', 'subdomain']); // Unique combination of email and subdomain
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletters');
    }
};
