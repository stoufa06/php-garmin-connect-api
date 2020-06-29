<?php
namespace Stoufa\GarminApi;

use League\Oauth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Server\Server;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use GuzzleHttp\Exception\BadResponseException;
use League\OAuth1\Client\Credentials\CredentialsInterface;
use League\OAuth1\Client\Server\User;

class GarminApi extends Server
{

    const API_URL = "https://connectapi.garmin.com/";
    const USER_API_URL = "https://healthapi.garmin.com/wellness-api/rest/";

    /**
     * Get the URL for retrieving temporary credentials.
     *
     * @return string
     */
    public function urlTemporaryCredentials()
    {
        return self::API_URL . 'oauth-service/oauth/request_token';
    }

    /**
     * Get the URL for redirecting the resource owner to authorize the client.
     *
     * @return string
     */
    public function urlAuthorization()
    {
        return 'http://connect.garmin.com/oauthConfirm';
    }

    /**
     * Get the URL retrieving token credentials.
     *
     * @return string
     */
    public function urlTokenCredentials()
    {
        return self::API_URL . 'oauth-service/oauth/access_token';
    }

    /**
     * Get the authorization URL by passing in the temporary credentials
     * identifier or an object instance.
     *
     * @param TemporaryCredentials|string
     *
     * @return string
     */
    public function getAuthorizationUrl($temporaryIdentifier)
    {
        // Somebody can pass through an instance of temporary
        // credentials and we'll extract the identifier from there.
        if ($temporaryIdentifier instanceof TemporaryCredentials) {
            $temporaryIdentifier = $temporaryIdentifier->getIdentifier();
        }
        //$parameters = array('oauth_token' => $temporaryIdentifier, 'oauth_callback' => 'http://70.38.37.105:1225');

        $url = $this->urlAuthorization();
        //$queryString = http_build_query($parameters);
        $queryString = "oauth_token=" . $temporaryIdentifier . "&oauth_callback=" . $this->clientCredentials->getCallbackUri();

        return $this->buildUrl($url, $queryString);
    }

    /**
     * Retrieves token credentials by passing in the temporary credentials,
     * the temporary credentials identifier as passed back by the server
     * and finally the verifier code.
     *
     * @param TemporaryCredentials $temporaryCredentials
     * @param string $temporaryIdentifier
     * @param string $verifier
     *
     * @return TokenCredentials
     */
    public function getTokenCredentials(TemporaryCredentials $temporaryCredentials, $temporaryIdentifier, $verifier)
    {
        if ($temporaryIdentifier !== $temporaryCredentials->getIdentifier()) {
            throw new \InvalidArgumentException(
                'Temporary identifier passed back by server does not match that of stored temporary credentials.
                Potential man-in-the-middle.'
            );
        }

        $uri = $this->urlTokenCredentials();
        $bodyParameters = array('oauth_verifier' => $verifier);

        $client = $this->createHttpClient();

        $headers = $this->getHeaders($temporaryCredentials, 'POST', $uri, $bodyParameters);
        try {
            $response = $client->post($uri, [
                'headers' => $headers,
                'form_params' => $bodyParameters
            ]);
        } catch (BadResponseException $e) {
            return $this->handleTokenCredentialsBadResponse($e);
        }
        
        return $this->createTokenCredentials((string)$response->getBody());
    }

    protected function protocolHeader($method, $uri, CredentialsInterface $credentials, array $bodyParameters = array())
    {
        $parameters = array_merge(
            $this->baseProtocolParameters(),
            $this->additionalProtocolParameters(),
            array(
                'oauth_token' => $credentials->getIdentifier(),

            ),
            $bodyParameters
        );
        $this->signature->setCredentials($credentials);

        $parameters['oauth_signature'] = $this->signature->sign(
            $uri,
            array_merge($parameters, $bodyParameters),
            $method
        );

        return $this->normalizeProtocolParameters($parameters);
    }

    public function getActivitySummary(TokenCredentials $tokenCredentials, array $params)
    {
        $client = $this->createHttpClient();
        $query = http_build_query($params);
        $query = 'activities?'.$query;
        $headers = $this->getHeaders($tokenCredentials, 'GET', self::USER_API_URL . $query);

        try {
            $response = $client->get(self::USER_API_URL . $query, [
                'headers' => $headers,
            ]);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $body = $response->getBody();
            $statusCode = $response->getStatusCode();

            throw new \Exception(
                "Received error [$body] with status code [$statusCode] when retrieving activity summary."
            );
        }
        return $response->getBody()->getContents();
    }

