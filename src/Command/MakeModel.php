<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/13
 * Time: 16:25
 */

namespace Fisherman\Command;

use Fisherman\Core\Config;
use Fisherman\DB\DBFactory;
use ICanBoogie\Inflector;
use Nadar\PhpComposerReader\Autoload;
use Nadar\PhpComposerReader\AutoloadSection;
use Nadar\PhpComposerReader\ComposerReader;
use PhpMyAdmin\SqlParser\Parser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class MakeModel extends Command {
    protected static $defaultName = "make:model";
    private $databaseName = "";
    private $workPath = "";

    private $typeMapping = array(
        'VARCHAR' => 'string',
        'TEXT' => 'string',
        'CHAR' => 'string',
        'INT' => 'int',
        'FLOAT' => 'float',
        'DOUBLE' => 'float',
        'DECIMAL' => 'float',
        'MEDIUMINT' => 'int',
        'TINYINT' => 'int'
    );
    private $type = array(
        "base",
        "curd"
    );

    function __construct($workPath) {
        parent::__construct(null);
        $this->workPath = $workPath;
    }

    protected function configure() {
        $this->setDescription("Create model from database.")->setHelp("Create a database access object model.");
        $this->setDefinition(
            new InputDefinition([
                new InputOption("spacename", "s", InputOption::VALUE_REQUIRED, "It's suffix of namespace.Well be making ProjectName\\Model\\SpaceName namespace.And create \"Model/SpaceName\" folder in project.Default:database name."),
                new InputOption('module', 'm', InputOption::VALUE_REQUIRED, "Create a module file which include the model.")
            ])
        );

        $this
            ->addArgument("DatabaseTag", InputArgument::REQUIRED, "Database tag in db.yml.")
            ->addArgument("TableName", InputArgument::OPTIONAL, "Table name.Consistent with database table which you want to make table.Generates a file name with the first letter uppercase.")
//            ->addArgument("SpaceName", InputArgument::OPTIONAL, "It's suffix of namespace.Well be making ProjectName\\Model\\SpaceName namespace.And create \"Model/SpaceName\" folder in project.")
//            ->addOption("module", "M", InputOption::VALUE_NONE, "Make same module.")
            ->addOption("type", "t", InputOption::VALUE_OPTIONAL, "Mode for model object.[base|curd]", "curd");


    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $dbTag = $input->getArgument("DatabaseTag");
        $tableName = $input->getArgument("TableName");
        $spaceName = $input->getOption("spacename");
        $moduleName = $input->getOption("module");
        $type = $input->getOption("type");
        $inflector = Inflector::get('en');


        //********************check****************************
        if (!in_array($type, $this->type, true)) {
            $typeStr = implode(" or ", $this->type);
            $output->writeln("Mode was error.It's must be <fg=red>{$typeStr}</>.");
            return;
        }

        $config = Config::getFile("config");

        if (!isset($config['project']['name']) || empty($config['project']['name'])) {
            $output->writeln("The project name has not found.");
            $output->writeln('<fg=red>You must add "project name" config into config.yml</>');
            return;
        }

        $dbConfig = Config::getFile("db");
        if (!isset($dbConfig[$dbTag]) || empty($dbConfig[$dbTag]) || empty($dbConfig[$dbTag]["writer"]["database"])) {
            $output->writeln("Can not found the db tag:<fg=red>{$dbTag}</> or not found <fg=red>database</> property.");
            return;
        }

        if (empty($spaceName)) {
            $spaceName = $inflector->camelize($dbConfig[$dbTag]["writer"]["database"], Inflector::UPCASE_FIRST_LETTER);
        }


        //*******************end check*****************************


        //****************Analysis database********************
        $fields = array();
        $pkFields = array();
        $uniqueFields = array();
        $requiredFields = array();
        $noRequiredFields = array();

        $conn = (new DBFactory($dbTag))->getDBAdapter();
        if ($conn === null) {
            $output->writeln("<fg=red>Can not connection database.</>");
            return;
        }
        $conn->getConn(false);
        $res = $conn->query("show create table {$tableName}")->fetch(\PDO::FETCH_ASSOC);
        $parse = new Parser($res["Create Table"]);
        $definitionFields = $parse->statements[0]->fields;

        foreach ($definitionFields as $df) {
            if ($df->key == null && $df->name != null) {
                $fields[$df->name]["type"] = $df->type->name;
                $fields[$df->name]["pk"] = isset($fields[$df->name]["pk"]) ? $fields[$df->name]["pk"] : false;
                $fields[$df->name]["camel_down"] = $inflector->camelize($df->name, Inflector::DOWNCASE_FIRST_LETTER);
                $fields[$df->name]["camel_up"] = $inflector->camelize($df->name, Inflector::UPCASE_FIRST_LETTER);
                //存入转义字符
                $fields[$df->name]["propType"] = $this->typeMapping[$df->type->name];

                //notnull & auto_increment
                $fields[$df->name]["notNull"] = false;
                $fields[$df->name]["autoIncrement"] = false;
                foreach ($df->options->options as $option) {
                    if ($option === "NOT NULL") {
                        $fields[$df->name]["notNull"] = true;
                    }
                    if ($option === "AUTO_INCREMENT") {
                        $fields[$df->name]["autoIncrement"] = true;
                    }
                }
                //UNSIGNED
                $fields[$df->name]["unsigned"] = false;
                foreach ($df->type->options->options as $option) {
                    if ($option === "UNSIGNED") {
                        $fields[$df->name]["unsigned"] = true;
                    }
                }

                //requiredFields
                if (!$fields[$df->name]["autoIncrement"]) {
                    if ($fields[$df->name]["notNull"]) {
                        $requiredFields[] = $df->name;
                    } else {
                        $noRequiredFields[] = $df->name;
                    }
                }
            }

            if ($df->key != null && $df->name == null) {
                //提取主键
                if ($df->key->type === "PRIMARY KEY") {
                    foreach ($df->key->columns as $name) {
                        $fields[$name["name"]]["pk"] = true;
                        $pkFields[] = $name["name"];
                    }
                }
                //提取唯一索引
                if ($df->key->type === "UNIQUE KEY") {
                    foreach ($df->key->columns as $name) {
                        $uniqueFields[] = $name["name"];
                    }
                }
            }
        }
        //****************end analysis database**********************

        //load requirement
        $renderArr = array();
        $renderArr["dateTime"] = date("Y-m-d H:i:s");
        $renderArr["typeMapping"] = $this->typeMapping;
        $renderArr["databaseName"] = $this->databaseName;
        $renderArr["tableName"] = $tableName;
        $renderArr["className"] = $inflector->camelize($tableName, Inflector::UPCASE_FIRST_LETTER);
        $renderArr["modelNameVar"] = $inflector->camelize($tableName, Inflector::DOWNCASE_FIRST_LETTER);
        $renderArr["spaceName"] = $spaceName;
        $renderArr["dbTag"] = $dbTag;
        $renderArr["fields"] = $fields;
        $renderArr["pkFields"] = $pkFields;
        $renderArr["uniqueFields"] = $uniqueFields;
        $renderArr["requiredFields"] = $requiredFields;
        $renderArr["noRequiredFields"] = $noRequiredFields;
        $renderArr["projectName"] = $config['project']['name'];

        $output->writeln("Build model file.");
        $loader = new FilesystemLoader(FINISHERMAN_PATH . "/resource/template");
        $twig = new Environment($loader);

        /*****************model template*************/
        $modelTemplate = $twig->load("/model/{$type}.twig");
        $modelString = $modelTemplate->render($renderArr);
        $modelPath = $this->workPath . "/Model/{$spaceName}";
        $modelFilePath = $modelPath . "/{$renderArr["className"]}.php";

        if (!is_dir($modelPath)) {
            $output->writeln("<fg=green>Create path for model: {$modelPath}</>");
            if (!mkdir($modelPath, 0755, true) && !is_dir($modelPath)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $modelPath));
            }
        }

        if (file_exists($modelFilePath)) {
            $output->writeln("Model file exists witch path is <fg=red>{$modelFilePath}</>.");
            return;
        }

        /****************module template********************/
        if (!empty($moduleName)) {
            $renderArr["moduleName"] = $moduleName;
            $moduleTemplate = $twig->load("/module.twig");

            $moduleString = $moduleTemplate->render($renderArr);
            $modulePath = $this->workPath . "/Module/{$spaceName}";
            $moduleFilePath = $modulePath . "/{$moduleName}.php";
            if (!is_dir($modulePath)) {
                $output->writeln("<fg=green>Create path for module: {$modulePath}</>");
                if (!mkdir($modulePath, 0755, true) && !is_dir($modulePath)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $modulePath));
                }
            }
            if (file_exists($moduleFilePath)) {
                $output->writeln("Module file exists witch path is <fg=red>{$modelFilePath}</>.");
                return;
            }
        }

        /******************check composer writeable***********/

        $composerReader = new ComposerReader($this->workPath . "/composer.json");
        if (!$composerReader->canRead()) {
            $output->writeln("<fg=red>Unable to read json.</>");
            return;
        }

        if (!$composerReader->canWrite()) {
            $output->writeln("<fg=red>Unable to write to existing json.</>");
            return;
        }

        /****************output file*************************/
        $modelFile = fopen($modelFilePath, "w");
        if ($modelFile == false) {
            $output->writeln("Can not create model file: <fg=red>{$modelFilePath}</>");
            return;
        }

        if (!empty($moduleName)) {
            $moduleFile = fopen($moduleFilePath, 'w');
            if ($moduleFile == false) {
                $output->writeln("Can not create model file: <fg=red>{$modelFilePath}</>");
                return;
            }
        }


        fwrite($modelFile, $modelString);
        fclose($modelFile);

        if (!empty($moduleName)) {
            fwrite($moduleFile, $moduleString);
            fclose($moduleFile);
        }


        /******************build composer.json**************/
        $output->writeln("Build composer.json.");


        $autoloadModel = new Autoload($composerReader, "{$config['project']['name']}\\Model\\{$spaceName}\\", "Model/{$spaceName}", AutoloadSection::TYPE_PSR4);
        $section = new AutoloadSection($composerReader);
        $section->add($autoloadModel);

        if (!empty($moduleName)) {
            $autoloadModule = new Autoload($composerReader, "{$config['project']['name']}\\Module\\{$spaceName}\\", "Module/{$spaceName}", AutoloadSection::TYPE_PSR4);
            $section->add($autoloadModule);
        }

        $composerReader->save();


        //composer autoload.
        $executeOutput = null;
        $command = <<<'CMD'
                if  command -v composer > /dev/null; then
                    composer dump-autoload -o
                else
                    echo "failed";
                fi
CMD;
        if (function_exists("exec")) {
            exec($command, $executeOutput);
            if (!empty($executeOutput) && $executeOutput[0] === 'failed') {
                $output->writeln("Composer command has not found.");
                $output->writeln('You must execute "composer dump-autoload -o" for autoload project.');
            }
        }

        $output->writeln("Building completed.");
        /******************end build composer.json***********/

        $output->writeln("Finished");
    }
}