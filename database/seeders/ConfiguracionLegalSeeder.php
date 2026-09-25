<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConfiguracionSistema;

class ConfiguracionLegalSeeder extends Seeder
{
    public function run(): void
    {
        $textos = [
            'legal.aviso_privacidad.version' => '2.8.4',
            'legal.aviso_privacidad.titulo'  => 'Aviso de Privacidad',
            'legal.aviso_privacidad.texto'   => $this->avisoPrivacidad(),
            'legal.terminos.version'         => '2.8.4',
            'legal.terminos.titulo'          => 'Términos y Condiciones de Uso',
            'legal.terminos.texto'           => $this->terminosCondiciones(),
        ];

        foreach ($textos as $clave => $valor) {
            ConfiguracionSistema::updateOrCreate(
                ['clave' => $clave],
                ['valor' => $valor]
            );
        }
    }

    private function avisoPrivacidad(): string
    {
        return <<<HTML
<h3>Aviso de Privacidad - ID SOFTAWRE HOUSE</h3>
<p><strong>Última actualización:</strong> 25 de septiembre de 2026</p>

<h4>1. Identidad del Responsable</h4>
<p>ID SOFTWARE HOUSE, con domicilio en Cuautla, Morelos, México, es el responsable del tratamiento de sus datos personales conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP).</p>

<h4>2. Datos Personales Tratados</h4>
<ul>
    <li><strong>Identificación:</strong> Nombre, CURP, DNI, número de empleado, firma.</li>
    <li><strong>Contacto:</strong> Domicilio, teléfono, correo electrónico.</li>
    <li><strong>Laborales:</strong> Puesto, cargo, área, horario, ingreso, historial.</li>
    <li><strong>Asistencia:</strong> Registros, justificaciones, horas extra.</li>
    <li><strong>Salud (SENSIBLES):</strong> Motivos de falta por enfermedad.</li>
    <li><strong>Geolocalización:</strong> Latitud/longitud al realizar acciones administrativas.</li>
</ul>

<h4>3. Finalidades del Tratamiento</h4>
<p><strong>Primarias:</strong> Gestión de personal, control de asistencia, cálculo de horas extras, reportes a RH y obligaciones laborales/fiscales.</p>
<p><strong>Secundarias (con consentimiento):</strong> Análisis estadístico, desarrollo de nuevas funcionalidades, investigación interna y mejora continua de la plataforma.</p>

<h4>4. Transferencia de Datos</h4>
<p>Sus datos no serán transferidos a terceros sin su consentimiento, salvo las excepciones previstas en el artículo 37 de la LFPDPPP.</p>

<h4>5. Derechos ARCO</h4>
<p>Usted puede ejercer sus derechos de Acceso, Rectificación, Cancelación y Oposición enviando una solicitud a nuestro correo. El plazo de respuesta es de 20 días hábiles.</p>

<h4>6. Medidas de Seguridad</h4>
<p>Implementamos medidas administrativas, técnicas y físicas para proteger sus datos: encriptación bcrypt, control de acceso basado en roles (RBAC), bitácora con geolocalización, aislamiento multi-empresa y documentos PDF protegidos.</p>

<h4>7. Cambios al Aviso</h4>
<p>Cualquier cambio será notificado vía correo electrónico y mediante un aviso en la plataforma.</p>

<h4>8. Consentimiento</h4>
<p>Al aceptar este Aviso de Privacidad, usted consiente el tratamiento de sus datos personales conforme a los términos aquí descritos.</p>
HTML;
    }

    private function terminosCondiciones(): string
    {
        return <<<HTML
<h3>Términos y Condiciones de Uso - ID SOFTAWRE HOUSE</h3>
<p><strong>Vigentes a partir del:</strong> 25 de septiembre de 2026</p>

<h4>1. Aceptación</h4>
<p>El uso de ID SOFTAWRE HOUSE (web o aplicación móvil) implica la aceptación plena de estos Términos y Condiciones. Si no está de acuerdo, deberá abstenerse de utilizar la plataforma.</p>

<h4>2. Descripción del Servicio</h4>
<p>ID SOFTAWRE HOUSE es una plataforma de gestión de asistencia, horas extras, faltas y personal, operada por ID SOFTWARE HOUSE, diseñada para empresas con múltiples obras y equipos en campo.</p>

<h4>3. Usuarios y Roles</h4>
<p>El acceso se otorga mediante roles: Administrador (acceso total), Contratista (gestión de sus empresas), Jefe de Obra/Seguridad (operativo), RH/Contabilidad (reportes).</p>

<h4>4. Uso de Datos para Análisis Estadístico</h4>
<p>Usted autoriza a ID SOFTWARE HOUSE a utilizar sus datos de manera <strong>anonimizada y agregada</strong> para fines de análisis estadístico, generación de reportes de productividad, investigación interna y mejora continua de la plataforma, sin que ello implique identificación individual.</p>

<h4>5. Uso en Web y Aplicaciones Móviles</h4>
<p>Estos términos aplican tanto al uso de ID SOFTAWRE HOUSE en navegadores web como en aplicaciones móviles (iOS/Android). El uso de geolocalización es obligatorio para auditoría de acciones administrativas.</p>

<h4>6. Migración de Datos y Nuevas Funcionalidades</h4>
<p>Usted autoriza expresamente a ID SOFTWARE HOUSE a <strong>migrar, transformar y adaptar sus datos</strong> hacia nuevas funcionalidades, módulos, versiones o desarrollos futuros de la empresa, manteniendo siempre la confidencialidad y seguridad de la información.</p>

<h4>7. Obligaciones del Usuario</h4>
<ul>
    <li>Proporcionar información veraz y actualizada.</li>
    <li>Mantener la confidencialidad de sus credenciales.</li>
    <li>No compartir su cuenta con terceros.</li>
    <li>No utilizar la plataforma para fines ilícitos.</li>
</ul>

<h4>8. Propiedad Intelectual</h4>
<p>Todo el código, diseño, documentación y contenido de ID SOFTAWRE HOUSE es propiedad exclusiva de ID SOFTWARE HOUSE. Queda prohibida su reproducción total o parcial sin autorización expresa.</p>

<h4>9. Limitación de Responsabilidad</h4>
<p>ID SOFTWARE HOUSE no será responsable por daños indirectos, lucro cesante o pérdida de datos derivados del uso indebido de la plataforma.</p>

<h4>10. Modificaciones</h4>
<p>Nos reservamos el derecho de modificar estos Términos en cualquier momento. Las modificaciones serán notificadas y requerirán nueva aceptación.</p>

<h4>11. Jurisdicción</h4>
<p>Estos Términos se rigen por las leyes de los Estados Unidos Mexicanos. Cualquier controversia será resuelta en los tribunales de Cuautla, Morelos.</p>

<h4>12. Consentimiento</h4>
<p>Al aceptar estos Términos y Condiciones, usted consiente expresamente el uso de sus datos para las finalidades aquí descritas, incluida la migración hacia nuevas funcionalidades de ID SOFTWARE HOUSE.</p>
HTML;
    }
}
