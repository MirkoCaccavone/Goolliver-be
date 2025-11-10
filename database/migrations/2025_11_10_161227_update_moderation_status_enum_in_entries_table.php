<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Aggiorna l'enum per includere 'pending_review'
        DB::statement("ALTER TABLE entries MODIFY COLUMN moderation_status ENUM('pending', 'approved', 'rejected', 'flagged', 'pending_review') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi 'pending_review' dall'enum (prima converti i valori esistenti)
        DB::statement("UPDATE entries SET moderation_status = 'pending' WHERE moderation_status = 'pending_review'");
        DB::statement("ALTER TABLE entries MODIFY COLUMN moderation_status ENUM('pending', 'approved', 'rejected', 'flagged') NOT NULL DEFAULT 'pending'");
    }
};
