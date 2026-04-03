<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'created_by' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function isDungeonMaster(User $user): bool
    {
        return $this->users()
            ->wherePivot('role', 'dm')
            ->where('users.id', $user->id)
            ->exists();
    }

    public function isMember(User $user): bool
    {
        return $this->users()->where('users.id', $user->id)->exists();
    }
}

