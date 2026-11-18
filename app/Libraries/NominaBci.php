<?php

declare(strict_types=1);

namespace App\Libraries;

class NominaBci
{
    /**
     * Archivo de texto de Pago de Nómina en Línea. Los campos van separados por
     * punto y coma, en el orden del instructivo, y el tipo de pago es OTR.
     *
     * @param array<string, mixed> $nomina
     * @param array<int, string>   $correos
     *
     * @return array{ok: bool, errores: list<string>, contenido: string, nombre: string}
     */
    public static function texto(array $nomina, array $correos = []): array
    {
        $nombre = sprintf('nomina-fondos-%04d-%02d.txt', (int) ($nomina['anio'] ?? 0), (int) ($nomina['mes'] ?? 0));
        $cargo = self::cuenta((string) ($nomina['cuenta_cargo'] ?? ''), 12, true);
        $errores = [];
        if ($cargo === null) {
            $errores[] = 'La cuenta de cargo lleva hasta 12 números. Es la cuenta de la empresa desde la que sale el dinero.';
        }
        $lineas = is_array($nomina['lineas'] ?? null) ? $nomina['lineas'] : [];
        if ($lineas === []) {
            $errores[] = 'La nómina no tiene personas.';
        }
        $filas = [];
        foreach ($lineas as $linea) {
            if (! is_array($linea)) {
                continue;
            }
            $persona = trim((string) ($linea['nombre'] ?? ''));
            $quien = $persona !== '' ? $persona : 'Una persona';
            $destino = self::cuenta((string) ($linea['cuenta_banco'] ?? ''), 18, false);
            if ($destino === null) {
                $errores[] = 'La cuenta de ' . $quien . ' supera los 18 caracteres del archivo, o no es una cuenta.';
            }
            $banco = (string) ($linea['banco_destino'] ?? '');
            if (! BancosChile::existe($banco)) {
                $errores[] = 'A ' . $quien . ' le falta el banco de destino. Se anota en Personas.';
            }
            $rut = strtoupper(str_replace('.', '', (string) ($linea['rut'] ?? '')));
            if (preg_match('/^([0-9]{1,12})-([0-9K])$/', $rut, $partes) !== 1) {
                $errores[] = 'A ' . $quien . ' le falta el RUT.';
                $cuerpo = '';
                $dv = '';
            } else {
                $cuerpo = $partes[1];
                $dv = $partes[2];
            }
            $monto = self::monto($linea['monto'] ?? '');
            if ($monto === null) {
                $errores[] = 'El monto de ' . $quien . ' tiene que ser un entero de hasta 16 dígitos.';
            }
            $beneficiario = self::textoCampo($persona, 45);
            if ($beneficiario === '') {
                $errores[] = 'A ' . $quien . ' le falta el nombre.';
            }
            if ($errores !== []) {
                continue;
            }
            $mensaje = self::textoCampo((string) ($linea['motivo'] ?? ''), 30);
            if ($mensaje === '') {
                $mensaje = 'Fondo por rendir';
            }
            $correo = trim((string) ($correos[(int) ($linea['usuario_id'] ?? 0)] ?? ''));
            if (strlen($correo) > 35 || filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
                $correo = '';
            }
            $filas[] = implode(';', [
                $cargo,
                $destino,
                $banco,
                $cuerpo,
                $dv,
                $beneficiario,
                $monto,
                '',
                '',
                'OTR',
                $mensaje,
                $correo,
                self::textoCampo($persona, 25),
            ]);
        }
        if ($errores !== []) {
            return ['ok' => false, 'errores' => $errores, 'contenido' => '', 'nombre' => $nombre];
        }

        return [
            'ok'        => true,
            'errores'   => [],
            'contenido' => implode("\r\n", $filas) . "\r\n",
            'nombre'    => $nombre,
        ];
    }

    private static function cuenta(string $raw, int $ancho, bool $soloNumeros): ?string
    {
        $plano = $soloNumeros
            ? (string) preg_replace('/\D+/', '', $raw)
            : strtoupper((string) preg_replace('/[^0-9A-Za-z]/', '', $raw));
        if ($plano === '' || strlen($plano) > $ancho) {
            return null;
        }

        return str_pad($plano, $ancho, '0', STR_PAD_LEFT);
    }

    private static function monto(mixed $raw): ?string
    {
        if (! is_numeric($raw)) {
            return null;
        }
        $monto = round((float) $raw, 2);
        $normal = number_format($monto, 2, '.', '');
        if ($monto <= 0 || ! str_ends_with($normal, '.00')) {
            return null;
        }
        $entero = substr($normal, 0, -3);
        if ($entero === '' || strlen($entero) > 16) {
            return null;
        }

        return $entero;
    }

    private static function textoCampo(string $valor, int $maximo): string
    {
        $valor = str_replace([';', '|', "\r", "\n", "\t"], ' ', $valor);
        $valor = trim((string) preg_replace('/\s+/', ' ', $valor));

        return mb_substr($valor, 0, $maximo);
    }
}
