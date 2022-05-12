<?php

namespace Source\Models;

use Firebase\JWT\JWT;
use Source\Core\Model;
use Source\Core\Session;
use Source\Core\View;
use Source\Models\FlutterApp\ApiControl;
use Source\Support\Email;

/**
 * Class Auth
 * @package Source\Models
 */
class Auth extends Model
{
    /**
     * Auth constructor.
     */
    public function __construct()
    {
        parent::__construct("users", ["id"], ["email", "password"]);
    }

    /**
     * @return null|User
     */
    public static function user(): ?User
    {
        $session = new Session();
        if (!$session->has("authUser")) {
            return null;
        }

        return (new User())->findById($session->authUser);
    }

    public function user_api(int $user_id): ?User
    {
        if(empty($user_id)){
            return null;
        }

        return (new User())->findById($user_id);
    }

    /**
     * log-out
     */
    public static function logout(): void
    {
        $session = new Session();
        $session->unset("authUser");
    }

    /**
     * @param User $user
     * @return bool
     */
    public function register(User $user): bool
    {
        if (!$user->save()) {
            $this->message = $user->message;
            return false;
        }

        $view = new View(__DIR__ . "/../../shared/views/email");
        $message = $view->render("confirm", [
            "first_name" => $user->first_name,
            "confirm_link" => url("/obrigado/" . base64_encode($user->email))
        ]);

        (new Email())->bootstrap(
            "Ative sua conta no " . CONF_SITE_NAME,
            $message,
            $user->email,
            "{$user->first_name} {$user->last_name}"
        )->send();

        return true;
    }

    /**
     * @param string $email
     * @param string $password
     * @param int $level
     * @return User|null
     */
    public function attempt(string $email, string $password, int $level = 1): ?User
    {
        if (!is_email($email)) {
            $this->message->warning("O e-mail informado não é válido");
            return null;
        }

        if (!is_passwd($password)) {
            $this->message->warning("A senha informada não é válida");
            return null;
        }

        $user = (new User())->findByEmail($email);

        if (!$user) {
            $this->message->error("O e-mail informado não está cadastrado");
            return null;
        }

        if (!passwd_verify($password, $user->password)) {
            $this->message->error("A senha informada não confere");
            return null;
        }

        if ($user->level < $level) {
            $this->message->error("Desculpe, mas você não tem permissão para logar-se aqui");
            return null;
        }

        if (passwd_rehash($user->password)) {
            $user->password = $password;
            $user->save();
        }

        return $user;
    }

    public function attemptFacebook(string $token): ?User
    {
        $fb = new \Facebook\Facebook([
            'app_id' => AUTH_FACEBOOK["app_id"],
            'app_secret' => AUTH_FACEBOOK["app_secret"],
            'default_graph_version' => 'v2.10', // v2.10
            'default_access_token' => "{$token}", // optional
        ]);

        try {
            // Get the \Facebook\GraphNodes\GraphUser object for the current user.
            // If you provided a 'default_access_token', the '{access-token}' is optional.
            //$response = $fb->get('/me', "{$AccessToken}");
            $response = $fb->get('/me?fields=id,name,email,first_name,last_name');
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            $this->message->error('Graph returned an error: ' . $e->getMessage());

            $Log = new Monolog("auth_facebook_exception", $e->getMessage());
            $Log->alert();
            $Log->emergency();

            //var_dump($e->getMessage());
            return null;
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            $this->message->error('Facebook SDK returned an error: ' . $e->getMessage());

            $Log = new Monolog("auth_facebook_sdk", $e->getMessage());
            $Log->alert();
            $Log->emergency();

            //var_dump($e->getMessage());
            return null;
        }

        $me = $response->getGraphUser();

        //$field = $response->getGraphNode();

        $user = (new User())->findByEmail($me["email"]);

        if(empty($user)){
            $newUser = new User();
            $newUser->fb_userid = $me["id"];
            $newUser->fb_access_token = $token;
            $newUser->first_name = $me["first_name"];
            $newUser->last_name = $me["last_name"];
            $newUser->email = $me["email"];
            $newUser->level = 1;
            $newUser->photo = "https://graph.facebook.com/{$me['id']}/picture?width=600";
            $newUser->status = "confirmed";

            if(!$newUser->save()){
                $this->message->error("Desculpe, mas ocorreu um falha ao tentar logar com Facebook. Tente novamente ou entre em contato.");
                return null;
            }

            return $newUser;
        }

        if(!empty($user->g_userid) && empty($user->fb_userid)){
            $this->message->warning("Você já logou no app com esta conta de e-mail usando login com Google");
            return null;
        }

        if(!empty($user->a_userid) && empty($user->fb_userid)){
            $this->message->warning("Você já logou no app com esta conta de e-mail usando login com a Apple");
            return null;
        }

        $user->fb_userid = $me["id"];
        $user->fb_access_token = $token;
        if(count(explode("https:", $user->photo)) == 2){
            $user->photo = "https://graph.facebook.com/{$me['id']}/picture?width=600";
        }
        $user->save();

        return $user;
    }

