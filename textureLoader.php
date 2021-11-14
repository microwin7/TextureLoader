<?php
#
# Скрипт загрузки скина и плаща
#
# https://github.com/microwin7/TextureLoader
#
start();
class Constants
{
    const DB_HOST = "127.0.0.1";
    const DB_PORT = 3306;
    const DB_USER = "servs";
    const DB_PASS = "pass";
    const DB_DB = "TEST_GRAVIT_MYSQL_5.2.0";
    const TB_USER = "users";
    const DEBUG = true; // Сохранение в файл debug.log !!! Не устанавливайте true навсегда и не забудьте после настройки удалить файл debug.log из папки
    const SKIN_PATH = "./skins_test/"; // Сюда вписать путь до skins/
    const CLOAK_PATH = "./cloaks_test/"; // Сюда вписать путь до cloaks/
    const REGEX_USERNAME = "\w{1,16}$";
    const REGEX_UUIDv4 = "\b[0-9a-f]{8}\b-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-\b[0-9a-f]{12}\b";
    const REGEX_UUIDv1 = "[a-f0-9]{8}\-[a-f0-9]{4}\-4[a-f0-9]{3}\-(8|9|a|b)[a-f0-9]{4}\-[a-f0-9]{12}";
    const MAX_FILE_SIZE = 300000;
    const VALIDATE_SIZE = array(
        "skin" => array(
            array('w' => 64, 'h' => 64), array('w' => 64, 'h' => 32), array('w' => 128, 'h' => 64),
            array('w' => 256, 'h' => 128), array('w' => 512, 'h' => 256), array('w' => 1024, 'h' => 512)
        ),
        "cloak" => array(
            array('w' => 64, 'h' => 32), array('w' => 128, 'h' => 64), array('w' => 256, 'h' => 128),
            array('w' => 512, 'h' => 256), array('w' => 1024, 'h' => 512)
        )
    );
}
class Occurrences
{
    public static $requiredUrl = null;
    public static $data = null;
    public static $mainDB = null;
    public static $login = null;
    public static $accessToken = null;
    public static $uuid = null;
    public static $url = null;

