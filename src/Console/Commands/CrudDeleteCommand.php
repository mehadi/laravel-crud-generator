<?php

namespace Mehadi\CRUDGenerator\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class CrudDeleteCommand extends Command
{
    protected $signature = 'crud:delete {model}';
    protected $description = 'Executing this command will systematically undo the CRUD operations and delete the related files, adhering to best practices for maintaining a clean and efficient codebase.';

    public function handle()
    {
        $modelName = $this->argument('model');
        $modelNameLower = strtolower($modelName);
        $controllerFileName = ucfirst($modelName) . 'Controller.php';
        $controllerFilePath = app_path("Http/Controllers/$controllerFileName");

        // Specify the model file path
        $modelFilePath = app_path("Models/$modelName.php");

        //delete migration
        $migrationFilesDir = database_path("migrations");

        // Get the list of all files in the directory
        $files = scandir($migrationFilesDir);

        // Find the first file that contains "_table"
        $migrationFilePath = null;
        foreach ($files as $file) {
            if (strpos($file, "_create_" . strtolower($modelName) . "s_table") !== false) {
                $migrationFilePath = $file;
                break; // Stop searching after the first match
            }
        }

        // Output the matching file
        if ($migrationFilePath !== null) {
            $migrationFilePath = database_path("migrations/$migrationFilePath");
        } else {
            $migrationFilePath = null;
        }
        $viewsDir = resource_path("views/{$modelNameLower}s");
        $deleteFiles = [
            $modelFilePath, $controllerFilePath, $migrationFilePath, $viewsDir
        ];

        $this->deleteFilesAndDir($deleteFiles);
        $this->removeRoutes($modelName);
        $this->removeNavigationLinks($modelName);
    }

    private function removeRoutes($modelName)
    {
        $modelNameLower = strtolower($modelName);
        $routeName = $modelNameLower . 's';
        $generatedRoute = "Route::resource('/$routeName', \\App\\Http\\Controllers\\" . $modelName . "Controller::class);";
        echo $generatedRoute;
        //add new route to web.php
        $web_file = base_path("routes/web.php");
        if (file_exists($web_file)) {
            $web_content = file_get_contents($web_file);
            if (strpos($web_content, $generatedRoute) !== false) {
                //Route exists deleting
                echo "\nRoute exists! Deleting..!";
                $updatedContent = str_replace($generatedRoute, '', $web_content);
                file_put_contents($web_file, $updatedContent);
            }else{
                echo "\nRoute does not exist!";
            }
        } else {
            echo "\nRoute file not found!";
        }
    }

    private function removeNavigationLinks($modelName)
    {
        $modelNameLower = strtolower($modelName);
        $routeName = $modelNameLower . 's';

        $navigationTemplate = __DIR__ . "/templates/navigationitem.txt";
        $file = resource_path("views/layouts/navigation.blade.php");
        $stringToReplace = "";
        if (file_exists($navigationTemplate)) {
            $templateContent = file_get_contents($navigationTemplate);
            // Replace placeholders with actual values
            $replacements = [
                '{{modelName}}' => $modelName,
                '{{routeName}}' => $routeName,
            ];
            $stringToReplace = str_replace(array_keys($replacements), array_values($replacements), $templateContent);
        }
        if (file_exists($file)) {
            $fileContent = file_get_contents($file);
            if (str_contains($fileContent, $modelName . 's')) {
                //Link is not on menu
                echo "\nLink is on Navigation menu! Deleting..!";
                $updatedContent = str_replace($stringToReplace, '', $fileContent);
                file_put_contents($file, $updatedContent);
            } else {
                echo "\nLink is not on Navigation menu.";
            }
        } else {
            echo "\nFile not found.";
        }
    }

    private function deleteFilesAndDir($data)
    {
        foreach ($data as $fileOrDir) {
            if (file_exists($fileOrDir)) {
                if (is_dir($fileOrDir)) {
                    // Delete a directory and its contents
                    $this->deleteDirectory($fileOrDir);
                    echo "Directory '{$fileOrDir}' and its contents deleted.\n";
                } else {
                    unlink($fileOrDir); // Delete a file
                    echo "File '{$fileOrDir}' deleted.\n";
                }
            } else {
                echo "File or directory '{$fileOrDir}' does not exist.\n";
            }
        }
    }

    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $filePath = $dir . '/' . $file;
                if (is_dir($filePath)) {
                    $this->deleteDirectory($filePath);
                } else {
                    unlink($filePath);
                }
            }
        }
        rmdir($dir);
    }


}
