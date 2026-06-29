<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personalization_profiles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->uuid('membership_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('scope', 32);
            $table->string('name')->nullable();
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['organization_id', 'scope'], 'personalization_profiles_org_scope_idx');
        });

        Schema::create('personalization_preferences', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->uuid('membership_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('scope', 32);
            $table->string('preference_key');
            $table->string('value_type', 24)->default('string');
            $table->json('value_payload')->nullable();
            $table->string('value_string')->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->integer('value_integer')->nullable();
            $table->decimal('value_decimal', 12, 4)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique([
                'organization_id', 'workspace_id', 'application_id', 'membership_id', 'user_id', 'scope', 'preference_key',
            ], 'personalization_preferences_scope_key_unique');
        });

        Schema::create('personalization_favorites', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->uuid('membership_id')->nullable();
            $table->uuid('user_id');
            $table->string('subject_type');
            $table->string('subject_public_id');
            $table->string('label')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id', 'user_id', 'subject_type', 'subject_public_id'], 'personalization_favorites_unique');
        });

        Schema::create('personalization_recent_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->uuid('membership_id')->nullable();
            $table->uuid('user_id');
            $table->string('subject_type');
            $table->string('subject_public_id');
            $table->string('title')->nullable();
            $table->timestamp('visited_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id', 'user_id', 'subject_type', 'subject_public_id'], 'personalization_recent_items_unique');
        });

        Schema::create('personalization_shortcuts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->uuid('membership_id')->nullable();
            $table->uuid('user_id');
            $table->string('shortcut_key');
            $table->string('label');
            $table->string('route')->nullable();
            $table->string('target')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id', 'user_id', 'shortcut_key'], 'personalization_shortcuts_unique');
        });

        Schema::create('personalization_onboarding_states', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->uuid('membership_id')->nullable();
            $table->uuid('user_id');
            $table->string('flow_key');
            $table->string('status', 24)->default('started');
            $table->string('current_step')->nullable();
            $table->json('completed_steps')->nullable();
            $table->json('dismissed_tips')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id', 'user_id', 'flow_key'], 'personalization_onboarding_states_unique');
        });

        Schema::create('personalization_activity_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->uuid('membership_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('activity_type');
            $table->string('subject_type')->nullable();
            $table->string('subject_public_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'activity_type'], 'personalization_activity_logs_org_activity_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personalization_activity_logs');
        Schema::dropIfExists('personalization_onboarding_states');
        Schema::dropIfExists('personalization_shortcuts');
        Schema::dropIfExists('personalization_recent_items');
        Schema::dropIfExists('personalization_favorites');
        Schema::dropIfExists('personalization_preferences');
        Schema::dropIfExists('personalization_profiles');
    }
};
