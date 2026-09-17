<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property UserRole $role
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $suspended_at
 * @property string|null $suspension_reason
 */
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'suspended_at',
        'suspension_reason',
        'youtube_channel_id',
        'youtube_channel_handle',
        'youtube_channel_title',
    ];

    /**
     * The column default only applies on insert, so a model that has not been
     * near the database yet — anything built with ->make() — would otherwise
     * carry a null role and blow up the first predicate that asked it a
     * question.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'role' => 'member',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'suspended_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Admins are creators too: anything a creator may do, an admin may do.
     */
    public function isCreator(): bool
    {
        return $this->role->reachesStudio();
    }

    /**
     * The question seven policies were each asking in their own copy of it.
     */
    public function isActiveAdmin(): bool
    {
        return $this->isAdmin() && ! $this->isSuspended();
    }

    /**
     * Whether this author's recipes go straight onto the site rather than
     * into the moderation queue.
     */
    public function canPublishWithoutReview(): bool
    {
        return $this->role->publishesWithoutReview() && ! $this->isSuspended();
    }

    /**
     * Gate for every Filament panel.
     *
     * Filament calls this on each panel request and on login, and aborts with
     * a 403 when it returns false. Suspending an account is therefore enough
     * to revoke access to both panels at once.
     *
     * The default arm refuses rather than falling through to one of the named
     * ones: a panel added later should be shut until someone deliberately
     * opens it, not inherit whichever branch happened to be written last.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->isActiveAdmin(),
            'studio' => $this->isCreator() && ! $this->isSuspended(),
            default => false,
        };
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * @param  Builder<User>  $query
     */
    public function scopeAdmins(Builder $query): void
    {
        $query->where('role', UserRole::Admin);
    }

    /**
     * @param  Builder<User>  $query
     */
    public function scopeSuspended(Builder $query): void
    {
        $query->whereNotNull('suspended_at');
    }

    /**
     * @return HasMany<Recipe, $this>
     */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    /**
     * @return HasMany<Rating, $this>
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * The recipes this cook has kept for later, newest save first.
     *
     * @return BelongsToMany<Recipe, $this>
     */
    public function savedRecipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'recipe_saves')
            ->withTimestamps()
            ->orderByDesc('recipe_saves.created_at');
    }

    /**
     * @return HasMany<RecipeSave, $this>
     */
    public function recipeSaves(): HasMany
    {
        return $this->hasMany(RecipeSave::class);
    }

    /**
     * @return HasMany<MealPlan, $this>
     */
    public function mealPlans(): HasMany
    {
        return $this->hasMany(MealPlan::class);
    }

    /**
     * @return HasOne<MealPlan, $this>
     */
    public function activeMealPlan(): HasOne
    {
        return $this->hasOne(MealPlan::class)->where('is_active', true);
    }

    /**
     * Every time this cook has asked to be made a creator, declines included.
     *
     * @return HasMany<CreatorApplication, $this>
     */
    public function creatorApplications(): HasMany
    {
        return $this->hasMany(CreatorApplication::class);
    }

    /**
     * The attempt that counts: whether they are waiting on a decision, and
     * what the last one was.
     *
     * @return HasOne<CreatorApplication, $this>
     */
    public function latestCreatorApplication(): HasOne
    {
        return $this->hasOne(CreatorApplication::class)->latestOfMany();
    }
}
