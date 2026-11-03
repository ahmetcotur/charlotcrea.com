<?php

namespace App\Console\Commands;

use A17\Twill\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateLocalAdmin extends Command
{
    protected $signature = 'charlot:local-admin';

    protected $description = 'Create a local-only administrator and save credentials outside version control';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Local environment only. Use twill:superadmin interactively for deployment.');

            return self::FAILURE;
        }
        if ($existing = User::where('email', 'admin@charlotcrea.test')->first()) {
            $path = storage_path('app/private/local-admin.txt');
            if ((! $existing->registered_at || ! $existing->password) && is_file($path)) {
                preg_match('/Password: (.+)/', file_get_contents($path), $match);
                if (isset($match[1])) {
                    $existing->registered_at = now();
                    $existing->password = Hash::make($match[1]);
                    $existing->save();
                }
            }
            $this->info('Local administrator already exists; credentials were not changed.');

            return self::SUCCESS;
        }
        $password = bin2hex(random_bytes(20));
        User::forceCreate([
            'name' => 'Charlot Crea', 'email' => 'admin@charlotcrea.test', 'published' => true,
            'role' => 'SUPERADMIN', 'registered_at' => now(), 'password' => Hash::make($password),
        ]);
        $path = storage_path('app/private/local-admin.txt');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0700, true);
        }
        file_put_contents($path, 'URL: '.rtrim(config('app.url'), '/')."/admin\nEmail: admin@charlotcrea.test\nPassword: ".$password."\n");
        chmod($path, 0600);
        $this->info('Local credentials saved in storage/app/private/local-admin.txt');

        return self::SUCCESS;
    }
}
