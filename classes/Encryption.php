<?php

class Encryption
{

    const HEADER_START = 'HBEGIN';
    const HEADER_END = 'HEND';
    const HEADER_PADDING_CHAR = '-';

    /** @var int unencrypted block size if block contains signature */
    private $unencryptedBlockSizeSigned = 6072;

    /**
     * block size will always be 8192 for a PHP stream
     * @see https://bugs.php.net/bug.php?id=21641
     * @var integer
     */
    protected $headerSize = 8192;

    /**
     * block size will always be 8192 for a PHP stream
     * @see https://bugs.php.net/bug.php?id=21641
     * @var integer
     */
    protected $blockSize = 8192;

    /** @var \ILogger */
    private $logger;

    /** @var \Crypt */
    private $crypt;

    /**
     * Encryption constructor.
     * @param ILogger $logger
     * @param Crypt $crypt
     */
    public function __construct(\ILogger $logger, \Crypt $crypt)
    {
        $this->logger = $logger;
        $this->crypt = $crypt;
    }

    /**
     * return header size of given file
     *
     * @param resource $stream
     * @return array
     */
    protected function getHeader(& $stream)
    {
        $headerSize = 0;

        $firstBlock = fread($stream, $this->headerSize);

        if (substr($firstBlock, 0, strlen(self::HEADER_START)) === self::HEADER_START) {
            $headerSize = $this->headerSize;
        }

        return [
            'size' => $headerSize,
            'cat' => $firstBlock
        ];
    }

    /**
     * calculate the unencrypted size
     *
     * gouglhupf: only signed files are supported
     *
     * @param string $path internal path relative to the storage root
     * @param int $size size of the physical file
     * @param string $fileKey decrypted fileKey of the given file
     * @param string $cipher cipher which the file was encrypt with
     *
     * @return array|false calculated unencrypted size
     */
    public function fixUnencryptedSize($path, $size, $fileKey)
    {
        $stream = fopen($path, 'r');

        // if we couldn't open the file we return the old unencrypted size
        if (!is_resource($stream)) {
            $this->logger->error('Could not open ' . $path . '. Recalculation of unencrypted size aborted.');
            return false;
        }

        // get header and skip it
        $header = $this->getHeader($stream);

        $newUnencryptedSize = 0;
        $size -= $header['size'];

        if ($header['size'] === 0) {
            $this->logger->error('Could not get header from file.');
            return false;
        }

        $headerData = $this->crypt->parseHeader($header['cat']);

        $cipher = isset($headerData['cipher']) ? $headerData['cipher'] : $this->crypt::DEFAULT_CIPHER;

        // fast path, else the calculation for $lastChunkNr is bogus
        if ($size === 0) {
            return false;
        }

        $unencryptedBlockSize = $this->unencryptedBlockSizeSigned;

        // calculate last chunk nr
        // next highest is end of chunks, one subtracted is last one
        // we have to read the last chunk, we can't just calculate it (because of padding etc)

        $lastChunkNr = ceil($size / $this->blockSize) - 1;
        // calculate last chunk position
        $lastChunkPos = ($lastChunkNr * $this->blockSize);
        // try to fseek to the last chunk, if it fails we have to read the whole file
        if (@fseek($stream, $lastChunkPos, SEEK_SET) === 0) {
            $newUnencryptedSize += $lastChunkNr * $unencryptedBlockSize;
        }

        $lastChunkContentEncrypted = '';
        $count = $this->blockSize;

        while ($count > 0) {
            $data = fread($stream, $this->blockSize);
            $count = strlen($data);
            $lastChunkContentEncrypted .= $data;
            if (strlen($lastChunkContentEncrypted) > $this->blockSize) {
                $newUnencryptedSize += $unencryptedBlockSize;
                $lastChunkContentEncrypted = substr($lastChunkContentEncrypted, $this->blockSize);
            }
        }

        fclose($stream);

        for ($version = 1; $version < 100; $version++) {
            try {
                $decryptedLastChunk = $this->crypt->symmetricDecryptFileContent($lastChunkContentEncrypted, $fileKey, $cipher, $version, $lastChunkNr . 'end');

                $newVersion = $version;

                break;

            } catch (Exception $e) {
            }
        }

        if (!isset($decryptedLastChunk, $newVersion)) {
            return false;
        }

        // calc the real file size with the size of the last chunk
        $newUnencryptedSize += strlen($decryptedLastChunk);

        return [
            $newUnencryptedSize,
            $newVersion
        ];
    }

}