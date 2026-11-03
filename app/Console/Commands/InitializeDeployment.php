<?php

namespace App\Console\Commands;

use A17\Twill\Models\User;
use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class InitializeDeployment extends Command
{
    protected $signature = 'charlot:initialize-deployment';

    protected $description = 'Migrate and initialize a deployment while preserving existing CMS edits and accounts';

    public function handle(): int
    {
        $commands = ['migrate' => ['--force' => true]];
        if (! Page::getConnectionResolver()->connection()->getSchemaBuilder()->hasTable('pages')) {
            $fresh = true;
        } else {
            $fresh = Page::count() === 0;
        }
        if ($fresh) {
            $commands['charlot:import'] = [];
        }
        foreach (['charlot:import-media', 'charlot:prepare-portfolio-content', 'charlot:link-project-brands', 'charlot:prepare-enquiry-pages', 'charlot:prepare-destination-seo', 'charlot:prepare-native-pages'] as $command) {
            $commands[$command] = [];
        }
        foreach ($commands as $command => $arguments) {
            if ($this->call($command, $arguments) !== self::SUCCESS) {
                $this->error('Deployment initialization stopped: '.$command);

                return self::FAILURE;
            }
        }
        $email = config('deployment.admin_email');
        $password = config('deployment.admin_password');
        if ($email && $password && ! User::where('email', $email)->exists()) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 16) {
                $this->error('Initial admin requires a valid email and at least 16 password characters.');

                return self::FAILURE;
            }
            User::forceCreate(['name' => 'Charlot Crea', 'email' => $email, 'password' => Hash::make($password), 'published' => true, 'role' => 'SUPERADMIN', 'registered_at' => now()]);
            $this->info('Initial administrator created.');
        }
        $this->call('cache:clear');
        if (! is_file(config('analytics.country_database'))) {
            $this->call('charlot:analytics-countries');
        }
        $this->info('Deployment ready. Existing accounts and edits preserved.');

        return self::SUCCESS;
    }
}
