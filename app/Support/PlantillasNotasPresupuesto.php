<?php

namespace App\Support;

use App\Models\Presupuesto;
use Illuminate\Support\Facades\DB;

class PlantillasNotasPresupuesto
{
    public const GRUPOS = ['PILAS' => 'Pilas', 'POZOS' => 'Pozos', 'MUROS_ANCLAS' => 'Muros y Anclas'];

    public const REGLAS_PILAS = [
        'exclusiones' => 'Exclusiones según los conceptos incluidos',
        'peso_acceso' => 'Capacidad del terreno y accesos según el equipo',
        'hora_extra' => 'Hora extra o equipo en espera según el equipo',
        'rima' => 'Uso de rima y sobreperforación según el equipo',
    ];

    private const NOTAS_MUROS_Y_ANCLAS = [
        'Este presupuesto incluye EPP según la norma, seguro social y personal calificado.',
        'Este presupuesto no incluye segurista, suministro de acero ni concreto, ni habilitado de acero.',
        'Este presupuesto se contempla ejecutar mediante la modalidad de precio alzado. Si hay un aumento en el mercado del 3 %, se ajustarán los precios.',
        'Al aceptar este presupuesto, enviar la orden de compra y el presupuesto firmado de autorizado por su representante legal.',
        'No nos hacemos responsables bajo ninguna circunstancia por daños a instalaciones subterráneas de cualquier tipo.',
        'Debajo de los puntos a perforar no deberán existir trabes, losas de concreto, tuberías o cualquier tipo de obstáculo.',
        'Cualquier actividad fuera de presupuesto deberá contar con un precio unitario autorizado antes de ejecutar los trabajos.',
        'Todo nuestro personal está asegurado ante el IMSS, por lo que se solicita que se nos comparta el SIROC para su debido registro.',
        'Se requiere por parte del cliente un velador o seguridad privada para el resguardo de la maquinaria y herramienta dentro de obra.',
        'En obras fuera de Guadalajara, nuestro personal tomará salida de obra al final de cada tercera semana y se ausentará el viernes y el sábado de esa semana.',
        'Vigencia del presupuesto: 30 días a partir de la fecha de elaboración.',
    ];

    public const PATENTE_POZOS = 'Patente N.º 163291. La invención de los pozos de infiltración prefabricados está protegida por la patente de la SECOFI N.º 163291 de S. H. Rivera. El desarrollo tecnológico y los procedimientos prácticos de aplicación son propiedad de Rivera Construcciones, S.A. de C.V.';

    private const NOTAS_POZOS = [
        'Este presupuesto no incluye trabajos de topografía como trazo y niveles de pozos, bocas de tormenta y trampas de grasa. En caso de derrumbes por materiales no estables, Rivera Construcciones, S.A. de C.V. no es responsable de las reparaciones correspondientes.',
        'Para ejecutar los trabajos se requiere una orden de compra y un anticipo del 100 % del pago total para programar la máquina perforadora y suministrar el material.',
        'Al recibir el pago se programará la realización de la obra.',
        'El tiempo aproximado de ejecución del pozo es de un jornal.',
        'Estos precios están sujetos a cambio sin previo aviso.',
        'Las dimensiones del pozo se cobrarán sobre medidas teóricas y pueden variar en obra.',
        'No incluye reposición de carpeta asfáltica.',
        'No incluye retiro del material producto de la perforación del pozo.',
        'No incluye ajustes de pozos, bocas de tormenta y trampas de grasa.',
        'No incluye la interconexión de la boca de tormenta al pozo o entre pozos.',
        'No incluye relleno fluido de 50 kg en pozos de infiltración.',
        'No incluye el reempedrado o reencarpetamiento en la interconexión en la parte superior (20 cm).',
        'Los pozos son exclusivamente para infiltrar aguas pluviales.',
        'No realizamos trámites ante las dependencias correspondientes a la localidad del proyecto, tales como Obras Públicas y la Comisión Nacional del Agua.',
        'No nos hacemos responsables por daños a instalaciones subterráneas como agua potable, drenaje, instalaciones eléctricas, teléfonos o gasoductos.',
        'Al recibir el pago se dan por aceptadas las cláusulas y notas de este presupuesto.',
    ];

    private const NOTAS_PILAS = [
        'Se requiere la mecánica de suelos y visita a la obra para validar el presupuesto.',
        'Incluye EPP, seguro social y personal calificado.',
        ['regla' => 'exclusiones'],
        'Se cobrará la cantidad real medida en obra. Si cambia el volumen o la ejecución se divide en varios eventos, se revisarán los precios y traslados.',
        'La perforación en roca o escombro queda fuera del alcance.',
        'Cuando el armado sea por cuenta del cliente, deberá acercarlo al punto de perforación.',
        'Equipo sujeto a disponibilidad. Confirmar antes del inicio de los trabajos.',
        'Para autorizar, enviar la orden de compra y el presupuesto firmado.',
        'Si el material perforado difiere del presupuestado, se ajustarán los precios unitarios.',
        ['regla' => 'peso_acceso'],
        'El cliente deberá informar la ubicación de las instalaciones subterráneas.',
        'Los puntos de perforación deberán estar libres de obstáculos.',
        'Mantener los cables eléctricos a un mínimo de 6 m de la torre.',
        'El ingreso a la obra y la zona de perforación deberán ser accesibles.',
        ['regla' => 'hora_extra'],
        'El cliente proporcionará trazo, niveles, agua, iluminación y corriente eléctrica según lo requiera la obra.',
        'Marcar el centro de cada perforación e indicar los niveles de perforación, acero y colado.',
        'Se requiere retroexcavadora para mover el material producto de la perforación a un primer punto.',
        'Para preparar lodos bentoníticos, se requiere un cárcamo de al menos 20 m³.',
        'Las actividades fuera del presupuesto requieren autorización previa del precio unitario.',
        'Compartir el SIROC para el registro correspondiente.',
        'Se requiere velador o seguridad privada para el resguardo de maquinaria y herramientas.',
        ['regla' => 'rima'],
        'En obras fuera de Guadalajara el personal toma salida de obra al final de cada tercera semana.',
        'Vigencia del presupuesto: 30 días a partir de la fecha de elaboración.',
    ];

    public static function grupo(string $tipo): string
    {
        return in_array($tipo, ['MUROS', 'ANCLAS'], true) ? 'MUROS_ANCLAS' : $tipo;
    }

    public static function predeterminada(string $grupo): array
    {
        $notas = match ($grupo) {
            'POZOS' => self::NOTAS_POZOS,
            'MUROS_ANCLAS' => self::NOTAS_MUROS_Y_ANCLAS,
            default => self::NOTAS_PILAS,
        };

        return array_map(fn ($nota) => is_string($nota) ? ['texto' => $nota] : $nota, $notas);
    }

    public static function vigente(string $grupo): array
    {
        $notas = DB::table('presupuesto_notas_plantillas')->where('grupo', $grupo)->value('notas');

        return $notas === null ? self::predeterminada($grupo) : json_decode($notas, true, flags: JSON_THROW_ON_ERROR);
    }

    public static function delBorrador(Presupuesto $presupuesto, string $grupo): array
    {
        return $presupuesto->presentacion_comercial['notas_plantilla'] ?? self::predeterminada($grupo);
    }
}
