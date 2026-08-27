<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * The counterpart to staff:create.
 *
 * Removing an account is not offered inside the panel. Registration is closed,
 * so a deleted last account cannot be recreated from a browser, and leaving
 * self-deletion in the settings screen meant anyone signed in could lock the
 * studio out of its own tool.
 */
class StaffRemove extends Command
{
    protected $signature = 'staff:remove {email : The account to remove}';

    protected $description = 'Remove a studio staff account';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("No account found for {$email}.");

            return self::FAILURE;
        }

        // There is no way back into the panel without one.
        if (User::query()->count() === 1) {
            $this->error('This is the only staff account. Create another one first.');

            return self::FAILURE;
        }

        $user->delete();

        $this->info("Removed {$email}.");

        return self::SUCCESS;
    }
}
