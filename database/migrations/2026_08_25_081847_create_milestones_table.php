<?php

use App\Enums\MilestoneStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->index()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('due_date')->nullable();
            $table->string('status')->default(MilestoneStatus::Pending->value)->index();

            // Ordering uses steps of 100 so a milestone can be inserted between
            // two others without rewriting every row after it.
            $table->unsignedInteger('position');

            $table->timestamps();

            $table->unique(['project_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestones');
    }
};
