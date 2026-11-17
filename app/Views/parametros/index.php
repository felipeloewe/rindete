<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>politica<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Parametrización</h1>
        <p>Estas reglas las lee la carga. El flujo, las tarjetas y las conexiones quedan vigentes para las etapas que vienen.</p>
    </div>
</header>

<section class="bloque">
    <h2>Cuentas</h2>
    <p>La genérica clasifica el gasto. La de remuneración se usa cuando el concepto se traspasa a la nómina: el de la persona, si existe, y si no el general.</p>
    <form method="post" action="<?= base_url('parametros/cuentas') ?>">
        <?= csrf_field() ?>
        <div class="grid-2">
            <label>Clase
                <select name="clase">
                    <option value="generica">Genérica</option>
                    <option value="remuneracion">Remuneración</option>
                </select>
            </label>
            <label>Código
                <input name="codigo" required maxlength="32" placeholder="5101001">
            </label>
        </div>
        <label>Nombre
            <input name="nombre" required maxlength="120" placeholder="Gastos de viaje">
        </label>
        <div class="grid-2">
            <label>Concepto de remuneración
                <select name="concepto">
                    <option value="">No aplica</option>
                    <?php foreach ($conceptos as $clave => $texto) : ?>
                        <option value="<?= esc($clave) ?>"><?= esc($texto) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <label>Concepto propio de
                <select name="usuario_id">
                    <option value="0">Toda la empresa</option>
                    <?php foreach ($usuarios as $miembro) : ?>
                        <option value="<?= esc($miembro['id']) ?>"><?= esc($miembro['nombre']) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
        </div>
        <button class="boton" type="submit">Guardar cuenta</button>
    </form>
    <?php if ($cuentas !== []) : ?>
        <table class="tabla">
            <thead><tr><th>Cuenta</th><th>Uso</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($cuentas as $cuenta) : ?>
                    <tr>
                        <td><?= esc($cuenta['codigo']) ?> <?= esc($cuenta['nombre']) ?></td>
                        <td>
                            <?= $cuenta['clase'] === 'remuneracion' ? 'Remuneración' : 'Genérica' ?>
                            <?php if ($cuenta['concepto'] !== '') : ?>
                                · <?= esc($conceptos[$cuenta['concepto']] ?? $cuenta['concepto']) ?>
                            <?php endif ?>
                            <?php if ((int) ($cuenta['usuario_id'] ?? 0) > 0) : ?>
                                · <?= esc($nombres['usuarios'][(int) $cuenta['usuario_id']] ?? '') ?>
                            <?php endif ?>
                        </td>
                        <td>
                            <form method="post" action="<?= base_url('parametros/cuentas/' . $cuenta['id'] . '/eliminar') ?>">
                                <?= csrf_field() ?>
                                <button class="boton claro" type="submit">Quitar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
</section>

<section class="bloque">
    <h2>Clasificación</h2>
    <p>Al confirmar un gasto, Rindete propone el asiento en la cuenta genérica de su categoría. Espera la centralización del mes.</p>
    <?php foreach ($categorias as $categoria) : ?>
        <?php $cuentaActual = (int) ($enlaces[(int) $categoria['id']] ?? 0); ?>
        <form method="post" action="<?= base_url('parametros/clasificacion') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="categoria_id" value="<?= esc($categoria['id']) ?>">
            <div class="grid-2">
                <label>Categoría
                    <input value="<?= esc($categoria['nombre']) ?>" readonly>
                </label>
                <label>Cuenta genérica
                    <select name="cuenta_id">
                        <option value="0">Sin cuenta</option>
                        <?php foreach ($cuentas as $cuenta) : ?>
                            <?php if ($cuenta['clase'] !== 'generica') continue; ?>
                            <option value="<?= esc($cuenta['id']) ?>" <?= $cuentaActual === (int) $cuenta['id'] ? 'selected' : '' ?>><?= esc($cuenta['codigo']) ?> <?= esc($cuenta['nombre']) ?></option>
                        <?php endforeach ?>
                    </select>
                </label>
            </div>
            <button class="boton" type="submit">Guardar</button>
        </form>
    <?php endforeach ?>
</section>

