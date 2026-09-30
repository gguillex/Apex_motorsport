<?php
/**
 * Pie común de la web pública.
 *
 * @var list<string> $scripts Scripts adicionales de assets/js/.
 * @var bool         $jquery  Carga jQuery (necesario para catalog.js).
 */
$scripts ??= [];
$jquery  ??= false;
?>
  <footer class="site-footer" id="contacto">
    <div class="footer-inner">
      <section class="footer-brand">
        <h2 class="footer-logo-text">APEX Motorsport</h2>
        <p class="footer-tagline">Exclusividad en movimiento</p>
      </section>

      <section class="footer-contact">
        <h3 class="footer-col-title">Contacto</h3>
        <address>
          <p>APEX Motorsport S.L.</p>
          <p>Paseo de la Castellana, 259</p>
          <p>28046 Madrid, España</p>
          <p><a href="tel:+34910000000">+34 910 000 000</a></p>
          <p><a href="mailto:info@apexmotorsport.es">info@apexmotorsport.es</a></p>
        </address>
      </section>

      <nav class="footer-legal" aria-label="Información legal">
        <h3 class="footer-col-title">Legal</h3>
        <ul>
          <li><a href="<?= e(url('legal.php')) ?>#privacidad">Política de privacidad</a></li>
          <li><a href="<?= e(url('legal.php')) ?>#aviso">Aviso legal</a></li>
          <li><a href="<?= e(url('legal.php')) ?>#cookies">Política de cookies</a></li>
          <li><a href="<?= e(url('legal.php')) ?>#terminos">Términos y condiciones</a></li>
          <li><a href="<?= e(url('legal.php')) ?>#accesibilidad">Accesibilidad</a></li>
        </ul>
      </nav>
    </div>

    <div class="footer-bottom">
      <p class="copyright">
        &copy; 2024&ndash;<?= date('Y') ?> APEX Motorsport S.L. Todos los derechos reservados.
      </p>
    </div>
  </footer>

  <?php if ($jquery): ?>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"
          integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
          crossorigin="anonymous"></script>
  <?php endif; ?>
  <script src="<?= e(asset('assets/js/app.js')) ?>"></script>
  <?php foreach ($scripts as $script): ?>
  <script src="<?= e(asset('assets/js/' . $script)) ?>"></script>
  <?php endforeach; ?>
</body>
</html>
