<?php
/********************************************
    Configuracion de conexion             
 ********************************************/
const BASE_URL = "http://localhost/gestordocs/";
const HOST = "localhost"; 
const PORT = "5432"; 
const USER = "postgres"; 
const PASS = "PgSena2024"; 
const DB = "gestion_archivo";
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