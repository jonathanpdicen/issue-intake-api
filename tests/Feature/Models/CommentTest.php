<?php

use App\Models\Comment;
use App\Models\Issue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deletes comments when their issue is deleted', function () {
    $issue = Issue::factory()->create();
    $otherIssue = Issue::factory()->create();
    Comment::factory()->count(2)->for($issue)->create();
    Comment::factory()->for($otherIssue)->create();

    $issue->delete();

    $this->assertDatabaseMissing('comments', ['issue_id' => $issue->id]);
    $this->assertDatabaseCount('comments', 1);
});
