<?php

namespace App\Actions;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Écrit une ligne dans le journal public (règle 6).
 */
class RecordActivity
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function handle(
        Model $subject,
        ?User $actor,
        ActivityAction $action,
        ?array $before = null,
        ?array $after = null,
        ?string $comment = null,
    ): ActivityLog {
        $log = new ActivityLog([
            'action' => $action,
            'before' => $before,
            'after' => $after,
            'comment' => $comment,
        ]);
        $log->subject()->associate($subject);
        $log->actor()->associate($actor);
        $log->save();

        return $log;
    }

    /**
     * Ne garde que les champs qui changent, pour l'état avant/après.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    public static function diff(array $before, array $after): array
    {
        $changed = array_keys(array_filter(
            $after,
            fn (mixed $value, string $key) => ($before[$key] ?? null) !== $value,
            ARRAY_FILTER_USE_BOTH,
        ));

        return [
            array_intersect_key($before, array_flip($changed)),
            array_intersect_key($after, array_flip($changed)),
        ];
    }
}
