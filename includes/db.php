<?php

function ptf_config() {
    $file = ROOT . '/includes/config.php';
    if (!is_file($file)) {
        throw new RuntimeException('Falta includes/config.php con los datos de la base.');
    }
    $config = require $file;
    if (empty($config['db_host']) || empty($config['db_name']) || empty($config['db_user'])) {
        throw new RuntimeException('La configuración de la base está incompleta.');
    }
    return $config;
}

function db() {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    if (!class_exists('PDO')) {
        throw new RuntimeException('PHP no tiene PDO disponible.');
    }
    $config = ptf_config();
    $hosts = [trim((string) $config['db_host'])];
    foreach (['localhost', '127.0.0.1'] as $local) {
        if (!in_array($local, $hosts, true)) {
            $hosts[] = $local;
        }
    }
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    if (defined('PDO::MYSQL_ATTR_CONNECT_TIMEOUT')) {
        $options[PDO::MYSQL_ATTR_CONNECT_TIMEOUT] = 4;
    }
    $ultimo = null;
    foreach ($hosts as $host) {
        if ($host === '') {
            continue;
        }
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $host,
            (int) $config['db_port'],
            $config['db_name']
        );
        try {
            $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
            db_install($pdo);
            return $pdo;
        } catch (PDOException $error) {
            $pdo = null;
            $ultimo = $error;
        }
    }
    throw $ultimo ?: new RuntimeException('No hay conexión con la base de datos.');
}

