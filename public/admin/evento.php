<?php
/**
 * Alta (sin ?id) y edición (con ?id=N) de eventos del calendario.
 */

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth;
use App\Csrf;
use App\Exception\DomainError;
use App\Repository\EventRepository;
use App\Validation\EventValidator;

Auth::requireAdmin();

$repo = new EventRepository(db());

$id = query_id();
$existing = $id > 0 ? $repo->find($id) : null;
if ($id > 0 && $existing === null) {
    render_error(404, 'El evento que intentas editar no existe.');
}
$isEdit = $existing !== null;

$event = $existing ?? ['fecha' => '', 'nombre' => '', 'descripcion' => ''];
$errors = [];

if (is_post()) {
    Csrf::verify();

    [$data, $errors] = EventValidator::validate($_POST);

    if (!$errors) {
        try {
            if ($isEdit) {
                $repo->update($id, $data);
                flash('success', 'Evento actualizado correctamente.');
            } else {
                $repo->create($data);
                flash('success', 'Evento añadido correctamente.');
            }
            redirect('admin/eventos.php');
        } catch (DomainError $e) {
            $errors['fecha'] = $e->getMessage();
        }
    }

    $event = $data;
}

$title = $isEdit ? 'Editar evento' : 'Añadir evento';
view('layout/admin_header', ['title' => $title, 'section' => 'eventos']);
?>

  <section class="card card--form">
    <form action="" method="post" class="form" novalidate>
      <?= Csrf::field() ?>
      <p class="form-group">
        <label for="fecha">Fecha</label>
        <input type="date" id="fecha" name="fecha" value="<?= e($event['fecha']) ?>" required<?= field_attrs($errors, 'fecha') ?>>
        <?= field_error($errors, 'fecha') ?>
      </p>
      <p class="form-group">
        <label for="nombre">Nombre del evento</label>
        <input type="text" id="nombre" name="nombre" value="<?= e($event['nombre']) ?>" maxlength="120" required<?= field_attrs($errors, 'nombre') ?>>
        <?= field_error($errors, 'nombre') ?>
      </p>
      <p class="form-group">
        <label for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="5" maxlength="1000" required<?= field_attrs($errors, 'descripcion') ?>><?= e($event['descripcion']) ?></textarea>
        <?= field_error($errors, 'descripcion') ?>
      </p>
      <p class="form-actions">
        <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Guardar cambios' : 'Añadir evento' ?></button>
        <a href="<?= e(url('admin/eventos.php')) ?>" class="btn btn--outline">Cancelar</a>
      </p>
    </form>
  </section>

<?php view('layout/admin_footer');
