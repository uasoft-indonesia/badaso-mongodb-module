<?php

namespace Uasoft\Badaso\Module\Mongodb\ContentManager;

use Carbon\Carbon;
use Illuminate\Filesystem\Filesystem as LaravelFileSystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;

class FileSystem
{
    /** @var LaravelFileSystem */
    private $filesystem;

    /** @var Composer */
    private $composer;

    /**
     * Create the event listener.
     */
    public function __construct(LaravelFileSystem $filesystem, Composer $composer)
    {
        $this->filesystem = $filesystem;
        $this->composer = $composer;
    }

    /**
     * Get seeder file.
     */
    public function getSeederFile(string $name, string $path): string
    {
        return $path.'/'.$name.'.php';
    }

    /**
     * Get Seed Folder Path.
     */
    public function getSeedCRUDFolderPath(): string
    {
        $path = base_path().'/database/seeders/Badaso/CRUD';
        if (! file_exists($path)) {
            mkdir($path, 0777);
        }

        return $path;
    }

    /**
     * Get Seed Folder Path.
     */
    public function getSeedManualGenerateFolderPath(): string
    {
        $path = base_path().'/database/seeders/Badaso/MongodbManualGenerate';
        if (! file_exists($path)) {
            mkdir($path, 0777);
        }

        return $path;
    }

    /**
     * Get Stub Path.
     */
    public function getStubPath(): string
    {
        return __DIR__.DIRECTORY_SEPARATOR;
    }

    /**
     * Delete Seed File.
     */
    public function deleteSeedFiles(string $file_name): bool
    {
        $seeder_file = $this->getSeederFile($file_name, $this->getSeedCRUDFolderPath());

        if ($this->filesystem->exists($seeder_file)) {
            return $this->filesystem->delete($seeder_file);
        }

        return false;
    }

    /**
     * Generate Seeder Class Name.
     */
    public function generateSeederClassName(string $model_slug, string $suffix): string
    {
        $model_string = '';

        $model_name = explode('-', $model_slug);
        foreach ($model_name as $model_name_exploded) {
            $model_string .= ucfirst($model_name_exploded);
        }

        return ucfirst($model_string).$suffix;
    }

    /**
     * Add Content to Seeder file.
     */
    public function addContentToSeederFile(string $seeder_file, string $seeder_contents, bool $run_composer_dump_autoload = true): bool
    {
        if (! $this->filesystem->put($seeder_file, $seeder_contents)) {
            return false;
        }

        if ($run_composer_dump_autoload) {
            $this->composer->dumpAutoloads();
        }

        return true;
    }

    /**
     * Get File Content.
     *
     * @param $file
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getFileContent($file): string
    {
        return $this->filesystem->get($file);
    }
}