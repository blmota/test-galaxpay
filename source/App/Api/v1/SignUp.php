<?php

namespace Source\App\Api\v1;

use Source\Core\Controller;
use Source\Core\View;
use Source\Models\AdUser;
use Source\Models\Auth;
use Source\Models\User;
use Source\Support\Email;

class SignUp extends Controller
{
    /** @var \Source\Models\User|null */
    protected $user;

    /** @var array|false */
    protected $headers;

    /** @var array|null */
    protected $response;

    public function __construct()
    {
        parent::__construct("/");

        header('Content-Type: application/json; charset=UTF-8');
        $this->headers = getallheaders();
    }

    /**
     * @param array|null $data
     */
    public function register(?array $data): void
    {
        $auth = new Auth();
        $newUser = new User();
        $newUser->bootstrap(
            $data["first_name"],
            $data["last_name"],
            $data["email"],
            $data["password"]
        );

        // Se o registro de novo usuário falhar retorna mensagem
        if(!$auth->register($newUser)){
            $this->call(
                400,
                "invalid_data",
                $newUser->message()->getText()
            )->back();
            return;
        }

        if(!$newUser->save()) {
            $this->call(
                "400",
                $newUser->message()->getType(),
                $newUser->message()->getText()
            )->back();
            return;
        }

        // Efetua autenticação do novo usuário e gera o token JWT
        $user = $auth->attempt($data['email'],$data['password']);
        $user->token = $auth->jwt($user->id);

        // Formata retorno de dados do usuário
        $userArr = \Source\Objects\User::model($user);

        $response["data"] = $userArr;
        $this->back($response);
        return;
    }

    /**
     * @param array|null $data
     */
    public function recover(?array $data): void
    {
        if(empty($data["email"])) {
            $this->call(
                400,
                "invalid_data",
                "Ooops!! Você precisa informar o e-mail do seu cadastro para recuperar a senha."
            )->back();
            return;
        }

        // verifica se e-mail está cadastrado
        $has_user = (new User())->findByEmail($data["email"]);

        if(!$has_user->count()) {
            $this->call(
                400,
                "empty_data",
                "Ooops!! E-mail informado não consta no nosso cadastro."
            )->back();
            return;
        }

        $user = $has_user->fetch();

        $view = new View(__DIR__ . "/../../../../shared/views/email");
        $message = $view->render("forget", [
            "first_name" => $user->first_name,
            "forget_link" => url("/recuperar-senha/" . base64_encode($user->email))
        ]);

        (new Email())->bootstrap(
            "Pedido de recuperação de senha no " . CONF_SITE_NAME,
            $message,
            $user->email,
            "{$user->first_name} {$user->last_name}"
        )->send();
    }

    /**
     * @param int $code
     * @param string|null $type
     * @param string|null $message
     * @param string $rule
     * @return SignUp
     */
    protected function call(int $code, string $type = null, string $message = null, string $rule = "errors"): SignUp
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
     * @return SignUp
     */
    protected function back(array $response = null): SignUp
    {
        if (!empty($response)) {
            $this->response = (!empty($this->response) ? array_merge($this->response, $response) : $response);
        }

        echo json_encode($this->response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return $this;
    }
}