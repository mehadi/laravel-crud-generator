<?php

namespace Mehadi\CRUDGenerator\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class CrudMakeCommand extends Command
{
    protected $signature = 'crud:make {model} {--data=}';
    protected $description = 'Example command for generating CRUD';

    public function handle()
    {
        $model = $this->argument('model');
        $data = $this->option('data');

        $this->line("Creating CRUD for $model .... ....");

        if ($data) {
            $fieldPairs = explode(',', $data);

            $fieldsArray = [];
            foreach ($fieldPairs as $fieldPair) {
                try {
                    list($name, $type, $input) = explode(':', $fieldPair);
                    $fieldsArray[] = [
                        'name' => $name,
                        'type' => $type,
                        'inputType' => $input,
                    ];
                } catch (\ErrorException $e) {
                    // Handle the error
                    $errorMessage = $e->getMessage();
                    echo "Error: $errorMessage \n";
                    $this->line("Please check the parameters you have passed during creating the MODEL!");
                }
            }
            //Print the generated fields array
            //$this->line(print_r($fieldsArray));
        } else {
            $this->line("Making $model without the fields...");
            sleep(.5);
        }
        $this->createController($model, $fieldsArray);
        $this->createModel($model, $fieldsArray);
        $this->createMigration($model, $fieldsArray);
        $this->generateViews($model, $fieldsArray);
        $this->generateNavigationLinks($model);

        $this->line("\n" . $model . ' CRUD generation completed.');
    }

    private function generateNavigationLinks($modelName)
    {

        $modelNameLower = strtolower($modelName);
        $routeName = $modelNameLower . 's';

        //add new route to web.php
        $web_file = base_path("routes/web.php");
        if (file_exists($web_file)) {
            $web_content = file_get_contents($web_file);
            $searchPattern = "Route::resource('/$routeName'";

            if (strpos($web_content, $searchPattern) !== false) {
                echo "\nThe pattern '{$searchPattern}' exists in the web.php file.";
            } else {
                echo "\nThe pattern '{$searchPattern}' does not exist in the web.php file.";
                $updatedContent = $web_content . "Route::resource('/$routeName', \\App\\Http\\Controllers\\" . $modelName . "Controller::class);\n";
                file_put_contents($web_file, $updatedContent);
            }
        } else {
            echo "\nRoute file not found!";
        }

        // add new item on navigation menu
        $navigationTemplate = __DIR__ . "/templates/navigationitem.txt";
        $file = resource_path("views/layouts/navigation.blade.php");
        $additionalText = "";
        if (file_exists($navigationTemplate)) {
            $templateContent = file_get_contents($navigationTemplate);
            // Replace placeholders with actual values
            $replacements = [
                '{{modelName}}' => $modelName,
                '{{routeName}}' => $routeName,
            ];
            $additionalText = str_replace(array_keys($replacements), array_values($replacements), $templateContent);
        }
        //$additionalText = "__";
        if (file_exists($file)) {
            $fileContent = file_get_contents($file);

            if (!str_contains($fileContent, $modelName . 's')) {
                $lastXNavLinkPosition = strrpos($fileContent, '</x-nav-link>');
                if ($lastXNavLinkPosition !== false) {
                    $updatedContent = substr_replace($fileContent, $additionalText, $lastXNavLinkPosition + 13, 0);
                    file_put_contents($file, $updatedContent);
                    echo "\nMenu added to navigation!.";
                } else {
                    echo "\nNo </x-nav-link> found in the file.";
                }
            } else {
                echo "\nLink is already on Navigation menu.";
            }
        } else {
            echo "\nFile not found.";
        }
    }


    private function generateViews($modelName, $fieldsArray)
    {
        $modelNameLower = strtolower($modelName);
        $views = ['index', 'create', 'edit'];
        // Define the paths
        $targetDirectory = resource_path("views/{$modelNameLower}s");

        // Create the target directory if it doesn't exist
        if (!file_exists($targetDirectory)) {
            mkdir($targetDirectory, 0777, true);
        }

        foreach ($views as $item) {
            $templateFilePath = __DIR__ . "/templates/$item.txt";
            $targetViewPath = resource_path("views/{$modelNameLower}s/$item.blade.php");

            // Read the template file
            if (file_exists($templateFilePath)) {

                /*Generate the fields start*/
                $generatedFields = "";

                if ($item == "create" || $item == "edit") {

                    foreach ($fieldsArray as $fieldsArrayItem) {
                        //print_r($fieldsArrayItem);
                        $inputType = $fieldsArrayItem['inputType'];
                        $inputTypeFilePath = __DIR__ . "/templates/inputTypes/$inputType.txt";
                        if ($item == "edit") {
                            $valueVariable = "{{" . "$" . $modelNameLower . "['" . $fieldsArrayItem['name'] . "']" . "}}";
                        } else {
                            $valueVariable = "";
                        }
                        $inputTypeFileContent = file_get_contents($inputTypeFilePath);
                        $inputTypeReplacements = [
                            '{{labelFor}}' => $fieldsArrayItem['name'],
                            '{{labelForName}}' => ucfirst($fieldsArrayItem['name']),
                            '{{inputType}}' => $fieldsArrayItem['inputType'],
                            '{{inputTypeName}}' => $fieldsArrayItem['name'],
                            '{{inputTypeID}}' => $fieldsArrayItem['name'],
                            '{{modelNameLower}}' => $modelNameLower,
                            '{{valueVariable}}' => $valueVariable
                        ];
                        $updatedInputTypeContent = str_replace(array_keys($inputTypeReplacements), array_values($inputTypeReplacements), $inputTypeFileContent);

                        $generatedFields = $generatedFields . "\n" . $updatedInputTypeContent;

                    }
                }
                /*Generate the fields end*/

                //print_r($generatedFields);

                $templateContent = file_get_contents($templateFilePath);
                // Replace placeholders with actual values
                $replacements = [
                    '{{modelName}}' => $modelName,
                    '{{modelNameLower}}' => $modelNameLower,
                    '{{formFields}}' => $generatedFields,
                ];

                $updatedContent = str_replace(array_keys($replacements), array_values($replacements), $templateContent);

                // Write the updated content to the view file
                if (!file_exists($targetViewPath)) {
                    file_put_contents($targetViewPath, $updatedContent);
                    echo "\n$item generated successfully.";
                } else {
                    echo "\n$item file already exists.";
                }
            } else {
                echo "\n$item file not found.";
            }
        }
    }

