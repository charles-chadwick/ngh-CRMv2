<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Move the free-text customer title onto the titles pivot, then drop the column.
     */
    public function up(): void
    {
        $titles = DB::table('customers')
            ->whereNotNull('title')
            ->where('title', '!=', '')
            ->distinct()
            ->pluck('title');

        foreach ($titles as $title) {
            $title_id = DB::table('titles')->where('title', $title)->value('id')
                ?? DB::table('titles')->insertGetId([
                    'title' => $title,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            $rows = DB::table('customers')
                ->where('title', $title)
                ->pluck('id')
                ->map(fn (int $customer_id): array => [
                    'customer_id' => $customer_id,
                    'title_id' => $title_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->all();

            DB::table('customer_titles')->insert($rows);
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }

    /**
     * Restore the column, keeping the first title attached to each customer.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('title')->nullable()->index()->after('customer_role_id');
        });

        DB::table('customer_titles')
            ->join('titles', 'titles.id', '=', 'customer_titles.title_id')
            ->orderBy('customer_titles.id')
            ->get(['customer_titles.customer_id', 'titles.title'])
            ->unique('customer_id')
            ->each(fn (object $row) => DB::table('customers')
                ->where('id', $row->customer_id)
                ->update(['title' => $row->title]));
    }
};
