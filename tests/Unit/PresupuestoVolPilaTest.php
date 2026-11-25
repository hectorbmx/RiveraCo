<?php

namespace Tests\Unit;

use App\Models\PresupuestoVolPila;
use PHPUnit\Framework\TestCase;

class PresupuestoVolPilaTest extends TestCase
{
    public function test_el_traslape_longitudinal_empieza_en_doce_metros_de_corte(): void
    {
        $pila = new PresupuestoVolPila([
            'diametro' => 0.8,
            'piezas' => 16,
            'longitud_l0' => 7,
            'intervalo_l0' => 0.075,
            'varillas_l0' => 20,
            'calibre_varilla_l0' => 6,
        ]);

        $corta = $pila->volumetria();
        $this->assertSame(0.0, $corta['traslape_varillas']);
        $this->assertEqualsWithDelta(439.802944555, $corta['acero_kg_por_pila'], 0.000001);
        $this->assertEqualsWithDelta(7036.84711288, $corta['acero'], 0.000001);

        $pila->longitud_l0 = 11.67;
        $this->assertSame(0.0, $pila->volumetria(0.5)['traslape_varillas']);

        $pila->longitud_l0 = 11.68;
        $this->assertEqualsWithDelta(42.8625, $pila->volumetria()['traslape_varillas'], 0.000001);
        $this->assertEqualsWithDelta(22.5, $pila->volumetria(0.5)['traslape_varillas'], 0.000001);
        $this->assertEqualsWithDelta(42.8625, $pila->volumetria(12)['traslape_varillas'], 0.000001);

        $pila->longitud_l0 = 22.7275;
        $this->assertEqualsWithDelta(42.8625, $pila->volumetria()['traslape_varillas'], 0.000001);
        $pila->longitud_l0 = 22.728;
        $this->assertEqualsWithDelta(85.725, $pila->volumetria()['traslape_varillas'], 0.000001);
    }
}
