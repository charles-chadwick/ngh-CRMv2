<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A non-null customer_id marks the topic as that customer's dedicated
     * conversation (the thread shown on the customer page). General topics
     * created from the Messages section leave it null.
     */
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->foreignIdFor(Customer::class)->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Customer::class);
        });
    }
};
