<?php

namespace Uasoft\Badaso\Module\Mongodb\ContentManager;

use Exception;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Uasoft\Badaso\Models\DataType;

class FileGenerator
{
    /** @var string */
    const TYPE_SEEDER_SUFFIX = 'CRUDDataTypeAdded';

    /** @var string */
    const ROW_SEEDER_SUFFIX = 'CRUDDataRowAdded';

    /** @var string */
    const DELETED_SEEDER_SUFFIX = 'CRUDDataDeleted';

    /** @var ContentGenerator */
    private $content_manager;

    /** @var FileSystem */
    private $file_system;

    /** @var DatabaseManager */
    private $database_manager;

    /**
     * FilesGenerator constructor.
     */
    public function __construct(ContentManager $content_manager, FileSystem $file_system, DatabaseManager $database_manager)
    {
        $this->content_manager = $content_manager;
        $this->file_system = $file_system;
        $this->database_manager = $database_manager->connection('mongodb');
    }

    /**
     * Generate Data Type Seed File.
     */
    public function generateDataTypeSeedFile(DataType $data_type): bool
    {
        $seeder_class_name = $this->file_system->generateSeederClassName(
            $data_type->slug,
            self::TYPE_SEEDER_SUFFIX
        );

        $stub = $this->file_system->getFileContent(
            $this->file_system->getStubPath().'../stubs/data_seed.stub'
        );

        $seed_folder_path = $this->file_system->getSeedCRUDFolderPath();

        $seeder_file = $this->file_system->getSeederFile($seeder_class_name, $seed_folder_path);

        $data_type->details = (! is_null($data_type->details)) ? json_encode($data_type->details) : null;

        $stub = $this->content_manager->replaceString('{{class}}', $seeder_class_name, $stub);

        $seed_content = $this->content_manager->populateContentToStubFile(
            $stub,
            $data_type,
            self::TYPE_SEEDER_SUFFIX
        );

        // We replace the #data_typeId with the $data_typeId variable
        // that will exist in seeder file.
        $seed_content = $this->addDataTypeId($seed_content);

        $this->file_system->addContentToSeederFile($seeder_file, $seed_content);

        return $this->updateOrchestraSeeder($seeder_class_name);
    }

    /**
     * Generate Data Row Seed File.
     *
     * @param $data_type
     */
    public function generateDataRowSeedFile(DataType $data_type): bool
    {
        $seeder_class_name = $this->file_system->generateSeederClassName(
            $data_type->slug,
            self::ROW_SEEDER_SUFFIX
        );

        $stub = $this->file_system->getFileContent(
            $this->file_system->getStubPath().'../stubs/row_seed.stub'
        );

        $seed_folder_path = $this->file_system->getSeedCRUDFolderPath();

        $seeder_file = $this->file_system->getSeederFile($seeder_class_name, $seed_folder_path);

        $stub = $this->content_manager->replaceString('{{class}}', $seeder_class_name, $stub);

        $seed_content = $this->content_manager->populateContentToStubFile(
            $stub,
            $data_type,
            self::ROW_SEEDER_SUFFIX
        );

        // We replace the #data_typeId with the $data_typeId variable
        // that will exist in seeder file.
        $seed_content = $this->addDataTypeId($seed_content);

        $this->file_system->addContentToSeederFile($seeder_file, $seed_content);

        return $this->updateOrchestraSeeder($seeder_class_name);
    }

    /**
     * Delete And Generate Seed Files.
     *
     * @param $data_type
     */
    public function deleteAndGenerate(DataType $data_type)
    {
        $this->deleteSeedFiles($data_type);

        $this->generateDataTypeSeedFile($data_type);

        $this->generateDataRowSeedFile($data_type);
    }

