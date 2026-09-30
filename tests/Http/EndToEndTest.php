<?php

declare(strict_types=1);

namespace Tests\Http;

use Tests\TestCase;
use Tests\TestDatabase;

require_once __DIR__ . '/Browser.php';
require_once __DIR__ . '/Server.php';

/**
 * Pruebas de extremo a extremo: peticiones HTTP reales contra la aplicación.
 */
final class EndToEndTest extends TestCase
{
    private static string $url;

    /** @var list<string> Imágenes existentes antes de los tests (para limpiar solo las nuevas). */
    private static array $existingUploads = [];

    public static function setUpBeforeClass(): void
    {
        self::$url = Server::start();
        self::$existingUploads = self::uploads();
    }

    public static function tearDownAfterClass(): void
    {
        Server::stop();
        foreach (array_diff(self::uploads(), self::$existingUploads) as $file) {
            unlink($file);
        }
    }

    public function setUp(): void
    {
        TestDatabase::reset();
    }

    private static function uploads(): array
    {
        return glob(PUBLIC_PATH . '/' . config('uploads.dir') . '/upload-*') ?: [];
    }

    private function browser(): Browser
    {
        return new Browser(self::$url);
    }

    private function loggedIn(string $user = 'admin', string $password = 'admin123'): Browser
    {
        $b = $this->browser();
        $b->submit('login.php', 'login.php', ['usuario' => $user, 'password' => $password]);
        $this->assertSame(302, $b->status, "No se pudo iniciar sesión como $user");
        return $b;
    }

    private function registered(string $user = 'cliente'): Browser
    {
        $b = $this->browser();
        $b->submit('registro.php', 'registro.php', ['usuario' => $user, 'password' => 'secreto123', 'password_confirmation' => 'secreto123']);
        $this->assertSame(302, $b->status, "No se pudo registrar $user");
        return $b;
    }

    private function stock(int $carId): int
    {
        return (int) db()->query("SELECT stock FROM coches WHERE id = $carId")->fetchColumn();
    }

    private function tinyJpeg(): \CURLFile
    {
        $path = tempnam(sys_get_temp_dir(), 'apex-img') . '.jpg';
        $img = imagecreatetruecolor(30, 20);
        imagefill($img, 0, 0, imagecolorallocate($img, 27, 58, 92));
        imagejpeg($img, $path);
        return new \CURLFile($path, 'image/jpeg', 'coche.jpg');
    }

    /* ---------------- Páginas públicas ---------------- */

    public function testPublicPagesRespondOk(): void
    {
        $b = $this->browser();
        foreach (['', 'index.php', 'coche.php?id=1', 'api/eventos.php', 'comparar.php', 'comparar.php?id=1&id2=5', 'login.php', 'registro.php', 'legal.php'] as $page) {
            $b->get($page);
            $this->assertSame(200, $b->status, "/$page debería responder 200");
            $this->assertStringNotContains('Warning', $b->body, "/$page muestra avisos de PHP");
            $this->assertStringNotContains('Fatal error', $b->body);
        }
    }

    public function testHomeFiltersAreBuiltFromDatabase(): void
    {
        $body = $this->browser()->get('index.php')->body;
        $this->assertStringContains('<option value="aston martin">Aston Martin</option>', $body);
        $this->assertStringContains('<option value="2024">2024</option>', $body);
    }

    public function testApiReturnsCatalogAsJson(): void
    {
        $b = $this->browser()->get('api/coches.php');
        $this->assertSame(200, $b->status);
        $this->assertStringContains('application/json', $b->contentType);

        $cars = json_decode($b->body, true);
        $this->assertSame(6, count($cars));
        $this->assertSame(['id', 'marca', 'modelo', 'anio', 'precio', 'motor', 'potencia', 'aceleracion', 'velocidad', 'stock', 'imagen'], array_keys($cars[0]));
        $this->assertSame(3.7, $cars[0]['aceleracion']);
        foreach ($cars as $car) {
            $this->assertTrue(is_file(PUBLIC_PATH . '/' . $car['imagen']), "Falta la imagen {$car['imagen']}");
        }
    }

    public function testApiRejectsPost(): void
    {
        $this->assertSame(405, $this->browser()->post('api/coches.php')->status);
    }

    public function testUnknownCarReturns404(): void
    {
        $this->assertSame(404, $this->browser()->get('coche.php?id=9999')->status);
        $this->assertSame(404, $this->browser()->get('coche.php?id=abc')->status);
    }

