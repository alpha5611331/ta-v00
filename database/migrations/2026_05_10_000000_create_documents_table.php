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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('storage_path');
            $table->string('file_type', 20);
            $table->longText('raw_text')->nullable();
            $table->longText('remediated_text')->nullable();
            $table->unsignedInteger('char_count')->default(0);
            $table->timestamp('braille_sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('file_type');
            $table->index('braille_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
