<?php

declare(strict_types=1);

namespace App\Libraries;

class IdFiscal
{
    /**
     * @return array{ok: bool, valor: string, error: string}
     */
    public static function validar(string $codigoPais, string $valor): array
    {
        $pais = Paises::pais($codigoPais);
        if ($pais === null) {
            return self::falla('Elige un país de la lista.');
        }

        $texto = trim($valor);
        if ($texto === '') {
            return self::falla('Escribe el ' . $pais['id_fiscal'] . ' de la empresa. Ejemplo: ' . $pais['ejemplo'] . '.');
        }

        return match ($codigoPais) {
            'CL'    => self::rutCl($texto, $pais['ejemplo']),
            'CO'    => self::nitCo($texto, $pais['ejemplo']),
            'MX'    => self::rfcMx($texto, $pais['ejemplo']),
            'SV'    => self::nitSv($texto, $pais['ejemplo']),
            'EC'    => self::rucEc($texto, $pais['ejemplo']),
            'PE'    => self::rucPe($texto, $pais['ejemplo']),
            default => self::falla('No pudimos validar el ' . $pais['id_fiscal'] . '.'),
        };
    }

    public static function emailValido(string $email): bool
    {
        $email = trim($email);
        if ($email === '' || strlen($email) > 160 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $partes = explode('@', $email);

        return count($partes) === 2 && str_contains($partes[1], '.');
    }

    /**
     * @return array{ok: bool, valor: string, error: string}
     */
    private static function falla(string $error): array
    {
        return ['ok' => false, 'valor' => '', 'error' => $error];
    }

    /**
     * @return array{ok: bool, valor: string, error: string}
     */
    private static function ok(string $valor): array
    {
        return ['ok' => true, 'valor' => $valor, 'error' => ''];
    }

    private static function errorDocumento(string $nombre, string $ejemplo): string
    {
        return 'El ' . $nombre . ' no es válido. Ejemplo: ' . $ejemplo . '.';
    }

    private static function digitos(string $valor): string
    {
        return (string) preg_replace('/\D+/', '', $valor);
    }

    private static function fecha(string $anio2, string $mes, string $dia): bool
    {
        return checkdate((int) $mes, (int) $dia, 2000 + (int) $anio2);
    }

    /**
     * @return array{ok: bool, valor: string, error: string}
     */
    private static function rutCl(string $texto, string $ejemplo): array
    {
        $plano = strtoupper((string) preg_replace('/[^0-9K]/', '', $texto));
        if (preg_match('/^[0-9]{7,8}[0-9K]$/', $plano) !== 1) {
            return self::falla(self::errorDocumento('RUT', $ejemplo));
        }

        $cuerpo = substr($plano, 0, -1);
        $dv     = substr($plano, -1);
        $suma   = 0;
        $factor = 2;
        for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
            $suma += (int) $cuerpo[$i] * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }
        $resto    = 11 - ($suma % 11);
        $esperado = $resto === 11 ? '0' : ($resto === 10 ? 'K' : (string) $resto);
        if ($esperado !== $dv) {
            return self::falla(self::errorDocumento('RUT', $ejemplo));
        }

        return self::ok($cuerpo . '-' . $dv);
    }

    /**
     * @return array{ok: bool, valor: string, error: string}
     */
    private static function nitCo(string $texto, string $ejemplo): array
    {
        $digitos = self::digitos($texto);
        if (preg_match('/^[0-9]{9,16}$/', $digitos) !== 1) {
            return self::falla('El NIT debe incluir el dígito de verificación. Ejemplo: ' . $ejemplo . '.');
        }

        $base  = substr($digitos, 0, -1);
        $dv    = (int) substr($digitos, -1);
        $pesos = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
        $suma  = 0;
        $largo = strlen($base);
        for ($i = 0; $i < $largo; $i++) {
            $suma += (int) $base[$largo - 1 - $i] * $pesos[$i];
        }
        $resto    = $suma % 11;
        $esperado = ($resto === 0 || $resto === 1) ? $resto : 11 - $resto;
        if ($esperado !== $dv) {
            return self::falla(self::errorDocumento('NIT', $ejemplo));
        }

        return self::ok($base . '-' . $dv);
    }

