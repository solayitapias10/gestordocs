
<?php
/********************************************
    Configuracion de conexion             
 ********************************************/
define('ROOT_PATH', dirname(__DIR__));
const BASE_URL = "https://gestordocs.alwaysdata.net/";
const HOST = "postgresql-gestordocs.alwaysdata.net";
const USER = "gestordocs";
const PASS = "Anyi22007";
const DB = "gestordocs_gestion_archivos";
const PORT = "5432";
const CHARSET = "charset=utf8";
const SECRET_KEY = "TuClaveSecreta2025!GestionDocs"; 
const GOOGLE_API_KEY = "AIzaSyCRM5PVcZuRliLGccykmpqbKkkTY9zHlsE";
const GOOGLE_CX_ID = "17f58a4640d1d4e6b";


/********************************************
    Configuracion del envio de correo                  
********************************************/

// Configuración SMTP
define('SMTP_HOST', 'smtp.gmail.com');  
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');         
define('SMTP_AUTH', true);
define('SMTP_USERNAME', 'solayitapias1@gmail.com');    
define('SMTP_PASSWORD', 'uwadfayhtzzakivm');       

// Información del remitente
define('MAIL_FROM_EMAIL', 'solayitapias1@gmail.com');
define('MAIL_FROM_NAME', 'Sistema de Gestión de Archivos');

// Configuración general
define('MAIL_CHARSET', 'UTF-8');
define('MAIL_IS_HTML', true);
?>
