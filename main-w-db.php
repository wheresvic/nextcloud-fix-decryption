<?php

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    throw new Exception("Windows is not supported by the program.");
}


spl_autoload_register(function ($class_name) {
    require 'classes/' . $class_name . '.php';
});

$user = Util::getUser($argv);

if (empty($user)) {
    throw new Exception("No argument for user found.");
}

define("_USER_", $user);

$config = new Config();

define("DEBUG", $config->getValue("debug"));
define("TEST", $config->getValue("test"));

$baseDir = $config->getValue("data_dir") . _USER_ . "/";


$logger = new CliLogger(
    new Colors()
);

if (!is_dir($baseDir)) {
    throw new Exception("There is no directory for the user provided");
}

if ($customFile = Util::getFile($argv, $logger)) {
    $files = array($customFile);
} else {
    $files = Util::grepFiles($baseDir, $logger);
}

// Init to log files

$fixedLogFile = dirname(__DIR__) . "/fixed-files.log";
$skippedLogFile = dirname(__DIR__) . "/skipped-files.log";

$logger->logFileNewRun($fixedLogFile);
$logger->logFileNewRun($skippedLogFile);

// connect to databases

$dbConnDataCur = $config->getDatabaseConfig((DEBUG) ? "test" : "current");
$dbConnDataOld = $config->getDatabaseConfig("backup");

$oldDatabase = new Database(
    $dbConnDataOld["host"],
    $dbConnDataOld["username"],
    $dbConnDataOld["password"],
    $dbConnDataOld["db"],
    $dbConnDataOld["port"]
);

$curDatabase = new Database(
    $dbConnDataCur["host"],
    $dbConnDataCur["username"],
    $dbConnDataCur["password"],
    $dbConnDataCur["db"],
    $dbConnDataCur["port"]
);

// get home storage id for user

$storeIDCur = $curDatabase->getStorageId(_USER_);

if ($storeIDCur !== $oldDatabase->getStorageId(_USER_)) {
    throw new Exception("User storage ids aren't the same in both database. WTF!");
}

$storageID = $storeIDCur;

// for each file repair, if in old database the correct data exists and do verbose logging

foreach ($files as $file) {
    $ocPath = str_replace($baseDir, "", $file);

    try {
        $fileMetaOld = $oldDatabase->getFile($ocPath, $storageID);
    } catch (Exception $e) {
        $logger->logCli($e->getMessage(), "warning");
        continue;
    }

    try {
        $fileMetaCur = $curDatabase->getFile($ocPath, $storageID);
    } catch (Exception $e) {
        $logger->logCli($e->getMessage(), "warning");
        continue;
    }

    if ($fileMetaCur['size'] > $fileMetaOld['size'] && $fileMetaOld['encrypted'] > 0) {
        $percentage = round(($fileMetaCur['size'] / $fileMetaOld['size'] - 1) * 100, 2);

        $logger->logCli("File can be fixed ($ocPath)");
        $logger->logCli("File size is about " . $percentage . "% bigger - " . $fileMetaCur['size'] . " -> " . $fileMetaOld['size'] . " ($ocPath)");

        $success = $curDatabase->updateFileByID($fileMetaCur['fileid'], $fileMetaOld['size'], $fileMetaOld['encrypted']);

        if (!DEBUG) {
            if ($success) {
                $logger->logFile("Fixed applied: File size of \"$ocPath\" updated (size:" . $fileMetaCur['size'] . "->" . $fileMetaOld['size'] . " - encrypted:" . $fileMetaCur['encrypted'] . "->" . $fileMetaOld['encrypted'] . ")", $fixedLogFile);
            } else {
                $logger->logCli("Error executing \"UPDATE\" query ($ocPath)", "error");
            }
        }

        if (TEST) {
            break;
        }
    } elseif ($fileMetaCur['size'] == $fileMetaOld['size']) {
        $logger->logCli("File seems to be okay and can't be fixed ($ocPath)", "warning");

        $logger->logFile("No fix applied: \"$ocPath\" ", $skippedLogFile, "warning");
    } else {
        $logger->logCli("File can't be fixed ($ocPath)", "warning");

        $logger->logFile("No fix applied: \"$ocPath\" ", $skippedLogFile, "warning");
    }
}