    public function attemptGoogle(string $token): ?User
    {
        $payload = jwt_decode($token);

        $user = (new User())->findByEmail($payload['email']);

        if(!$user){
            $newUser = new User();
            $newUser->g_userid = $payload['sub'];
            $newUser->first_name = $payload['given_name'];
            $newUser->last_name = $payload['family_name'];
            $newUser->email = $payload['email'];
            $newUser->level = 1;
            $newUser->photo = $payload['picture'];
            $newUser->status = "registered";

            if(!$newUser->save()){
                $this->message->error("Desculpe, mas ocorreu um falha ao tentar logar com sua conta Google. Tente novamente ou entre em contato.");
                return null;
            }

            return $newUser;
        }

        if(!empty($user->fb_userid) && empty($user->g_userid)){
            $this->message->error("Você já logou no app com esta conta de e-mail usando login com Facebook");
            return null;
        }

        if(!empty($user->a_userid) && empty($user->g_userid)){
            $this->message->error("Você já logou no app com esta conta de e-mail usando login com a Apple");
            return null;
        }

        if(count(explode("https:", $user->photo)) == 2){
            $user->photo = $payload['picture'];
        }

        //var_dump($user);
        $user->save();

        return $user;
    }

    public function attemptApple(string $token, string $a_userid): ?User
    {
        //var_dump("APPLE AUTH");
        $payload = jwt_decode($token);

        $where = (!$payload["is_private_email"] ? " OR email = {$payload['email']}" : "");
        $user = (new User())->find("a_userid = :appleid" . $where,"appleid={$a_userid}")->fetch();

        if(!$user){
            if($payload["is_private_email"]){
                return null;
            }

            $newUser = new User();
            $newUser->a_userid = $a_userid;
            $newUser->first_name = strstr($payload["fullName"], ' ', true);
            $newUser->last_name = ltrim(strstr($payload["fullName"], ' ', false), " ");
            $newUser->email = $payload["email"];
            $newUser->level = 1;
            $newUser->photo = null;
            $newUser->status = "confirmed";

            //var_dump($newUser);

            if(!$newUser->save()){
                $this->message->error("Desculpe, mas ocorreu um falha ao tentar logar com sua conta Apple. Tente novamente ou entre em contato.");
                return null;
            }

            return $newUser;
        }

        $user->a_userid = $a_userid;
        if(count(explode("https:", $user->photo)) == 2){
            $user->photo = null;
        }
        $user->save();

        return $user;
    }

    /**
     * @param string $email
     * @param string $password
     * @param bool $save
     * @param int $level
     * @return bool
     */
    public function login(string $email, string $password, bool $save = false, int $level = 1): bool
    {
        $user = $this->attempt($email, $password, $level);
        if (!$user) {
            return false;
        }

        if ($save) {
            setcookie("authEmail", $email, time() + 604800, "/");
        } else {
            setcookie("authEmail", null, time() - 3600, "/");
        }

        //LOGIN
        (new Session())->set("authUser", $user->id);
        return true;
    }

    /**
     * @param string $email
     * @return bool
     */
    public function forget(string $email): bool
    {
        $user = (new User())->findByEmail($email);

        if (!$user) {
            $this->message->warning("O e-mail informado não está cadastrado.");
            return false;
        }

        $user->forget = md5(uniqid(rand(), true));
        $user->save();

        $view = new View(__DIR__ . "/../../shared/views/email");
        $message = $view->render("forget", [
            "first_name" => $user->first_name,
            "forget_link" => url("/recuperar/{$user->email}|{$user->forget}")
        ]);

        (new Email())->bootstrap(
            "Recupere sua senha no " . CONF_SITE_NAME,
            $message,
            $user->email,
            "{$user->first_name} {$user->last_name}"
        )->send();

        return true;
    }

    /**
     * @param string $email
     * @param string $code
     * @param string $password
     * @param string $passwordRe
     * @return bool
     */
    public function reset(string $email, string $code, string $password, string $passwordRe): bool
    {
        $user = (new User())->findByEmail($email);

        if (!$user) {
            $this->message->warning("A conta para recuperação não foi encontrada.");
            return false;
        }

        if ($user->forget != $code) {
            $this->message->error("Desculpe, mas o código de verificação não é válido.");
            return false;
        }

        if (!is_passwd($password)) {
            $min = CONF_PASSWD_MIN_LEN;
            $max = CONF_PASSWD_MAX_LEN;
            $this->message->info("Sua senha deve ter entre {$min} e {$max} caracteres.");
            return false;
        }

        if ($password != $passwordRe) {
            $this->message->warning("Você informou duas senhas diferentes.");
            return false;
        }

        $user->password = $password;
        $user->forget = null;
        $user->save();
        return true;
    }

    /**
     * @param int $user_id
     * @return string|null
     */
    public function jwt(int $user_id): ?string
    {
        if($user_id) {
            $payload = array(
                "iat" => time(),
                "sub" => $user_id
            );

            return JWT::encode($payload, JWT_SECRET_KEY);
        }

        return null;
    }

    /**
     * @param string $token
     * @return array
     */
    public function verify_jwt(string $token): array
    {
        try{
            $jwt = JWT::decode($token, JWT_SECRET_KEY, ["HS256"]);
            $jwt->status = true;
            return (array) $jwt;
        } catch (\Exception $err){
            //var_dump($err);
            return ["status" => false, "error" => $err->getMessage()];
        }
    }
}