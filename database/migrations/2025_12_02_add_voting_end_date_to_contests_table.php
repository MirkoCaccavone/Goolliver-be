<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contests', function (Blueprint $table) {
            // Aggiunge il campo voting_end_date per la fine della votazione
            $table->dateTime('voting_end_date')->nullable()->after('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contests', function (Blueprint $table) {
            $table->dropColumn('voting_end_date');
        });
    }
};
