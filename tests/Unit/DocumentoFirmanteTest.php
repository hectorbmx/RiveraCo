<?php

namespace Tests\Unit;

use App\Models\DocumentoFirmante;
use PHPUnit\Framework\TestCase;

class DocumentoFirmanteTest extends TestCase
{
    public function test_supports_reusable_document_scope_and_field_constants(): void
    {
        $this->assertSame('orden_compra', DocumentoFirmante::DOCUMENTO_ORDEN_COMPRA);
        $this->assertSame('reposicion_caja_chica', DocumentoFirmante::DOCUMENTO_REPOSICION_CAJA_CHICA);
        $this->assertSame('general', DocumentoFirmante::AMBITO_GENERAL);
        $this->assertSame('reposicion_gastos_almacen', DocumentoFirmante::AMBITO_REPOSICION_GASTOS_ALMACEN);
        $this->assertSame('giralda', DocumentoFirmante::AMBITO_GIRALDA);
        $this->assertSame('elaboro', DocumentoFirmante::CAMPO_ELABORO);
        $this->assertSame('vobo', DocumentoFirmante::CAMPO_VOBO);
        $this->assertSame('autorizo', DocumentoFirmante::CAMPO_AUTORIZO);
    }

    public function test_ambito_can_be_mass_assigned(): void
    {
        $firmante = new DocumentoFirmante([
            'documento' => DocumentoFirmante::DOCUMENTO_REPOSICION_CAJA_CHICA,
            'ambito' => DocumentoFirmante::AMBITO_GIRALDA,
            'campo' => DocumentoFirmante::CAMPO_VOBO,
            'user_id' => 1,
            'activo' => true,
        ]);

        $this->assertSame(DocumentoFirmante::DOCUMENTO_REPOSICION_CAJA_CHICA, $firmante->documento);
        $this->assertSame(DocumentoFirmante::AMBITO_GIRALDA, $firmante->ambito);
        $this->assertSame(DocumentoFirmante::CAMPO_VOBO, $firmante->campo);
        $this->assertSame(1, $firmante->user_id);
        $this->assertTrue($firmante->activo);
    }
}
