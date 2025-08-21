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
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_id')->unique(); // Human readable ticket ID
            $table->string('subdomain')->nullable(); // For multi-subdomain support
            $table->enum('type', ['bug_report', 'feature_request', 'contact', 'general_support']);
            $table->string('name');
            $table->string('email');
            $table->string('subject');
            $table->text('message');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->string('category')->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->string('url')->nullable(); // URL where the issue occurred
            $table->json('attachments')->nullable(); // File attachments
            $table->json('metadata')->nullable(); // Additional data
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index(['subdomain', 'type']);
            $table->index(['status', 'priority']);
            $table->index('ticket_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
