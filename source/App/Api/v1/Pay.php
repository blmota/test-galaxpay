<?php

namespace Source\App\Api\v1;

use Source\App\Api\Api;

class Pay extends Api
{
    /** @var string */
    private $access_token;
    /** @var int */
    private $expires_in;
    /** @var string */
    private $scope;

    /** @var array */
    private $error;

    public function __construct()
    {
        parent::__construct();
    }

    public function auth(): void
    {
        $headers = [
            "Authorization: Basic " . base64_encode(CONF_GALAX_ID.":".CONF_GALAX_HASH)
        ];

        $body = [
            "grant_type" => "authorization_code",
            "scope" => "customers.read customers.write plans.read plans.write transactions.read transactions.write webhooks.write cards.read cards.write card-brands.read charges.read charges.write boletos.read"
        ];

        $curl_exec = json_decode(cUrl(CONF_GALAXPAY_DEV . "/token", $headers, json_encode($body), "post"));

//        echo "<pre>";
//        var_dump($curl_exec);
//        echo "</pre>";

        if(!empty($curl_exec->error)) {
            // error -> [ message, details[] ]
            $this->error = $curl_exec->error;
            return;
        }

        if(!empty($curl_exec->access_token)) {
            $this->access_token = $curl_exec->access_token;
            $this->expires_in = $curl_exec->expires_in;
            $this->scope = $curl_exec->scope;
        }

        var_dump($this->access_token, $this->expires_in, $this->scope);
    }

    public function getError(): ?array
    {
        return $this->error;
    }
}