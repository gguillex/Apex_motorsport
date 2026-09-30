<?php
/**
 * Factura de una compra del usuario autenticado.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Auth;
use App\Repository\PurchaseRepository;

Auth::requireLogin();

$purchase = (new PurchaseRepository(db()))->findForUser(query_id(), Auth::id());
if ($purchase === null) {
    render_error(404, 'La factura no existe o no pertenece a tu cuenta.');
}

const IVA = 0.21;
$total  = (float) $purchase['precio'];
$base   = round($total / (1 + IVA), 2);
$iva    = round($total - $base, 2);
$number = sprintf('APX-%s-%06d', date('Y', strtotime($purchase['fecha'])), $purchase['id']);

view('layout/header', ['title' => "Factura $number", 'styles' => ['factura.css']]);
?>

  <main class="page" id="contenido">
    <article class="invoice-box">
      <header class="invoice-header">
        <div>
          <p class="invoice-brand">APEX Motorsport</p>
          <p class="invoice-muted">Paseo de la Castellana, 259 · 28046 Madrid</p>
          <p class="invoice-muted">CIF B-00000000</p>
        </div>
        <dl class="invoice-meta">
          <div><dt>Factura</dt><dd><?= e($number) ?></dd></div>
          <div><dt>Fecha</dt><dd><?= e(format_datetime($purchase['fecha'])) ?></dd></div>
          <div><dt>Cliente</dt><dd><?= e(Auth::user()['usuario']) ?></dd></div>
        </dl>
      </header>

      <h1 class="invoice-title">Factura de compra</h1>

      <table class="invoice-lines">
        <thead>
          <tr><th scope="col">Concepto</th><th scope="col" class="num">Importe</th></tr>
        </thead>
        <tbody>
          <tr>
            <td><?= e(car_name($purchase)) ?></td>
            <td class="num"><?= e(format_price($base)) ?></td>
          </tr>
        </tbody>
        <tfoot>
          <tr><th scope="row">Base imponible</th><td class="num"><?= e(format_price($base)) ?></td></tr>
          <tr><th scope="row">IVA (21&nbsp;%)</th><td class="num"><?= e(format_price($iva)) ?></td></tr>
          <tr class="invoice-total"><th scope="row">Total abonado</th><td class="num"><?= e(format_price($total)) ?></td></tr>
        </tfoot>
      </table>

      <footer class="invoice-actions no-print">
        <button type="button" class="btn btn--primary" data-print>Imprimir / PDF</button>
        <a href="<?= e(url('mis_compras.php')) ?>" class="btn btn--outline">Volver a mis compras</a>
      </footer>
    </article>
  </main>

<?php view('layout/footer');
