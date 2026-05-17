<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_user_system_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mobile_user_id')->constrained('mobile_users')->cascadeOnDelete();
            $table->unsignedBigInteger('leave_user_id')->nullable()->index();
            $table->unsignedBigInteger('medical_user_id')->nullable()->index();
            $table->boolean('can_access_leave')->default(false);
            $table->boolean('can_access_medical')->default(false);
            $table->timestamps();

            $table->unique('mobile_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_user_system_access');
    }
};
