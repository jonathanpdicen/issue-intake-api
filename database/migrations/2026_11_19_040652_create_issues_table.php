<?php

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Enums\SummaryStatus;
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
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->enum('priority', IssuePriority::values())
                ->index();
            $table->string('category')
                ->index();
            $table->enum('status', IssueStatus::values())
                ->default(IssueStatus::OPEN->value)
                ->index();
            $table->text('summary')
                ->nullable();
            $table->text('suggested_next_action')
                ->nullable();
            $table->enum('summary_status', SummaryStatus::values())
                ->default(SummaryStatus::PENDING->value);
            $table->boolean('needs_attention')
                ->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
