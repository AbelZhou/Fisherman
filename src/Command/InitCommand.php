<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/13
 * Time: 11:25
 */

namespace Fisherman\Command;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class InitCommand extends Command {
    protected static $defaultName = "init";
    private $workPath = "";

    function __construct($workPath) {
        parent::__construct(null);
        $this->workPath = $workPath;
    }

    protected function configure() {
        $this->setDescription("Initialization your project.")
            ->setHelp("This command can help you to create project struct.");
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $paths = array(
            "/Conf/local",
            "/Conf/rls",
            "/Conf/test",
            "/Model/DBName",
            "/Module/DBName",
        );

        foreach ($paths as $path) {
            $path = $this->workPath . $path;
            if (!mkdir($path, 0755, true) && !is_dir($path)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
            }
        }
        $output->writeln("Create folder completed.");

        $files = array(
            "/Conf/local/cache.yml",
            "/Conf/local/config.yml",
            "/Conf/local/db.yml",
            "/Model/DBName/Tablename.php",
            "/Module/DBName/Servicename.php",
            "/composer.json",
            "/bootstrap.php"
        );

        foreach ($files as $file) {
            copy(FINISHERMAN_PATH . "/template" . $file, $this->workPath . $file);
            chmod($this->workPath . $file, 0755);
        }

        $output->writeln("Building completed.");
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
        $output->writeln("Finished");
    }


}