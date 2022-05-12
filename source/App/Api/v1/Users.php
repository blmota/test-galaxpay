<?php

namespace Source\App\Api\v1;

use Source\App\Api\Api;
use Source\Models\AdUser;

class Users extends Api
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $user = \Source\Objects\User::model($this->user);

        $response["data"] = $user;
        $this->back($response);
        return;
    }
}