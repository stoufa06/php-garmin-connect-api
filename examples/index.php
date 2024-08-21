<?php 
require __DIR__.'/../vendor/autoload.php';

use League\OAuth1\Client\Credentials\TokenCredentials;
use Stoufa\GarminApi\GarminApi;
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
session_start();


// unset($_SESSION['identifier']);
// unset($_SESSION['secret']);

if(isset($_GET['oauth_token'], $_GET['oauth_verifier']) || isset($_SESSION['identifier'], $_SESSION['secret'])) {

    try
    {
        $config = array(
            'identifier'     => getenv('GARMIN_KEY'),
            'secret'         => getenv('GARMIN_SECRET'),
            'callback_uri'   => getenv('GARMIN_CALLBACK_URI') 
        );
    
        $server = new GarminApi($config);
    
        if (isset($_SESSION['identifier'], $_SESSION['secret'])) {

            $identifier = $_SESSION['identifier'];
            $secret = $_SESSION['secret'];

            // recreate tokenCredentials from identifier and secret
            $tokenCredentials = new TokenCredentials();
            $tokenCredentials->setIdentifier($identifier);
            $tokenCredentials->setSecret($secret);

        }
        else {
            // Retrieve the temporary credentials we saved before
            $temporaryCredentials = $_SESSION['temporaryCredentials'];
                
            // We will now retrieve token credentials from the server.
            $tokenCredentials = $server->getTokenCredentials($temporaryCredentials, $_GET['oauth_token'], $_GET['oauth_verifier']);
            
            // save token identifier in session
            $_SESSION['identifier'] = $tokenCredentials->getIdentifier();
            $_SESSION['secret'] = $tokenCredentials->getSecret();
        }
       
        
        $uploadStartTimeInSeconds = (new DateTime())->modify('-1 day')->getTimestamp(); // start time in seconds 
        $uploadEndTimeInSeconds = (new DateTime())->getTimestamp(); // end time in seconds

        // Backfill activities before pulling activities (probably you must wait before it fills the summaries)
        $params = [
            'summaryStartTimeInSeconds' => $uploadStartTimeInSeconds, // time in seconds utc
            'summaryEndTimeInSeconds' => $uploadEndTimeInSeconds // time in seconds utc
        ];
        //$server->backfillActivitySummary($tokenCredentials, $params);

        // User id
        $userId = $server->getUserUid($tokenCredentials);
        
        // Activity summaries
        $params = [
            'uploadStartTimeInSeconds' => $uploadStartTimeInSeconds, // time in seconds utc
            'uploadEndTimeInSeconds' => $uploadEndTimeInSeconds // time in seconds utc
        ];
        $summary = $server->getActivitySummary($tokenCredentials, $params);


    
    }
    catch (\Throwable $th)
    {
        // catch your exception here
        $error = $th->getMessage();
    }
  
}
else {
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
        $error = $th->getMessage();
    }
   
}



    
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
</head>
<body>
    
<div class="container mt-3">
<?php if(isset($error)):?>
    <div class="alert alert-danger" role="alert">
    <?=$error?>
    </div>
<?php elseif(isset($tokenCredentials)):?>
<dl>
    <dt>User ID</dt>
    <dd><?=$userId?></dd>

    <dt>Token</dt>
    <dd  class="bg-light"><pre><code><?php print_r(($tokenCredentials))?></code></pre></dd>

    <dt>Get Identifier</dt>
    <dd  class="bg-light">$tokenCredentials->getIdentifier() => <?php echo $tokenCredentials->getIdentifier().PHP_EOL;?></dd>
    
    <dt>Get Secrent</dt>
    <dd  class="bg-light">$tokenCredentials->getSecret() => <?php echo $tokenCredentials->getSecret();?></dd>


    <dt>Create new TokenCredentials from identifier and secret</dt>
    <dd class="bg-light">
    <pre><code>
$identifier = $tokenCredentials->getIdentifier();
$secret = $tokenCredentials->getSecret();

$ts = new TokenCredentials();
$ts->setIdentifier($identifier);
$ts->setSecret($secret);
print_r($ts);

<?php 
    $identifier = $tokenCredentials->getIdentifier();
    $secret = $tokenCredentials->getSecret();

    $ts = new TokenCredentials();
    $ts->setIdentifier($identifier);
    $ts->setSecret($secret);
    print_r($ts);
    ?>
    </code></pre>
    </dd>
    <dt>Summary</dt>
    <dd class="bg-light"><pre><?php print_r(json_decode($summary))?></pre></dd>
</dl>
<?php else : ?>
    <h2>Click to connect your garmin account</h2>
    <a class="btn btn-primary" href="<?=$link?>" role="button">Connect Garmin</a>
<?php endif?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/js/bootstrap.bundle.min.js" integrity="sha384-b5kHyXgcpbZJO/tY9Ul7kGkf1S0CWuKcCD38l8YkeH8z8QjE0GmW1gYU5S9FOnJ0" crossorigin="anonymous"></script>

</body>
</html>