<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
date_default_timezone_set('America/Lima');
require_once 'Config/Config.php';
require_once 'Config/Functions.php';

$ruta = !empty($_GET['url']) ? $_GET['url'] : "principal/index";
$array = explode("/", $ruta);
$controller = ucfirst($array[0]);
$metodo = "index";
$parametros = [];

if (!empty($array[1])) {
    if ($array[1] != "") {
        $metodo = $array[1];
    }
}

if (count($array) > 2) {
    $parametros = array_slice($array, 2);
}

require_once 'Config/App/Autoload.php';
$dirControllers = "Controllers/" . $controller . ".php";

if (file_exists($dirControllers)) {
    require_once $dirControllers;
    $controller = new $controller();
    if (method_exists($controller, $metodo)) {
        call_user_func_array([$controller, $metodo], $parametros);
    } else {
        // Redirige al nuevo controlador de errores si el método no existe
        header('Location: ' . BASE_URL . 'error');
        exit;
    }
} else {
    // Redirige al nuevo controlador de errores si el controlador no existe
    header('Location: ' . BASE_URL . 'error');
    exit;
}
