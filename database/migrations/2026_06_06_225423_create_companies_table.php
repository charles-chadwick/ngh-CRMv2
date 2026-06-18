<?php

use App\Models\CompanyType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(CompanyType::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->string('title');
            $table->text('description')
                ->index()
                ->nullable();
            $table->string('title_normalized')
                ->storedAs('regexp_replace(title, \'[^a-zA-Z0-9]+\', \'\')')
                ->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
