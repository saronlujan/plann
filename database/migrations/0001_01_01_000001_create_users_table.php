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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('google_id')->nullable()->unique();
            // Remote picture from an OAuth provider.
            $table->string('avatar_url')->nullable();
            // Uploaded picture: the file name only, the folder is built in code.
            $table->string('avatar')->nullable();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->string('locale', 5)->default('pt');
            $table->string('theme')->default('system');
            $table->string('color')->default('zinc');
            $table->boolean('sound_enabled')->default(true);
            $table->string('sound_theme')->default('blip');
            $table->boolean('notifications_enabled')->default(false);
            $table->unsignedTinyInteger('notify_days_before')->default(3);
            // Preferred currency for new records. Constrained in the currencies
            // migration, which is the first point where that table exists.
            $table->unsignedBigInteger('default_currency_id')->nullable();
            $table->rememberToken();
            // Whoever runs the platform, as opposed to whoever pays for it. A
            // plain flag rather than roles: there is exactly one privilege here —
            // seeing across workspaces. Kept out of the model's fillable list, so
            // it can only be set from the console.
            $table->boolean('is_admin')->default(false);
            // The app is single-user by design: one workspace, one person. The
            // unique index is what actually guarantees it.
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
