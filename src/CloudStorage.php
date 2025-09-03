<?php
namespace ArmoniaGoogle;

use Google\Cloud\Storage\StorageClient;

class CloudStorage
{
    const GOOGLE_STORAGE_URL = 'https://storage.googleapis.com/';
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
     * Create Folder to Cloud Storage
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $folderName cloud directory that will be created
     * @return object StorageObject
     */
    public static function createFolderToCloud(string $folderName)
    {
        $storage = new StorageClient([
            'keyFile' => json_decode(file_get_contents(self::getStorageKey()), true)
        ]);

        $bucket = $storage->bucket(self::getStorageBucket());

        return $bucket->upload("", [
            'name' => $folderName,
        ]);
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
            'keyFile' => json_decode(file_get_contents(self::getStorageKey()), true)
        ]);

        $bucket = $storage->bucket(self::getStorageBucket());

        $filename = $file_path;
        
        return $bucket->upload($content, [
            'name' => $filename,
        ]);
    }

    /**
     * Upload File to Cloud Storage
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param object $file
     * @param string $file_directory cloud directory where the file upload to
     * @param string $filename custom filename
     * @return bool false
     */
    public static function uploadToCloud($file, string $file_directory = '', string $filename = '')
    {
        $storage = new StorageClient([
            'keyFile' => json_decode(file_get_contents(self::getStorageKey()), true)
        ]);

        $bucket = $storage->bucket(self::getStorageBucket());
        $basename = !empty($filename) ? $filename : basename($file);
        $filename = (!empty($file_directory)) ? rtrim($file_directory, '/\\') . '/' . $basename : $basename;
        $bucket->upload(fopen($file, 'r'), [
            'name' => $filename,
        ]);

        return true;
    }

    /**
     * get URL to Cloud Storage
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $file_url file path in the bucket exclude the bucket name
     * @return string url
     */
    public static function urlFromCloud(string $file_url, string $expires = '+1 hours')
    {
        $storage = new StorageClient([
            'keyFile' => json_decode(file_get_contents(self::getStorageKey()), true)
        ]);
        $bucket = $storage->bucket(self::getStorageBucket());
        $object = $bucket->object($file_url);
        $url = $object->signedUrl(new \DateTime($expires));

        return $url;
    }

    /**
     * get public URL to Cloud Storage
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $file_url file path in the bucket exclude the bucket name
     * @return string url
     */
    public static function publicUrlFromCloud(string $file_url)
    {
        return self::GOOGLE_STORAGE_URL.self::getStorageBucket().'/'.$file_url;
    }
    
    /**
     * read file from Cloud Storage
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $file_url file path in the bucket exclude the bucket name
     * @return object $contents
     */
    public static function readFromCloud(string $file_url)
    {
        $storage = new StorageClient([
            'keyFile' => json_decode(file_get_contents(self::getStorageKey()), true)
        ]);

        $bucket = $storage->bucket(self::getStorageBucket());
        $object = $bucket->object($file_url);
        $stream = $object->downloadAsStream();
        $contents = $stream->getContents();

        return $contents;
    }

    /**
     * download file from Cloud Storage
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $file_url file path in the bucket exclude the bucket name
     * @param string $file_directory local directory where the file save to
     * @return bool true
     */
    public static function downloadFromCloud(string $file_url, string $local_path)
    {
        $storage = new StorageClient([
            'keyFile' => json_decode(file_get_contents(self::getStorageKey()), true)
        ]);

        $bucket = $storage->bucket(self::getStorageBucket());
        $object = $bucket->object($file_url);
        $object->downloadToFile($local_path);

        return true;
    }

    /**
     * delete file from Cloud Storage
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $bucke_name bucket name
     * @param string $file_directory file path in the bucket exclude the bucket name
     * @return bool true
     */
    public static function deleteFromCloud(string $file_url, string $bucket_name = '')
    {
        if (empty($bucket_name)) $bucket_name = self::getStorageBucket();

        $storage = new StorageClient([
            'keyFile' => json_decode(file_get_contents(self::getStorageKey()), true)
        ]);

        $bucket = $storage->bucket($bucket_name);
        $object = $bucket->object($file_url);
        $object->delete();
        return true;
    }
}
