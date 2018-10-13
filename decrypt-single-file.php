<?php

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    throw new Exception("Windows is not supported by the programm.");
}

spl_autoload_register(function ($class_name) {
    require 'classes/' . $class_name . '.php';
});

$colors = new Colors();
$logger = new ILogger($colors);
$logCli = new CliLogger($colors);
$config = new Config();

$user = $argv[1];
$masterUid = $argv[2];
$masterPrivateKeyFile = $argv[3];
$masterShareKeyFile = $argv[4];
$fileKeyFile = $argv[5];
$encryptedFile = $argv[6];
$outputFile = $argv[7];

// TODO:
$logCli->logCli("User: $user, MasterPrivateKeyFile: $masterPrivateKeyFile");

if (empty($user)) {
    throw new Exception("No argument for user found.");
}

if (!is_file($masterPrivateKeyFile)) {
    throw new Exception("There is no master private key file at the provided location");
}

if (!is_file($masterShareKeyFile)) {
    throw new Exception("Invalid master share key file");
}

if (!is_file($fileKeyFile)) {
    throw new Exception("Invalid file key file");
}

if (!is_file($encryptedFile)) {
    throw new Exception("Invalid/incorrect encrypted file");
}

$privateKeyCatFile = file_get_contents($masterPrivateKeyFile);
$password = Util::promptPassword();

$crypt = new Crypt(
    $logger,
    $user,
    $config->getValue("instance_id"),
    $config->getValue("instance_secret")
);

// TODO: fix to get the users private key somehow
$privateKey = $crypt->decryptPrivateKey($privateKeyCatFile, $config->getValue("instance_secret"), $masterUid);

if (!$privateKey) {
    $logger->error("Wrong password or corrupted private key");
    die(1);
}

$shareKeyCatFile = file_get_contents($masterShareKeyFile);
$fileKeyCatFile = file_get_contents($fileKeyFile);
$encryptedFileContents = file_get_contents($encryptedFile);

$fileKey = $crypt->multiKeyDecrypt($fileKeyCatFile, $shareKeyCatFile, $privateKey);

$encryption = new Encryption(
    $logger,
    $crypt
);

$result = $encryption->decrypt($encryptedFileContents, $fileKey);

// $result = $encryption->fixUnencryptedSize($encryptedFile, filesize($encryptedFile), $fileKey);

file_put_contents($outputFile, $result);

// print_r($result);

?>
