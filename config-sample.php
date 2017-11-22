<?php

return [
    "debug" => true, // dry run
    "test" => true, // exit after first file on which a fix has been applied to
    "data_dir" => "", // folder for your data directory
    "instance_id" => "ocwxw0qp9iav", // instance_id from your config file - used to generate password hash require to decrypt private key
    "instance_secret" => "IEBppf9SjnNEDZxmnAFXeI6RoZVElBTklB4WQbk+0+0aC8Dc", // instance_secret from your config file - used to generate password hash require to decrypt private key
    "current_db" => [
        "host"     => "db", // hostname or IP address (see php mysqli)
        "username" => "user",
        "password" => "secret",
        "db"       => "nextcloud", // database name
        "port"     => 3307 // optional, standard is 3306
    ],
    "backup_db" => [
        "host"     => "8.8.8.8",
        "username" => "user",
        "password" => "secret",
        "db"       => "nextcloud"
    ],
    "test_db" => [
        "host"     => "8.8.8.8",
        "username" => "user",
        "password" => "secret",
        "db"       => "nextcloud"
    ]
];
