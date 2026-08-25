<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('company')->nullable();
            $table->string('phone')->nullable();

            // Portal access. Only the SHA-256 hash is ever stored: the token is a
            // bearer credential and is shown exactly once, at inquiry conversion.
            // Nullable because staff can create a client before granting access.
            $table->string('portal_token_hash', 64)->nullable()->unique();
            $table->timestamp('portal_token_expires_at')->nullable();
            $table->timestamp('portal_token_revoked_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
