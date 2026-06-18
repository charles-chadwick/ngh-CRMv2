<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assigned_to')
                ->nullable();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('initiated_at')->nullable();
            $table->string('status');
            $table->string('title');
            $table->string('type');
            $table->string('title_normalized')
                ->storedAs('regexp_replace(title, \'[^a-zA-Z0-9]+\', \'\')')
                ->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
