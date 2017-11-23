# Nextcloud (Owncloud) fix encryption

This small program can help to restore inaccessible files encrypted with standard encryption method.

## Overview

Nextcloud's encryption requires a unique "file key", the size of the decrypted file and the version number
to decrypt a file.

If you lost the file size of the decrypted file and or the version number this php script might help you.

Requirements:
* Linux or rather a UNIX-System to run it on (Windows is not supported)
* PHP... :)
* the script has to have access to the Nextcloud data directory including the
  * private key
  * share key
  * file key
* read/write access to the database of Nextcloud

There are two possible methods to that.

### First method

The first is build around the function ```fixUnencryptedSize``` from the Nextcloud sourcecode. This will require the password of the user
whose files you want to decrypt.

<!-- TODO: want it does -->

Required config values:
* ```data_dir```
* ```instance_id```
* ```instance_secret```
* ```current_db```

File to execute: ```main.php USER [-f "/path/to/optional/file"]```

### Second Method

If you have a database backup with the files in working order you can try this one.

<!-- TODO: want it does -->

Required config values:
* ```data_dir```
* ```current_db```
* ```backup_db```

File to execute: ```main-w-db.php USER [-f "/path/to/optional/file"]```

## Get started

* clone the repo or download it as a zip
* copy ```config-sample.php``` to ```config.php``` and enter the correct data in the fields
* run the file of your selected method with parameter of the user and optimally a file option if only want to fix a single file

Examples:
* ```php /home/admin/nextcloud-fix-decryption/main.php frank -f "/var/www/html/nextcloud/data/frank/Pictures/2017211101-151310-0.jpg"```
* ```php /home/admin/nextcloud-fix-decryption/main-w-db.php bela-m```

I suggest to run debug (dry-run) first, then "test", check the file for success and lastly to run a full cycle.

## Not implemented at the present time

* non UNIX Systems (e.g. Windows)
* recovery key support
* old file versions

---------------------------------------

I hope this helps with your recovery. Albeit as a starting point.
