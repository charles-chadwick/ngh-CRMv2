<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
$table->string('type');
$table->string('street_address')->nullable();
$table->string('city')->nullable();
$table->string('state')->nullable();
$table->string('zip')->nullable();
$table->string('work_phone')->nullable();
$table->string('mobile_phone')->nullable();
$table->timestamps();
$table->softDeletes();//
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
