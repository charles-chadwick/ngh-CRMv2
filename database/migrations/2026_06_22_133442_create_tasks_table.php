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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('subject');
            $table->nullableMorphs('assignee');
            $table->string('type');
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('status')->default('Open');
            $table->dateTime('due_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->dateTime('remind_at')->nullable();
            $table->dateTime('reminded_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
