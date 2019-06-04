<?php
namespace ArmoniaGoogle;

use Google\Cloud\Storage\StorageClient;

class CloudStorage
{
    /**
     * @var array $config
     */
    private static $config = [];

    /**
     * set cloud client credential
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $storage_key
     * @param string $storage_bucket
     * @return void
     */
    public static function setClient(string $storage_key, string $storage_bucket)
    {
        self::$config['storage_key'] = $storage_key;
        self::$config['storage_bucket'] = $storage_bucket;
    }

    /**
     * get cloud storage key
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @return string cloud storage key
     */
    private static function getStorageKey()
    {
        return self::$config['storage_key'];
    }

    /**
     * get cloud storage bucket
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @return string cloud storage bucket
     */
    private static function getStorageBucket()
    {
        return self::$config['storage_bucket'];
    }

    /**
     * Save Content to Cloud Storage
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param object file content
     * @param string $file_path cloud directory and filename that will be created
     * @return bool false
     */
    public static function saveToCloud($content, string $file_path)
    {
        $storage = new StorageClient([
            'keyFile' => json_decode(file_get_contents(APP_PATH . '/' . self::getStorageKey()), true)
        ]);

        $bucket = $storage->bucket(self::getStorageBucket());

        $filename = $file_path;
        $bucket->upload($content, [
            'name' => $filename,
        ]);

        return true;
    }

    /**
     * Upload File to Cloud Storage
     *
     * @author Wilson <huanyong.chan@armonia-tech.com>
     * @param object $file
     * @param string $file_directory cloud directory where the file upload to
     * @return bool false
     */
    public static function uploadToCloud($file, string $file_directory = '')
    {
        $storage = new StorageClient([
            'keyFile' => json_decode(file_get_contents(APP_PATH . '/' . self::getStorageKey()), true)
        ]);

        $bucket = $storage->bucket(self::getStorageBucket());

        $filename = (!empty($file_directory)) ? $file_directory . '/' . basename($file) : basename($file);
        $bucket->upload(fopen($file, 'r'), [
            'name' => $filename,
        ]);

        return true;
    }
}
