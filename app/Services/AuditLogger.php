<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AuditLogger
{
    public function record(User $actor, string $action, Model $subject, ?array $before = null, ?array $after = null): void
    {
        $safe = static fn (?array $values): ?array => $values === null
            ? null
            : collect($values)->except(['password', 'remember_token', 'token'])->all();

        DB::table('audit_logs')->insert([
            'actor_user_id' => $actor->id,
            'action' => $action,
            'subject_type' => class_basename($subject),
            'subject_id' => $subject->getKey(),
            'old_values' => $before === null ? null : json_encode($safe($before), JSON_THROW_ON_ERROR),
            'new_values' => $after === null ? null : json_encode($safe($after), JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }
}
