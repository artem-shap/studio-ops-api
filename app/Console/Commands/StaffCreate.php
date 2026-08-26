<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Public registration is off, so this is how a studio account comes to exist.
 *
 * Deliberate rather than self-served: anyone with an account here can see every
 * client, every project and every inquiry, and can issue portal links.
 */
class StaffCreate extends Command
{
    protected $signature = 'staff:create
        {--name= : The person\'s name}
        {--email= : Their email address}';

    protected $description = 'Create a studio staff account';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->secret('Password') ?: '';

        $validator = Validator::make(
            compact('name', 'email', 'password'),
            [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', Password::default()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $this->newLine();
        $this->info("Staff account created for {$email}.");

        return self::SUCCESS;
    }
}
