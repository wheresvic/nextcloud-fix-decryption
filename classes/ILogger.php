<?php

class ILogger {

    /** @var Colors */
    private $colors;

    public function __construct( Colors $colors ) {
        $this->colors = $colors;
    }

    public function success( $message, $app = null ) {
        echo $this->colors->getColoredString('[Status] ' . $message, "green") . "\n";
    }

    public function error( $message, $app = null ) {
        echo $this->colors->getColoredString('[Error] ' . $message, "red") . "\n";
    }

    public function warning( $message, $app = null ) {
        echo $this->colors->getColoredString('[Warning] ' . $message, "yellow") . "\n";
    }

}
