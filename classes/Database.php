<?php


class Database
{
    protected $connection;
    protected $logger;

    public function __construct(
        $host,
        $username,
        $password,
        $db = "nextcloud",
        $post = 3306
    )
    {
        $this->connection = new mysqli($host, $username, $password, $db, $post);

        if ($this->connection->connect_errno) {
            throw new Exception("Unable to connect to old database (" . $this->connection->connect_error . ")");
        }
    }

    /**
     * @param string $user
     * @return int
     * @throws Exception
     */
    public function getStorageId($user)
    {
        $query = "SELECT `numeric_id` FROM `oc_storages` WHERE `id` = \"home::" . $user . "\"";

        $result = $this->connection->query($query);

        if (!$result) {
            throw new Exception("Unable to execute user storage query in current database");
        }

        $resultFetched = $result->fetch_all(MYSQLI_ASSOC);

        if (empty($resultFetched)) {
            throw new Exception("Unable to find user storage in current database");
        }

        return (int) $resultFetched[0]['numeric_id'];
    }

    /**
     * @param string $path
     * @param int|string $storageID
     * @return array
     * @throws Exception
     */
    public function getFile($path, $storageID)
    {
        $query = "SELECT `fileid`,`size`,`encrypted` FROM `oc_filecache` WHERE `path` = \"$path\" AND `storage` = " . $storageID;

        $result = $this->connection->query($query);

        if (!$result) {
            throw new Exception("Unable to execute query for file in current database ($path)");
        }

        $fileMeta = $result->fetch_all(MYSQLI_ASSOC);

        if (empty($fileMeta)) {
            throw new Exception("Unable to find file in current database - skipping file ($path)");
        }

        if (count($fileMeta) > 1) {
            throw new Exception("Query found to many files - skipping file ($path)");
        }

        return $fileMeta[0];
    }

    /**
     * @param int|string $fileid
     * @param int|string $size
     * @param int|string $encrypted
     * @return boolean
     */
    public function updateFileByID($fileid, $size, $encrypted)
    {
        $query = "UPDATE `oc_filecache` SET `size` = '" . $size . "', `encrypted` = '" . $encrypted . "' WHERE `oc_filecache`.`fileid` = " . $fileid . ";";

        if (DEBUG) {
            print_r($query . "\n");

            return true;
        } else {
            return $this->connection->real_query($query);
        }
    }

    /**
     * @param string $path
     * @param int|string $storageID
     * @param int|string $size
     * @param int|string $encrypted
     * @return boolean
     */
    public function updateFileByPath($path, $storageID, $size, $encrypted)
    {
        $query = "UPDATE `oc_filecache` SET `size` = '" . $size . "', `encrypted` = '" . $encrypted . "' WHERE `oc_filecache`.`path` = '" . $path . "' AND `oc_filecache`.`storage` = " . $storageID . ";";

        if (DEBUG) {
            print_r($query . "\n");

            return true;
        } else {
            return $this->connection->real_query($query);
        }
    }
}
