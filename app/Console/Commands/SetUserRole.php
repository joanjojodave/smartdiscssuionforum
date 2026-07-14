<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetUserRole extends Command
{
    protected $signature = 'users:set-role {email} {role}';

    protected $description = "Set a user's role (admin, lecturer, or member) by email";

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No user found with that email.');

            return self::FAILURE;
        }

        $user->update(['role' => $this->argument('role')]);
        $this->info("{$user->name} is now {$this->argument('role')}.");

        return self::SUCCESS;
    }
}
