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
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('method', 10)->index();
            $table->text('url');
            $table->string('path', 255)->index();
            $table->unsignedSmallInteger('status_code')->index();
            $table->decimal('duration_ms', 10, 2)->default(0.00);
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('device_name', 100)->nullable()->index();
            $table->json('headers')->nullable();
            $table->json('query_params')->nullable();
            $table->json('payload')->nullable();
            $table->json('response_headers')->nullable();
            $table->longText('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['created_at', 'status_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
    }
};
