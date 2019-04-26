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
        $this->publish('webauthn-config');
        $this->publish('webauthn-migrations');
        $this->publish('webauthn-assets');
        $this->publish('webauthn-views');
    }

    /**
     * Publish one asset.
     *
     * @param string $tag
     * @return void
     */
    private function publish($tag)
    {
        $this->call('vendor:publish', [
            '--tag' => $tag,
            '--force' => $this->option('force'),
        ]);
    }
}
