<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Multi-tenant configuration for HRMS APIs.
        if (!Schema::hasTable('hrms_client_configs')) {
            Schema::create('hrms_client_configs', function (Blueprint $table) {
                $table->id();
                $table->string('client_name');
                $table->string('api_key')->unique()->comment('Bearer token used by HRMS to call TadreebLMS');
                $table->string('hrms_provider')->comment('zoho, sap, darwinbox, custom');
                $table->json('provider_credentials')->nullable()->comment('Specific keys for outbound webhook/syncs if TadreebLMS pushes to HRMS');
                $table->json('field_mappings')->nullable();
                $table->string('webhook_url')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hrms_assignment_rules')) {
            Schema::create('hrms_assignment_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hrms_client_config_id')->constrained('hrms_client_configs')->cascadeOnDelete();
                $table->string('condition_field')->comment('e.g. department, position');
                $table->string('condition_operator')->default('=');
                $table->string('condition_value');
                $table->unsignedInteger('course_id');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hrms_sync_logs')) {
            Schema::create('hrms_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hrms_client_config_id')->nullable()->constrained('hrms_client_configs')->nullOnDelete();
                $table->string('action')->comment('user.created, course.assigned, webhook.sent');
                $table->string('status')->comment('success, failed, pending');
                $table->text('message')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hrms_sync_logs');
        Schema::dropIfExists('hrms_assignment_rules');
        Schema::dropIfExists('hrms_client_configs');
    }
};
