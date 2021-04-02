<?php function curl_route($url, $data)
{
    // Initialize curl object
    try {
        $ch = curl_init();
        if ($ch === false) {
            throw new Exception('failed to initialize');
        }
        // Set curl options
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => 1, // Return information from server
            CURLOPT_URL => $url,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_POST => 1, // Normal HTTP post
            CURLOPT_POSTFIELDS => $data
        ));

        // Execute curl and return result to $response
        $response = curl_exec($ch);
        var_dump($response);
        // Close request
        if ($response === false || $response == null) {
            throw new Exception(curl_error($ch), curl_errno($ch));
        }
        curl_close($ch);
    } catch (Exception $e) {

        trigger_error(
            sprintf(
                'Curl failed with error #%d: %s',
                $e->getCode(),
                $e->getMessage()
            ),
            E_USER_ERROR
        );
    }
    return $response;
}

function CallAPI($method, $url, $data = false)
{
    $curl = curl_init();

    switch ($method) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    // Optional Authentication:
    /*     curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, "username:password"); */

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
}
