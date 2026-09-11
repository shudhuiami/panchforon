<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the first administrator so someone can reach /admin on a fresh
 * install. Idempotent: re-running promotes the existing account rather than
 * failing on the unique email, and never rewrites an existing password.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('admin.seed_email');
        $password = (string) config('admin.seed_password');

        $user = User::query()->firstOrNew(['email' => $email]);

        $user->fill([
            'name' => $user->exists ? $user->name : (string) config('admin.seed_name'),
            'is_admin' => true,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        if (! $user->exists) {
            $user->password = Hash::make($password);
            $user->email_verified_at = now();
        }

        $user->save();

        $this->command?->info($user->wasRecentlyCreated
            ? "Created admin {$email}."
            : "Promoted existing account {$email} to admin.");

        if ($user->wasRecentlyCreated && $password === 'password') {
            $this->command?->warn('Seeded with the default password. Change it before going live.');
        }
    }
}
