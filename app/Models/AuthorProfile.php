<?php

namespace App\Models;

use App\Enums\Permission;
use App\GraphQL\Concerns\ScopesVisibilityToAuthenticatedUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuthorProfile extends Model
{
    use HasFactory;
    use ScopesVisibilityToAuthenticatedUser;

    protected $fillable = [
        'user_id',
        'name',
        'bio',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class);
    }

    protected static function viewAnyPermission(): string
    {
        return Permission::AUTHORS_VIEW_ANY->value;
    }

    protected static function restrictToOwnedRecords(Builder $builder, User $user): void
    {
        $builder->where('user_id', $user->id);
    }
}