<section class="bloque">
    <h2>Conexiones</h2>
    <p>El cierre y el flujo de pagos usan solo las que quedan vigentes.</p>
    <form method="post" action="<?= base_url('parametros/conexiones') ?>">
        <?= csrf_field() ?>
        <div class="grid-2">
            <label>Sistema
                <input name="nombre" required maxlength="120" placeholder="Contabilidad, banco, nómina…">
            </label>
            <label>Sirve a
                <select name="destino">
                    <?php foreach ($destinos as $clave => $texto) : ?>
                        <option value="<?= esc($clave) ?>"><?= esc($texto) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
        </div>
        <label class="check"><input type="checkbox" name="vigente" value="1" checked> Vigente</label>
        <button class="boton" type="submit">Guardar conexión</button>
    </form>
    <?php if ($conexiones !== []) : ?>
        <table class="tabla">
            <tbody>
                <?php foreach ($conexiones as $conexion) : ?>
                    <tr>
                        <td><?= esc($conexion['nombre']) ?><small><?= esc($destinos[$conexion['destino']] ?? '') ?><?= (int) $conexion['vigente'] === 1 ? ' · vigente' : ' · no vigente' ?></small></td>
                        <td>
                            <form method="post" action="<?= base_url('parametros/conexiones/' . $conexion['id'] . '/eliminar') ?>">
                                <?= csrf_field() ?>
                                <button class="boton claro" type="submit">Quitar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
</section>

<section class="bloque">
    <h2>Flujo de aceptación</h2>
    <p>Quién acepta y en qué orden. La solicitud de fondos y el cierre con saldo negativo se detienen si el monto supera el máximo, si el tipo no está permitido o si la persona no está habilitada.</p>
    <form method="post" action="<?= base_url('parametros/flujo') ?>">
        <?= csrf_field() ?>
        <label>Monto máximo de una rendición
            <input name="monto_maximo_rendicion" value="<?= esc($empresa['monto_maximo_rendicion'] ?? '0') ?>">
            <small>Usa 0 si no hay máximo.</small>
        </label>
        <label class="check"><input type="checkbox" name="detener_monto" value="1" <?= (int) ($empresa['detener_monto'] ?? 1) === 1 ? 'checked' : '' ?>> Detener si el monto supera el máximo</label>
        <label class="check"><input type="checkbox" name="detener_tipo" value="1" <?= (int) ($empresa['detener_tipo'] ?? 1) === 1 ? 'checked' : '' ?>> Detener si el tipo de gasto no está permitido</label>
        <label class="check"><input type="checkbox" name="detener_habilitacion" value="1" <?= (int) ($empresa['detener_habilitacion'] ?? 1) === 1 ? 'checked' : '' ?>> Detener si la persona no está habilitada</label>
        <button class="boton" type="submit">Guardar reglas</button>
    </form>
    <form method="post" action="<?= base_url('parametros/aceptadores') ?>">
        <?= csrf_field() ?>
        <div class="grid-2">
            <label>Acepta
                <select name="usuario_id" required>
                    <?php foreach ($usuarios as $miembro) : ?>
                        <option value="<?= esc($miembro['id']) ?>"><?= esc($miembro['nombre']) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <label>Orden
                <input name="orden" inputmode="numeric" value="1" required>
            </label>
        </div>
        <button class="boton" type="submit">Agregar al flujo</button>
    </form>
    <?php if ($aceptadores !== []) : ?>
        <table class="tabla">
            <tbody>
                <?php foreach ($aceptadores as $paso) : ?>
                    <tr>
                        <td><?= esc((string) $paso['orden']) ?>. <?= esc($nombres['usuarios'][(int) $paso['usuario_id']] ?? '') ?></td>
                        <td>
                            <form method="post" action="<?= base_url('parametros/aceptadores/' . $paso['id'] . '/eliminar') ?>">
                                <?= csrf_field() ?>
                                <button class="boton claro" type="submit">Quitar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
</section>

