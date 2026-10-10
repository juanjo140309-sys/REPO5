<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/config.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$errores = [];
$editando = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $errores[] = 'La sesión del formulario venció. Recarga la página e inténtalo otra vez.';
    } else {
        $accion = $_POST['accion'] ?? '';
        if ($accion === 'eliminar') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if ($id) {
                $stmt = $pdo->prepare('DELETE FROM pedidos WHERE id = ?');
                $stmt->execute([$id]);
                header('Location: index.php?mensaje=eliminado');
                exit;
            }
            $errores[] = 'El pedido indicado no es válido.';
        } elseif ($accion === 'guardar') {
            $idRaw = $_POST['id'] ?? '';
            $id = $idRaw === '' ? null : filter_var($idRaw, FILTER_VALIDATE_INT);
            $direccion = trim((string)($_POST['direccion'] ?? ''));
            $estado = (string)($_POST['estado'] ?? 'Preparando');
            $sencilla = filter_var($_POST['cantidad_sencilla'] ?? '0', FILTER_VALIDATE_INT);
            $doble = filter_var($_POST['cantidad_doble'] ?? '0', FILTER_VALIDATE_INT);
            $mixta = filter_var($_POST['cantidad_mixta'] ?? '0', FILTER_VALIDATE_INT);

            if ($idRaw !== '' && !$id) $errores[] = 'El identificador del pedido no es válido.';
            if ($direccion === '' || mb_strlen($direccion) > 255) $errores[] = 'Escribe una dirección válida (máximo 255 caracteres).';
            if (!in_array($estado, ['Preparando', 'Despachado'], true)) $errores[] = 'Selecciona un estado válido.';
            foreach (['Sencilla' => $sencilla, 'Doble' => $doble, 'Mixta' => $mixta] as $nombre => $cantidad) {
                if ($cantidad === false || $cantidad < 0 || $cantidad > 10000) {
                    $errores[] = "La cantidad de hamburguesas {$nombre} debe ser un número entre 0 y 10000.";
                }
            }
            if (($sencilla ?? 0) + ($doble ?? 0) + ($mixta ?? 0) < 1) {
                $errores[] = 'Agrega al menos una hamburguesa al pedido.';
            }

            if (!$errores) {
                if ($id) {
                    $stmt = $pdo->prepare('UPDATE pedidos SET direccion=?, estado=?, cantidad_sencilla=?, cantidad_doble=?, cantidad_mixta=? WHERE id=?');
                    $stmt->execute([$direccion, $estado, $sencilla, $doble, $mixta, $id]);
                    header('Location: index.php?mensaje=actualizado');
                } else {
                    $stmt = $pdo->prepare('INSERT INTO pedidos (direccion, estado, cantidad_sencilla, cantidad_doble, cantidad_mixta) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([$direccion, $estado, $sencilla, $doble, $mixta]);
                    header('Location: index.php?mensaje=creado');
                }
                exit;
            }
            $editando = [
                'id' => $idRaw, 'direccion' => $direccion, 'estado' => $estado,
                'cantidad_sencilla' => $sencilla === false ? 0 : $sencilla,
                'cantidad_doble' => $doble === false ? 0 : $doble,
                'cantidad_mixta' => $mixta === false ? 0 : $mixta,
            ];
        }
    }
}

if (isset($_GET['editar']) && ctype_digit((string)$_GET['editar'])) {
    $stmt = $pdo->prepare('SELECT * FROM pedidos WHERE id = ?');
    $stmt->execute([(int)$_GET['editar']]);
    $pedido = $stmt->fetch();
    if ($pedido) $editando = $pedido;
}

$stmt = $pdo->query('SELECT * FROM pedidos ORDER BY creado_en DESC, id DESC');
$pedidos = $stmt->fetchAll();

$mensaje = [
    'creado' => 'Pedido registrado correctamente.',
    'actualizado' => 'Pedido actualizado correctamente.',
    'eliminado' => 'Pedido eliminado correctamente.',
][$_GET['mensaje'] ?? ''] ?? '';

