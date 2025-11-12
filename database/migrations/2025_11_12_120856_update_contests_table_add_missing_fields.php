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
        Schema::table('contests', function (Blueprint $table) {
            // Rinomino le colonne date per coerenza
            $table->renameColumn('start_at', 'start_date');
            $table->renameColumn('end_at', 'end_date');
        });

        Schema::table('contests', function (Blueprint $table) {
            // Aggiungo i nuovi campi necessari
            $table->integer('current_participants')->default(0)->after('max_participants');
            $table->string('category')->nullable()->after('description');
            $table->decimal('entry_fee', 8, 2)->default(0)->after('prize');

            // Aggiorno l'enum status con i nuovi valori
            $table->dropColumn('status');
        });

        Schema::table('contests', function (Blueprint $table) {
            $table->enum('status', ['active', 'upcoming', 'ended', 'open', 'voting', 'closed'])->default('upcoming')->after('entry_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contests', function (Blueprint $table) {
            // Rimuovo i campi aggiunti
            $table->dropColumn(['current_participants', 'category', 'entry_fee']);

            // Ripristino l'enum status originale
            $table->dropColumn('status');
        });

        Schema::table('contests', function (Blueprint $table) {
            $table->enum('status', ['open', 'voting', 'closed'])->default('open');

            // Ripristino i nomi delle colonne originali
            $table->renameColumn('start_date', 'start_at');
            $table->renameColumn('end_date', 'end_at');
        });
    }
};
