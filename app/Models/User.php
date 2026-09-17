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
 * @property bool $is_admin
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
        'is_admin',
        'suspended_at',
        'suspension_reason',
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
            'is_admin' => 'boolean',
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * Keeps role and is_admin agreeing while both columns exist.
     *
     * The role column is the one being kept; is_admin is dropped once every
     * reader has moved off it. Until then either can be written — the admin
     * panel still toggles the boolean, while new code sets the role — so
     * whichever one changed decides the other. Delete this hook, and the
     * column, in the same change.
     */
    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if ($user->isDirty('is_admin') && ! $user->isDirty('role')) {
                $user->role = $user->is_admin ? UserRole::Admin : UserRole::Member;

                return;
            }

            $user->is_admin = $user->role === UserRole::Admin;
        });
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
     * Gate for the Filament admin panel.
     *
     * Filament calls this on every panel request and on login, and aborts with
     * a 403 when it returns false. Suspended admins are locked out too, so
     * suspending an account is sufficient to revoke panel access.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin && ! $this->isSuspended();
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
        $query->where('is_admin', true);
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
}
