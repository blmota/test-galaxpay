<?php

namespace Source\Models;

use Source\Core\Model;

class Order extends Model
{
    public function __construct()
    {
        parent::__construct("app_orders",["id"],["user_id","title","amount"]);
    }

    public function bootstrap(
        int $user_id,
        String $title,
        String $amount,
        String $status
    )
    {
        $this->user_id = $user_id;
        $this->title = $title;
        $this->amount = $amount;
        $this->status = $status;
    }
}