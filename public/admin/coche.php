<?php
/**
 * Alta (sin ?id) y edición (con ?id=N) de coches.
 */

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth;
use App\Csrf;
use App\Exception\DomainError;
use App\Repository\CarRepository;
use App\Service\ImageUploader;
use App\Validation\CarValidator;

Auth::requireAdmin();

$repo = new CarRepository(db());
$uploader = new ImageUploader(PUBLIC_PATH, (string) config('uploads.dir'), (int) config('uploads.max_bytes'));

$id = query_id();
$existing = $id > 0 ? $repo->find($id) : null;
if ($id > 0 && $existing === null) {
    render_error(404, 'El coche que intentas editar no existe.');
}
$isEdit = $existing !== null;

$car = $existing ?? ['stock' => 5, 'imagen' => ''];
$errors = [];

if (is_post()) {
    Csrf::verify();

    [$data, $errors] = CarValidator::validate($_POST);
    $data['imagen'] = $existing['imagen'] ?? '';
    $file = $_FILES['imagen'] ?? null;

    if (!ImageUploader::hasFile($file) && !$isEdit) {
        $errors['imagen'] = 'Selecciona una foto del vehículo.';
    }

    if (!$errors && ImageUploader::hasFile($file)) {
        try {
            $data['imagen'] = $uploader->store($file);
        } catch (DomainError $e) {
            $errors['imagen'] = $e->getMessage();
        }
    }

    if (!$errors) {
        if ($isEdit) {
            $repo->update($id, $data);
            if ($data['imagen'] !== $existing['imagen']) {
                $uploader->delete($existing['imagen']);
            }
            flash('success', 'Coche actualizado correctamente.');
        } else {
            $repo->create($data);
            flash('success', 'Coche añadido correctamente.');
        }
        redirect('admin/');
    }

    // Volver a mostrar lo que el usuario escribió.
    foreach (CarRepository::FIELDS as $field) {
        if ($field !== 'imagen') {
            $car[$field] = input_string($_POST, $field);
        }
    }
}

$fields = [
    // nombre => [etiqueta, tipo, atributos extra]
    'marca'       => ['Marca', 'text', 'maxlength="50"'],
    'modelo'      => ['Modelo', 'text', 'maxlength="80"'],
    'anio'        => ['Año', 'number', 'min="1900" max="' . ((int) date('Y') + 1) . '"'],
    'precio'      => ['Precio (€, IVA incluido)', 'number', 'min="0.01" step="0.01"'],
    'motor'       => ['Motor', 'text', 'maxlength="100" placeholder="p. ej. V8 Biturbo"'],
    'potencia'    => ['Potencia (CV)', 'number', 'min="0" max="5000"'],
    'aceleracion' => ['Aceleración 0–100 km/h (s)', 'number', 'min="0" max="99.9" step="0.1"'],
    'velocidad'   => ['Velocidad máxima (km/h)', 'number', 'min="0" max="600"'],
    'stock'       => ['Stock (unidades)', 'number', 'min="0"'],
];

$title = $isEdit ? 'Editar coche' : 'Añadir coche';
view('layout/admin_header', ['title' => $title, 'section' => 'coches']);
?>

  <section class="card card--form">
    <form action="" method="post" enctype="multipart/form-data" class="form form--grid" novalidate>
      <?= Csrf::field() ?>

      <?php foreach ($fields as $name => [$label, $type, $attrs]): ?>
        <p class="form-group">
          <label for="<?= $name ?>"><?= e($label) ?></label>
          <input type="<?= $type ?>" id="<?= $name ?>" name="<?= $name ?>" value="<?= e($car[$name] ?? '') ?>"
                 <?= $attrs ?> required<?= field_attrs($errors, $name) ?>>
          <?= field_error($errors, $name) ?>
        </p>
      <?php endforeach; ?>

      <div class="form-group form-group--full">
        <label for="imagen">Foto <span class="hint">(JPG, PNG o WebP, máx. <?= intdiv((int) config('uploads.max_bytes'), 1024 * 1024) ?> MB)</span></label>
        <?php if (!empty($car['imagen'])): ?>
          <img src="<?= e(url($car['imagen'])) ?>" alt="Foto actual" class="form-preview">
          <span class="hint">Deja el campo vacío para mantener la foto actual.</span>
        <?php endif; ?>
        <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp"
               <?= $isEdit ? '' : 'required' ?><?= field_attrs($errors, 'imagen') ?>>
        <?= field_error($errors, 'imagen') ?>
      </div>

      <p class="form-actions form-group--full">
        <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Guardar cambios' : 'Añadir coche' ?></button>
        <a href="<?= e(url('admin/')) ?>" class="btn btn--outline">Cancelar</a>
      </p>
    </form>
  </section>

<?php view('layout/admin_footer');