<section class="bloque">
    <h2>Tarjetas</h2>
    <p>Una tarjeta puede tener más de una persona con acceso. El cargo se contrasta con los gastos y el monto de esta mantención.</p>
    <form method="post" action="<?= base_url('parametros/tarjetas') ?>">
        <?= csrf_field() ?>
        <div class="grid-2">
            <label>Nombre
                <input name="nombre" required maxlength="120" placeholder="Visa empresa">
            </label>
            <label>Monto aceptado
                <input name="monto_aceptado" value="0">
            </label>
        </div>
        <label class="check"><input type="checkbox" name="personal" value="1"> De uso personal</label>
        <p>Personas con acceso</p>
        <div class="marcas">
            <?php foreach ($usuarios as $miembro) : ?>
                <label class="check"><input type="checkbox" name="usuario_id[]" value="<?= esc($miembro['id']) ?>"> <?= esc($miembro['nombre']) ?></label>
            <?php endforeach ?>
        </div>
        <p>Gastos de la tarjeta</p>
        <div class="marcas">
            <?php foreach ($categorias as $categoria) : ?>
                <label class="check"><input type="checkbox" name="categoria_id[]" value="<?= esc($categoria['id']) ?>"> <?= esc($categoria['nombre']) ?></label>
            <?php endforeach ?>
        </div>
        <button class="boton" type="submit">Guardar tarjeta</button>
    </form>
    <?php if ($tarjetas !== []) : ?>
        <table class="tabla">
            <tbody>
                <?php foreach ($tarjetas as $tarjeta) : ?>
                    <tr>
                        <td>
                            <?= esc($tarjeta['nombre']) ?>
                            <small>
                                <?= (int) $tarjeta['personal'] === 1 ? 'Personal' : 'Empresa' ?>
                                · <?= esc(\App\Libraries\Montos::formato((float) $tarjeta['monto_aceptado'], $moneda)) ?>
                                <?php foreach ($tarjeta['personas'] as $id) : ?>
                                    · <?= esc($nombres['usuarios'][$id] ?? '') ?>
                                <?php endforeach ?>
                            </small>
                        </td>
                        <td>
                            <form method="post" action="<?= base_url('parametros/tarjetas/' . $tarjeta['id'] . '/eliminar') ?>">
                                <?= csrf_field() ?>
                                <button class="boton claro" type="submit">Quitar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
</section>

<section class="bloque">
    <h2>Gastos aceptados</h2>
    <p>Cada persona carga solo lo que marcas. Si nadie queda marcado, la restricción se apaga.</p>
    <form method="get" action="<?= base_url('parametros') ?>">
        <label>Persona
            <select name="persona">
                <?php foreach ($usuarios as $item) : ?>
                    <option value="<?= esc($item['id']) ?>" <?= (int) $persona === (int) $item['id'] ? 'selected' : '' ?>><?= esc($item['nombre']) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <button class="boton claro" type="submit">Ver</button>
    </form>
    <form method="post" action="<?= base_url('parametros/aceptados') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="usuario_id" value="<?= esc((string) $persona) ?>">
        <div class="marcas">
            <?php foreach ($categorias as $categoria) : ?>
                <label class="check"><input type="checkbox" name="categoria_id[]" value="<?= esc($categoria['id']) ?>" <?= in_array((int) $categoria['id'], $aceptados, true) ? 'checked' : '' ?>> <?= esc($categoria['nombre']) ?></label>
            <?php endforeach ?>
        </div>
        <button class="boton" type="submit">Guardar gastos aceptados</button>
    </form>
</section>

<section class="bloque">
    <h2>Tipos de documento</h2>
    <p>La carga ofrece estos tipos. Un monto sobre el aceptado queda observado. Sin comprobante solo cuando el tipo lo permite y el monto cabe.</p>
    <form method="post" action="<?= base_url('parametros/tipos') ?>">
        <?= csrf_field() ?>
        <div class="grid-2">
            <label>Tipo
                <input name="nombre" required maxlength="80" placeholder="Vale, Transbank…">
            </label>
            <label>Monto aceptado
                <input name="monto_aceptado" value="0">
            </label>
        </div>
        <label class="check"><input type="checkbox" name="sin_comprobante" value="1"> Permite guardar sin comprobante</label>
        <button class="boton" type="submit">Agregar tipo</button>
    </form>
    <?php foreach ($tipos as $tipo) : ?>
        <form method="post" action="<?= base_url('parametros/tipos/' . $tipo['id']) ?>">
            <?= csrf_field() ?>
            <div class="grid-2">
                <label>Tipo
                    <input name="nombre" required maxlength="80" value="<?= esc($tipo['nombre']) ?>">
                </label>
                <label>Monto aceptado
                    <input name="monto_aceptado" value="<?= esc($tipo['monto_aceptado']) ?>">
                </label>
            </div>
            <label class="check"><input type="checkbox" name="sin_comprobante" value="1" <?= (int) $tipo['sin_comprobante'] === 1 ? 'checked' : '' ?>> Permite guardar sin comprobante</label>
            <label class="check"><input type="checkbox" name="activo" value="1" <?= (int) $tipo['activo'] === 1 ? 'checked' : '' ?>> Vigente</label>
            <button class="boton" type="submit">Actualizar</button>
        </form>
    <?php endforeach ?>
</section>
<?= $this->endSection() ?>
