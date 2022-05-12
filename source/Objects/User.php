<?php

namespace Source\Objects;

class User
{
    public static function model(\Source\Models\User $user): ?array
    {
        $user->datebirth = (!empty($user->datebirth) ? (new \DateTime($user->datebirth))->format('d/m/Y') : "");
        $user->created_at = (!empty($user->created_at) ? (new \DateTime($user->created_at))->format('d/m/Y') : "");

        $response = json_decode(json_encode($user->data()), true);

        unset(
            $response["password"],
            $response["forget"],
            $response["level"],
            $response["status"],
            $response["updated_at"]
        );

        return $response;
    }
}