$val = static function(string $campo, $predeterminado = '') use ($editando) {
    return $editando[$campo] ?? $predeterminado;
};
$totalPedidos = count($pedidos);
$preparando = count(array_filter($pedidos, static fn($p) => $p['estado'] === 'Preparando'));
$despachados = $totalPedidos - $preparando;
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pedidos | Hamburguesería</title>
<style>
:root{--bg:#fff8f0;--card:#fff;--ink:#28211d;--muted:#786d66;--primary:#d94825;--primary-dark:#b93519;--gold:#ffc857;--line:#f0dfd1;--green:#18794e;--shadow:0 12px 32px rgba(73,42,22,.08)}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.5 system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}
header{background:linear-gradient(120deg,#382016,#71351f);color:white;padding:28px 20px}
.wrap{width:min(1180px,94%);margin:auto}.brand{display:flex;align-items:center;gap:14px}.logo{width:52px;height:52px;display:grid;place-items:center;border-radius:16px;background:var(--gold);font-size:28px}
h1{font-size:clamp(1.5rem,3vw,2.2rem);margin:0}header p{margin:4px 0 0;color:#f7dfd0}
main{padding:26px 0 44px}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:22px}.stat,.panel{background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:var(--shadow)}.stat{padding:18px}.stat span{display:block;color:var(--muted);font-size:.9rem}.stat strong{font-size:1.8rem}
.columns{display:grid;grid-template-columns:minmax(280px,.85fr) minmax(0,1.6fr);gap:20px;align-items:start}.panel{padding:22px}.panel h2{font-size:1.25rem;margin:0 0 16px}
label{display:block;font-weight:650;margin:13px 0 6px}input,select{width:100%;padding:11px 12px;border:1px solid #d9c6b7;border-radius:10px;background:#fff;color:var(--ink);font:inherit}input:focus,select:focus{outline:3px solid #ffd8a6;border-color:var(--primary)}.quantities{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.quantities label{font-size:.88rem}.hint{color:var(--muted);font-size:.85rem;margin:7px 0 0}
button,.button{border:0;border-radius:10px;padding:11px 15px;font:inherit;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:6px}.primary{background:var(--primary);color:white}.primary:hover{background:var(--primary-dark)}.secondary{background:#f6e7da;color:#583421}.danger{background:#ffebe7;color:#a52a16}.actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:18px}.notice{padding:12px 15px;border-radius:10px;margin-bottom:16px;background:#e8f6ed;color:#17643e}.errors{padding:12px 15px;border-radius:10px;margin-bottom:16px;background:#fff0ee;color:#a52a16}.errors ul{margin:4px 0 0;padding-left:20px}
.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:650px}th,td{text-align:left;padding:12px 10px;border-bottom:1px solid var(--line);vertical-align:top}th{font-size:.82rem;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);background:#fffaf6}td{font-size:.94rem}.address{max-width:210px;overflow-wrap:anywhere}.badge{display:inline-block;border-radius:99px;padding:4px 9px;font-size:.8rem;font-weight:750}.preparando{background:#fff0ce;color:#815300}.despachado{background:#dff5e7;color:#17643e}.qty{white-space:nowrap}.empty{text-align:center;color:var(--muted);padding:28px}
@media(max-width:850px){.columns{grid-template-columns:1fr}.stats{grid-template-columns:repeat(3,1fr)}}@media(max-width:480px){.stats{gap:8px}.stat{padding:12px}.stat strong{font-size:1.4rem}.panel{padding:16px}.quantities{gap:7px}.quantities input{padding:9px 6px}}
</style>
</head>
<body>
<header><div class="wrap brand"><div class="logo" aria-hidden="true">🍔</div><div><h1>Pedidos de hamburguesería</h1><p>Registra, organiza y consulta los pedidos de tus clientes.</p></div></div></header>
<main class="wrap">
<?php if ($mensaje): ?><div class="notice" role="status"><?=e($mensaje)?></div><?php endif; ?>
<?php if ($errores): ?><div class="errors" role="alert"><strong>Revisa lo siguiente:</strong><ul><?php foreach ($errores as $error): ?><li><?=e($error)?></li><?php endforeach; ?></ul></div><?php endif; ?>
<section class="stats" aria-label="Resumen de pedidos">
<div class="stat"><span>Total de pedidos</span><strong><?= $totalPedidos ?></strong></div>
<div class="stat"><span>Preparando</span><strong><?= $preparando ?></strong></div>
<div class="stat"><span>Despachados</span><strong><?= $despachados ?></strong></div>
</section>
<div class="columns">
<section class="panel">
<h2><?= $editando ? '✏️ Editar pedido' : '➕ Registrar pedido' ?></h2>
<form method="post" action="index.php">
<input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>">
<input type="hidden" name="accion" value="guardar">
<?php if ($editando && !empty($editando['id'])): ?><input type="hidden" name="id" value="<?=e($val('id'))?>"><?php endif; ?>
<label for="direccion">Dirección de entrega</label>
<input id="direccion" name="direccion" maxlength="255" required placeholder="Ej. Calle 10 # 20-30, casa 4" value="<?=e($val('direccion'))?>">
<label for="estado">Estado del pedido</label>
<select id="estado" name="estado" required>
<option value="Preparando" <?=$val('estado','Preparando')==='Preparando'?'selected':''?>>Preparando</option>
<option value="Despachado" <?=$val('estado')==='Despachado'?'selected':''?>>Despachado</option>
</select>
<label>Cantidad de hamburguesas</label>
<div class="quantities">
<div><label for="sencilla">Sencilla</label><input id="sencilla" name="cantidad_sencilla" type="number" min="0" max="10000" step="1" value="<?=e($val('cantidad_sencilla',0))?>" required></div>
<div><label for="doble">Doble</label><input id="doble" name="cantidad_doble" type="number" min="0" max="10000" step="1" value="<?=e($val('cantidad_doble',0))?>" required></div>
<div><label for="mixta">Mixta</label><input id="mixta" name="cantidad_mixta" type="number" min="0" max="10000" step="1" value="<?=e($val('cantidad_mixta',0))?>" required></div>
</div>
<p class="hint">Indica 0 si no se pidió ese tipo. Debe haber al menos una hamburguesa.</p>
<div class="actions"><button class="primary" type="submit"><?= $editando ? 'Guardar cambios' : 'Registrar pedido' ?></button><?php if ($editando): ?><a class="button secondary" href="index.php">Cancelar</a><?php endif; ?></div>
</form>
</section>
<section class="panel">
<h2>📋 Pedidos registrados</h2>
<?php if (!$pedidos): ?><div class="empty">Todavía no hay pedidos. Registra el primero usando el formulario.</div><?php else: ?>
<div class="table-wrap"><table>
<thead><tr><th>Pedido</th><th>Dirección</th><th>Hamburguesas</th><th>Estado</th><th>Acciones</th></tr></thead>
<tbody>
<?php foreach ($pedidos as $p): ?>
<tr>
<td><strong>#<?=e($p['id'])?></strong><br><span class="hint"><?=e(date('d/m/Y H:i', strtotime($p['creado_en'])))?></span></td>
<td class="address"><?=e($p['direccion'])?></td>
<td class="qty">Sencilla: <?=e($p['cantidad_sencilla'])?><br>Doble: <?=e($p['cantidad_doble'])?><br>Mixta: <?=e($p['cantidad_mixta'])?></td>
<td><span class="badge <?=$p['estado']==='Despachado'?'despachado':'preparando'?>"><?=e($p['estado'])?></span></td>
<td><div class="actions" style="margin:0"><a class="button secondary" href="index.php?editar=<?=e($p['id'])?>">Editar</a>
<form method="post" action="index.php" onsubmit="return confirm('¿Seguro que deseas eliminar el pedido #<?=e($p['id'])?>?');" style="margin:0">
<input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?=e($p['id'])?>"><button class="danger" type="submit">Eliminar</button>
</form></div></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</section>
</div>
</main>
</body>
</html>
