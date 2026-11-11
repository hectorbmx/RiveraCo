<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Impresion\FirmaUsuario;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FirmaCreadorImpresionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_creator_signature_is_rendered_and_can_be_embedded_in_purchase_order_pdf(): void
    {
        if (! extension_loaded('gd')) $this->markTestSkipped('Requiere GD para crear un PNG de prueba.');
        $user = new User(['name' => 'Creador de prueba']);
        $user->id = 12;
        $user->firma_digital_path = 'usuarios/12/firmas/prueba.png';
        $image = imagecreatetruecolor(120, 40);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 255, 255, 255, 127));
        imageline($image, 5, 30, 110, 10, imagecolorallocate($image, 0, 0, 0));
        ob_start(); imagepng($image); $png = ob_get_clean(); imagedestroy($image);
        Storage::disk('public')->put($user->firma_digital_path, $png);

        $path = FirmaUsuario::ruta($user);
        $this->assertNotNull($path);
        $html = view('impresion.firma-creador', ['usuarioFirma' => $user])->render();
        $this->assertStringContainsString('Firma de Creador de prueba', $html);
        $this->assertStringContainsString('usuarios/12/firmas/prueba.png', $html);
        $pdf = new \FPDF(); $pdf->AddPage(); $pdf->Image($path, 10, 10, 30, 10, 'PNG');
        $this->assertStringContainsString('/Subtype /Image', $pdf->Output('S'));
    }

    public function test_missing_invalid_or_other_users_signature_is_never_displayed(): void
    {
        $user = new User(['name' => 'Creador']); $user->id = 12;
        $this->assertNull(FirmaUsuario::ruta($user));
        $this->assertStringNotContainsString('<img', view('impresion.firma-creador', ['usuarioFirma' => $user])->render());
        $user->firma_digital_path = 'usuarios/12/firmas/inexistente.png';
        $this->assertNull(FirmaUsuario::ruta($user));
        Storage::disk('public')->put('usuarios/12/firmas/invalida.png', 'no es una imagen');
        $user->firma_digital_path = 'usuarios/12/firmas/invalida.png';
        $this->assertNull(FirmaUsuario::ruta($user));
        $user->firma_digital_path = 'usuarios/99/firmas/otra.png';
        $this->assertNull(FirmaUsuario::ruta($user));
    }
}
