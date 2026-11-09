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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->index()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('kind', 20);
            $table->char('last_four', 4)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['family_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
