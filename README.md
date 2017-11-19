# Nextcloud (Owncloud) fix encryption

This small program can help to restore inaccessible files encrypted with standard encryption method.

## Overview

Nextcloud's encryption requires a unique "file key", the size of the decryption file and the version number
to decrypt a file.

If you lost the file size of the decrypted file and or the version number of file this php script might help you.

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

The first is build around the function ```fixUnencryptedSize```. This will require the password of the user
whose files you want to decrypt.

File: ```main.php```

### Second Method

If you have a database backup with the files in working order you can try this one.

File: ```main-w-db.php```

## Get started

* clone the repo or download it as a zip
* copy ```config-sample.php``` to ```config.php``` and enter the correct data in the fields
* run the file of your selected method with parameter of the user (```php /path/to/script.php -u ncuser``` or ```php /path/to/script.php -user=ncuser```)

I suggest to run debug (dry-run) first, then "test", check the file for success and lastly to run a full cycle.
