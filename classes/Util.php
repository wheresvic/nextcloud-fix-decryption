<?php

class Util
{

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
    public static function grepFiles($path, $logger)
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
