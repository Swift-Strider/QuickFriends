<?php

declare(strict_types=1);

function download($file, $url): void
{
    file_put_contents($file, file_get_contents($url));
}

function cp_dir($src, $dst)
{
    $dir = opendir($src);
    @mkdir($dst);
    while (($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                cp_dir($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

function run(string $command): int
{
    echo "\t>> $command\n";
    exec($command, $output, $code);
    return $code;
}

$exitCode = 0;

$testsLocation = "tests" . DIRECTORY_SEPARATOR . "integration" . DIRECTORY_SEPARATOR;
$sources = ["src", "plugin.yml", "resources"];
$sourcesString = implode(",", $sources);
$pocketmineVersion = "4.0.0";
$php = "php -dphar.readonly=0";
$consoleScript = "build/ConsoleScript.php";
$virions = "build/virions";

@mkdir("build");
@mkdir("build/virions");
download($consoleScript, "https://github.com/pmmp/DevTools/raw/master/src/ConsoleScript.php");
download("$virions/libasynql.phar", "https://poggit.pmmp.io/r/177279/libasynql_dev-177.phar");
download("$virions/await-generator.phar", "https://poggit.pmmp.io/r/167785/await-generator_dev-83.phar");

foreach (new DirectoryIterator($testsLocation) as $file) {
    if ($file->isDot() || !$file->isDir()) continue;

    $name = $file->getFilename();
    $isMySQL = str_ends_with($name, "-mysql");
    $isSqlite = str_ends_with($name, "-sqlite");
    $data = $file->getPathname() . "/data";

    if ($isMySQL) {
        $shared = $file->getPathname() . "/../" . substr($name, 0, -6);
    } else if ($isSqlite) {
        $shared = $file->getPathname() . "/../" . substr($name, 0, -7);
    } else {
        continue;
    }

    echo "\n! Building plugin directory...\n";

    @rmdir("build/generated");
    @mkdir("build/generated", 0777, true);
    foreach ($sources as $source) {
        if (is_dir($source)) {
            cp_dir($source, "build/generated/$source");
        } else {
            @copy($source, "build/generated/$source");
        }
    }
    if (file_exists($shared . "/src")) {
        cp_dir($shared . "/src", "build/generated/src");
    }
    if (file_exists($file->getPathname() . "/src")) {
        cp_dir($file->getPathname() . "/src", "build/generated/src");
    }

    echo "! Done\n";

    echo "! Building the plugin phar file...\n";

    @unlink("build/QuickFriends.phar");
    run("$php $consoleScript --make $sourcesString --relative build/generated --out build/QuickFriends.phar");
    run("$php $virions/libasynql.phar build/QuickFriends.phar");
    run("$php $virions/await-generator.phar build/QuickFriends.phar");

    echo "! Done\n";

    echo "! Running Docker Commands\n";

    run("docker network create $name-network");
    if ($isMySQL) {
        run("docker kill $name-database");
        run("docker rm $name-database");
        run("docker run --rm -d --name $name-database --network $name-network -e MYSQL_RANDOM_ROOT_PASSWORD=1 -e MYSQL_USER=username -e MYSQL_PASSWORD=password -e MYSQL_DATABASE=quickfriends mysql:8.0");
        run("docker cp tools/integration-test/mysql-wait-script $name-database:/wait");
    }
    run("docker kill $name-pocketmine");
    run("docker rm $name-pocketmine");
    run("docker create --rm --name $name-pocketmine --network $name-network -u root pmmp/pocketmine-mp:$pocketmineVersion start-pocketmine --debug.level=2");
    run("docker cp build/QuickFriends.phar $name-pocketmine:/plugins");
    run("docker cp $data $name-pocketmine:/");

    if ($isMySQL) {
        echo "! Waiting for MySQL before starting pocketmine...\n";
        while (true) {
            $output = system("docker exec $name-database bash /wait");
            if ($output === "mysqld is alive") break;
            sleep(5);
        }
        echo "! MySQL has started!\n";
    }

    $cmd = "docker start -a $name-pocketmine";
    $proc_fds = [
        ["pipe", "r"],
        ["pipe", "w"],
        ["pipe", "w"]
    ];
    $process = proc_open($cmd, $proc_fds, $pipes, realpath("."));
    $testPassed = false;
    if (is_resource($process)) {
        $killTime = null;
        while ($line = fgets($pipes[1])) {
            echo $line;
            if (str_contains($line, "Test Successful!")) {
                $testPassed = true;
                $killTime = microtime(true);
            }
            if (
                str_contains($line, "Disabling QuickFriends") ||
                str_contains($line, "[Server thread/ERROR]: [QuickFriends]")
            ) {
                $killTime ??= microtime(true) + 3;
            }
            if (isset($killTime) && microtime(true) >= $killTime) {
                echo "! Killing process early!\n";
                run("docker kill $name-pocketmine");
                proc_close($process);
                break;
            }
        }
    }

    echo "! Cleaning up docker containers...\n";

    if ($isMySQL) {
        run("docker rm --force $name-database");
    }
    run("docker rm --force $name-pocketmine");

    echo "! Done\n";
    if ($testPassed) {
        echo "$ Test Succeeded!\n";
    } else {
        echo "$ Test Failed!\n";
        $exitCode = 1;
    }
}

exit($exitCode);
