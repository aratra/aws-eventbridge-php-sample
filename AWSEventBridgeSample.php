<?php
   /*
  Following code sample shows - 
  Step 0: Install AWS PHP SDK - https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/getting-started_installation.html
  Step 1: How to create an AWS credentials profile - https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials.html
  Step 2: Create an EventBridge Client 
  Step 3: Put a rule on the default EventBus to intercept "com.magento" source events 
  Step 4: [Optional-for testing] Create a AWS SNS Topic and Subscriber
  Step 5: [Optional-for testing] Put the SNS Topic as a Target for the event rule created in Step 3
  Step 6: Put an event with dummy data on the EventBus 
  
  PS-you have to accept the subscription confirmation email to recieve the event notifications
  - Update AccessKey, SecretKey & replace_me@email.com in the code below
 */
 
 //Step 0: Install AWS PHP SDK and include autoloader to utilize AWS SDK
  require './vendor/autoload.php';
  use Aws\EventBridge\EventBridgeClient;
  use Aws\Sns\SnsClient;
  use Aws\Exception\AwsException;
  
 /* Step 1: Setup aws credentials. Recommended way is to use AWS shared credentials file and profiles 
    - [Not recommended] you can use hardcoded credentials by un-commenting $credentials and replacing AccessKey and SecretKey .
    - Ref: https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials.html
 */
 
 //$credentials = new Aws\Credentials\Credentials('AccessKey','SecretKey');
 
//Step 2: Create a client
 $eventBridgeClient = new EventBridgeClient([
    'profile' => 'default',  //AWS profile to use for credentials [comment this if using hardcoded credentials]
    'region' => 'eu-west-1', //EU (Ireland)	eu-west-1
    'version' => 'latest',
 //   'credentials' => $credentials //if using hard-coded credentials
]);

//Step 3: Create a rule to intercept events with 'com.magento' source
/*https://docs.aws.amazon.com/eventbridge/latest/APIReference/API_PutRule.html*/

try {
    $result = $eventBridgeClient->putRule(array(
        'Name' => 'magento-event-rule', // REQUIRED
        'Description'=> 'Rule to intercept events with source="com.magento"',
        'EventBusName'=>'default',
        'State' => 'ENABLED',
        'EventPattern' => '{"source":["com.magento"]}'
    ));
    printf("putting rule\n");
    //var_dump($result);
} catch (AwsException $e) {
    // output error message if fails
    error_log($e->getMessage());
}

// Step 4: [Optional-for testing] Create a AWS SNS Topic and Subscriber

$SnSclient = new SnsClient([
    'profile' => 'default',
    'region' => 'eu-west-1',
    'version' => 'latest',
    //'credentials' => $credentials 
]);

//create a topic
$topicName = 'magento-event-notifications';
$topicARN = NULL;   

try {
    $result = $SnSclient->createTopic([
        'Name' => $topicName,
    ]);
    printf("creating Topic\n");
    //var_dump($result);
    
    $topicARN =  $result->get('TopicArn');
    
    //create an email subscriber 
    $protocol = 'email';
    $endpoint = 'replace_me@email.com';
    $alreadySubscribed = false;
    
    $result = $SnSclient->listSubscriptionsByTopic(array('TopicArn' => $topicARN));
    
    foreach ($result ['Subscriptions'] as $key => $value) {
    if ($endpoint == $result ['Subscriptions'][$key]['Endpoint']) {
        $alreadySubscribed = true;
                printf("already Subscribed to Topic\n");
       } 
    }
    
    if(!$alreadySubscribed) {
        $result = $SnSclient->subscribe([
        'Protocol' => $protocol,
        'Endpoint' => $endpoint,
        'ReturnSubscriptionArn' => true,
        'TopicArn' => $topicARN,
         ]);
        printf("subscribing email to Topic\n");
        //var_dump($result);
        
        //setup IAM permissions on the new topic
        //get the current policy
        $result = $SnSclient->getTopicAttributes([
            'TopicArn' => $topicARN, // REQUIRED
        ]);
        //new IAM policy to append
        $policy = [
            'Sid' => 'Allow_Publish_Events', 
            'Effect' => 'Allow', 
            'Principal' => ['Service' => 'events.amazonaws.com'],
            'Resource'=>$topicARN, 
            'Action'=>'sns:Publish'
            ];
        
        $updatedPolicy =json_decode($result ['Attributes']['Policy'],true);
        array_push($updatedPolicy['Statement'],$policy);
        
        $result = $SnSclient->setTopicAttributes([
            'AttributeName' => 'Policy', // REQUIRED
            'AttributeValue' => json_encode($updatedPolicy),
            'TopicArn' => $topicARN, // REQUIRED
        ]);
    }
    
} catch (AwsException $e) {
    // output error message if fails
    error_log($e->getMessage());
} 

//Step 5: [Optional-for testing] Put the SNS Topic as a Target for the event rule created in Step 3
try {
    $result = $eventBridgeClient->putTargets([
        'Rule' => 'magento-event-rule', // REQUIRED
        'Targets' => [ // REQUIRED
            [
                'Arn' => $topicARN, // REQUIRED
                'Id' => $topicName // REQUIRED
            ],
        ],
    ]);
    printf("Put SNS Target\n");
    
    //var_dump($result);
} catch (AwsException $e) {
    // output error message if fails
    error_log($e->getMessage());
}
 
$eventDetail = new stdClass();
$eventDetail->key = "keyName";
$eventDetail->value = "testKeyValue";
try {
    $result = $eventBridgeClient->putEvents([
        'Entries' => [ // REQUIRED
            [
                'Detail' => json_encode($eventDetail),
                'DetailType' => 'detailType',
                'EventBusName' => 'default',
                'Resources' => ['resourceARN'],
                'Source' => 'com.magento',
                'Time' => time()
            ],
        ],
    ]);
   // var_dump($result);
   printf("Sending event\n");
    print_r($result);
    
} catch (AwsException $e) {
    // output error message if fails
    error_log($e->getMessage());
}


/*
$exampleEvent = '{
  "version": "0",
  "id": "6a7e8feb-b491-4cf7-a9f1-bf3703467718",
  "detail-type": "EC2 Instance State-change Notification",
  "source": "com.magento",
  "account": "111122223333",
  "time": "2015-12-22T18:43:48Z",
  "region": "eu-west-1",
  "resources": [
    "arn:aws:ec2:us-east-2:123456789012:instance/i-12345678"
  ],
  "detail": {
    "instance-id": "i-12345678",
    "state": "terminated"
  }
}';
*/
?>
