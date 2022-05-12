<?php

namespace Source\Objects\App\v1;

use Source\Models\Order;
use Source\Objects\User;

class OrderMap
{
    public static function map(Order $order): ?array
    {
        $order->amount = str_price($order->amount);
        $response = json_decode(json_encode($order->data()), true);

        unset(
            $response["user_id"],
            $response["user"]["token"]
        );

        return $response;
    }
}