    public function testComparatorHighlightsBetterValues(): void
    {
        $body = $this->browser()->get('comparar.php?id=1&id2=5')->body;
        $this->assertStringContains('Ferrari 488 GTB', $body);
        $this->assertStringContains('Bugatti Chiron', $body);
        $this->assertStringContains('is-better', $body);
    }

    public function testSecurityHeadersAreSent(): void
    {
        $ch = curl_init(self::$url . 'index.php');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_NOBODY => true]);
        $headers = strtolower((string) curl_exec($ch));
        curl_close($ch);

        $this->assertStringContains('x-content-type-options: nosniff', $headers);
        $this->assertStringContains('x-frame-options: sameorigin', $headers);
        $this->assertStringContains('httponly', $headers);
        $this->assertStringContains('samesite=lax', $headers);
    }

    /* ---------------- Autenticación ---------------- */

    public function testLoginWithValidAndInvalidCredentials(): void
    {
        $b = $this->browser();
        $b->submit('login.php', 'login.php', ['usuario' => 'admin', 'password' => 'mala']);
        $this->assertSame(200, $b->status);
        $this->assertStringContains('Usuario o contraseña incorrectos', $b->body);

        $b->submit('login.php', 'login.php', ['usuario' => 'admin', 'password' => 'admin123']);
        $this->assertSame('admin/', $b->redirectsTo());
    }

    public function testLoginSqlInjectionIsRejected(): void
    {
        $b = $this->browser();
        $b->submit('login.php', 'login.php', ['usuario' => "admin' -- ", 'password' => 'x']);
        $this->assertStringContains('Usuario o contraseña incorrectos', $b->body);
    }

    public function testFormsWithoutCsrfTokenAreRejected(): void
    {
        $b = $this->browser();
        $b->post('login.php', ['usuario' => 'admin', 'password' => 'admin123']);
        $this->assertSame(419, $b->status);

        $b->post('login.php', ['usuario' => 'admin', 'password' => 'admin123', '_token' => str_repeat('a', 64)]);
        $this->assertSame(419, $b->status);
    }

    public function testRegistrationCreatesCustomerAndLogsIn(): void
    {
        $b = $this->registered('nuevo.cliente');
        $this->assertSame('index.php', $b->redirectsTo());
        $this->assertStringContains('nuevo.cliente', $b->follow()->body);

        $role = db()->query("SELECT rol FROM usuarios WHERE usuario = 'nuevo.cliente'")->fetchColumn();
        $this->assertSame('usuario', $role);
    }

    public function testRegistrationCannotEscalateToAdmin(): void
    {
        $b = $this->browser();
        $b->submit('registro.php', 'registro.php', [
            'usuario' => 'listillo', 'password' => 'secreto123', 'password_confirmation' => 'secreto123', 'rol' => 'admin',
        ]);
        $this->assertSame('usuario', db()->query("SELECT rol FROM usuarios WHERE usuario = 'listillo'")->fetchColumn());
    }

    public function testRegistrationShowsValidationErrors(): void
    {
        $b = $this->browser();
        $b->submit('registro.php', 'registro.php', ['usuario' => 'admin', 'password' => 'secreto123', 'password_confirmation' => 'secreto123']);
        $this->assertStringContains('ya está en uso', $b->body);

        $b->submit('registro.php', 'registro.php', ['usuario' => 'otro', 'password' => '123', 'password_confirmation' => '123']);
        $this->assertStringContains('al menos 8 caracteres', $b->body);
    }

    public function testLogoutRequiresPost(): void
    {
        $b = $this->loggedIn();
        $b->get('logout.php');
        $this->assertSame(200, $b->get('admin/')->status, 'Un GET a logout.php no debe cerrar la sesión');

        $b->submit('admin/', 'logout.php');
        $this->assertSame('login.php', $b->get('admin/')->redirectsTo());
    }

    /* ---------------- Control de acceso ---------------- */

    public function testGuestsAreRedirectedToLogin(): void
    {
        $b = $this->browser();
        foreach (['mis_compras.php', 'cuenta.php', 'factura.php?id=1', 'admin/', 'admin/usuarios.php', 'admin/coche.php', 'admin/eventos.php', 'admin/evento.php'] as $page) {
            $this->assertSame('login.php', $b->get($page)->redirectsTo(), "/$page debería exigir login");
        }
    }

    public function testCustomersCannotAccessAdmin(): void
    {
        $b = $this->registered();
        foreach (['admin/', 'admin/usuarios.php', 'admin/coche.php', 'admin/usuario.php', 'admin/usuario.php?id=1', 'admin/eventos.php', 'admin/evento.php'] as $page) {
            $this->assertSame(403, $b->get($page)->status, "/$page debería devolver 403 a un cliente");
        }
        $b->post('admin/coche_borrar.php', ['id' => 1, '_token' => $b->get('index.php')->csrfToken()]);
        $this->assertSame(403, $b->status);
        $this->assertSame(1, (int) db()->query('SELECT COUNT(*) FROM coches WHERE id = 1')->fetchColumn());
    }

    /* ---------------- Compras ---------------- */

    public function testFullPurchaseFlow(): void
    {
        $b = $this->registered();
        $before = $this->stock(1);

        $b->submit('coche.php?id=1', 'comprar.php', ['id' => 1]);
        $this->assertSame(302, $b->status);
        $this->assertTrue(str_starts_with((string) $b->redirectsTo(), 'factura.php?id='));
        $this->assertSame($before - 1, $this->stock(1));

        $invoice = $b->follow();
        $this->assertSame(200, $invoice->status);
        $this->assertStringContains('Ferrari 488 GTB', $invoice->body);
        $this->assertStringContains('245.000', $invoice->body);
        $this->assertStringContains('IVA', $invoice->body);

        $history = $b->get('mis_compras.php')->body;
        $this->assertStringContains('Ferrari 488 GTB', $history);
    }

    public function testPurchaseRequiresPostAndCsrf(): void
    {
        $b = $this->registered();
        $before = $this->stock(1);

        $b->get('comprar.php?id=1');
        $this->assertSame($before, $this->stock(1), 'Un GET no debe realizar compras');

        $b->post('comprar.php', ['id' => 1]);
        $this->assertSame(419, $b->status);
        $this->assertSame($before, $this->stock(1));
    }

    public function testCannotBuySoldOutCar(): void
    {
        db()->exec('UPDATE coches SET stock = 0 WHERE id = 2');
        $b = $this->registered();

        $page = $b->get('coche.php?id=2')->body;
        $this->assertStringContains('Agotado', $page);

        $b->post('comprar.php', ['id' => 2, '_token' => $b->csrfToken()]);
        $this->assertSame('coche.php?id=2', $b->redirectsTo());
        $this->assertStringContains('se ha agotado', $b->follow()->body);
        $this->assertSame(0, $this->stock(2));
    }

    public function testUsersCannotSeeOtherUsersInvoices(): void
    {
        $ana = $this->registered('ana');
        $ana->submit('coche.php?id=3', 'comprar.php', ['id' => 3]);
        $invoiceUrl = $ana->redirectsTo();

        $luis = $this->registered('luis');
        $this->assertSame(404, $luis->get($invoiceUrl)->status);
        $this->assertSame(200, $ana->get($invoiceUrl)->status);
    }

    /* ---------------- Administración ---------------- */

    public function testAdminCanCreateEditAndDeleteCar(): void
    {
        $b = $this->loggedIn();

        $fields = [
            'marca' => 'Pagani', 'modelo' => 'Zonda', 'anio' => '2020', 'precio' => '1500000', 'motor' => 'V12',
            'potencia' => '760', 'aceleracion' => '3,1', 'velocidad' => '350', 'stock' => '2',
        ];
        $b->post('admin/coche.php', $fields + ['_token' => $b->get('admin/coche.php')->csrfToken(), 'imagen' => $this->tinyJpeg()]);
        $this->assertSame('admin/', $b->redirectsTo(), 'Crear coche debería redirigir al listado');

        $car = db()->query("SELECT * FROM coches WHERE modelo = 'Zonda'")->fetch();
        $this->assertSame('3.1', $car['aceleracion']);
        $this->assertTrue(is_file(PUBLIC_PATH . '/' . $car['imagen']), 'La imagen subida debe existir');
        $this->assertStringContains('Zonda', $b->get('api/coches.php')->body);

        $id = (int) $car['id'];
        $b->submit("admin/coche.php?id=$id", "admin/coche.php?id=$id", ['modelo' => 'Zonda R'] + $fields);
        $this->assertSame('Zonda R', db()->query("SELECT modelo FROM coches WHERE id = $id")->fetchColumn());
        $this->assertSame($car['imagen'], db()->query("SELECT imagen FROM coches WHERE id = $id")->fetchColumn(), 'Sin nueva foto se conserva la actual');

        $b->submit('admin/', 'admin/coche_borrar.php', ['id' => $id]);
        $this->assertSame(0, (int) db()->query("SELECT COUNT(*) FROM coches WHERE id = $id")->fetchColumn());
        clearstatcache();
        $this->assertFalse(is_file(PUBLIC_PATH . '/' . $car['imagen']), 'La imagen subida debe borrarse con el coche');
    }

    public function testAdminCarFormValidation(): void
    {
        $b = $this->loggedIn();
        $b->submit('admin/coche.php', 'admin/coche.php', ['marca' => '', 'anio' => '1500', 'precio' => '-5']);
        $this->assertSame(200, $b->status);
        $this->assertStringContains('Este campo es obligatorio', $b->body);
        $this->assertStringContains('Selecciona una foto', $b->body);
        $this->assertSame(6, (int) db()->query('SELECT COUNT(*) FROM coches')->fetchColumn());
    }

    public function testUploadRejectsNonImages(): void
    {
        $b = $this->loggedIn();
        $fake = tempnam(sys_get_temp_dir(), 'apex') . '.jpg';
        file_put_contents($fake, '<?php echo "hackeado"; ?>');

        $b->post('admin/coche.php', [
            'marca' => 'X', 'modelo' => 'Y', 'anio' => '2020', 'precio' => '1', 'motor' => 'Z', 'potencia' => '1',
            'aceleracion' => '1', 'velocidad' => '1', 'stock' => '1',
            '_token' => $b->get('admin/coche.php')->csrfToken(),
            'imagen' => new \CURLFile($fake, 'image/jpeg', 'shell.php.jpg'),
        ]);
        unlink($fake);

        $this->assertStringContains('Formato de imagen no válido', $b->body);
        $this->assertSame(6, (int) db()->query('SELECT COUNT(*) FROM coches')->fetchColumn());
    }

    public function testCannotDeleteCarWithPurchases(): void
    {
        $client = $this->registered();
        $client->submit('coche.php?id=4', 'comprar.php', ['id' => 4]);

        $admin = $this->loggedIn();
        $admin->submit('admin/', 'admin/coche_borrar.php', ['id' => 4]);
        $this->assertStringContains('No se puede borrar un coche con compras', $admin->follow()->body);
        $this->assertSame(1, (int) db()->query('SELECT COUNT(*) FROM coches WHERE id = 4')->fetchColumn());
    }

    public function testAdminUserManagement(): void
    {
        $b = $this->loggedIn();
        $b->submit('admin/usuario.php', 'admin/usuario.php', [
            'usuario' => 'gestor', 'password' => 'secreto123', 'password_confirmation' => 'secreto123', 'rol' => 'admin',
        ]);
        $this->assertSame('admin/usuarios.php', $b->redirectsTo());
        $user = db()->query("SELECT id, rol FROM usuarios WHERE usuario = 'gestor'")->fetch();
        $this->assertSame('admin', $user['rol']);

        // No puede borrarse a sí mismo.
        $b->submit('admin/usuarios.php', 'admin/usuario_borrar.php', ['id' => 1]);
        $this->assertStringContains('No puedes borrar tu propia cuenta', $b->follow()->body);

        $b->submit('admin/usuarios.php', 'admin/usuario_borrar.php', ['id' => $user['id']]);
        $this->assertSame(0, (int) db()->query("SELECT COUNT(*) FROM usuarios WHERE usuario = 'gestor'")->fetchColumn());
    }

    public function testStoredXssIsEscapedEverywhere(): void
    {
        $payload = '<script>alert(1)</script>';
        db()->prepare('UPDATE coches SET modelo = ?, motor = ? WHERE id = 1')->execute([$payload, $payload]);

        $admin = $this->loggedIn();
        foreach (['coche.php?id=1', 'comparar.php?id=1', 'admin/', 'admin/coche.php?id=1'] as $page) {
            $body = $admin->get($page)->body;
            $this->assertStringNotContains($payload, $body, "XSS sin escapar en /$page");
            $this->assertStringContains('&lt;script&gt;', $body);
        }
    }

    /* ---------------- Eventos ---------------- */

    public function testEventsApiReturnsCalendarByDate(): void
    {
        $b = $this->browser()->get('api/eventos.php');
        $this->assertSame(200, $b->status);
        $this->assertStringContains('application/json', $b->contentType);

        $events = json_decode($b->body, true);
        $this->assertSame(14, count($events));
        $this->assertSame('APEX Autumn Collection Preview', $events['2026-10-15']['name']);
        $this->assertArrayHasKey('desc', $events['2026-10-15']);
    }

    public function testEmptyEventsApiReturnsObject(): void
    {
        db()->exec('DELETE FROM eventos');
        $this->assertSame('{}', $this->browser()->get('api/eventos.php')->body);
    }

    public function testAdminCanManageEvents(): void
    {
        $b = $this->loggedIn();

        $b->submit('admin/evento.php', 'admin/evento.php', ['fecha' => '2027-06-01', 'nombre' => 'Evento de prueba', 'descripcion' => 'Descripción']);
        $this->assertSame('admin/eventos.php', $b->redirectsTo());
        $this->assertStringContains('Evento de prueba', $b->get('api/eventos.php')->body);

        $id = (int) db()->query("SELECT id FROM eventos WHERE fecha = '2027-06-01'")->fetchColumn();
        $b->submit("admin/evento.php?id=$id", "admin/evento.php?id=$id", ['fecha' => '2027-06-02', 'nombre' => 'Evento editado', 'descripcion' => 'Otra']);
        $this->assertSame('Evento editado', db()->query("SELECT nombre FROM eventos WHERE id = $id")->fetchColumn());

        $b->submit('admin/evento.php', 'admin/evento.php', ['fecha' => '2026-10-15', 'nombre' => 'Duplicado', 'descripcion' => 'X']);
        $this->assertStringContains('Ya hay un evento programado ese día', $b->body);

        $b->submit('admin/eventos.php', 'admin/evento_borrar.php', ['id' => $id]);
        $this->assertSame(0, (int) db()->query("SELECT COUNT(*) FROM eventos WHERE id = $id")->fetchColumn());
    }

    public function testEventNamesAreEscaped(): void
    {
        db()->exec("UPDATE eventos SET nombre = '<img src=x onerror=alert(1)>' WHERE fecha = '2026-10-15'");
        $body = $this->loggedIn()->get('admin/eventos.php')->body;
        $this->assertStringNotContains('<img src=x', $body);
        $this->assertStringContains('&lt;img src=x', $body);
    }

    /* ---------------- Usuarios: edición y cuenta ---------------- */

    public function testAdminCanEditUserRoleAndResetPassword(): void
    {
        $this->registered('pedro');
        $id = (int) db()->query("SELECT id FROM usuarios WHERE usuario = 'pedro'")->fetchColumn();

        $admin = $this->loggedIn();
        $admin->submit("admin/usuario.php?id=$id", "admin/usuario.php?id=$id", [
            'usuario' => 'pedro', 'rol' => 'admin', 'password' => 'reseteada123', 'password_confirmation' => 'reseteada123',
        ]);
        $this->assertSame('admin/usuarios.php', $admin->redirectsTo());
        $this->assertSame('admin', db()->query("SELECT rol FROM usuarios WHERE id = $id")->fetchColumn());

        $pedro = $this->loggedIn('pedro', 'reseteada123');
        $this->assertSame(200, $pedro->get('admin/')->status, 'Tras ascenderlo, pedro accede al panel');
    }

    public function testAdminCannotChangeOwnRole(): void
    {
        $admin = $this->loggedIn();
        $admin->submit('admin/usuario.php?id=1', 'admin/usuario.php?id=1', ['usuario' => 'admin', 'rol' => 'usuario']);
        $this->assertStringContains('No puedes cambiar tu propio rol', $admin->body);
        $this->assertSame('admin', db()->query('SELECT rol FROM usuarios WHERE id = 1')->fetchColumn());
    }

    public function testRenamingOwnAccountUpdatesSession(): void
    {
        $admin = $this->loggedIn();
        $admin->submit('admin/usuario.php?id=1', 'admin/usuario.php?id=1', ['usuario' => 'jefe', 'rol' => 'admin']);
        $this->assertStringContains('Hola, <strong>jefe</strong>', $admin->follow()->body);
    }

    public function testUserCanChangeOwnPassword(): void
    {
        $b = $this->registered('marta');

        $b->submit('cuenta.php', 'cuenta.php', ['current_password' => 'mala', 'password' => 'nuevaclave123', 'password_confirmation' => 'nuevaclave123']);
        $this->assertStringContains('La contraseña actual no es correcta', $b->body);

        $b->submit('cuenta.php', 'cuenta.php', ['current_password' => 'secreto123', 'password' => 'nuevaclave123', 'password_confirmation' => 'nuevaclave123']);
        $this->assertSame('cuenta.php', $b->redirectsTo());
        $this->assertStringContains('Contraseña actualizada', $b->follow()->body);

        $this->loggedIn('marta', 'nuevaclave123');
    }
}
