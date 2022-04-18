<?php
namespace ArmoniaGoogle;

use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;

class GoogleDrive
{
    /**
     * @var array $config
     */
    private static $config = [];

    /**
     * set google app name
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $app_name
     * @param string $credential
     * @param string $token
     * @return void
     */
    public static function setClient(string $app_name, string $credential, string $token)
    {
        self::$config['app_name'] = $app_name;
        self::$config['credential'] = $credential;
        self::$config['token'] = $token;
    }

    /**
     * get google client credential
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @return string app_name
     */
    private static function getGoogleAppName()
    {
        return self::$config['app_name'];
    }

    /**
     * get google credential file path
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @return string credential file path
     */
    private static function getGoogleCredential()
    {
        return self::$config['credential'];
    }

    /**
     * get google token file path
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @return string token file path
     */
    private static function getGoogleToken()
    {
        return self::$config['token'];
    }

    /**
     * connect to google drive
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @return object $client
     */
    private static function connect()
    {
        $client = new Google_Client();
        $client->setApplicationName(self::getGoogleAppName());
        $client->setScopes(Google_Service_Drive::DRIVE);
        $client->setAuthConfig(self::getGoogleCredential());
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.
        $tokenPath = self::getGoogleToken();
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }

        return $client;
    }

    /**
     * get google drive folder ID by name
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $name
     * @param string $parentFolderId
     * @param int optional $pageSize
     * @return array $output folder id and name
     */
    public static function getFolderIdByName(string $name, string $parentFolderId = '', int $pageSize = 10)
    {
        // Get the API client and construct the service object.
        $client = self::connect();
        $service = new Google_Service_Drive($client);
        $searchInFolder = '';

        if (!empty($parentFolderId)) {
            $searchInFolder = " and '".$parentFolderId."' in parents";
        }

        // Print the names and IDs for up to $pageSize files.
        $optParams = array(
            'q' => "trashed = false and mimeType = 'application/vnd.google-apps.folder' and name = '".$name."'".$searchInFolder,
            'pageSize' => $pageSize,
            'spaces' => 'drive',
            'fields' => 'nextPageToken, files(id, name)'
        );

        $results = $service->files->listFiles($optParams);

        if (count($results->getFiles()) > 0) {
            $output = [];
            foreach ($results->getFiles() as $file) {
                $temp = [];
                $temp['name'] = $file->getName();
                $temp['id'] = $file->getId();
                $output[] = $temp;
            }
        } else {
            $output = false;
        }
        return $output;
    }

    /**
     * get google drive folder ID by custom property
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $name
     * @param string $parentFolderId
     * @param int optional $pageSize
     * @return array $output folder id and name
     */
    public static function getFolderIdByCustomProperty(string $name, string $value, string $parentFolderId = '', int $pageSize = 100)
    {
        // Get the API client and construct the service object.
        $client = self::connect();
        $service = new Google_Service_Drive($client);
        $searchInFolder = '';
        if (!empty($parentFolderId)) {
            $searchInFolder = " and '".$parentFolderId."' in parents";
        }

        // Print the names and IDs for up to $pageSize files.
        $optParams = array(
            'q' => "trashed = false and mimeType = 'application/vnd.google-apps.folder' and appProperties has { key='".$name."' and value='".$value."' }".$searchInFolder,
            'pageSize' => $pageSize,
            'spaces' => 'drive',
            'fields' => 'nextPageToken, files(id, name)'
        );
        $results = $service->files->listFiles($optParams);

        if (count($results->getFiles()) > 0) {
            $output = [];
            foreach ($results->getFiles() as $file) {
                $temp = [];
                $temp['name'] = $file->getName();
                $temp['id'] = $file->getId();
                $output[] = $temp;
            }
        } else {
            $output = [];
        }
        return $output;
    }

    /**
     * get google drive file by folder id
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $folderId
     * @param string optional $mimeType
     * @param int optional $pageSize
     * @return array $output folder id and name
     */
    public static function getFileByFolderId(string $folderId, string $mimeType = '', int $pageSize = 10)
    {
        // Get the API client and construct the service object.
        $client = self::connect();
        $service = new Google_Service_Drive($client);

        $mime_filter = (!empty($mimeType)) ? "mimeType = '".$mimeType."' and " : "";
        // Print the names and IDs for up to $pageSize files.
        $optParams = array(
            'q' => "trashed = false and ".$mime_filter."'".$folderId."' in parents",
            'pageSize' => $pageSize,
            'spaces' => 'drive',
            'fields' => 'nextPageToken, files(id, name)'
        );
        $results = $service->files->listFiles($optParams);

        if (count($results->getFiles()) > 0) {
            $output = [];
            foreach ($results->getFiles() as $file) {
                $temp = [];
                $temp['name'] = $file->getName();
                $temp['id'] = $file->getId();
                $output[] = $temp;
            }
        } else {
            $output = [];
        }
        return $output;
    }

    /**
     * get google drive file id by name
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $folderId
     * @param string optional $optional_query
     * @param int optional $pageSize
     * @return array $output folder id and name
     */
    public static function getFileByName(string $name, string $optional_query = '', int $pageSize = 10)
    {
        // Get the API client and construct the service object.
        $client = self::connect();
        $service = new Google_Service_Drive($client);

        $optional_query = (!empty($optional_query)) ? " and ".$optional_query : "";
        // Print the names and IDs for up to $pageSize files.
        $optParams = array(
            'q' => "trashed = false and name = '".$name."'".$optional_query,
            'pageSize' => $pageSize,
            'spaces' => 'drive',
            'fields' => 'nextPageToken, files(id, name)'
        );
        $results = $service->files->listFiles($optParams);

        if (count($results->getFiles()) > 0) {
            $output = [];
            foreach ($results->getFiles() as $file) {
                $temp = [];
                $temp['name'] = $file->getName();
                $temp['id'] = $file->getId();
                $output[] = $temp;
            }
        } else {
            $output = [];
        }
        return $output;
    }

    /**
     * get google drive file details by id
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $file_id
     * @return mixed $file boolean/array
     */
    public static function getFileById(string $file_id)
    {
        // Get the API client and construct the service object.
        $client = self::connect();
        $service = new Google_Service_Drive($client);
        $file = $service->files->get($file_id);
        return $file;
    }

    /**
     * get google drive file id by custom query
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $custom_query
     * @param int optional $pageSize
     * @return array $output file id and name
     */
    public static function getFileByCustomQuery(string $custom_query, int $pageSize = 10)
    {
        // Get the API client and construct the service object.
        $client = self::connect();
        $service = new Google_Service_Drive($client);

        $custom_query = (!empty($custom_query)) ? " and ".$custom_query : "";
        // Print the names and IDs for up to $pageSize files.
        $optParams = array(
            'q' => "trashed = false ".$custom_query,
            'pageSize' => $pageSize,
            'spaces' => 'drive',
            'fields' => 'nextPageToken, files(id, name)'
        );
        $results = $service->files->listFiles($optParams);

        if (count($results->getFiles()) > 0) {
            $output = [];
            foreach ($results->getFiles() as $file) {
                $temp = [];
                $temp['name'] = $file->getName();
                $temp['id'] = $file->getId();
                $output[] = $temp;
            }
        } else {
            $output = [];
        }
        return $output;
    }

    /**
     * create google drive folder
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $name
     * @param string $parentFolderId
     * @param array optional $metadata
     * @return mixed boolean or int folder id
     */
    public static function createFolder(string $name, string $parentFolderId = '', array $metadata = [])
    {
        $client = self::connect();
        $service = new Google_Service_Drive($client);

        $setting = [];
        $setting['mimeType'] = 'application/vnd.google-apps.folder';
        $setting['name'] = $name;

        if (!empty($metadata)) {
            $setting['appProperties'] = array($metadata);
        }
        
        if (!empty($parentFolderId)) {
            $setting['parents'] = array($parentFolderId);
        }

        $fileMetadata = new Google_Service_Drive_DriveFile($setting);

        $file = $service->files->create($fileMetadata, array('fields' => 'id'));
        
        return (!empty($file)) ? $file->id : false;
    }

    /**
     * update google drive folder name
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $name
     * @param string $folderId
     * @param array optional $metadata
     * @return mixed boolean or int folder id
     */
    public static function updateFolderName(string $name, string $folderId, array $metadata = [])
    {
        $client = self::connect();
        $service = new Google_Service_Drive($client);

        $setting = [];
        $setting['mimeType'] = 'application/vnd.google-apps.folder';
        $setting['name'] = $name;

        if (!empty($metadata)) {
            $setting['appProperties'] = array($metadata);
        }

        $fileMetadata = new Google_Service_Drive_DriveFile($setting);

        $file = $service->files->update($folderId, $fileMetadata, array('fields' => 'id'));
        
        return (!empty($file)) ? $file->id : false;
    }

    /**
     * move google drive file to new folder by id
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $fileId
     * @param string $destinationFolderId
     * @return mixed boolean or int folder id
     */
    public static function moveFileByFileId(string $fileId, string $destinationFolderId)
    {
        $client = self::connect();
        $service = new Google_Service_Drive($client);

        $emptyFileMetadata = new Google_Service_Drive_DriveFile();
        // Retrieve the existing parents to remove
        $file = $service->files->get($fileId, array('fields' => 'parents'));
        $previousParents = join(',', $file->parents);
        // Move the file to the new folder
        $file = $service->files->update($fileId, $emptyFileMetadata, array(
            'addParents' => $destinationFolderId,
            'removeParents' => $previousParents,
            'fields' => 'id, parents'));

        return (!empty($file)) ? $file->id : false;
    }

    /**
     * delete google drive file by file id
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $fileId
     * @return boolean
     */
    public static function deleteFileByFileId(string $fileId)
    {
        $client = self::connect();
        $service = new Google_Service_Drive($client);

        $service->files->delete($fileId);

        return true;
    }

    /**
     * download google drive file by file id
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $fileId
     * @return mixed boolean or file content
     */
    public static function downloadFileByFileId(string $fileId)
    {
        $client = self::connect();
        $service = new Google_Service_Drive($client);

        $response = $service->files->get($fileId, array('alt' => 'media'));
        
        if (!empty($response)) {
            $content = $response->getBody()->getContents();
            return $content;
        } else {
            return false;
        }
    }
}
