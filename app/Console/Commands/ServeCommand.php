<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand as BaseServeCommand;
use Illuminate\Support\Facades\File;

use function Illuminate\Support\php_binary;

class ServeCommand extends BaseServeCommand
{
    protected function serverCommand()
    {
        $uploadTmp = storage_path('app/tmp');
        if (! is_dir($uploadTmp)) {
            File::makeDirectory($uploadTmp, 0755, true);
        }

        $server = file_exists(base_path('server.php'))
            ? base_path('server.php')
            : base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php');

        return [
            php_binary(),
            '-d', 'upload_tmp_dir='.$uploadTmp,
            '-d', 'upload_max_filesize=20M',
            '-d', 'post_max_size=25M',
            '-S',
            $this->host().':'.$this->port(),
            $server,
        ];
    }
}
