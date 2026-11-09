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
        Schema::create('commitment_amounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commitment_id')->constrained()->cascadeOnDelete();
            $table->date('effective_on');
            $table->bigInteger('amount_cents');
            $table->timestamps();

            $table->unique(['commitment_id', 'effective_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commitment_amounts');
    }
};