    /**
     * @return array{ok: bool, valor: string, error: string}
     */
    private static function rfcMx(string $texto, string $ejemplo): array
    {
        $plano = mb_strtoupper((string) preg_replace('/[\s\-]+/', '', $texto), 'UTF-8');
        $chars = preg_split('//u', $plano, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            return self::falla(self::errorDocumento('RFC', $ejemplo));
        }
        $largo = count($chars);
        if ($largo !== 12 && $largo !== 13) {
            return self::falla(self::errorDocumento('RFC', $ejemplo));
        }

        $letras = $largo === 13 ? 4 : 3;
        $prefijo = implode('', array_slice($chars, 0, $letras));
        $fecha   = implode('', array_slice($chars, $letras, 6));
        if (preg_match('/^[A-ZÑ&]+$/u', $prefijo) !== 1 || preg_match('/^[0-9]{6}$/', $fecha) !== 1) {
            return self::falla(self::errorDocumento('RFC', $ejemplo));
        }
        if (! self::fecha(substr($fecha, 0, 2), substr($fecha, 2, 2), substr($fecha, 4, 2))) {
            return self::falla('La fecha dentro del RFC no es válida. Ejemplo: ' . $ejemplo . '.');
        }

        $tabla = [];
        $orden = preg_split('//u', '0123456789ABCDEFGHIJKLMN&OPQRSTUVWXYZ Ñ', -1, PREG_SPLIT_NO_EMPTY);
        foreach ($orden ?: [] as $i => $char) {
            $tabla[$char] = $i;
        }
        $base = $chars;
        array_pop($base);
        if ($largo === 12) {
            array_unshift($base, ' ');
        }
        $suma   = 0;
        $factor = 13;
        foreach ($base as $char) {
            if (! isset($tabla[$char])) {
                return self::falla(self::errorDocumento('RFC', $ejemplo));
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
            return self::falla(self::errorDocumento('RFC', $ejemplo));
        }

        return self::ok($plano);
    }

    /**
     * @return array{ok: bool, valor: string, error: string}
     */
    private static function nitSv(string $texto, string $ejemplo): array
    {
        $digitos = self::digitos($texto);
        if (preg_match('/^[0-9]{14}$/', $digitos) !== 1 || ! in_array($digitos[0], ['0', '1', '9'], true)) {
            return self::falla(self::errorDocumento('NIT', $ejemplo));
        }
        if (! self::fecha(substr($digitos, 8, 2), substr($digitos, 6, 2), substr($digitos, 4, 2))) {
            return self::falla('La fecha dentro del NIT no es válida. Ejemplo: ' . $ejemplo . '.');
        }

        $correlativo = substr($digitos, 10, 3);
        if ($correlativo <= '100') {
            $pesos = [14, 13, 12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2];
            $suma  = 0;
            for ($i = 0; $i < 13; $i++) {
                $suma += (int) $digitos[$i] * $pesos[$i];
            }
            $esperado = ($suma % 11) % 10;
        } else {
            $pesos = [2, 7, 6, 5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
            $suma  = 0;
            for ($i = 0; $i < 13; $i++) {
                $suma += (int) $digitos[$i] * $pesos[$i];
            }
            $resto    = $suma % 11;
            $esperado = $resto <= 1 ? 0 : 11 - $resto;
        }
        if ((int) $digitos[13] !== $esperado) {
            return self::falla(self::errorDocumento('NIT', $ejemplo));
        }

        $bonito = substr($digitos, 0, 4) . '-' . substr($digitos, 4, 6) . '-' . substr($digitos, 10, 3) . '-' . $digitos[13];

        return self::ok($bonito);
    }

    /**
     * @return array{ok: bool, valor: string, error: string}
     */
    private static function rucEc(string $texto, string $ejemplo): array
    {
        $digitos = self::digitos($texto);
        if (preg_match('/^[0-9]{13}$/', $digitos) !== 1) {
            return self::falla(self::errorDocumento('RUC', $ejemplo));
        }
        $provincia = (int) substr($digitos, 0, 2);
        if (($provincia < 1 || $provincia > 24) && $provincia !== 30) {
            return self::falla(self::errorDocumento('RUC', $ejemplo));
        }

        $tercero = (int) $digitos[2];
        $ok      = false;
        if ($tercero < 6) {
            $ok = self::cedulaEc(substr($digitos, 0, 10)) && (int) substr($digitos, 10, 3) >= 1;
        } elseif ($tercero === 6) {
            $coef = [3, 2, 7, 6, 5, 4, 3, 2];
            $suma = 0;
            for ($i = 0; $i < 8; $i++) {
                $suma += (int) $digitos[$i] * $coef[$i];
            }
            $resto = $suma % 11;
            $dv    = $resto === 0 ? 0 : 11 - $resto;
            $ok    = $dv === (int) $digitos[8] && (int) substr($digitos, 9, 4) >= 1;
        } elseif ($tercero === 9) {
            $coef = [4, 3, 2, 7, 6, 5, 4, 3, 2];
            $suma = 0;
            for ($i = 0; $i < 9; $i++) {
                $suma += (int) $digitos[$i] * $coef[$i];
            }
            $resto = $suma % 11;
            $dv    = $resto === 0 ? 0 : 11 - $resto;
            $ok    = $dv === (int) $digitos[9] && (int) substr($digitos, 10, 3) >= 1;
        }
        if (! $ok) {
            return self::falla(self::errorDocumento('RUC', $ejemplo));
        }

        return self::ok($digitos);
    }

    private static function cedulaEc(string $cedula): bool
    {
        if (preg_match('/^[0-9]{10}$/', $cedula) !== 1) {
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

    /**
     * @return array{ok: bool, valor: string, error: string}
     */
    private static function rucPe(string $texto, string $ejemplo): array
    {
        $digitos = self::digitos($texto);
        if (preg_match('/^(10|15|16|17|20)[0-9]{9}$/', $digitos) !== 1) {
            return self::falla(self::errorDocumento('RUC', $ejemplo));
        }
        $pesos = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma  = 0;
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
            return self::falla(self::errorDocumento('RUC', $ejemplo));
        }

        return self::ok($digitos);
    }
}
