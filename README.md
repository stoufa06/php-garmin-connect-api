# Table of contents
- [Table of contents](#table-of-contents)
- [Description](#description)
- [Installtion](#installtion)
- [Usage](#usage)
  - [Get authorization link](#get-authorization-link)
  - [Get token credentials](#get-token-credentials)
  - [Get Garmin user id](#get-garmin-user-id)
  - [Backfill activities](#backfill-activities)
  - [Deregistration](#deregistration)
  - [Avalaible methods (for the moment)](#avalaible-methods-for-the-moment)
    - [Get summary activities](#get-summary-activities)
    - [Backfill activities](#backfill-activities-1)

# Description
PHP library to connect and use garmin wellness api

# Installtion
```
composer require stoufa06/php-garmin-connect-api
```
# Usage 
## Get authorization link
```php
use Stoufa\GarminApi\GarminApi;

/** use this if you are not using utc timezone in your server or your php code **/
$timezone = date_default_timezone_get(); 
date_default_timezone_set('UTC');

try
{

    $config = array(
        'identifier'     => getenv('GARMIN_KEY'),
        'secret'         => getenv('GARMIN_SECRET'),
        'callback_uri'   => getenv('GARMIN_CALLBACK_URI') 
    );

    $server = new GarminApi($config);

    // Retreive temporary credentials from server 
    $temporaryCredentials = $server->getTemporaryCredentials();

    // Save temporary crendentials in session to use later to retreive authorization token
    $_SESSION['temporaryCredentials'] = $temporaryCredentials;

    // Get authorization link 
    $link = $server->getAuthorizationUrl($temporaryCredentials);
}
catch (\Throwable $th)
{
    // catch your exception here
}
finally
{
    date_default_timezone_set($timezone);
}

```
## Get token credentials

After the user connects his garmin account successfully it will redirect to callback_uri. "oauth_token" and "oauth_verifier" should be available in $_GET. 

```php
/** use this if you are not using utc timezone in your server or your php code **/
$timezone = date_default_timezone_get(); 
date_default_timezone_set('UTC');

try
{
    $config = array(
        'identifier'     => getenv('GARMIN_KEY'),
        'secret'         => getenv('GARMIN_SECRET'),
        'callback_uri'   => getenv('GARMIN_CALLBACK_URI') 
    );

    $server = new GarminApi($config);

    // Retrieve the temporary credentials we saved before
    $temporaryCredentials = $_SESSION['temporaryCredentials'];

    // We will now retrieve token credentials from the server.
    $tokenCredentials = $server->getTokenCredentials($temporaryCredentials, $_GET['oauth_token'], $_GET['oauth_verifier']);

}
catch (\Throwable $th)
{
    // catch your exception here
}
finally
{
    date_default_timezone_set($timezone);
}
```

## Get Garmin user id

```php
$userId = $server->getUserUid($tokenCredentials);
```

## Backfill activities

When you connect garmin account and get token credentials first time, you won't be able to get previous activities because garmin does not give you activities older than your token credentials. Instead you need to use backfill method to fullfull your token with previous activities (no more than one month).


```php
// backfill activities for last 7 days ago
$params = [
    'summaryStartTimeInSeconds' => strtotime("-7 days", time()),
    'summaryEndTimeInSeconds' => time()
];
$server->backfillActivitySummary($tokenCredentials, $params);
```
## Deregistration
```php
$server->deleteUserAccessToken($tokenCredentials);
```

## Avalaible methods (for the moment)

### Get summary activities
```php
$params = [
    'uploadStartTimeInSeconds' => 1598814036, // time in seconds utc
    'uploadEndTimeInSeconds' => 1598900435 // time in seconds utc
];

// Activity summaries
$server->getActivitySummary($tokenCredentials, $params);

// Manually activity summaries
$server->getManuallyActivitySummary($tokenCredentials, $params);

// Activity details summaries
$server->getActivityDetailsSummary($tokenCredentials, $params);
```


### Backfill activities
```php
// For backfill params can be with upload start time
$params = [
    'uploadStartTimeInSeconds' => 1598814036, // time in seconds utc
    'uploadEndTimeInSeconds' => 1598900435 // time in seconds utc
];
// or with summary start time
$params = [
    'summaryStartTimeInSeconds' => 1598814036, // time in seconds utc
    'summaryEndTimeInSeconds' => 1598900435 // time in seconds utc
];

// Backfill activity summaries
$server->backfillActivitySummary($tokenCredentials, $params);

// Backfill daily activity summaries
$server->backfillDailySummary($tokenCredentials, $params);

// Backfill epoch summaries
$server->backfillEpochSummary($tokenCredentials, $params);

// Backfill activity details summaries
$server->backfillActivityDetailsSummary($tokenCredentials, $params);

// Backfill sleep summaries
$server->backfillSleepSummary($tokenCredentials, $params);

// Backfill body composition summaries
$server->backfillBodyCompositionSummary($tokenCredentials, $params);

// Backfill stress details summaries
$server->backfillStressDetailsSummary($tokenCredentials, $params);

// Backfill user metrics summaries
$server->backfillUserMetricsSummary($tokenCredentials, $params);

// Backfill pulse ox summaries
$server->backfillPulseOxSummary($tokenCredentials, $params);

// Backfill respiration summaries
$server->backfillRespirationSummary($tokenCredentials, $params);
```