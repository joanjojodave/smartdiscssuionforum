<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Services\InactivityMonitorService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:check-inactive-members')]
#[Description('Warn or blacklist group members who have crossed the configured inactivity threshold (SDD 1.2).')]
class CheckInactiveMembers extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(InactivityMonitorService $service)
    {
        Group::all()->each(function (Group $group) use ($service) {
            $service->checkGroup($group);
        });

        $this->info('Inactivity check complete for '.Group::count().' group(s).');
    }
}
