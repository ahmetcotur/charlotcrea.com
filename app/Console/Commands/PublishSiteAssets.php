<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishSiteAssets extends Command
{
    protected $signature = 'charlot:assets';

    protected $description = 'Publish existing site assets without replacing Twill admin assets';

    public function handle(): int
    {
        File::ensureDirectoryExists(public_path('assets'));
        foreach (glob(base_path('dist/*')) as $path) {
            if (is_file($path) && in_array(pathinfo($path, PATHINFO_EXTENSION), ['css', 'js', 'ico'])) {
                File::copy($path, public_path(basename($path)));
            }
        }
        foreach (File::directories(base_path('dist/assets')) as $path) {
            $target = public_path('assets/'.basename($path));
            if (! file_exists($target)) {
                File::link($path, $target);
            }
        }
        foreach (File::files(base_path('dist/assets')) as $file) {
            $target = public_path('assets/'.$file->getFilename());
            if (! file_exists($target)) {
                File::link($file->getPathname(), $target);
            }
        }
        File::copy(resource_path('css/site-content.css'), public_path('site-content.css'));
        File::copy(resource_path('js/admin-location-picker.js'), public_path('admin-location-picker.js'));
        File::copy(resource_path('css/admin-location-picker.css'), public_path('admin-location-picker.css'));
        File::copy(resource_path('css/admin-pages.css'), public_path('admin-pages.css'));
        File::copy(resource_path('js/admin-pages.js'), public_path('admin-pages.js'));
        File::copy(resource_path('js/hero-filmstrip.js'), public_path('hero-filmstrip.js'));
        File::copy(resource_path('js/contact-form.js'), public_path('contact-form.js'));
        File::copy(resource_path('css/client-portal.css'), public_path('client-portal.css'));
        File::copy(resource_path('css/admin-crm.css'), public_path('admin-crm.css'));
        File::copy(resource_path('js/admin-crm.js'), public_path('admin-crm.js'));
        $this->info('Site assets published; original assets are linked locally.');

        return self::SUCCESS;
    }
}