    protected function createController($modelName, $fields): void
    {

        $output = "[\n";
        foreach ($fields as $field) {
            $output .= "\t\t\t[\n";
            $output .= "\t\t\t\t'name' => '{$field['name']}',\n";
            $output .= "\t\t\t\t'type' => '{$field['type']}',\n";
            $output .= "\t\t\t\t'inputType' => '{$field['inputType']}'\n";
            $output .= "\t\t\t],\n";
        }
        $output .= "\t\t];\n";



        $modelNameLower = strtolower($modelName);
        // Load the controller template from the file
        $templateFilePath = __DIR__ . '/templates/controller.txt';

        $controllerTemplate = file_get_contents($templateFilePath);

        $replacements = [
            '{{modelName}}' => $modelName,
            '{{modelNameLower}}' => $modelNameLower,
            '{{formFields}}' => $output
        ];
        $controllerCode = str_replace(array_keys($replacements), array_values($replacements), $controllerTemplate);

        // Specify the controller file path
        $controllerFileName = ucfirst($modelName) . 'Controller.php';
        $controllerFilePath = app_path("Http/Controllers/$controllerFileName");

        // Create the controller file
        if (!file_exists($controllerFilePath)) {
            file_put_contents($controllerFilePath, $controllerCode);
            echo "$controllerFileName controller created successfully";
        } else {
            echo "$controllerFileName controller already exists";
        }
    }


    protected function createModel($modelName, $fields)
    {
        $attributes = array_column($fields, 'name'); // Define the model attributes

        // Load the model template from the file
        $templateFilePath = __DIR__ . '/templates/model.txt';
        $modelTemplate = file_get_contents($templateFilePath);

        // Replace placeholders with actual values
        $attributeList = "'" . implode("', '", $attributes) . "'";
        $modelCode = str_replace('{{attributes}}', $attributeList, $modelTemplate);
        $modelCode = str_replace('{{modelName}}', $modelName, $modelCode);

        // Specify the model file path
        $modelFilePath = app_path("Models/$modelName.php");

        // Create the model file
        if (!file_exists($modelFilePath)) {
            file_put_contents($modelFilePath, $modelCode);
            echo "\n$modelName model created successfully";

            //we can create migration here
            //$this->createMigration($data);
        } else {
            echo "\n$modelName model already exists";
        }
    }


    protected function createMigration($modelName, $fields)
    {

        // Generate the current timestamp for the migration file name
        $timestamp = now()->format('Y_m_d_His');
        $migrationFileName = $timestamp . "_create_" . strtolower($modelName) . "s_table";


        $attributes = array_column($fields, 'name'); // Extract attribute names

        // Load the migration template from the file
        $templateFilePath = __DIR__ . '/templates/migration.txt';
        $migrationTemplate = file_get_contents($templateFilePath);

        // Replace placeholders with actual values
        $attributeFields = '';
        foreach ($attributes as $key => $attribute) {
            echo $fields[$key]['type'];
            $attributeFields .= "\$table->{$fields[$key]['type']}('$attribute');\n\t\t\t";
        }
        $migrationCode = str_replace('{{attributeFields}}', $attributeFields, $migrationTemplate);
        $migrationCode = str_replace('{{migrationFileName}}', $migrationFileName, $migrationCode);
        $migrationCode = str_replace('{{tableName}}', strtolower($modelName) . 's', $migrationCode);

        // Specify the migration file path
        $migrationFilePath = database_path("migrations/$migrationFileName.php");
        $migrationFilesDir = database_path("migrations");

        // Get the list of all files in the directory
        $files = scandir($migrationFilesDir);

        // Find the first file that contains "_table"
        $matchingFile = null;
        foreach ($files as $file) {
            if (strpos($file, "_create_" . strtolower($modelName) . "s_table") !== false) {
                $matchingFile = $file;
                break; // Stop searching after the first match
            }
        }

        // Output the matching file
        if ($matchingFile !== null) {
            echo "\n$migrationFileName migration already exists";
        } else {
            file_put_contents($migrationFilePath, $migrationCode);
            echo "\n$migrationFileName migration created successfully ";
        }
    }
}
