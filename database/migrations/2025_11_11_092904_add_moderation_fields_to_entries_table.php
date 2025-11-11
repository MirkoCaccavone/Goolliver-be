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
        Schema::table('entries', function (Blueprint $table) {
            $table->text('moderation_reason')->nullable()->after('moderation_status');
            $table->timestamp('moderated_at')->nullable()->after('moderation_reason');
            $table->unsignedBigInteger('moderated_by')->nullable()->after('moderated_at');

            // Aggiungiamo la foreign key per moderated_by
            $table->foreign('moderated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropForeign(['moderated_by']);
            $table->dropColumn(['moderation_reason', 'moderated_at', 'moderated_by']);
        });
    }
};
