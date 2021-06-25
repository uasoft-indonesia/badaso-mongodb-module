<?php

namespace Uasoft\Badaso\Module\Mongodb\Commands;

use Exception;
use Illuminate\Console\Command;
use Uasoft\Badaso\Module\Mongodb\ContentManager\FileGenerator;

class BadasoGenerateSeeder extends Command
{
    protected $suffix = 'CollectionSeeder';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'badaso-mongodb:generate-seeder {collections}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate seed files for collections on mongodb database connection.';

    /** @var FileGenerator */
    private $file_generator;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(FileGenerator $file_generator)
    {
        parent::__construct();

        $this->file_generator = $file_generator;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $collections = explode(',', $this->argument('collections'));

        try {
            foreach ($collections as $collection) {
                $this->printResult(
                    $collection,
                    $this->file_generator->generateSeedFile($collection, $this->suffix)
                );
            }
        } catch (Exception $exception) {
            dd($exception);
            $this->printResult($collection, false);
        }
    }

    /**
     * Print Result.
     */
    public function printResult(string $collection, bool $isSuccess = true)
    {
        if ($isSuccess) {
            $this->info("Created a seed file from collection: {$collection}");
            return;
        }

        $this->error("Could not create seed file from collection: {$collection}");
    }
}
