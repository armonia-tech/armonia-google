<?php
namespace ArmoniaGoogle;

# Imports the Google Cloud client library
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Topic;

class CloudPubSub
{
    /**
     * @var array $config
     */
    private static $config = [];

    /**
     * set cloud client credential
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $service_account_key
     * @param string $project_id
     * @return void
     */
    public static function setClient(string $service_account_key, string $project_id)
    {
        self::$config['access_key']   = $service_account_key;
        self::$config['project_id']   = $project_id;
    }

    /**
     * get cloud service account key
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @return string cloud key
     */
    private static function getAccessKey()
    {
        return json_decode(file_get_contents(self::$config['access_key']), true);
    }

    /**
     * Init Client
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @return void
     */
    private function initClient()
    {
        return new PubSubClient([
            'projectId' => self::$config['project_id'],
            'keyFile'   => self::getAccessKey(),
        ]);
    }

    /**
     * Create Topic
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $topic_name
     * @return boolean|string
     */
    public static function createTopic(string $topic_name)
    {
        # Init Client
        $client = self::initClient();

        # Creates the new topic
        $topic = $client->createTopic($topic_name);
        return (!empty($topic->name())) ? $topic->name() : false;
    }

    /**
     * Get Topics
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @return array
     */
    public static function getTopics()
    {
        $output = [];

        # Init Client
        $client = self::initClient();

        # Get Topic
        $topics = $client->topics();
        foreach ($topics as $topic) {
            $info = $topic->info();
            $output[] = $info['name'];
        }
        return $output;
    }

    /**
     * Create Subscription
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $topic
     * @param string $subscription
     * @return boolean
     */
    public static function createSubscription(string $subscription, string $topic)
    {
        // Init Client
        $client = self::initClient();

        // Get an instance of a previously created topic.
        $subscription = $client->subscribe($subscription, $topic);
        return (!empty($subscription->name())) ? $subscription->name() : false;
    }

    /**
     * Get Subscriptions
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @return boolean
     */
    public static function getSubscriptions()
    {
        $output = [];

        // Init Client
        $client = self::initClient();

        // Get an instance of a previously created subscription.
        $subscriptions = $client->subscriptions();
        foreach ($subscriptions as $subscription) {
            $info = $subscription->info();
            $output[] = $info['name'];
        }
        return $output;
    }

    /**
     * Public topic message
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $topic_name
     * @param array $message
     * @return boolean|array
     */
    public static function publishTopicMessage(string $topic_name, array $message)
    {
        // Init Client
        $client = self::initClient();

        // Get an instance of a previously created topic.
        $topic = $client->topic($topic_name);

        // Publish a message to the topic.
        return $topic->publish($message);
    }

    /**
     * Pull topic message
     *
     * @author Armonia Tech <developer@armonia-tech.com>
     * @param string $subscription_name
     * @return array
     */
    public static function pullTopicMessage(string $subscription_name)
    {
        $output = [];

        // Init Client
        $client = self::initClient();

        // Get an instance of a previously created subscription.
        $subscription = $client->subscription($subscription_name);

        // Pull all available messages.
        $messages = $subscription->pull();
        foreach ($messages as $message) {
            $output[] = [
                'message' => $message->data(),
            ];
            //echo $message->attribute('location');
        }
        return $output;
    }
}
