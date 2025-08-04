<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    /**
     * @return void
     */
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->index();
            $table->string('locale', 5)->index();
            $table->text('value');
            $table->string('tag')->nullable()->index();
            $table->timestamps();

            $table->unique(['key', 'locale']);

            // Add composite indexes for common query patterns
            $table->index(['locale', 'tag']);
            $table->index(['key', 'locale', 'tag']);
            $table->index(['created_at', 'updated_at']);
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
