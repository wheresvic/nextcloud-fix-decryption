<?php

class Util
{
    /**
     * @return string
     */
    public static function promptPassword()
    {
        print "Password: ";
        return trim(fgets(STDIN));
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
