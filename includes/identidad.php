<?php

function email_valido($email) {
    $email = trim((string) $email);
    if ($email === '' || strlen($email) > 160) {
        return false;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $partes = explode('@', $email);
    if (count($partes) !== 2 || strpos($partes[1], '.') === false) {
        return false;
    }
    return true;
}

function id_fiscal_validar($codigoPais, $valor) {
    $catalogo = paises();
    if (!isset($catalogo[$codigoPais])) {
        return ['ok' => false, 'valor' => '', 'error' => 'Elige un país de la lista.'];
    }
    $pais = $catalogo[$codigoPais];
    $nombre = $pais['id_fiscal'];
    $ejemplo = $pais['ejemplo'];
    $texto = trim((string) $valor);
    if ($texto === '') {
        return ['ok' => false, 'valor' => '', 'error' => 'Escribe el ' . $nombre . ' de la empresa. Ejemplo: ' . $ejemplo . '.'];
    }
    switch ($codigoPais) {
        case 'CL':
            return id_fiscal_rut_cl($texto, $ejemplo);
        case 'CO':
            return id_fiscal_nit_co($texto, $ejemplo);
        case 'MX':
            return id_fiscal_rfc_mx($texto, $ejemplo);
        case 'SV':
            return id_fiscal_nit_sv($texto, $ejemplo);
        case 'EC':
            return id_fiscal_ruc_ec($texto, $ejemplo);
        case 'PE':
            return id_fiscal_ruc_pe($texto, $ejemplo);
    }
    return ['ok' => false, 'valor' => '', 'error' => 'No pudimos validar el ' . $nombre . '.'];
}

function id_fiscal_error($nombre, $ejemplo) {
    return 'El ' . $nombre . ' no es válido. Ejemplo: ' . $ejemplo . '.';
}

function id_fiscal_digitos($valor) {
    return preg_replace('/\D+/', '', (string) $valor);
}

function id_fiscal_fecha($anio2, $mes, $dia) {
    $anio = 2000 + (int) $anio2;
    return checkdate((int) $mes, (int) $dia, $anio);
}

function id_fiscal_rut_cl($texto, $ejemplo) {
    $plano = strtoupper(preg_replace('/[^0-9K]/', '', $texto));
    if (!preg_match('/^[0-9]{7,8}[0-9K]$/', $plano)) {
        return ['ok' => false, 'valor' => '', 'error' => id_fiscal_error('RUT', $ejemplo)];
    }
    $cuerpo = substr($plano, 0, -1);
    $dv = substr($plano, -1);
    $suma = 0;
    $factor = 2;
    for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
        $suma += (int) $cuerpo[$i] * $factor;
        $factor = $factor === 7 ? 2 : $factor + 1;
    }
    $resto = 11 - ($suma % 11);
    $esperado = $resto === 11 ? '0' : ($resto === 10 ? 'K' : (string) $resto);
    if ($esperado !== $dv) {
        return ['ok' => false, 'valor' => '', 'error' => id_fiscal_error('RUT', $ejemplo)];
    }
    return ['ok' => true, 'valor' => $cuerpo . '-' . $dv, 'error' => ''];
}

function id_fiscal_nit_co($texto, $ejemplo) {
    $digitos = id_fiscal_digitos($texto);
    if (!preg_match('/^[0-9]{9,16}$/', $digitos)) {
        return ['ok' => false, 'valor' => '', 'error' => 'El NIT debe incluir el dígito de verificación. Ejemplo: ' . $ejemplo . '.'];
    }
    $base = substr($digitos, 0, -1);
    $dv = (int) substr($digitos, -1);
    $pesos = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
    $suma = 0;
    $largo = strlen($base);
    for ($i = 0; $i < $largo; $i++) {
        $suma += (int) $base[$largo - 1 - $i] * $pesos[$i];
    }
    $resto = $suma % 11;
    $esperado = ($resto === 0 || $resto === 1) ? $resto : 11 - $resto;
    if ($esperado !== $dv) {
        return ['ok' => false, 'valor' => '', 'error' => id_fiscal_error('NIT', $ejemplo)];
    }
    return ['ok' => true, 'valor' => $base . '-' . $dv, 'error' => ''];
}

