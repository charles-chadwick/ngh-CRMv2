<?php

/** @noinspection DuplicatedCode */

use App\Models\Company;
use App\Models\CustomerRole;
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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->index()->nullable();
            $table->foreignIdFor(CustomerRole::class)->nullable()->index()->constrained()->nullOnDelete();
            $table->string('title')->index()->nullable();
            $table->string('prefix')->index()->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('suffix')->index()->nullable();
            $table->string('email')->unique()->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->string('first_name_normalized')
                ->storedAs('regexp_replace(first_name, \'[^a-zA-Z0-9]+\', \'\')')
                ->index();
            $table->string('last_name_normalized')
                ->storedAs('regexp_replace(last_name, \'[^a-zA-Z0-9]+\', \'\')')
                ->index();
            $table->timestamps();
        });

        Schema::create('customer_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
        Schema::dropIfExists('customer_password_reset_tokens');
    }
};
