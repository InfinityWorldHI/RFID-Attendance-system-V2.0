<?php

class GoogleCalendarApi
{
    protected string $clientId = "";
    protected string $clientSecret = "";
    protected array $options = [];

    protected string $refreshToken = "";
    protected string $accessToken = "";
    protected int $expiration = 0;
    public bool $tokenUpdated = false;

    public function __construct(String $clientId, String $clientSecret, array $options)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->options = $options;
        if (count($options)) {
            $this->setRefreshToken($options["accessToken"], $options["refreshToken"], $options["expiration"]);
        }
    }

    private function curl(string $url, string $curlPost, int &$returnCode, string $requestType = "")
    {
        // var_dump($url, $curlPost);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        if (!empty($curlPost)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        }
        if (!empty($requestType)) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requestType);
        };
        if (!empty($this->accessToken)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->accessToken/*, ' Content-Type: application/json'*/));
        } /* else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(' Content-Type: application/json'));
        } */
        $data = curl_exec($ch);
        $returnCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (json_decode($data)) {
            $data = json_decode($data, true);
        }
        return $data;
    }

    public function getOAuthUrl(): string
    {
        return 'https://accounts.google.com/o/oauth2/auth?' .
            'client_id=' . $this->clientId .
            '&access_type=' . 'offline' .
            '&prompt=' . 'consent' .
            '&scope=' . urlencode('https://www.googleapis.com/auth/calendar') .
            '&redirect_uri=' . urlencode($this->options["redirectUrl"]) .
            '&response_type=' . 'code';
    }

    public function setRefreshToken($accessToken, $refreshToken, $expiration)
    {
        $this->refreshToken = $refreshToken;
        $this->expiration = $expiration;
        $this->accessToken = $accessToken;
    }

    public function refreshAccessToken()
    {
        if (time() >= ($this->expiration - 100)) {
            $data = $this->GetRefreshAccessToken();
            $this->accessToken = $data["access_token"];
            $this->expiration = time() + $data["expires_in"];
            $this->tokenUpdated = true;
        }
    }

    public function getConfig()
    {
        return [
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'redirectUrl' => $this->options['redirectUrl'],
            'accessToken' => $this->accessToken,
            'refreshToken' => $this->refreshToken,
            'expiration' => $this->expiration,
        ];
    }

    public function GetAccessToken($code)
    {
        $http_code = 0;
        $this->accessToken = "";
        $data = $this->curl(
            'https://accounts.google.com/o/oauth2/token',
            'client_id=' . $this->clientId .
                '&redirect_uri=' . $this->options["redirectUrl"] .
                '&client_secret=' . $this->clientSecret .
                '&code=' . $code .
                '&grant_type=authorization_code',
            // http_build_query([
            //     'grant_type' => 'authorization_code',
            //     'code' => $code,
            //     'client_secret' => $this->clientSecret,
            //     'client_id' => $this->clientId,
            //     'redirect_uri' => $this->options["redirectUrl"],
            // ]),
            $http_code
        );
        if ($http_code != 200) {
            throw new Exception('Error : Failed to receive access token');
        }
        $this->expiration = time() + $data["expires_in"];
        $this->accessToken = $data["access_token"];

        //detect changes in refresh tokens
        if (isset($data["refresh_token"]) && $data["refresh_token"] != $this->refreshToken) {
            $this->refreshToken = $data["refresh_token"];
        }

        return $data;
    }

    public function GetRefreshAccessToken()
    {
        $http_code = 0;
        $this->accessToken = "";
        $data = $this->curl(
            'https://accounts.google.com/o/oauth2/token',
            http_build_query([
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->refreshToken,
                'grant_type' => 'refresh_token'
            ]),
            $http_code
        );
        if ($http_code != 200) {
            throw new Exception('Error : Failed to refresh access token');
        }

        return $data;
    }

    public function GetUserCalendarTimezone()
    {
        $this->refreshAccessToken();
        $http_code = 0;
        $data = $this->curl(
            'https://www.googleapis.com/calendar/v3/users/me/settings/timezone',
            "",
            $http_code
        );
        //echo '<pre>';print_r($data);echo '</pre>';
        if ($http_code != 200) {
            throw new Exception('Error : Failed to get timezone');
        }
        return $data['value'];
    }

    public function GetCalendarsList()
    {
        if(empty($this->clientId) && empty($refreshToken)){
            return [];
        }
        $this->refreshAccessToken();
        $http_code = 0;
        $data = $this->curl(
            'https://www.googleapis.com/calendar/v3/users/me/calendarList?' . http_build_query([
                'fields' => 'items(id,summary,timeZone)',
                'minAccessRole' => 'owner',
            ]),
            "",
            $http_code,
        );
        //echo '<pre>';print_r($data);echo '</pre>';
        if ($http_code != 200)
            throw new Exception('Error : Failed to get calendars list');

        return $data['items'];
    }

    // need to add repeat argument here
    public function CreateCalendarEvent($calendarId, $summary, $allDay, $recurrence, $recurrenceEnd, $eventTime, $eventTimezone)
    {
        $this->refreshAccessToken();
        $curlPost = array('summary' => $summary); // event title

        // if event is an all day event or not 
        if ($allDay == 1) {
            $curlPost['start'] = array('date' => $eventTime['event_date']);
            $curlPost['end'] = array('date' => $eventTime['event_date']);
        } else {
            $curlPost['start'] = array('dateTime' => $eventTime['start_time'], 'timeZone' => $eventTimezone);
            $curlPost['end'] = array('dateTime' => $eventTime['end_time'], 'timeZone' => $eventTimezone);
        }

        // if event repeats or not
        if ($recurrence == 1) {
            // repeats weekly until XXXX
            // RRULE:FREQ=WEEKLY;UNTIL=XXXX

            $curlPost['recurrence'] = array("RRULE:FREQ=WEEKLY;UNTIL=" . str_replace('-', '', $recurrenceEnd) . ";");
        }
        $http_code = 0;
        $data = $this->curl(
            'https://www.googleapis.com/calendar/v3/calendars/' . $calendarId . '/events',
            json_encode($curlPost),
            $http_code
        );
        // echo '<pre>';print_r($data);echo '</pre>';
        if ($http_code != 200)
            throw new Exception('Error : Failed to create event');

        return $data['id'];
    }

    public function UpdateCalendarEvent($eventId, $calendarId, $summary, $allDay, $eventTime, $eventTimezone)
    {
        $this->refreshAccessToken();
        $curlPost = array('summary' => $summary);
        if ($allDay == 1) {
            $curlPost['start'] = array('date' => $eventTime['event_date']);
            $curlPost['end'] = array('date' => $eventTime['event_date']);
        } else {
            $curlPost['start'] = array('dateTime' => $eventTime['start_time'], 'timeZone' => $eventTimezone);
            $curlPost['end'] = array('dateTime' => $eventTime['end_time'], 'timeZone' => $eventTimezone);
        }
        $http_code = 0;
        $data = $this->curl(
            'https://www.googleapis.com/calendar/v3/calendars/' . $calendarId . '/events/' . $eventId,
            json_encode($curlPost),
            $http_code,
            'PUT'
        );
        //echo '<pre>';print_r($data);echo '</pre>';
        if ($http_code != 200)
            throw new Exception('Error : Failed to update event');
        return $data;
    }

    public function DeleteCalendarEvent($eventId, $calendarId)
    {
        $this->refreshAccessToken();
        $data = $this->curl(
            'https://www.googleapis.com/calendar/v3/calendars/' . $calendarId . '/events/' . $eventId,
            "",
            $http_code,
            'DELETE'
        );
        if ($http_code != 204)
            throw new Exception('Error : Failed to delete event');
        return $data;
    }
}
