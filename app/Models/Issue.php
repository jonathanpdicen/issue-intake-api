<?php

namespace App\Models;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Enums\SummaryStatus;
use Database\Factories\IssueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property IssuePriority $priority
 * @property string $category
 * @property IssueStatus $status
 * @property string|null $summary
 * @property string|null $suggested_next_action
 * @property SummaryStatus $summary_status
 * @property bool $needs_attention
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'description', 'priority', 'category', 'status'])]
class Issue extends Model
{
    /** @use HasFactory<IssueFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    public const SORTABLE_COLUMNS = ['created_at', 'priority', 'updated_at'];

    protected static function booted(): void
    {
        static::saving(function (Issue $issue): void {
            $issue->needs_attention = $issue->priority === IssuePriority::HIGH;
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => IssuePriority::class,
            'status' => IssueStatus::class,
            'summary_status' => SummaryStatus::class,
            'needs_attention' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Issue>  $query
     */
    #[Scope]
    protected function status(Builder $query, ?IssueStatus $status): void
    {
        $query->when($status, fn (Builder $query) => $query->where('status', $status));
    }

    /**
     * @param  Builder<Issue>  $query
     */
    #[Scope]
    protected function priority(Builder $query, ?IssuePriority $priority): void
    {
        $query->when($priority, fn (Builder $query) => $query->where('priority', $priority));
    }

    /**
     * @param  Builder<Issue>  $query
     */
    #[Scope]
    protected function category(Builder $query, ?string $category): void
    {
        $query->when($category, fn (Builder $query) => $query->where('category', $category));
    }

    /**
     * Sort by a whitelisted column; a leading "-" means descending.
     *
     * @param  Builder<Issue>  $query
     */
    #[Scope]
    protected function sorted(Builder $query, string $sort): void
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $query->orderBy(ltrim($sort, '-'), $direction)->orderBy('id', $direction);
    }
}
