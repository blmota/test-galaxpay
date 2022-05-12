<?php

namespace Source\App\Api\v1;

use Source\App\Api\Api;
use Source\Models\Order;
use Source\Objects\App\v1\OrderMap;
use Source\Objects\User;

class Orders extends Api
{
    public function __construct()
    {
        parent::__construct();
    }

    public function list(): void
    {
        $orders = (new Order())->find("user_id = :user","user={$this->user->id}");

        if(!$orders->count()) {
            $response["data"] = [];
            $response["message"] = "Ooops!! Nenhum pedido encontrado.";
            $this->back($response);
            return;
        }

        $list = [];
        foreach ($orders->order("updated_at DESC")->fetch(true) as $order) {
            $order->user = User::model($this->user);
            $list[] = OrderMap::map($order);
        }

        $response["data"] = $list;
        $this->back($response);
    }

    public function create(?array $data): void
    {
        $newOrder = new Order();
        $newOrder->bootstrap(
            $this->user->id,
            $data["title"],
            str_price_db($data["amount"]),
            "waiting"
        );

        if(!$newOrder->save()) {
            $this->call(
                "400",
                $newOrder->message()->getType(),
                $newOrder->message()->getText()
            )->back();
            return;
        }

        $newOrder->user = User::model($this->user);
        $response["data"] = OrderMap::map($newOrder);
        $this->back($response);
    }

    public function update(?array $data): void
    {
        if(empty($data["id"])) {
            $this->call(
                "400",
                "invalid_data",
                "Ooops!! Informe o ID do pedido para realizar o update."
            )->back();
            return;
        }

        $order = (new Order())->findById($data["id"]);

        if(!$order) {
            $this->call(
                "400",
                "empty_data",
                "Ooops!! Pedido não existe ou foi removido recentemente."
            )->back();
            return;
        }

        $order->title = $data["title"];
        $order->amount = str_price_db($data["amount"]);
        $order->status = $data["status"];

        if(!$order->save()) {
            $this->call(
                "400",
                $order->message()->getType(),
                $order->message()->getText()
            )->back();
            return;
        }

        $order->user = User::model($this->user);
        $response["data"] = OrderMap::map($order);
        $this->back($response);
    }
}