    function __construct()
    {
        self::requiredUrl();
        self::getData();
        self::getLogin();
        self::getAccessToken();
        self::getUUID();
        self::getUrl();
    }
    public static function requiredUrl(): string
    {
        if (self::$requiredUrl == null) {
            $requiredUrl = $_SERVER['QUERY_STRING'];
            exists($requiredUrl) ?: response(array('error' => 'null_requiredUrl'));
            return self::$requiredUrl = $requiredUrl;
        } else return self::$requiredUrl;
    }
    public static function initMainDB()
    {
        if (self::$mainDB == null)
            self::$mainDB = new db();
    }
    public static function getData()
    {
        if (self::$data == null) {
            $data = json_decode(file_get_contents('php://input'), true);
            if ($data === null) response(array('error' => 'null_data'));
            return self::$data = $data;
        } else return self::$data;
    }
    public static function getLogin()
    {
        if (self::$login == null) {
            $login = (string) self::getData()['login'];
            exists($login) ?: response(array('error' => 'null_login'));
            preg_match("/^" . Constants::REGEX_USERNAME . "/", $login) ?: response(array('error' => 'invalid_login'));
            return self::$login = $login;
        } else return self::$login;
    }
    public static function getAccessToken()
    {
        if (self::$accessToken == null) {
            $accessToken = (string) self::getData()['accessToken'];
            exists($accessToken) ?: response(array('error' => 'null_accessToken'));
            return self::$accessToken = $accessToken;
        } else return self::$accessToken;
    }
    public static function getUUID()
    {
        if (self::$uuid == null) {
            $uuid = (string) self::getData()['uuid'];
            exists($uuid) ?: response(array('error' => 'null_uuid'));
            (preg_match("/" . Constants::REGEX_UUIDv1 . "/", $uuid) ||
                preg_match("/" . Constants::REGEX_UUIDv4 . "/", $uuid)) ?: response(array('error' => 'invalid_uuid'));
            return self::$uuid = $uuid;
        } else return self::$uuid;;
    }
    public static function getUrl()
    {
        if (self::$url == null) {
            $url = (string) self::getData()['url'];
            exists($url) ?: response(array('error' => 'null_url'));
            filter_var($url, FILTER_VALIDATE_URL,  FILTER_FLAG_PATH_REQUIRED) ?: response(array('error' => 'invalid_url'));
            return self::$url = $url;
        } else return self::$url;
    }
}
class Methods
{
    public static function getTextureDownloadUrl($url)
    {
        $data = file_get_contents($url, false, stream_context_create(['http' => ['ignore_errors' => true]]), 0, Constants::MAX_FILE_SIZE);
        $headers = self::parseHeaders($http_response_header);
        if (Constants::DEBUG) {
            foreach ($headers as $key => $value) {
                debug_log('HEADERS', $key . ': ' . $value);
            }
        }
        ($headers['reponse_code'] == 200) ?: response(array('error' => 'invalid_response'));
        ($headers['Content-Type'] == "application/octet-stream" || $headers['Content-Type'] == "image/png") ?: response(array('error' => 'invalid_headers_url'));
        return $data;
    }
    private static function parseHeaders($headers)
    {
        $head = array();
        foreach ($headers as $key => $value) {
            $t = explode(':', $value, 2);
            if (isset($t[1]))
                $head[trim($t[0])] = trim($t[1]);
            else {
                $head[] = $value;
                if (preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $value, $out))
                    $head['reponse_code'] = intval($out[1]);
            }
        }
        return $head;
    }
}
class Check
{
    public static function skin()
    {
        self::handle(Constants::SKIN_PATH);
    }
    public static function cloak()
    {
        self::handle(Constants::CLOAK_PATH);
    }
    private static function handle($path)
    {
        $data = Methods::getTextureDownloadUrl(Occurrences::$url);
        if (Occurrences::$requiredUrl == "skin") !self::slim($data) ?: response(array('error' => 'slim_not_supported'));
        $mime = self::getImageMimeType($data);
        if ($mime != "png") response(array('error' => 'resource_not_png'));
        $image = imagecreatefromstring($data);
        $w = imagesx($image);
        $h = imagesy($image);
        $sizes = Constants::VALIDATE_SIZE[Occurrences::$requiredUrl];
        $valid_size = false;
        foreach ($sizes as $key => $value) {
            if ($value['w'] == $w && $value['h'] == $h) $valid_size = true;
        }
        $valid_size ?: response(array('error' => 'invalid_size_png'));
        if (file_put_contents($path . Occurrences::$login . ".png", $data)) response(array('success' => true));
    }
    private static function getBytesFromHexString($hexdata)
    {
        for ($count = 0; $count < strlen($hexdata); $count += 2)
            $bytes[] = chr(hexdec(substr($hexdata, $count, 2)));
        return implode($bytes);
    }
    private static function getImageMimeType($imagedata)
    {
        $imagemimetypes = array(
            "jpeg" => "FFD8",
            "png" => "89504E470D0A1A0A",
            "gif" => "474946",
            "bmp" => "424D",
            "tiff" => "4949",
            "tiff" => "4D4D"
        );
        foreach ($imagemimetypes as $mime => $hexbytes) {
            $bytes = self::getBytesFromHexString($hexbytes);
            if (substr($imagedata, 0, strlen($bytes)) == $bytes)
                return $mime;
        }
        return NULL;
    }
    private static function slim($data): bool
    {
        $image = imagecreatefromstring($data);
        $fraction = imagesx($image) / 8;
        $x = $fraction * 6.75;
        $y = $fraction * 2.5;
        $rgba = imagecolorsforindex($image, imagecolorat($image, $x, $y));
        if ($rgba["alpha"] === 127)
            return true;
        else return false;
    }
}
function start()
{
    logs();
    $oc = new Occurrences();
    $requiredUrl = $oc::$requiredUrl;
    $oc::initMainDB();
    $tb = Constants::TB_USER;
    $qr = $oc::$mainDB->query("SELECT `id` FROM $tb WHERE `login`=? AND `uuid` = ? AND `accessToken` = ? LIMIT 1", "sss", $oc::$login, $oc::$uuid, $oc::$accessToken)->fetch_assoc();
    if ($qr == null) response(array('error' => 'not_found'));
    $check = new Check();
    $check->$requiredUrl();
}
function logs()
{
    if (Constants::DEBUG) {
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        debug_log('RequiredUrl', Occurrences::requiredUrl());
        foreach (Occurrences::getData() as $key => $value) {
            debug_log('DATA', $key . ': ' . $value);
        }
    }
}
function debug_log($what, $log)
{
    if (Constants::DEBUG) {
        file_put_contents("debug.log", date('d.m.Y H:i:s - ') . "[$what]: " . $log . "\n", FILE_APPEND);
    }
}
function response($msg = null)
{
    header("Content-Type: application/json; charset=UTF-8");
    die(json_encode((object) $msg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
function exists(...$var)
{
    $i = true;
    foreach ($var as $v) {
        $i = (!empty($v) && isset($v) && $i) ? true : false;
    }
    return $i;
}
class db
{
    private $mysqli;
    private $last;
    public function __construct()
    {
        $this->mysqli = new mysqli(Constants::DB_HOST, Constants::DB_USER, Constants::DB_PASS, Constants::DB_DB, Constants::DB_PORT);
        if ($this->mysqli->connect_errno) {
            $this->debug("Connect error: " . $this->mysqli->connect_error);
        }
        $this->mysqli->set_charset("utf8");
    }
    public function __destruct()
    {
        $this->close();
    }
    public function close()
    {
        if (!is_null($this->mysqli)) {
            $this->mysqli->close();
        }
    }
    function refValues($arr)
    {
        $refs = array();
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }
    private function argsToString($args)
    {
        if (count($args) == 0)
            return "";
        $str = $args[0] . "";
        for ($i = 1; $i < count($args); ++$i) {
            $str .= ", " . $args[$i];
        }
        return $str;
    }
    public function query($sql, $form = "", ...$args)
    {
        $this->debug(" Executing query " . $sql . " with params: $form ->" . $this->argsToString($args));
        $stmt = $this->mysqli->prepare($sql);
        if ($this->mysqli->errno) {
            $this->debug('Statement preparing error[1]: ' . $this->mysqli->error . " ($sql)");
            exit();
        }
        array_unshift($args, $form);
        if ($form != "") {
            call_user_func_array(array($stmt, "bind_param"), $this->refValues($args));
        }
        $stmt->execute();
        if ($stmt->errno) {
            $this->debug("Statement execution error: " . $stmt->error . "($sql)");
            exit();
        }
        $this->last = $stmt->get_result();
        $stmt->close();
        return $this->last;
    }
    public function assoc()
    {
        if ($this->last === null) {
            return null;
        }
        return $this->last->fetch_assoc();
    }
    public function all()
    {
        if ($this->last === null) {
            return null;
        }
        return $this->last->fetch_all();
    }
    public function debug($message)
    {
        if (Constants::DEBUG) {
            file_put_contents("debug.log", date('d.m.Y H:i:s - ') . $message . "\n", FILE_APPEND);
        }
    }
}
