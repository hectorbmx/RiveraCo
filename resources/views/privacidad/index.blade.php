<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidad | Rivera Construcciones</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
    <div class="max-w-4xl mx-auto px-4 py-12 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <header class="bg-[#0B265A] px-6 py-8 text-white">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-xl font-bold">R</div>
                    <div>
                        <p class="text-sm uppercase tracking-[0.2em] text-blue-100">Rivera Construcciones</p>
                        <h1 class="text-2xl font-semibold mt-1">Política de Privacidad</h1>
                    </div>
                </div>
            </header>

            <main class="px-6 py-8 sm:px-8 lg:px-10 space-y-8 text-sm leading-7">
                <section>
                    <p>
                        En Rivera Construcciones, cuidamos la privacidad de nuestros usuarios y de la información que se procesa en nuestra aplicación móvil y web.
                        Esta política describe qué datos recopilamos, cómo los usamos, con quién los compartimos y qué derechos tienes respecto a tu información.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-slate-900 mb-3">1. Información que recopilamos</h2>
                    <ul class="list-disc pl-5 space-y-2">
                        <li>Datos de acceso: nombre, correo electrónico, usuario, contraseña encriptada y permisos asociados.</li>
                        <li>Datos de perfil y empresa: nombre de la organización, obra, área, puesto o función dentro de la operación.</li>
                        <li>Datos de uso: fecha y hora de acceso, actividad dentro de la aplicación, navegación, acciones realizadas en módulos y registros de operación.</li>
                        <li>Datos de documentación: documentos, facturas, evidencias, fotos, archivos o información relacionada con procesos de obra, compras o administración.</li>
                        <li>Datos de dispositivos: tipo de dispositivo, sistema operativo, idioma, IP y datos técnicos necesarios para seguridad y soporte.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-slate-900 mb-3">2. Finalidad del tratamiento</h2>
                    <p>Usamos la información para:</p>
                    <ul class="list-disc pl-5 space-y-2 mt-2">
                        <li>Brindar acceso y operar la aplicación.</li>
                        <li>Gestionar proyectos, obras, compras, empleados, facturación, seguimientos y documentación interna.</li>
                        <li>Mejorar la seguridad, el control de acceso y la administración de la plataforma.</li>
                        <li>Atender soporte técnico, incidencias y requerimientos legales o internos.</li>
                        <li>Cumplir con obligaciones fiscales, contables, laborales o regulatorias aplicables.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-slate-900 mb-3">3. Base legal y uso responsable</h2>
                    <p>
                        Tratamos tus datos con base en la relación contractual con la empresa, la necesidad de prestar el servicio, la seguridad operativa, el cumplimiento de obligaciones legales y, cuando corresponda, el consentimiento del usuario para ciertos tratamientos adicionales.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-slate-900 mb-3">4. Compartición de información</h2>
                    <p>La información puede compartirse únicamente con:</p>
                    <ul class="list-disc pl-5 space-y-2 mt-2">
                        <li>Personal autorizado de Rivera Construcciones.</li>
                        <li>Proveedores de servicios tecnológicos o infraestructura que operan bajo contratos de confidencialidad.</li>
                        <li>Autoridades competentes cuando exista obligación legal o requerimiento oficial.</li>
                    </ul>
                    <p class="mt-3">No vendemos ni comercializamos tus datos personales.</p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-slate-900 mb-3">5. Seguridad</h2>
                    <p>
                        Implementamos medidas razonables de seguridad para proteger la información contra acceso no autorizado, uso indebido, pérdida o alteración. Sin embargo, ningún sistema es completamente invulnerable, por lo que la seguridad de la información dependerá también del uso responsable por parte de los usuarios y del entorno del dispositivo utilizado.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-slate-900 mb-3">6. Conservación</h2>
                    <p>
                        Conservamos la información durante el tiempo necesario para cumplir con las finalidades de la aplicación, la relación contractual, los requisitos legales, fiscales o de seguridad, y para atender posibles responsabilidades derivadas del uso de la plataforma.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-slate-900 mb-3">7. Derechos del usuario</h2>
                    <p>De acuerdo con la normativa aplicable, puedes solicitar lo siguiente:</p>
                    <ul class="list-disc pl-5 space-y-2 mt-2">
                        <li>Acceso a tus datos.</li>
                        <li>Corrección de información incorrecta o incompleta.</li>
                        <li>Limitación o oposición al tratamiento, cuando sea procedente.</li>
                        <li>Eliminación de tus datos, en los casos permitidos por la ley.</li>
                        <li>Información sobre la finalidad y bases del tratamiento.</li>
                    </ul>
                    <p class="mt-3">Para ejercer estos derechos, puedes comunicarte con el responsable de la información de la empresa.</p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-slate-900 mb-3">8. Contacto</h2>
                    <p>
                        Si tienes dudas sobre esta política o deseas ejercer tus derechos relacionados con la privacidad, puedes comunicarte con Rivera Construcciones a través del correo institucional o al responsable de la aplicación designado por la empresa.
                    </p>
                </section>

                <section class="border-t border-slate-200 pt-6">
                    <p class="text-xs text-slate-500">
                        Última actualización: {{ now()->format('d/m/Y') }}
                    </p>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
