<?php
namespace ArmoniaGoogle;

use Google\Cloud\Tasks\V2\CloudTasksClient;
use Google\Cloud\Tasks\V2\HttpMethod;
use Google\Cloud\Tasks\V2\HttpRequest;
use Google\Cloud\Tasks\V2\Task;

class CloudTask
{
    public static function createTask(
        string $project,
        string $location,
        string $queue,
        string $APIurl,
        string $httpMethod,
        array  $payload = []
    ) {
        try {
            putenv('GOOGLE_APPLICATION_CREDENTIALS='.getenv('STORAGE_KEY'));

            $cloudTasksClient = new CloudTasksClient();
            $formattedParent  = $cloudTasksClient->queueName($project, $location, $queue);

            // Create an Http Request Object.
            $httpRequest = new HttpRequest();
            $httpRequest->setUrl($APIurl);

            switch ($httpMethod) {
                case 'GET':
                    $httpMethod = HttpMethod::GET;
                    break;
                case 'PUT':
                    $httpMethod = HttpMethod::PUT;
                    break;
                case 'DELETE':
                    $httpMethod = HttpMethod::DELETE;
                    break;
                default:
                    $httpMethod = HttpMethod::POST;
                    break;
            }

            $httpRequest->setHttpMethod($httpMethod);

            if (! empty($payload)) {
                $httpRequest->setBody(json_encode($payload));
            }

            // Create a Cloud Task object.
            $task = new Task();
            $task->setHttpRequest($httpRequest);

            $response = $cloudTasksClient->createTask($formattedParent, $task);
            
            return $response;
        } finally {
            $cloudTasksClient->close();
        }
    }
}
