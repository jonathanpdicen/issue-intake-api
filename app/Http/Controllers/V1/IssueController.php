<?php

namespace App\Http\Controllers\V1;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Enums\SummaryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Issue\StoreRequest;
use App\Http\Requests\Issue\UpdateRequest;
use App\Http\Resources\IssueResource;
use App\Jobs\GenerateIssueSummary;
use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class IssueController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $sortOptions = collect(Issue::SORTABLE_COLUMNS)
            ->flatMap(fn (string $column): array => [$column, "-{$column}"])
            ->all();

        $filters = $request->validate([
            'status' => [Rule::enum(IssueStatus::class)],
            'priority' => [Rule::enum(IssuePriority::class)],
            'category' => ['string', 'max:100'],
            'per_page' => ['integer', 'min:1', 'max:100'],
            'sort' => [Rule::in($sortOptions)],
        ]);

        $issues = Issue::query()
            ->withCount('comments')
            ->status($request->enum('status', IssueStatus::class))
            ->priority($request->enum('priority', IssuePriority::class))
            ->category($filters['category'] ?? null)
            ->sorted($filters['sort'] ?? '-created_at')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return IssueResource::collection($issues);
    }

    public function store(StoreRequest $request): IssueResource
    {
        $issue = Issue::query()
            ->create([
                ...$request->validated(),
                'reporter_id' => $request->user()->id,
            ])
            ->refresh();

        GenerateIssueSummary::dispatch($issue)->afterCommit();

        return new IssueResource($issue);
    }

    public function show(Issue $issue): IssueResource
    {
        $issue->load('comments');

        return new IssueResource($issue);
    }

    public function update(UpdateRequest $request, Issue $issue): IssueResource
    {
        $issue->fill($request->validated());

        $descriptionChanged = $issue->isDirty('description');

        if ($descriptionChanged) {
            $issue->forceFill([
                'summary' => null,
                'suggested_next_action' => null,
                'summary_status' => SummaryStatus::PENDING,
            ]);
        }

        $issue->save();

        if ($descriptionChanged) {
            GenerateIssueSummary::dispatch($issue)->afterCommit();
        }

        return new IssueResource($issue);
    }
}