    /**
     * Update Orchestra Seeder Run Method.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function updateOrchestraSeeder(string $className): bool
    {
        $database_seeder_path = $this->file_system->getSeedCRUDFolderPath();

        $seeder_class_name = 'BadasoDeploymentOrchestratorSeeder';

        $file = $this->file_system->getSeederFile($seeder_class_name, $database_seeder_path);

        $content = $this->file_system->getFileContent($file);

        $content = $this->content_manager->updateDeploymentOrchestraSeederContent($className, $content);

        return $this->file_system->addContentToSeederFile($file, $content);
    }

    /**
     * Delete Seed Files.
     */
    public function deleteSeedFiles(DataType $data_type)
    {
        $data_type_seeder_class = $this->file_system->generateSeederClassName(
            $data_type->slug,
            self::TYPE_SEEDER_SUFFIX
        );

        $data_row_seeder_class = $this->file_system->generateSeederClassName(
            $data_type->slug,
            self::ROW_SEEDER_SUFFIX
        );

        $this->file_system->deleteSeedFiles($data_type_seeder_class);

        $this->file_system->deleteSeedFiles($data_row_seeder_class);
    }

    /**
     * Generate Seed File For Deleted Data.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function generateSeedFileForDeletedData(DataType $data_type): bool
    {
        $seeder_class_name = $this->file_system->generateSeederClassName(
            $data_type->slug,
            self::DELETED_SEEDER_SUFFIX
        );

        $stub = $this->file_system->getFileContent(
            $this->file_system->getStubPath().'../stubs/delete_seed.stub'
        );

        $seed_folder_path = $this->file_system->getSeedCRUDFolderPath();

        $seeder_file = $this->file_system->getSeederFile($seeder_class_name, $seed_folder_path);

        $stub = $this->content_manager->replaceString('{{class}}', $seeder_class_name, $stub);

        $seed_content = $this->content_manager->populateContentToStubFile(
            $stub,
            $data_type,
            self::DELETED_SEEDER_SUFFIX
        );

        $this->file_system->addContentToSeederFile($seeder_file, $seed_content);

        return $this->updateOrchestraSeeder($seeder_class_name);
    }

    /**
     * Replace with $data_type Variable.
     *
     * @return mixed|string
     */
    public function addDataTypeId(string $seed_content)
    {
        if (strpos($seed_content, '#dataTypeId') !== 'false') {
            $seed_content = str_replace('\'#dataTypeId\'', '$data_type->id', $seed_content);
        }

        return $seed_content;
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function generateSeedFile(string $collection_name, string $suffix): bool
    {
        if (! Schema::connection('mongodb')->hasCollection($collection_name)) {
            throw new Exception(sprintf('%s table does\'nt exist.'));
        }

        $data = $this->repackSeedData($this->database_manager->table($collection_name)->get());

        $seeder_class_name = $this->file_system->generateSeederClassName(Str::studly($collection_name), $suffix);

        $stub = $this->file_system->getFileContent(
            $this->file_system->getStubPath().'../stubs/seed.stub'
        );

        $seed_folder_path = $this->file_system->getSeedManualGenerateFolderPath();

        $seeder_file = $this->file_system->getSeederFile($seeder_class_name, $seed_folder_path);

        $stub = $this->content_manager->replaceString('{{class}}', $seeder_class_name, $stub);
        $stub = $this->content_manager->replaceString('{{collection}}', $collection_name, $stub);

        $seed_content = $this->content_manager->populateCollectionContentToSeeder($stub, $collection_name, $data);

        // generate file BadasoManualGenerateSeeder.php
        $content_generator = new ContentGenerator();
        $class_badaso_manual_generate_seeder = 'BadasoMongodbManualGenerateSeeder';
        $path_folder_manual_generate_seeder = $this->file_system->getSeederFile($class_badaso_manual_generate_seeder, $seed_folder_path);
        $file_content_manual_generate_seeder = $this->file_system->getFileContent($path_folder_manual_generate_seeder);
        $file_content_manual_generate_seeder = $content_generator->generateManualSeederContent($seeder_class_name, $file_content_manual_generate_seeder);
        $this->file_system->addContentToSeederFile($path_folder_manual_generate_seeder, $file_content_manual_generate_seeder, false);
        // end

        return $this->file_system->addContentToSeederFile($seeder_file, $seed_content);
    }

    /**
     * Repacks data read from the database.
     *
     * @param array|object $data
     */
    public function repackSeedData($data): array
    {
        if (! is_array($data)) {
            $data = $data->toArray();
        }

        $data_array = [];
        if (! empty($data)) {
            foreach ($data as $row) {
                $row_array = [];
                foreach ($row as $column_name => $column_value) {
                    $row_array[$column_name] = $column_value;
                }
                $data_array[] = $row_array;
            }
        }

        return $data_array;
    }
}