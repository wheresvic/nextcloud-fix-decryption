<?php

// TODO: argument
define("_USER_", "user");


spl_autoload_register(function ($class_name) {
    require 'classes/' . $class_name . '.php';
});

$config = new Config();

define("DEBUG", $config->getValue("debug"));
define("TEST", $config->getValue("test"));

define("_DATADIR_", $config->getValue("data_dir"));


$logger = new CliLogger(
    new Colors()
);

if (!is_dir(_DATADIR_ . _USER_)) {
    throw new Exception("There is no directory for the user provided");
}

$files = Util::grepFiles(_DATADIR_ . _USER_, $logger);

// Init to log files

$fixedLogFile = "fixed-files.log";
$skippedLogFile = "skipped-files.log";

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
    $ocPath = str_replace(_DATADIR_ . _USER_ . "/", "", $file);

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
