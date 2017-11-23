<?php

class Util
{

    /**
     * @param array $argv
     * @return string|boolean
     */
    public static function getUser(&$argv)
    {
        if (!empty($argv[1])) {
            return $argv[1];
        }

        return false;
    }

    /**
     * @param array $argv
     * @param \CliLogger $logger
     * @return string|boolean
     */
    public static function getFile(&$argv, &$logger)
    {
        if (isset($argv[2])) {
            if ($argv[2] === "-f" && isset($argv[3])) {
                $file = $argv[3];
            } elseif (strpos($argv[2], "--file=") === 0) {
                $file = substr($argv[2], 7);
            }
        }

        if (empty($file)) {
            return false;
        } elseif (!file_exists($file)) {
            $logger->logCli("File does not exist", "warning");
            return false;
        }

        return $file;
    }

    /**
     * @param string $prompt
     * @return string
     * @throws Exception
     */
    public static function promptPassword($prompt = "Password: ")
    {
        $command = "/usr/bin/env bash -c 'echo OK'";
        if (rtrim(shell_exec($command)) !== 'OK') {
            throw new Exception("Can't invoke bash");
        }

        print $prompt;

        $command = "/usr/bin/env bash -c 'read -s password && echo \$password'";
        $password = rtrim(exec($command));

        print "\n";

        return $password;
    }

    /**
     * @param string $path
     * @param \CliLogger $logger
     * @return array
     */
    public static function grepFiles($path, &$logger)
    {

        $logger->logCli("grep through files...");

        $grepOut = shell_exec("grep -lrnw '" . $path . "/files/' -e '^HBEGIN'");

        $logger->logCli("grep completed");

        $files = explode("\n", $grepOut);

        if (end($files) == "") {
            array_pop($files);
        }

        return $files;
    }
}
