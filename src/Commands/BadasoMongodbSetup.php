<?php

namespace Uasoft\Badaso\Module\Mongodb\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class BadasoMongodbSetup extends Command
{
    private $force;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'badaso-mongodb:setup {--force=false}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup mongodb module';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $this->force = $this->option('force');
            if ($this->force == null || $this->force == 'true') {
                $this->force = true;
            } else {
                $this->force = false;
            }

            $this->publishBadasoMongodbProvider();
            $this->addingBadasoMongodbEnv();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    protected function publishBadasoMongodbProvider()
    {
        $command_params = ['--tag' => 'BadasoMongodbModule'];
        if ($this->force) {
            $command_params['--force'] = true;
        }

        Artisan::call('vendor:publish', $command_params);

        $this->info('Badaso mongodb provider published');
    }

    protected function addingBadasoMongodbEnv()
    {
        try {
            $env_path = base_path('.env');

            $env_file = file_get_contents($env_path);
            $arr_env_file = explode("\n", $env_file);

            $env_will_adding = $this->envListUpload();

            $new_env_adding = [];
            $new_env_adding[0] = PHP_EOL;
            $new_env_adding[1] = '# Badaso MongoDB Module';
            foreach ($env_will_adding as $key_add_env => $val_add_env) {
                $status_adding = true;
                foreach ($arr_env_file as $key_env_file => $val_env_file) {
                    $val_env_file = trim($val_env_file);
                    if (substr($val_env_file, 0, 1) != '#' && $val_env_file != '' && strstr($val_env_file, $key_add_env)) {
                        $status_adding = false;
                        break;
                    }
                }
                if ($status_adding) {
                    $new_env_adding[] = "{$key_add_env}={$val_add_env}";
                }
            }

            foreach ($new_env_adding as $index_env_add => $val_env_add) {
                $arr_env_file[] = $val_env_add;
            }

            $env_file = join("\n", $arr_env_file);
            file_put_contents($env_path, $env_file);

            $this->info('Adding badaso env');
        } catch (\Exception $e) {
            $this->error('Failed adding badaso env '.$e->getMessage());
        }
    }

    protected function envListUpload()
    {
        return [
            'MONGO_HOST'        => '127.0.0.1',
            'MONGO_PORT'        => '27017',
            'MONGO_DATABASE'    => 'laravel',
            'MONGO_USERNAME'    => '',
            'MONGO_PASSWORD'    => '',
        ];
    }
}
