<?php

namespace LaravelWebauthn\Console;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravelwebauthn:publish {--force : Overwrite any existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish all of the LaravelWebauthn resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call('vendor:publish', [
            '--tag' => 'webauthn-config',
            '--force' => $this->option('force'),
        ]);
        $this->call('vendor:publish', [
            '--tag' => 'webauthn-migrations',
            '--force' => $this->option('force'),
        ]);
    }
}