function id_fiscal_rfc_mx($texto, $ejemplo) {
    $plano = strtoupper(preg_replace('/[\s\-]+/', '', $texto));
    if (function_exists('mb_strtoupper')) {
        $plano = mb_strtoupper($plano, 'UTF-8');
    }
    $chars = preg_split('//u', $plano, -1, PREG_SPLIT_NO_EMPTY);
    $largo = count($chars);
    if ($largo !== 12 && $largo !== 13) {
        return ['ok' => false, 'valor' => '', 'error' => id_fiscal_error('RFC', $ejemplo)];
    }
    $letras = $largo === 13 ? 4 : 3;
    $prefijo = implode('', array_slice($chars, 0, $letras));
    $fecha = implode('', array_slice($chars, $letras, 6));
    if (!preg_match('/^[A-ZÑ&]+$/u', $prefijo) || !preg_match('/^[0-9]{6}$/', $fecha)) {
        return ['ok' => false, 'valor' => '', 'error' => id_fiscal_error('RFC', $ejemplo)];
    }
    if (!id_fiscal_fecha(substr($fecha, 0, 2), substr($fecha, 2, 2), substr($fecha, 4, 2))) {
        return ['ok' => false, 'valor' => '', 'error' => 'La fecha dentro del RFC no es válida. Ejemplo: ' . $ejemplo . '.'];
    }
    $tabla = [];
    $orden = '0123456789ABCDEFGHIJKLMN&OPQRSTUVWXYZ Ñ';
    $ordenChars = preg_split('//u', $orden, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($ordenChars as $i => $char) {
        $tabla[$char] = $i;
    }
    $base = $chars;
    array_pop($base);
    if ($largo === 12) {
        array_unshift($base, ' ');
    }
    $suma = 0;
    $factor = 13;
    foreach ($base as $char) {
        if (!isset($tabla[$char])) {
            return ['ok' => false, 'valor' => '', 'error' => id_fiscal_error('RFC', $ejemplo)];
        }
        $suma += $tabla[$char] * $factor;
        $factor--;
    }
    $resto = $suma % 11;
    if ($resto === 0) {
        $esperado = '0';
    } elseif ($resto === 1) {
        $esperado = 'A';
    } else {
        $esperado = (string) (11 - $resto);
    }
    if ($chars[$largo - 1] !== $esperado) {
        return ['ok' => false, 'valor' => '', 'error' => id_fiscal_error('RFC', $ejemplo)];
    }
    return ['ok' => true, 'valor' => $plano, 'error' => ''];
}

function id_fiscal_nit_sv($texto, $ejemplo) {
    $digitos = id_fiscal_digitos($texto);
    if (!preg_match('/^[0-9]{14}$/', $digitos) || !in_array($digitos[0], ['0', '1', '9'], true)) {
        return ['ok' => false, 'valor' => '', 'error' => id_fiscal_error('NIT', $ejemplo)];
    }
    $dia = substr($digitos, 4, 2);
    $mes = substr($digitos, 6, 2);
    $anio = substr($digitos, 8, 2);
    if (!id_fiscal_fecha($anio, $mes, $dia)) {
        return ['ok' => false, 'valor' => '', 'error' => 'La fecha dentro del NIT no es válida. Ejemplo: ' . $ejemplo . '.'];
    }
    $correlativo = substr($digitos, 10, 3);
    if ($correlativo <= '100') {
        $pesos = [14, 13, 12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2];
        $suma = 0;
        for ($i = 0; $i < 13; $i++) {
            $suma += (int) $digitos[$i] * $pesos[$i];
        }
        $esperado = ($suma % 11) % 10;
    } else {
        $pesos = [2, 7, 6, 5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;
        for ($i = 0; $i < 13; $i++) {
            $suma += (int) $digitos[$i] * $pesos[$i];
        }
        $resto = $suma % 11;
        $esperado = $resto <= 1 ? 0 : 11 - $resto;
    }
    if ((int) $digitos[13] !== $esperado) {
        return ['ok' => false, 'valor' => '', 'error' => id_fiscal_error('NIT', $ejemplo)];
    }
    $bonito = substr($digitos, 0, 4) . '-' . substr($digitos, 4, 6) . '-' . substr($digitos, 10, 3) . '-' . $digitos[13];
    return ['ok' => true, 'valor' => $bonito, 'error' => ''];
}

function id_fiscal_ruc_ec($texto, $ejemplo) {
    $digitos = id_fiscal_digitos($texto);
    if (!preg_match('/^[0-9]{13}$/', $digitos)) {
        return ['ok' => false, 'valor' => '', 'error' => id_fiscal_error('RUC', $ejemplo)];
    }
    $provincia = (int) substr($digitos, 0, 2);
    if (($provincia < 1 || $provincia > 24) && $provincia !== 30) {
        return ['ok' => false, 'valor' => '', 'error' => id_fiscal_error('RUC', $ejemplo)];
    }
    $tercero = (int) $digitos[2];
    $ok = false;
    if ($tercero < 6) {
        $ok = id_fiscal_cedula_ec(substr($digitos, 0, 10)) && (int) substr($digitos, 10, 3) >= 1;
    } elseif ($tercero === 6) {
        $coef = [3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;
        for ($i = 0; $i < 8; $i++) {
            $suma += (int) $digitos[$i] * $coef[$i];
        }
        $resto = $suma % 11;
        $dv = $resto === 0 ? 0 : 11 - $resto;
        $ok = $dv === (int) $digitos[8] && (int) substr($digitos, 9, 4) >= 1;
    } elseif ($tercero === 9) {
        $coef = [4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;
        for ($i = 0; $i < 9; $i++) {
            $suma += (int) $digitos[$i] * $coef[$i];
        }
        $resto = $suma % 11;
        $dv = $resto === 0 ? 0 : 11 - $resto;
        $ok = $dv === (int) $digitos[9] && (int) substr($digitos, 10, 3) >= 1;
    }
    if (!$ok) {
        return ['ok' => false, 'valor' => '', 'error' => id_fiscal_error('RUC', $ejemplo)];
    }
    return ['ok' => true, 'valor' => $digitos, 'error' => ''];
}

function id_fiscal_cedula_ec($cedula) {
    if (!preg_match('/^[0-9]{10}$/', $cedula)) {
        return false;
    }
    $provincia = (int) substr($cedula, 0, 2);
    if (($provincia < 1 || $provincia > 24) && $provincia !== 30) {
        return false;
    }
    if ((int) $cedula[2] > 5) {
        return false;
    }
    $coef = [2, 1, 2, 1, 2, 1, 2, 1, 2];
    $suma = 0;
    for ($i = 0; $i < 9; $i++) {
        $valor = (int) $cedula[$i] * $coef[$i];
        if ($valor >= 10) {
            $valor -= 9;
        }
        $suma += $valor;
    }
    $dv = (10 - ($suma % 10)) % 10;
    return $dv === (int) $cedula[9];
}

function id_fiscal_ruc_pe($texto, $ejemplo) {
    $digitos = id_fiscal_digitos($texto);
    if (!preg_match('/^(10|15|16|17|20)[0-9]{9}$/', $digitos)) {
        return ['ok' => false, 'valor' => '', 'error' => id_fiscal_error('RUC', $ejemplo)];
    }
    $pesos = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
    $suma = 0;
    for ($i = 0; $i < 10; $i++) {
        $suma += (int) $digitos[$i] * $pesos[$i];
    }
    $resto = 11 - ($suma % 11);
    if ($resto === 10) {
        $esperado = 0;
    } elseif ($resto === 11) {
        $esperado = 1;
    } else {
        $esperado = $resto;
    }
    if ((int) $digitos[10] !== $esperado) {
        return ['ok' => false, 'valor' => '', 'error' => id_fiscal_error('RUC', $ejemplo)];
    }
    return ['ok' => true, 'valor' => $digitos, 'error' => ''];
}
