<?php

namespace App\Services\Presupuestos;

use App\Models\Presupuesto;
use App\Models\PresupuestoVersion;
use App\Support\ImagenesTipoPresupuesto;
use App\Support\PresentacionPresupuesto;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfPresupuesto
{
    public function __construct(private ImagenesTipoPresupuesto $imagenes)
    {
    }

    public function deVersion(Presupuesto $presupuesto, PresupuestoVersion $version, array $presentacion)
    {
        $version->setRelation('presupuesto', $presupuesto);
        $datos = $version->datos;
        $origen = $datos['presupuesto'];
        $folio = $version->folio();
        $cabecera = [
            'cliente' => ($datos['cliente']['nombre_comercial'] ?? null) ?: $origen['nombre_cliente'],
            'obra' => $origen['nombre_obra'],
            'equipo' => $origen['equipo'],
            'folio' => $folio,
            'referencia' => $origen['codigo_proyecto'],
            'fecha' => $version->created_at->format('d/m/Y'),
            'imagen_tipo' => $this->imagenes->rutaPdf($presupuesto->tipo),
        ];
        $archivo = $this->nombrePdf($origen['codigo_proyecto'], ltrim($folio, '#'));

        return $this->imprimir($cabecera, $presentacion, $archivo);
    }

    public function delPresupuesto(Presupuesto $presupuesto, array $presentacion)
    {
        $cabecera = [
            'cliente' => $presupuesto->nombreClienteVisible(),
            'obra' => $presupuesto->nombre_obra,
            'equipo' => Presupuesto::EQUIPOS_PROPUESTOS[$presupuesto->equipo_propuesto] ?? null,
            'folio' => $presupuesto->codigo_proyecto,
            'referencia' => null,
            'fecha' => now()->format('d/m/Y'),
            'imagen_tipo' => $this->imagenes->rutaPdf($presupuesto->tipo),
        ];

        return $this->imprimir($cabecera, $presentacion, $this->nombrePdf($presupuesto->codigo_proyecto));
    }

    private function imprimir(array $cabecera, array $presentacion, string $archivo)
    {
        if (! isset($presentacion['partidas'])) {
            $presentacion['filas'] = PresentacionPresupuesto::numerar(collect($presentacion['filas']))->all();
        }
        $pdf = Pdf::loadView('presupuesto.pdf-comercial', compact('cabecera', 'presentacion'))->setPaper('letter');
        $pdf->render();
        $lienzo = $pdf->getDomPDF()->getCanvas();
        $margen = 36;
        if ($lienzo->get_page_count() > 1) {
            $compacto = Pdf::loadView('presupuesto.pdf-comercial', compact('cabecera', 'presentacion') + ['compacto' => true])->setPaper('letter');
            $compacto->render();
            $lienzoCompacto = $compacto->getDomPDF()->getCanvas();
            if ($lienzoCompacto->get_page_count() < $lienzo->get_page_count()) {
                $pdf = $compacto;
                $lienzo = $lienzoCompacto;
                $margen = 30;
            }
        }

        $lienzo->page_script(function (int $pagina, int $total, $lienzo, $fuentes) use ($margen): void {
            $pie = $lienzo->get_height() - 23;
            $color = [28 / 255, 39 / 255, 129 / 255];
            $blanco = [1, 1, 1];
            $fuente = $fuentes->getFont('DejaVu Sans');
            $paginaTexto = "Hoja {$pagina} de {$total}";
            $lienzo->filled_rectangle(0, $pie, $lienzo->get_width(), 23, $color);
            $lienzo->text($margen, $pie + 7, 'RIVERA CONSTRUCCIONES, S.A. de C.V.', $fuente, 6.5, $blanco);
            $lienzo->text($lienzo->get_width() / 2 + 10, $pie + 7, 'pilas@riveraco.com.mx', $fuente, 6.5, $blanco);
            $lienzo->text($lienzo->get_width() - $margen - $fuentes->getTextWidth($paginaTexto, $fuente, 6.5),
                $pie + 7, $paginaTexto, $fuente, 6.5, $blanco);
        });

        return $pdf->stream($archivo);
    }

    private function nombrePdf(string $referencia, ?string $revision = null): string
    {
        $referencia = substr(preg_replace('/[^A-Za-z0-9_-]+/', '_', $referencia), 0, 90);
        $sufijo = $revision === null ? '' : "_{$revision}";

        return "Presupuesto_{$referencia}{$sufijo}.pdf";
    }

}
