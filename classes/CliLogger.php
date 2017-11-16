<?php

class CliLogger
{

    protected $colors;

    public function __construct(Colors $colors)
    {
        $this->colors = $colors;
    }

    /**
     * @param string $message
     * @param string $type
     * @return array
     */
    protected function getText($message, $type)
    {
        if ($type === "status") {
            $typeName = "Status";
            $typeColor = "green";
        } elseif ($type === "warning") {
            $typeName = "Warning";
            $typeColor = "yellow";
        } else {
            $typeName = "Error";
            $typeColor = "red";
        }

        return [
            "[$typeName] $message",
            $typeColor
        ];
    }

    /**
     * @param string $message
     * @param string $type
     */
    public function logCli($message, $type = "status")
    {
        $m = $this->getText($message, $type);
        echo $this->colors->getColoredString($m[0], $m[1]) . "\n";
    }

    /**
     * @param string $message
     * @param string $file
     * @param string $type
     */
    public function logFile($message, $file, $type = "status")
    {
        file_put_contents($file, $this->getText($message, $type)[0] . "\n", FILE_APPEND);
    }

    /**
     * @param string $file
     */
    public function logFileNewRun($file, $newFile = false)
    {
        date_default_timezone_set('UTC');

        file_put_contents(
            $file,
            "----------------------------------------------------------------\n[New run] " . date(DATE_ATOM) . "\n----------------------------------------------------------------\n",
            ($newFile) ? 0 : FILE_APPEND
        );
    }
}
