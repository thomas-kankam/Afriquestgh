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
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('admin_slug')->unique();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->unique();
            $table->string('status')->default('inactive');
            $table->longText('profile_image')->nullable();
            $table->string('role_slug')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // indexes
            $table->index('admin_slug', 'admins_admin_slug_idx');
            $table->index('email', 'admins_email_idx');
            $table->index('status', 'admins_status_idx');
            $table->index('deleted_at', 'admins_deleted_at_idx');
            $table->index('role_slug', 'admins_role_slug_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
