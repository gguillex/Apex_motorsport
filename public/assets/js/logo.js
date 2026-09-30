/**
 * Dibuja el logotipo de APEX Motorsport en el <canvas id="logo">.
 */
(function () {
  'use strict';

  const canvas = document.getElementById('logo');
  if (!canvas || !canvas.getContext) return;

  const ctx = canvas.getContext('2d');
  const size = 200;
  const color = getComputedStyle(document.documentElement).getPropertyValue('--color-text').trim() || '#1a1a1a';

  ctx.clearRect(0, 0, canvas.width, canvas.height);
  ctx.save();
  ctx.translate((canvas.width - size) / 2, (canvas.height - size) / 2);

  // Aspa central
  ctx.lineWidth = 20;
  ctx.lineCap = 'round';
  ctx.strokeStyle = color;
  ctx.beginPath();
  ctx.moveTo(40, 40);
  ctx.lineTo(160, 160);
  ctx.moveTo(160, 40);
  ctx.lineTo(40, 160);
  ctx.stroke();

  // Iniciales
  ctx.fillStyle = color;
  ctx.font = 'bold 42px sans-serif';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText('A', 100, 15);
  ctx.fillText('P', 100, 185);

  ctx.restore();
})();
