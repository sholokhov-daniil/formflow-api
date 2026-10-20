<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Infrastructure\Eloquent;

use DateTime;

use App\Modules\Identity\Infrastructure\Eloquent\User;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @final
 * @property string   $id
 * @property int      $user_id
 * @property string   $workspace_id
 * @property DateTime $created_at
 * @property DateTime $updated_at
 */
#[Fillable(['user_id', 'workspace_id', 'created_at', 'updated_at'])]
final class WorkspaceMembership extends Model
{
    use HasUuids;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id', 'id');
    }
}
