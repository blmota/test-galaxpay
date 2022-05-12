<?php
/**
 * DATABASE
 */
const CONF_DB_HOST = "localhost";
const CONF_DB_USER = "root";
const CONF_DB_PASS =  "";
const CONF_DB_NAME = "bluware";

/**
 * PROJECT URLs
 */
const CONF_URL_BASE = "https://www.bluware.com.br";
const CONF_URL_TEST = "https://www.localhost/galaxpay";

/**
 * SITE
 */
const CONF_SITE_NAME = "Bluware";
const CONF_SITE_TITLE = "SOFTWARE PARA CLUBES E ASSOCIAÇÕES";
const CONF_SITE_DESC = "A Bluware é uma empresa reconhecida nacionalmente como referência em software para Gestão Administrativa, Financeira e Controle de Acesso, exclusivamente para Clubes e Associações. Nossas soluções foram desenvolvidas para simplificar as operações de secretaria, aperfeiçoar os processos gerenciais e gerar mais retorno financeiro para o Clube e satisfação para os associados.";
const CONF_SITE_LANG = "pt_BR";
const CONF_SITE_DOMAIN = "bluware.com.br";
const CONF_SITE_ADDR_STREET = "R. Marechal Deodoro";
const CONF_SITE_ADDR_NUMBER = "830";
const CONF_SITE_ADDR_COMPLEMENT = "Sala 101";
const CONF_SITE_ADDR_CITY = "Blumenau";
const CONF_SITE_ADDR_STATE = "SC";
const CONF_SITE_ADDR_ZIPCODE = "";

/**
 * SOCIAL
 */
const CONF_SOCIAL_TWITTER_CREATOR = "#";
const CONF_SOCIAL_TWITTER_PUBLISHER = "#";
const CONF_SOCIAL_FACEBOOK_APP = "#";
const CONF_SOCIAL_FACEBOOK_PAGE = "#";
const CONF_SOCIAL_FACEBOOK_AUTHOR = "#";
const CONF_SOCIAL_GOOGLE_PAGE = "#";
const CONF_SOCIAL_GOOGLE_AUTHOR = "#";
const CONF_SOCIAL_INSTAGRAM_PAGE = "#";
const CONF_SOCIAL_YOUTUBE_PAGE = "#";

/*
 *  LOGIN OAUTH2 CLIENT
 * **/
const AUTH_FACEBOOK = [
    "app_id" => "",
    "app_secret" => "",
    "app_redirect" => "",
    "app_version" => "v4.0"
];

/**
 * CREDENTIAL GOOGLE API KEY
 */
const GOOGLE_API_KEY = "";

/**
 * DATES
 */
const CONF_DATE_BR = "d/m/Y H:i:s";
const CONF_DATE_APP = "Y-m-d H:i:s";

/**
 * PASSWORD
 */
const CONF_PASSWD_MIN_LEN = 8;
const CONF_PASSWD_MAX_LEN = 40;
const CONF_PASSWD_ALGO = PASSWORD_DEFAULT;
const CONF_PASSWD_OPTION = ["cost" => 10];

/**
 * VIEW
 */
const CONF_VIEW_PATH = __DIR__ . "/../../shared/views";
const CONF_VIEW_EXT = "php";
const CONF_VIEW_THEME = "";
const CONF_VIEW_APP = "";
const CONF_VIEW_ADMIN = "";

/**
 * UPLOAD
 */
const CONF_UPLOAD_DIR = "storage";
const CONF_UPLOAD_IMAGE_DIR = "images";
const CONF_UPLOAD_FILE_DIR = "files";
const CONF_UPLOAD_MEDIA_DIR = "medias";

/**
 * IMAGES
 */
const CONF_IMAGE_CACHE = CONF_UPLOAD_DIR . "/" . CONF_UPLOAD_IMAGE_DIR . "/cache";
const CONF_IMAGE_SIZE = 2000;
const CONF_IMAGE_QUALITY = ["jpg" => 75, "png" => 5];

/**
 * MAIL
 */
const CONF_MAIL_HOST = "smtp.sendgrid.net";
const CONF_MAIL_PORT = "587";
const CONF_MAIL_USER = "apikey";
const CONF_MAIL_PASS = "**********";
const CONF_MAIL_SENDER = ["name" => "Bluware", "address" => "email@email.com"];
const CONF_MAIL_SUPPORT = "email@email.com";
const CONF_MAIL_OPTION_LANG = "br";
const CONF_MAIL_OPTION_HTML = true;
const CONF_MAIL_OPTION_AUTH = true;
const CONF_MAIL_OPTION_SECURE = "tls";
const CONF_MAIL_OPTION_CHARSET = "utf-8";

/**
 * PAGAR.ME
 */
const CONF_PAGARME_MODE = "test";
const CONF_PAGARME_LIVE = "ak_live_*****";
const CONF_PAGARME_TEST = "ak_test_*****";
const CONF_PAGARME_BACK = CONF_URL_BASE . "/pay/callback";

/**
 * GALAXPAY
 */
const CONF_GALAXPAY_DEV = "https://api.sandbox.cloud.galaxpay.com.br/v2";
const CONF_GALAX_ID = "5473";
const CONF_GALAX_HASH = "83Mw5u8988Qj6fZqS4Z8K7LzOo1j28S706R0BeFe";

/*
 *  JWT
 * **/
const JWT_SECRET_KEY = "6bd9f25b606bbcf4e34d224dffe58b62"; // secret key to jwt authenticate