function db_install(PDO $pdo) {
    $tablas = [
        "CREATE TABLE IF NOT EXISTS companies (
            id VARCHAR(32) NOT NULL,
            nombre VARCHAR(120) NOT NULL,
            pais CHAR(2) NOT NULL,
            id_fiscal VARCHAR(40) NOT NULL DEFAULT '',
            detectar_duplicados TINYINT(1) NOT NULL DEFAULT 1,
            dias_duplicado INT NOT NULL DEFAULT 3,
            comprobante_desde DECIMAL(14,2) NOT NULL DEFAULT 1,
            creado DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
        "CREATE TABLE IF NOT EXISTS users (
            id VARCHAR(32) NOT NULL,
            company_id VARCHAR(32) NOT NULL,
            nombre VARCHAR(80) NOT NULL,
            email VARCHAR(160) NOT NULL,
            password VARCHAR(255) NOT NULL,
            rol VARCHAR(32) NOT NULL,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            reset_token VARCHAR(64) NOT NULL DEFAULT '',
            reset_expires INT NOT NULL DEFAULT 0,
            remember_selector VARCHAR(32) NOT NULL DEFAULT '',
            remember_token VARCHAR(64) NOT NULL DEFAULT '',
            remember_expires INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY users_email (email),
            KEY users_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
        "CREATE TABLE IF NOT EXISTS categories (
            id VARCHAR(32) NOT NULL,
            company_id VARCHAR(32) NOT NULL,
            nombre VARCHAR(80) NOT NULL,
            detalle VARCHAR(180) NOT NULL DEFAULT '',
            tope DECIMAL(14,2) NOT NULL DEFAULT 0,
            activa TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY categories_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
        "CREATE TABLE IF NOT EXISTS expenses (
            id VARCHAR(32) NOT NULL,
            company_id VARCHAR(32) NOT NULL,
            user_id VARCHAR(32) NOT NULL,
            category_id VARCHAR(32) NOT NULL,
            concepto VARCHAR(140) NOT NULL,
            proveedor VARCHAR(140) NOT NULL,
            fecha DATE NOT NULL,
            monto DECIMAL(14,2) NOT NULL,
            moneda CHAR(3) NOT NULL,
            tipo_doc VARCHAR(80) NOT NULL DEFAULT '',
            id_fiscal VARCHAR(40) NOT NULL DEFAULT '',
            num_doc VARCHAR(40) NOT NULL DEFAULT '',
            codigo_fiscal VARCHAR(80) NOT NULL DEFAULT '',
            nota VARCHAR(500) NOT NULL DEFAULT '',
            foto VARCHAR(255) NOT NULL DEFAULT '',
            estado VARCHAR(32) NOT NULL,
            report_id VARCHAR(32) NOT NULL DEFAULT '',
            duplicado TINYINT(1) NOT NULL DEFAULT 0,
            fuera_politica TINYINT(1) NOT NULL DEFAULT 0,
            sin_comprobante TINYINT(1) NOT NULL DEFAULT 0,
            fiscal_incompleto TINYINT(1) NOT NULL DEFAULT 0,
            creado DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY expenses_company (company_id),
            KEY expenses_user (user_id),
            KEY expenses_report (report_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
        "CREATE TABLE IF NOT EXISTS reports (
            id VARCHAR(32) NOT NULL,
            company_id VARCHAR(32) NOT NULL,
            user_id VARCHAR(32) NOT NULL,
            titulo VARCHAR(140) NOT NULL,
            desde DATE NULL,
            hasta DATE NULL,
            nota VARCHAR(500) NOT NULL DEFAULT '',
            estado VARCHAR(32) NOT NULL,
            comentario_revision VARCHAR(500) NOT NULL DEFAULT '',
            revisor_id VARCHAR(32) NOT NULL DEFAULT '',
            creado DATETIME NOT NULL,
            actualizado DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY reports_company (company_id),
            KEY reports_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
        "CREATE TABLE IF NOT EXISTS report_expenses (
            report_id VARCHAR(32) NOT NULL,
            expense_id VARCHAR(32) NOT NULL,
            orden INT NOT NULL DEFAULT 0,
            PRIMARY KEY (report_id, expense_id),
            KEY report_expenses_expense (expense_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
        "CREATE TABLE IF NOT EXISTS sequences (
            nombre VARCHAR(32) NOT NULL,
            valor INT NOT NULL,
            PRIMARY KEY (nombre)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
        "CREATE TABLE IF NOT EXISTS invoice_links (
            company_id VARCHAR(32) NOT NULL,
            token VARCHAR(32) NOT NULL,
            creado DATETIME NOT NULL,
            PRIMARY KEY (company_id),
            UNIQUE KEY invoice_links_token (token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
        "CREATE TABLE IF NOT EXISTS invoice_clients (
            id VARCHAR(32) NOT NULL,
            company_id VARCHAR(32) NOT NULL,
            razon VARCHAR(160) NOT NULL,
            id_fiscal VARCHAR(40) NOT NULL,
            id_fiscal_key VARCHAR(40) NOT NULL,
            email VARCHAR(160) NOT NULL DEFAULT '',
            telefono VARCHAR(40) NOT NULL DEFAULT '',
            contacto VARCHAR(80) NOT NULL DEFAULT '',
            pais_cliente VARCHAR(8) NOT NULL DEFAULT '',
            direccion VARCHAR(180) NOT NULL DEFAULT '',
            ciudad VARCHAR(80) NOT NULL DEFAULT '',
            comercial_nombre VARCHAR(80) NOT NULL DEFAULT '',
            comercial_email VARCHAR(160) NOT NULL DEFAULT '',
            financiero_nombre VARCHAR(80) NOT NULL DEFAULT '',
            financiero_email VARCHAR(160) NOT NULL DEFAULT '',
            creado DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY invoice_clients_company (company_id, id_fiscal_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
        "CREATE TABLE IF NOT EXISTS invoice_asks (
            id VARCHAR(32) NOT NULL,
            company_id VARCHAR(32) NOT NULL,
            nombre VARCHAR(120) NOT NULL,
            obligatorio TINYINT(1) NOT NULL DEFAULT 0,
            formato VARCHAR(255) NOT NULL DEFAULT '',
            orden SMALLINT NOT NULL DEFAULT 0,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            creado DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY invoice_asks_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
        "CREATE TABLE IF NOT EXISTS invoice_files (
            id VARCHAR(32) NOT NULL,
            company_id VARCHAR(32) NOT NULL,
            request_id VARCHAR(32) NOT NULL,
            nombre VARCHAR(120) NOT NULL,
            path VARCHAR(255) NOT NULL,
            PRIMARY KEY (id),
            KEY invoice_files_request (request_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
        "CREATE TABLE IF NOT EXISTS invoice_requests (
            id VARCHAR(32) NOT NULL,
            company_id VARCHAR(32) NOT NULL,
            client_id VARCHAR(32) NOT NULL,
            contacto VARCHAR(80) NOT NULL DEFAULT '',
            nota VARCHAR(500) NOT NULL DEFAULT '',
            estado VARCHAR(32) NOT NULL,
            codigo_fiscal VARCHAR(80) NOT NULL DEFAULT '',
            folio VARCHAR(16) NOT NULL DEFAULT '',
            send_id VARCHAR(32) NOT NULL DEFAULT '',
            creado DATETIME NOT NULL,
            actualizado DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY invoice_requests_company (company_id, creado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
        "CREATE TABLE IF NOT EXISTS invoice_fields (
            company_id VARCHAR(32) NOT NULL,
            clave VARCHAR(32) NOT NULL,
            modo VARCHAR(16) NOT NULL,
            obligatorio TINYINT(1) NOT NULL DEFAULT 0,
            orden SMALLINT NOT NULL DEFAULT 0,
            PRIMARY KEY (company_id, clave)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
        "CREATE TABLE IF NOT EXISTS invoice_sends (
            id VARCHAR(32) NOT NULL,
            company_id VARCHAR(32) NOT NULL,
            email VARCHAR(160) NOT NULL,
            nombre VARCHAR(80) NOT NULL DEFAULT '',
            token VARCHAR(32) NOT NULL,
            estado VARCHAR(32) NOT NULL,
            request_id VARCHAR(32) NOT NULL DEFAULT '',
            folio VARCHAR(16) NOT NULL DEFAULT '',
            creado DATETIME NOT NULL,
            abierto DATETIME NULL,
            completado DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY invoice_sends_token (token),
            KEY invoice_sends_company (company_id, creado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
        "CREATE TABLE IF NOT EXISTS app_logs (
            id INT NOT NULL AUTO_INCREMENT,
            creado DATETIME NOT NULL,
            nivel VARCHAR(16) NOT NULL,
            origen VARCHAR(80) NOT NULL,
            mensaje TEXT NOT NULL,
            archivo VARCHAR(255) NOT NULL DEFAULT '',
            linea INT NOT NULL DEFAULT 0,
            url VARCHAR(255) NOT NULL DEFAULT '',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
    ];
    try {
        foreach ($tablas as $sql) {
            $pdo->exec($sql);
        }
    } catch (PDOException $error) {
        $existe = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
        if (!$existe) {
            throw $error;
        }
    }
    $columnas = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
    if ($columnas && !in_array('remember_selector', $columnas, true)) {
        $pdo->exec("ALTER TABLE users
            ADD COLUMN remember_selector VARCHAR(32) NOT NULL DEFAULT '',
            ADD COLUMN remember_token VARCHAR(64) NOT NULL DEFAULT '',
            ADD COLUMN remember_expires INT NOT NULL DEFAULT 0");
    }
    $asks = $pdo->query('SHOW COLUMNS FROM invoice_asks')->fetchAll(PDO::FETCH_COLUMN);
    if ($asks && !in_array('formato', $asks, true)) {
        $pdo->exec("ALTER TABLE invoice_asks ADD COLUMN formato VARCHAR(255) NOT NULL DEFAULT ''");
    }
    if ($asks && !in_array('orden', $asks, true)) {
        $pdo->exec("ALTER TABLE invoice_asks ADD COLUMN orden SMALLINT NOT NULL DEFAULT 0");
    }
    if ($asks && !in_array('activo', $asks, true)) {
        $pdo->exec("ALTER TABLE invoice_asks ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1");
    }
    $fields = $pdo->query('SHOW COLUMNS FROM invoice_fields')->fetchAll(PDO::FETCH_COLUMN);
    if ($fields && !in_array('orden', $fields, true)) {
        $pdo->exec("ALTER TABLE invoice_fields ADD COLUMN orden SMALLINT NOT NULL DEFAULT 0");
    }
    $requests = $pdo->query('SHOW COLUMNS FROM invoice_requests')->fetchAll(PDO::FETCH_COLUMN);
    if ($requests && !in_array('folio', $requests, true)) {
        $pdo->exec("ALTER TABLE invoice_requests ADD COLUMN folio VARCHAR(16) NOT NULL DEFAULT ''");
    }
    if ($requests && !in_array('send_id', $requests, true)) {
        $pdo->exec("ALTER TABLE invoice_requests ADD COLUMN send_id VARCHAR(32) NOT NULL DEFAULT ''");
    }
    $clientes = $pdo->query('SHOW COLUMNS FROM invoice_clients')->fetchAll(PDO::FETCH_COLUMN);
    $extras = [
        'pais_cliente' => "VARCHAR(8) NOT NULL DEFAULT ''",
        'direccion' => "VARCHAR(180) NOT NULL DEFAULT ''",
        'ciudad' => "VARCHAR(80) NOT NULL DEFAULT ''",
        'comercial_nombre' => "VARCHAR(80) NOT NULL DEFAULT ''",
        'comercial_email' => "VARCHAR(160) NOT NULL DEFAULT ''",
        'financiero_nombre' => "VARCHAR(80) NOT NULL DEFAULT ''",
        'financiero_email' => "VARCHAR(160) NOT NULL DEFAULT ''",
    ];
    foreach ($extras as $columna => $definicion) {
        if ($clientes && !in_array($columna, $clientes, true)) {
            $pdo->exec('ALTER TABLE invoice_clients ADD COLUMN ' . $columna . ' ' . $definicion);
        }
    }
}

function registrar_log($nivel, $origen, $mensaje, $archivo = '', $linea = 0) {
    static $activo = false;
    if ($activo) {
        return;
    }
    $activo = true;
    try {
        $pdo = db();
        $statement = $pdo->prepare('INSERT INTO app_logs (creado, nivel, origen, mensaje, archivo, linea, url) VALUES (?,?,?,?,?,?,?)');
        $statement->execute([
            date('Y-m-d H:i:s'),
            substr((string) $nivel, 0, 16),
            substr((string) $origen, 0, 80),
            (string) $mensaje,
            substr((string) $archivo, 0, 255),
            (int) $linea,
            substr(($_SERVER['REQUEST_METHOD'] ?? 'CLI') . ' ' . ($_SERVER['REQUEST_URI'] ?? ''), 0, 255),
        ]);
    } catch (Throwable $error) {
    }
    $activo = false;
}

function db_bool($value) {
    return !empty($value);
}

function db_fecha($value) {
    $value = trim((string) $value);
    return $value === '' ? null : $value;
}

function db_momento($value) {
    $stamp = strtotime((string) $value);
    if (!$stamp) {
        $stamp = time();
    }
    return date('Y-m-d H:i:s', $stamp);
}

function db_load() {
    $pdo = db();
    $users = $pdo->query('SELECT * FROM users')->fetchAll();
    if (!$users) {
        return null;
    }
    $links = [];
    foreach ($pdo->query('SELECT report_id, expense_id FROM report_expenses ORDER BY report_id, orden, expense_id') as $link) {
        $links[$link['report_id']][] = $link['expense_id'];
    }
    $seq = [];
    foreach ($pdo->query('SELECT nombre, valor FROM sequences') as $row) {
        $seq[$row['nombre']] = (int) $row['valor'];
    }
    $companies = [];
    foreach ($pdo->query('SELECT * FROM companies') as $row) {
        $companies[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'pais' => $row['pais'],
            'id_fiscal' => $row['id_fiscal'],
            'detectar_duplicados' => db_bool($row['detectar_duplicados']),
            'dias_duplicado' => (int) $row['dias_duplicado'],
            'comprobante_desde' => (float) $row['comprobante_desde'],
            'creado' => $row['creado'],
        ];
    }
    $people = [];
    foreach ($users as $row) {
        $people[] = [
            'id' => $row['id'],
            'company_id' => $row['company_id'],
            'nombre' => $row['nombre'],
            'email' => $row['email'],
            'password' => $row['password'],
            'rol' => $row['rol'],
            'activo' => db_bool($row['activo']),
            'reset_token' => $row['reset_token'],
            'reset_expires' => (int) $row['reset_expires'],
            'remember_selector' => $row['remember_selector'] ?? '',
            'remember_token' => $row['remember_token'] ?? '',
            'remember_expires' => (int) ($row['remember_expires'] ?? 0),
        ];
    }
    $categories = [];
    foreach ($pdo->query('SELECT * FROM categories') as $row) {
        $categories[] = [
            'id' => $row['id'],
            'company_id' => $row['company_id'],
            'nombre' => $row['nombre'],
            'detalle' => $row['detalle'],
            'tope' => (float) $row['tope'],
            'activa' => db_bool($row['activa']),
        ];
    }
    $expenses = [];
    foreach ($pdo->query('SELECT * FROM expenses') as $row) {
        $expenses[] = [
            'id' => $row['id'],
            'company_id' => $row['company_id'],
            'user_id' => $row['user_id'],
            'category_id' => $row['category_id'],
            'concepto' => $row['concepto'],
            'proveedor' => $row['proveedor'],
            'fecha' => $row['fecha'],
            'monto' => (float) $row['monto'],
            'moneda' => $row['moneda'],
            'tipo_doc' => $row['tipo_doc'],
            'id_fiscal' => $row['id_fiscal'],
            'num_doc' => $row['num_doc'],
            'codigo_fiscal' => $row['codigo_fiscal'],
            'nota' => $row['nota'],
            'foto' => $row['foto'],
            'estado' => $row['estado'],
            'report_id' => $row['report_id'],
            'duplicado' => db_bool($row['duplicado']),
            'fuera_politica' => db_bool($row['fuera_politica']),
            'sin_comprobante' => db_bool($row['sin_comprobante']),
            'fiscal_incompleto' => db_bool($row['fiscal_incompleto']),
            'creado' => $row['creado'],
        ];
    }
    $reports = [];
    foreach ($pdo->query('SELECT * FROM reports') as $row) {
        $reports[] = [
            'id' => $row['id'],
            'company_id' => $row['company_id'],
            'user_id' => $row['user_id'],
            'titulo' => $row['titulo'],
            'desde' => $row['desde'] ?: '',
            'hasta' => $row['hasta'] ?: '',
            'nota' => $row['nota'],
            'estado' => $row['estado'],
            'expense_ids' => $links[$row['id']] ?? [],
            'comentario_revision' => $row['comentario_revision'],
            'revisor_id' => $row['revisor_id'],
            'creado' => $row['creado'],
            'actualizado' => $row['actualizado'],
        ];
    }
    return [
        'seq' => $seq,
        'companies' => $companies,
        'users' => $people,
        'categories' => $categories,
        'expenses' => $expenses,
        'reports' => $reports,
    ];
}

function db_delete_missing(PDO $pdo, $table, array $ids) {
    if (!$ids) {
        $pdo->exec('DELETE FROM `' . $table . '`');
        return;
    }
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $statement = $pdo->prepare('DELETE FROM `' . $table . '` WHERE id NOT IN (' . $marks . ')');
    $statement->execute(array_values($ids));
}

function db_sync(array $store) {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $company = $pdo->prepare('INSERT INTO companies (id, nombre, pais, id_fiscal, detectar_duplicados, dias_duplicado, comprobante_desde, creado) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), pais=VALUES(pais), id_fiscal=VALUES(id_fiscal), detectar_duplicados=VALUES(detectar_duplicados), dias_duplicado=VALUES(dias_duplicado), comprobante_desde=VALUES(comprobante_desde), creado=VALUES(creado)');
        foreach ($store['companies'] as $row) {
            $company->execute([
                $row['id'], $row['nombre'], $row['pais'], $row['id_fiscal'] ?? '',
                db_bool($row['detectar_duplicados'] ?? true) ? 1 : 0,
                (int) ($row['dias_duplicado'] ?? 3),
                (float) ($row['comprobante_desde'] ?? 0),
                db_momento($row['creado'] ?? ''),
            ]);
        }
        $user = $pdo->prepare('INSERT INTO users (id, company_id, nombre, email, password, rol, activo, reset_token, reset_expires, remember_selector, remember_token, remember_expires) VALUES (?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE company_id=VALUES(company_id), nombre=VALUES(nombre), email=VALUES(email), password=VALUES(password), rol=VALUES(rol), activo=VALUES(activo), reset_token=VALUES(reset_token), reset_expires=VALUES(reset_expires), remember_selector=VALUES(remember_selector), remember_token=VALUES(remember_token), remember_expires=VALUES(remember_expires)');
        foreach ($store['users'] as $row) {
            $user->execute([
                $row['id'], $row['company_id'], $row['nombre'], $row['email'], $row['password'], $row['rol'],
                db_bool($row['activo'] ?? true) ? 1 : 0,
                $row['reset_token'] ?? '',
                (int) ($row['reset_expires'] ?? 0),
                $row['remember_selector'] ?? '',
                $row['remember_token'] ?? '',
                (int) ($row['remember_expires'] ?? 0),
            ]);
        }
        $category = $pdo->prepare('INSERT INTO categories (id, company_id, nombre, detalle, tope, activa) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE company_id=VALUES(company_id), nombre=VALUES(nombre), detalle=VALUES(detalle), tope=VALUES(tope), activa=VALUES(activa)');
        foreach ($store['categories'] as $row) {
            $category->execute([
                $row['id'], $row['company_id'], $row['nombre'], $row['detalle'] ?? '',
                (float) ($row['tope'] ?? 0),
                db_bool($row['activa'] ?? true) ? 1 : 0,
            ]);
        }
        $expense = $pdo->prepare('INSERT INTO expenses (id, company_id, user_id, category_id, concepto, proveedor, fecha, monto, moneda, tipo_doc, id_fiscal, num_doc, codigo_fiscal, nota, foto, estado, report_id, duplicado, fuera_politica, sin_comprobante, fiscal_incompleto, creado) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE company_id=VALUES(company_id), user_id=VALUES(user_id), category_id=VALUES(category_id), concepto=VALUES(concepto), proveedor=VALUES(proveedor), fecha=VALUES(fecha), monto=VALUES(monto), moneda=VALUES(moneda), tipo_doc=VALUES(tipo_doc), id_fiscal=VALUES(id_fiscal), num_doc=VALUES(num_doc), codigo_fiscal=VALUES(codigo_fiscal), nota=VALUES(nota), foto=VALUES(foto), estado=VALUES(estado), report_id=VALUES(report_id), duplicado=VALUES(duplicado), fuera_politica=VALUES(fuera_politica), sin_comprobante=VALUES(sin_comprobante), fiscal_incompleto=VALUES(fiscal_incompleto), creado=VALUES(creado)');
        foreach ($store['expenses'] as $row) {
            $expense->execute([
                $row['id'], $row['company_id'], $row['user_id'], $row['category_id'],
                $row['concepto'], $row['proveedor'], $row['fecha'], (float) $row['monto'], $row['moneda'],
                $row['tipo_doc'] ?? '', $row['id_fiscal'] ?? '', $row['num_doc'] ?? '', $row['codigo_fiscal'] ?? '',
                $row['nota'] ?? '', $row['foto'] ?? '', $row['estado'], $row['report_id'] ?? '',
                db_bool($row['duplicado'] ?? false) ? 1 : 0,
                db_bool($row['fuera_politica'] ?? false) ? 1 : 0,
                db_bool($row['sin_comprobante'] ?? false) ? 1 : 0,
                db_bool($row['fiscal_incompleto'] ?? false) ? 1 : 0,
                db_momento($row['creado'] ?? ''),
            ]);
        }
        $report = $pdo->prepare('INSERT INTO reports (id, company_id, user_id, titulo, desde, hasta, nota, estado, comentario_revision, revisor_id, creado, actualizado) VALUES (?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE company_id=VALUES(company_id), user_id=VALUES(user_id), titulo=VALUES(titulo), desde=VALUES(desde), hasta=VALUES(hasta), nota=VALUES(nota), estado=VALUES(estado), comentario_revision=VALUES(comentario_revision), revisor_id=VALUES(revisor_id), creado=VALUES(creado), actualizado=VALUES(actualizado)');
        foreach ($store['reports'] as $row) {
            $report->execute([
                $row['id'], $row['company_id'], $row['user_id'], $row['titulo'],
                db_fecha($row['desde'] ?? ''), db_fecha($row['hasta'] ?? ''),
                $row['nota'] ?? '', $row['estado'], $row['comentario_revision'] ?? '', $row['revisor_id'] ?? '',
                db_momento($row['creado'] ?? ''), db_momento($row['actualizado'] ?? ''),
            ]);
        }
        db_delete_missing($pdo, 'report_expenses', []);
        $link = $pdo->prepare('INSERT INTO report_expenses (report_id, expense_id, orden) VALUES (?,?,?)');
        foreach ($store['reports'] as $row) {
            $orden = 0;
            foreach ($row['expense_ids'] ?? [] as $expenseId) {
                $link->execute([$row['id'], $expenseId, $orden]);
                $orden++;
            }
        }
        db_delete_missing($pdo, 'expenses', array_column($store['expenses'], 'id'));
        db_delete_missing($pdo, 'reports', array_column($store['reports'], 'id'));
        db_delete_missing($pdo, 'categories', array_column($store['categories'], 'id'));
        db_delete_missing($pdo, 'users', array_column($store['users'], 'id'));
        db_delete_missing($pdo, 'companies', array_column($store['companies'], 'id'));
        $sequence = $pdo->prepare('INSERT INTO sequences (nombre, valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)');
        foreach ($store['seq'] as $nombre => $valor) {
            $sequence->execute([$nombre, (int) $valor]);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}
