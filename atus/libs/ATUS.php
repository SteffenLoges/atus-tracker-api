<?php

namespace ATUS\libs;

if (!defined('ATUS')) {
  die('Direct access not permitted');
}

use ATUS\Release;


class ATUS
{

  static function init()
  {

    set_error_handler([__CLASS__, 'errorHandler']);
    set_exception_handler([__CLASS__, 'exceptionHandler']);
    spl_autoload_register([__CLASS__, 'autoloader']);

    if (!isset($_GET['authentication']) || $_GET['authentication'] !== ATUS__AUTHENTICATION_TOKEN) {
      throw new \Exception('Invalid authentication token');
    }

    if (!isset($_GET['action'])) {
      throw new \Exception('No action specified');
    }

    if ($_GET['action'] === 'upload') {
      return self::handleUploadRequest();
    }

    throw new \Exception('Invalid action');
  }

  static function autoloader($class)
  {
    $class = __DIR__ . '/../' . str_replace('\\', '/', str_ireplace('ATUS\\', '', $class)) . '.php';

    if (!file_exists($class)) {
      die('Class not found: ' . $class);
      throw new \Exception('Class not found: ' . $class);
    }

    require_once $class;
  }

  static function errorHandler($errno, $errstr, $errfile, $errline)
  {
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
  }

  static function exceptionHandler($exception)
  {
    http_response_code(500);

    $ret =  [
      'success' => false,
      'message' => $exception->getMessage(),
    ];

    if (ATUS__DEBUG) {
      $ret['trace'] = $exception->getTrace();
      $ret['code'] = $exception->getCode();
      $ret['file'] = $exception->getFile();
      $ret['line'] = $exception->getLine();
    }

    echo json_encode($ret);
  }


  private static $db;

  static function getDB()
  {
    $dbConfig = [
      'host' => ATUS__DB_HOST,
      'port' => ATUS__DB_PORT,
      'dbname' => ATUS__DB_NAME,
    ];

    if (defined('ATUS__DB_CHARSET')) {
      $dbConfig['charset'] = ATUS__DB_CHARSET;
    }

    if (!self::$db) {
      $dsn = array_map(function ($k, $v) {
        return "$k=$v";
      }, array_keys($dbConfig), $dbConfig);

      self::$db = new \PDO(
        'mysql:' . implode(';', $dsn),
        ATUS__DB_USER,
        ATUS__DB_PASS
      );
    }

    return self::$db;
  }

  static function handleUploadRequest()
  {

    foreach (['torrent', 'nfo'] as $key) {
      if (!isset($_FILES[$key])) {
        throw new \Exception('Missing file: ' . $key);
      }
    }

    foreach (['hash', 'name', 'category', 'categoryRaw', 'pre', 'fileList', 'metaFiles', 'userID'] as $key) {
      if (!isset($_POST[$key])) {
        throw new \Exception('Missing post key: ' . $key);
      }
    }

    $release = new Release(
      $_FILES['torrent']['tmp_name'],
      $_FILES['nfo']['tmp_name'],
      $_POST['hash'],
      $_POST['name'],
      $_POST['category'],
      $_POST['categoryRaw'],
      $_POST['pre'],
      json_decode($_POST['fileList'], true),
      json_decode($_POST['metaFiles'], true),
      $_POST['userID']
    );

    $release->upload();

    echo json_encode([
      'success' => true,
      'message' => 'Upload successful',
    ]);
  }
}
