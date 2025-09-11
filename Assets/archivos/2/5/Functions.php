
<?php

function time_ago($fecha)
{
    $diferencia = time() - $fecha;
    if ($diferencia < 1) {
        return 'Justo ahora';
    }
    $condicion = array(
        12 * 30 * 24 * 60 * 60  => 'año',
        30 * 24 * 60 * 60 => 'mes',
        24 * 60 * 60 => 'dia',
        60 * 60 => 'hora',
        60 => 'minuto',
        1 => 'segundo'
    );
    foreach ($condicion as $secs => $str) {
        $d = $diferencia / $secs;
        if ($d >= 1) {
            //redondear
            $t = round($d);
            return 'hace ' . $t . ' ' . $str . ($t > 1 ? 's' : '');
        }
    }
}


function formatearTamano($tamano)
{
    $unidades = array('B', 'KB', 'MB', 'GB', 'TB');
    $tamanoByte = $tamano;
    $i = 0;

    while ($tamanoByte >= 1024 && $i < count($unidades) - 1) {
        $tamanoByte /= 1024;
        $i++;
    }

    return round($tamanoByte, 2) . ' ' . $unidades[$i];
}

function formatBytes($bytes)
{
    if ($bytes > 0) {
        $i = floor(log($bytes) / log(1024));
        $sizes = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        return sprintf('%.2f', $bytes / pow(1024, $i)) * 1 . ' ' . $sizes[$i];
    }
    return '0 B';
}

?>