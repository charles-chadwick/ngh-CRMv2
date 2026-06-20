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
            $table->integer('contactable_id');
            $table->string('contactable_type');
            $table->string('type');
            $table->string('street_address')
                ->nullable()
                ->index();
            $table->string('city')
                ->nullable()
                ->index();
            $table->string('state')
                ->nullable()
                ->index();
            $table->string('zip')
                ->nullable()
                ->index();
            $table->string('work_phone')
                ->nullable()
                ->index();
            $table->string('mobile_phone')
                ->nullable()
                ->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
