<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `feature_flags` — turning behaviour on without a deploy.
 *
 * The distinction from `settings` is intent, and it is worth being precise about:
 * a setting is a VALUE an operator chose (the timezone, the date format); a flag
 * is a BEHAVIOUR that may be mid-rollout. A flag is expected to be temporary and
 * is expected to be deleted when the rollout finishes. Storing them together is
 * how a rollout flag survives for three years because nobody could tell which kind
 * of row it was.
 *
 * `rollout_percentage` is what makes a flag a gradual rollout rather than a
 * switch, and it is deterministic per user — see `App\Services\FeatureFlags`.
 * A flag that gives a different answer on two consecutive requests from the same
 * user is worse than one that is simply on or off, because it cannot be reasoned
 * about or debugged.
 *
 * `variants` holds a weighted split for more than two groups, so a flag can serve
 * 10% to a treatment and 90% to the rest without a second table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('feature_flags')) {
            return;
        }

        Schema::create('feature_flags', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('boolean');

            // The flag's value when it is on. JSON so a string flag and an integer
            // flag need no separate columns.
            $table->json('value')->nullable();

            $table->boolean('is_enabled')->default(false);
            $table->unsignedTinyInteger('rollout_percentage')->default(100);

            /**
             * Weighted variants, e.g. `[{"value": true, "weight": 10}, {"value": false, "weight": 90}]`.
             *
             * Optional and, when present, takes precedence over `rollout_percentage`.
             * Stored as JSON because it is read whole and never queried.
             */
            $table->json('variants')->nullable();

            // Per-role targeting. A flag aimed at administrators must not be
            // resolvable by asking "is this on?" of a non-administrator.
            $table->json('target_roles')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
    }
};
