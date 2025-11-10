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
            // Aggiungi i nuovi campi per la moderazione e i metadati (dopo caption)
            $table->decimal('moderation_score', 3, 2)->default(0)->after('caption');
            $table->enum('moderation_status', ['pending', 'approved', 'rejected', 'flagged'])
                ->default('pending')->after('moderation_score');

            // Campi per i metadati del file
            $table->integer('file_size')->nullable()->after('moderation_status');
            $table->string('mime_type', 50)->nullable()->after('file_size');
            $table->json('dimensions')->nullable()->after('mime_type'); // {width: 1920, height: 1080}

            // Stato di elaborazione
            $table->enum('processing_status', ['uploading', 'processing', 'completed', 'failed'])
                ->default('uploading')->after('dimensions');

            // Metadati aggiuntivi
            $table->json('metadata')->nullable()->after('processing_status');

            // Campi per SEO e ricerca
            $table->string('title')->nullable()->after('metadata');
            $table->text('description')->nullable()->after('title');
            $table->string('location')->nullable()->after('description');
            $table->json('tags')->nullable()->after('location');

            // Campi per informazioni fotografiche
            $table->string('camera_model')->nullable()->after('tags');
            $table->string('settings')->nullable()->after('camera_model'); // ISO, aperture, etc.

            // Contatori per performance
            $table->integer('votes_count')->default(0)->after('settings');
            $table->integer('views_count')->default(0)->after('votes_count');

            // Indici per performance
            $table->index(['contest_id', 'moderation_status']);
            $table->index(['user_id', 'created_at']);
            $table->index('moderation_status');
            $table->index('processing_status');
            $table->index('votes_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            // Rimuovi i nuovi campi
            $table->dropColumn([
                'moderation_score',
                'moderation_status',
                'file_size',
                'mime_type',
                'dimensions',
                'processing_status',
                'metadata',
                'title',
                'description',
                'location',
                'tags',
                'camera_model',
                'settings',
                'votes_count',
                'views_count'
            ]);

            // Rimuovi indici
            $table->dropIndex(['status']);
            $table->dropIndex(['moderation_score']);
            $table->dropIndex(['created_at']);
        });
    }
};
