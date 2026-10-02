<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `settings` — the application's own configuration, editable at runtime.
 *
 * Everything here was previously a `.env` value, which is the wrong home for it.
 * An operator cannot change the timezone of the business from a `.env` file on a
 * running server without a deploy and a restart, and a deploy to change one
 * string is a deploy that can break five others. So the values an operator needs
 * to adjust while the application is live live here instead.
 *
 * THE VALUE IS JSON, NOT A STRING. A settings table whose values are strings
 * forces every reader to know the type — is `"300"` an int, a string, or the
 * number of seconds someone will compare with `<`? Storing JSON and casting on
 * read means the type travels with the value.
 *
 * `group` is not decoration: it is what makes the settings screen navigable, and
 * a single flat list of forty settings is a screen nobody scrolls to the bottom of.
 *
 * Guarded on existence so the migration is safe to re-run, consistent with every
 * other migration in this project.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            return;
        }

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->default('general')->index();
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_encrypted')->default(false);

            // Who changed it and when. A settings table without an audit trail is
            // how a production incident becomes "somebody must have changed it".
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