    public function getManuallyActivitySummary(TokenCredentials $tokenCredentials, array $params)
    {
        $client = $this->createHttpClient();
        $query = http_build_query($params);
        $query = 'manuallyUpdatedActivities?'.$query;
        $headers = $this->getHeaders($tokenCredentials, 'GET', self::USER_API_URL . $query);

        try {
            $response = $client->get(self::USER_API_URL . $query, [
                'headers' => $headers,
            ]);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $body = $response->getBody();
            $statusCode = $response->getStatusCode();
            throw new \Exception(
                "Received error [$body] with status code [$statusCode] when retrieving manually activity summary."
            );
        }
        return $response->getBody()->getContents();
    }

    public function getActivityDetailsSummary(TokenCredentials $tokenCredentials, array $params)
    {
        $client = $this->createHttpClient();
        $query = http_build_query($params);
        $query = 'activityDetails?'.$query;
        $headers = $this->getHeaders($tokenCredentials, 'GET', self::USER_API_URL . $query);

        try {
            $response = $client->get(self::USER_API_URL . $query, [
                'headers' => $headers,
            ]);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $body = $response->getBody();
            $statusCode = $response->getStatusCode();
            throw new \Exception(
                "Received error [$body] with status code [$statusCode] when retrieving manually activity summary."
            );
        }
        return $response->getBody()->getContents();
    }
    
    public function backfill(TokenCredentials $tokenCredentials, string $uri, array $params) {
        $client = $this->createHttpClient();
        $query = http_build_query($params);
        $query = 'backfill/'.$uri.'?'.$query;
        $headers = $this->getHeaders($tokenCredentials, 'GET', self::USER_API_URL . $query);

        try {
            $response = $client->get(self::USER_API_URL . $query, [
                'headers' => $headers,
            ]);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $body = $response->getBody();
            $statusCode = $response->getStatusCode();

            throw new \Exception(
                "Received error [$body] with status code [$statusCode] when requesting historic $uri summary."
            );
        }
        return $response->getBody()->getContents();
    }

    public function backfillActivitySummary(TokenCredentials $tokenCredentials, array $params)
    {
        return $this->backfill($tokenCredentials, 'activities', $params);
    }

    public function backfillDailySummary(TokenCredentials $tokenCredentials, array $params)
    {
        return $this->backfill($tokenCredentials, 'dailies', $params);
    }

    public function backfillEpochSummary(TokenCredentials $tokenCredentials, array $params)
    {
        return $this->backfill($tokenCredentials, 'epochs', $params);
    }
    public function backfillActivityDetailsSummary(TokenCredentials $tokenCredentials, array $params)
    {
        return $this->backfill($tokenCredentials, 'activityDetails', $params);
    }

    public function backfillSleepSummary(TokenCredentials $tokenCredentials, array $params)
    {
        return $this->backfill($tokenCredentials, 'sleep', $params);
    }

    public function backfillBodyCompositionSummary(TokenCredentials $tokenCredentials, array $params)
    {
        return $this->backfill($tokenCredentials, 'bodyComps', $params);
    }

    public function backfillStressDetailsSummary(TokenCredentials $tokenCredentials, array $params)
    {
        return $this->backfill($tokenCredentials, 'stressDetails', $params);
    }

    public function backfillUserMetricsSummary(TokenCredentials $tokenCredentials, array $params)
    {
        return $this->backfill($tokenCredentials, 'userMetrics', $params);
    }

    public function backfillPulseOxSummary(TokenCredentials $tokenCredentials, array $params)
    {
        return $this->backfill($tokenCredentials, 'pulseOx', $params);
    }

    public function backfillRespirationSummary(TokenCredentials $tokenCredentials, array $params)
    {
        return $this->backfill($tokenCredentials, 'respiration', $params);
    }

    public function deleteUserAccessToken(TokenCredentials $tokenCredentials) {
        $uri = 'user/registration';
        $client = $this->createHttpClient();
        $headers = $this->getHeaders($tokenCredentials, 'DELETE', self::USER_API_URL . $uri);

        try {
            $response = $client->delete(self::USER_API_URL . $uri, [
                'headers' => $headers,
            ]);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $body = $response->getBody();
            $statusCode = $response->getStatusCode();

            throw new \Exception(
                "Received error [$body] with status code [$statusCode] when deleting user access token."
            );
        }
        return true;
    }


    

    public function urlUserDetails()
    {
        return self::USER_API_URL . 'user/id';
    }

    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        $user = new User();

        $user->uid = $data['userId'];


        $user->extra = (array) $data;

        return $user;
    }

    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        return $data['userId'];
    }

    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
    }

    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
    }
}
