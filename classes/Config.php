<?php

class Config
{
    /** @var array $config */
    protected $config;

    public function __construct()
    {
        $config = (@include dirname(__DIR__) . '/config.php');

        if (!is_array($config)) {
            throw new Exception("No config file found or it is not valid!");
        }

        $this->config = $config;
    }

    public function getValue($key)
    {
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        } else {
            return null;
        }
    }

    public function getDatabaseConfig($type)
    {
        return filter_var_array(
            $this->getValue($type . '_db'),
            array(
                'host' => FILTER_DEFAULT,
                'username' => FILTER_DEFAULT,
                'password' => FILTER_DEFAULT,
                'db' => FILTER_DEFAULT,
                'port' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'flags' => FILTER_REQUIRE_SCALAR,
                    'options' => array('default' => 3306)
                )
            )
        );
    }

}
