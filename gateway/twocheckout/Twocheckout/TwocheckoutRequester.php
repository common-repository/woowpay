<?php

class Twocheckout_Requester
{
    public $apiUrl;
    private $privateKey;

    function __construct() {
        $this->privateKey = TwocheckoutApi::$privateKey;
        $this->apiUrl = TwocheckoutApi::$apiUrl;
    }

    function do_call($data)
    {
        $data['privateKey'] = $this->privateKey;
        $data = json_encode($data);
        $url = $this->apiUrl;

        $params     = array(
            'timeout'  => 120,
            'blocking' => true,
            'headers'  => array(
                'Content-Type' => 'application/json',
                // 'Content-Typent-length' => strlen($data)
            ),
            'body'     => $data
        );
        $connection = wp_remote_post( $url, $params );
        if( !is_wp_error($connection) ) {
            return $connection['body'];
        }else {
            throw new Twocheckout_Error("cURL call failed", "403");
        }
    }

}