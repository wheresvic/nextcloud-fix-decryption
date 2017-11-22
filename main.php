<?php

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    throw new Exception("Windows is not supported by the programm.");
}


spl_autoload_register(function ($class_name) {
    include 'classes/' . $class_name . '.php';
});

$user = Util::getUser($argv);

if (empty($user)) {
    throw new Exception("No argument for user found.");
}

define("_USER_", $user);

$config = new Config();

define("DEBUG", $config->getValue("debug"));
define("TEST", $config->getValue("test"));
define("_instanceId_", $config->getValue("instance_id"));
define("_instanceSecret_", $config->getValue("instance_secret"));

define("_DATADIR_", $config->getValue("data_dir"));

$baseDir = _DATADIR_ . _USER_ . "/";

$privateKeyCatFile = file_get_contents($baseDir . "files_encryption/OC_DEFAULT_MODULE/" . _USER_ . ".privateKey");

$password = Util::promptPassword();

$colors = new Colors();
$logger = new ILogger($colors);
$logCli = new CliLogger($colors);
$crypt = new Crypt(
    $logger,
    _USER_,
    _instanceId_,
    _instanceSecret_
);

$dbConnDataCur = $config->getDatabaseConfig((DEBUG) ? "test" : "current");

$database = new Database(
    $dbConnDataCur["host"],
    $dbConnDataCur["username"],
    $dbConnDataCur["password"],
    $dbConnDataCur["db"],
    $dbConnDataCur["port"]
);

$storageID = $database->getStorageId(_USER_);

$privateKey = $crypt->decryptPrivateKey($privateKeyCatFile, $password, _USER_);

if (!$privateKey) {
    $logger->error("Wrong password or corrupted private key");

    die(1);
}

$encryption = new Encryption(
    $logger,
    $crypt
);

if ($customFile = Util::getFile($argv, $logCli)) {
    $files = array($customFile);
} else {
    $files = Util::grepFiles($baseDir, $logCli);
}

foreach ($files as $file) {
    $ocPath = str_replace($baseDir, "", $file);
    $keyFileDir = $baseDir . "files_encryption/keys/" . $ocPath . "/OC_DEFAULT_MODULE/";

    $shareKeyCatFile = file_get_contents($keyFileDir . _USER_ . ".shareKey");
    $fileKeyCatFile = file_get_contents($keyFileDir . "fileKey");

    $fileKey = $crypt->multiKeyDecrypt($fileKeyCatFile, $shareKeyCatFile, $privateKey);

    $newData = $encryption->fixUnencryptedSize($file, filesize($file), $fileKey);

    if ($newData === false) {
        $logger->error("No solutions found for this file");
    } else {
        $logger->success("Fixed: '$ocPath' - New size: $newData[0], new encryption: $newData[1]");

        if (!DEBUG) {
            $database->updateFileByPath($ocPath, $storageID, $newData[0], $newData[1]);
        }
    }

    if (TEST) {
        break;
    }
}
