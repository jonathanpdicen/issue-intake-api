<?php

namespace App\Http\Controllers\V1\Issue;

use App\Http\Controllers\Controller;
use App\Http\Requests\Issue\Comment\StoreRequest;
use App\Http\Resources\CommentResource;
use App\Models\Issue;

class CommentController extends Controller
{
    public function __invoke(StoreRequest $request, Issue $issue): CommentResource
    {
        $comment = $issue->comments()->create($request->validated());

        return new CommentResource($comment);
    }
}
