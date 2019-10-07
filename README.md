# aws-eventbridge-php-sample

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
