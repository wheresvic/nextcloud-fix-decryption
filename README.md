# decrypt-single-file

## Overview

Nextcloud's encryption requires a unique "file key", the size of the decrypted file and the version number
to decrypt a file.

```bash
php decrypt-single-file.php vic master_eda584b7 ~/temp/master-keys/master_eda584b7.privateKey ~/temp/DSC03842.JPG-keys/OC_DEFAULT_MODULE/master_eda584b7.shareKey ~/temp/DSC03842.JPG-keys/OC_DEFAULT_MODULE/fileKey ~/temp/DSC03842.JPG ~/temp/result.jpg
```
