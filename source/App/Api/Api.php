<?php

namespace Source\App\Api;

use Source\Core\Controller;
use Source\Models\Auth;

class Api extends Controller
{
    /** @var \Source\Models\User|null */
    protected $user;

    /** @var array|false */
    protected $headers;

    /** @var array|null */
    protected $response;

    protected $version;

    /**
     * CafeApi constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct("/");

        header('Content-Type: application/json; charset=UTF-8');
        $this->headers = getallheaders();

        if((!empty($this->headers["action"]) && $this->headers["action"] != "register") || empty($this->headers["action"])){
            $auth = $this->auth();
            if (!$auth) {
                exit;
            }
        }

        $version_api = "'\'v1";
        if(count(explode('/', $_SERVER['REQUEST_URI'])) > 3){
            $version_api = "'\'" . explode('/', $_SERVER['REQUEST_URI'])[3];
        }
        $this->version = str_replace("'", "", $version_api);
    }

    /**
     * @param int $code
     * @param string|null $type
     * @param string|null $message
     * @param string $rule
     * @return Api
     */
    protected function call(int $code, string $type = null, string $message = null, string $rule = "errors"): Api
    {
        http_response_code($code);

        if (!empty($type)) {
            $this->response = [
                $rule => [
                    "type" => $type,
                    "message" => (!empty($message) ? $message : null)
                ]
            ];
        }
        return $this;
    }

    /**
     * @param array|null $response
     * @return Api
     */
    protected function back(array $response = null): Api
    {
        if (!empty($response)) {
            $this->response = (!empty($this->response) ? array_merge($this->response, $response) : $response);
        }

        echo json_encode($this->response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return $this;
    }

    /**
     * @return bool
     */
    private function auth(): bool
    {
        if(empty($this->headers["token"]) && empty($this->headers["action"])){
            $this->call(
                400,
                "auth_empty",
                "Ação de login não informada."
            )->back();
            return false;
        }

        $auth = new Auth();
        if(!empty($this->headers["token"])){
            // jwt token authenticate
            $valid = $auth->verify_jwt($this->headers["token"]);
            if(!$valid["status"]){
                $this->call(
                    400,
                    "auth_empty",
                    "Token inválido!"
                )->back();
                return false;
            }

            $user = $auth->user_api($valid["sub"]);
        }else {
            // action to login
            switch ($this->headers["action"]) {
                case"email":
                    $endpoint = ["apiAuth", 3, 60];
                    $request = $this->requestLimit($endpoint[0], $endpoint[1], $endpoint[2], true);

                    if (!$request) {
                        return false;
                    }

                    if (empty($this->headers["email"]) || empty($this->headers["password"])) {
                        $this->call(
                            400,
                            "auth_empty",
                            "Favor informe seu e-mail e senha"
                        )->back();
                        return false;
                    }

                    $user = $auth->attempt($this->headers["email"], $this->headers["password"], 1);
                    break;
                case"facebook":
                    if(empty($this->headers["accesstoken"])){
                        $this->call(
                            400,
                            "auth_empty",
                            "Token de acesso não informado"
                        )->back();
                        return false;
                    }

                    $user = $auth->attemptFacebook($this->headers["accesstoken"]);
                    if(empty($user)){
                        $this->call(
                            400,
                            "auth_empty",
                            $auth->message()->getText()
                        )->back();
                        return false;
                    }
                    break;
                case"google":
                    if(empty($this->headers["accesstoken"])){
                        $this->call(
                            400,
                            "auth_empty",
                            "Token de acesso não informado"
                        )->back();
                        return false;
                    }

                    $user = $auth->attemptGoogle($this->headers["accesstoken"]);
                    if(empty($user)){
                        $this->call(
                            400,
                            "auth_empty",
                            $auth->message()->getText()
                        )->back();
                        return false;
                    }
                    break;
                case"apple":
                    if(empty($this->headers["accesstoken"])){
                        $this->call(
                            400,
                            "auth_empty",
                            "Token de acesso não informado"
                        )->back();
                        return false;
                    }

                    if(empty($this->headers["a_userid"])){
                        $this->call(
                            400,
                            "invalid_data",
                            "Apple ID não informado"
                        )->back();
                        return false;
                    }

                    $user = $auth->attemptApple($this->headers["accesstoken"], $this->headers["a_userid"]);

                    if(empty($user)){
                        $applePayload = jwt_decode($this->headers["accesstoken"]);
                        if($applePayload["is_private_email"]){
                            $this->back(["data" => ["email" => null]]);
                        }
                    }
                    break;
            }
        }

        if (empty($user)) {
            if(empty($endpoint)){
                return false; // when user null and case social login
            }

            $this->requestLimit($endpoint[0], $endpoint[1], $endpoint[2]);
            $this->call(
                401,
                "invalid_auth",
                $auth->message()->getText()
            )->back();
            return false;
        }

        $user->token = $auth->jwt($user->id);
        $this->user = $user;
        return true;
    }

    /**
     * @param string $endpoint
     * @param int $limit
     * @param int $seconds
     * @param bool $attempt
     * @return bool
     */
    protected function requestLimit(string $endpoint, int $limit, int $seconds, bool $attempt = false): bool
    {
        $userToken = (!empty($this->headers["email"]) ? base64_encode($this->headers["email"]) : null);

        if (!$userToken) {
            $this->call(
                400,
                "invalid_data",
                "Você precisa informar seu e-mail e senha para continuar"
            )->back();

            return false;
        }

        $cacheDir = __DIR__ . "/../../../" . CONF_UPLOAD_DIR . "/requests";
        if (!file_exists($cacheDir) || !is_dir($cacheDir)) {
            mkdir($cacheDir, 0755);
        }

        $cacheFile = "{$cacheDir}/{$userToken}.json";
        if (!file_exists($cacheFile) || !is_file($cacheFile)) {
            fopen($cacheFile, "w");
        }

        $userCache = json_decode(file_get_contents($cacheFile));
        $cache = (array)$userCache;

        $save = function ($cacheFile, $cache) {
            $saveCache = fopen($cacheFile, "w");
            fwrite($saveCache, json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fclose($saveCache);
        };

        if (empty($cache[$endpoint]) || $cache[$endpoint]->time <= time()) {
            if (!$attempt) {
                $cache[$endpoint] = [
                    "limit" => $limit,
                    "requests" => 1,
                    "time" => time() + $seconds
                ];

                $save($cacheFile, $cache);
            }

            return true;
        }

        if ($cache[$endpoint]->requests >= $limit) {
            $this->call(
                400,
                "request_limit",
                "Você exedeu o limite de requisições para essa ação"
            )->back();

            return false;
        }

        if (!$attempt) {
            $cache[$endpoint] = [
                "limit" => $limit,
                "requests" => $cache[$endpoint]->requests + 1,
                "time" => $cache[$endpoint]->time
            ];

            $save($cacheFile, $cache);
        }
        return true;
    }
}