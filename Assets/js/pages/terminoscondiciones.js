/**
 * Archivo: Assets/js/pages/terminos-condiciones.js
 * Gestión de términos y condiciones y políticas de datos
 * Creado por el equipo Gaes 1 - SENA ADSO 2025
 */

$(document).ready(function() {
    // Cargar contenido de términos y condiciones al abrir el modal
    $('#modalTerminos').on('show.bs.modal', function() {
        cargarTerminosCondiciones();
    });

    // Cargar contenido de políticas de datos al abrir el modal
    $('#modalPoliticas').on('show.bs.modal', function() {
        cargarPoliticasDatos();
    });

    // Manejar aceptación de términos
    $('#aceptarTerminosBtn').on('click', function() {
        $('#aceptarTerminos').prop('checked', true);
        $('#modalTerminos').modal('hide');
        
        Swal.fire({
            icon: 'success',
            title: 'Términos Aceptados',
            text: 'Has aceptado los términos y condiciones',
            timer: 2000,
            showConfirmButton: false
        });
    });

    // Manejar aceptación de políticas
    $('#aceptarPoliticasBtn').on('click', function() {
        $('#aceptarTerminos').prop('checked', true);
        $('#modalPoliticas').modal('hide');
        
        Swal.fire({
            icon: 'success',
            title: 'Políticas Aceptadas',
            text: 'Has aceptado las políticas de tratamiento de datos',
            timer: 2000,
            showConfirmButton: false
        });
    });
});

/**
 * Carga el contenido de términos y condiciones
 */
function cargarTerminosCondiciones() {
    const contenido = `
        <div class="terms-content">
            <p class="text-muted"><small>Última actualización: ${new Date().toLocaleDateString('es-CO')}</small></p>
            
            <h6><i class="material-icons me-1">info</i> 1. INFORMACIÓN GENERAL</h6>
            <p>GestorDocs es una plataforma de gestión de archivos y documentos desarrollada conforme a la legislación colombiana. Al registrarte y usar nuestros servicios, aceptas los siguientes términos y condiciones.</p>

            <h6><i class="material-icons me-1">person</i> 2. ACEPTACIÓN DE TÉRMINOS</h6>
            <p>Al crear una cuenta en GestorDocs, confirmas que:</p>
            <ul>
                <li>Eres mayor de edad (18 años) o tienes autorización de tus padres/tutores</li>
                <li>Proporcionas información veraz y actualizada</li>
                <li>Aceptas cumplir con estos términos y la legislación colombiana</li>
                <li>Eres responsable de mantener la confidencialidad de tu cuenta</li>
            </ul>

            <h6><i class="material-icons me-1">security</i> 3. USO DEL SERVICIO</h6>
            <p>Te comprometes a:</p>
            <ul>
                <li>Usar la plataforma solo para fines legales</li>
                <li>No subir contenido que infrinja derechos de autor</li>
                <li>No compartir material ofensivo, ilegal o dañino</li>
                <li>Mantener actualizada tu información de contacto</li>
                <li>No intentar vulnerar la seguridad del sistema</li>
            </ul>

            <h6><i class="material-icons me-1">storage</i> 4. CONTENIDO Y ALMACENAMIENTO</h6>
            <p>Respecto a tus archivos:</p>
            <ul>
                <li>Mantienes todos los derechos sobre tu contenido</li>
                <li>Eres responsable de hacer respaldos de tus archivos</li>
                <li>Nos reservamos el derecho de eliminar contenido que viole estos términos</li>
                <li>El espacio de almacenamiento puede tener límites según tu plan</li>
            </ul>

            <h6><i class="material-icons me-1">gavel</i> 5. LIMITACIÓN DE RESPONSABILIDAD</h6>
            <p>GestorDocs se compromete a brindar el mejor servicio posible, sin embargo:</p>
            <ul>
                <li>No garantizamos disponibilidad del 100% del servicio</li>
                <li>No somos responsables por pérdida de datos por mal uso</li>
                <li>Nuestra responsabilidad se limita al valor pagado por el servicio</li>
            </ul>

            <h6><i class="material-icons me-1">edit</i> 6. MODIFICACIONES</h6>
            <p>Nos reservamos el derecho de modificar estos términos en cualquier momento. Los cambios serán notificados con 30 días de anticipación.</p>

            <h6><i class="material-icons me-1">contact_support</i> 7. LEY APLICABLE</h6>
            <p>Estos términos se rigen por las leyes de la República de Colombia. Cualquier disputa será resuelta en los tribunales competentes de Colombia.</p>

            <hr>
            <p class="text-center"><strong>SENA - Centro de Servicios Empresariales y Turísticos</strong><br>
            <small>Programa ADSO - Gaes 1 - 2025</small></p>
        </div>
    `;
    
    $('#contenidoTerminos').html(contenido);
}

/**
 * Carga el contenido de políticas de tratamiento de datos
 */
function cargarPoliticasDatos() {
    const contenido = `
        <div class="privacy-content">
            <p class="text-muted"><small>Última actualización: ${new Date().toLocaleDateString('es-CO')}</small></p>
            
            <div class="alert alert-info">
                <i class="material-icons me-2">info</i>
                <strong>Conforme a la Ley 1581 de 2012 y Decreto 1377 de 2013</strong><br>
                Protección de Datos Personales en Colombia
            </div>

            <h6><i class="material-icons me-1">business</i> 1. RESPONSABLE DEL TRATAMIENTO</h6>
            <p><strong>Razón Social:</strong> GestorDocs - SENA ADSO<br>
            <strong>Dirección:</strong> Centro de Servicios Empresariales y Turísticos<br>
            <strong>Ciudad:</strong> Colombia<br>
            <strong>Email:</strong> contacto@gestordocs.edu.co</p>

            <h6><i class="material-icons me-1">list</i> 2. DATOS RECOLECTADOS</h6>
            <p>Recolectamos los siguientes datos personales:</p>
            <ul>
                <li><strong>Datos de identificación:</strong> Nombre completo, correo electrónico</li>
                <li><strong>Datos de contacto:</strong> Teléfono, dirección</li>
                <li><strong>Datos técnicos:</strong> Dirección IP, tipo de navegador, dispositivo</li>
                <li><strong>Datos de uso:</strong> Archivos subidos, actividad en la plataforma</li>
            </ul>

            <h6><i class="material-icons me-1">assignment</i> 3. FINALIDADES DEL TRATAMIENTO</h6>
            <p>Utilizamos tus datos personales para:</p>
            <ul>
                <li>Crear y gestionar tu cuenta de usuario</li>
                <li>Proporcionar nuestros servicios de gestión de archivos</li>
                <li>Comunicarnos contigo sobre tu cuenta y servicios</li>
                <li>Mejorar nuestros servicios y experiencia de usuario</li>
                <li>Cumplir con obligaciones legales</li>
                <li>Enviar notificaciones importantes del servicio</li>
            </ul>

            <h6><i class="material-icons me-1">verified_user</i> 4. DERECHOS DEL TITULAR</h6>
            <p>Como titular de datos personales, tienes derecho a:</p>
            <ul>
                <li><strong>Conocer:</strong> Qué datos tenemos y cómo los usamos</li>
                <li><strong>Actualizar:</strong> Corregir datos inexactos o incompletos</li>
                <li><strong>Rectificar:</strong> Solicitar corrección de información errónea</li>
                <li><strong>Suprimir:</strong> Solicitar eliminación de tus datos (cuando sea procedente)</li>
                <li><strong>Revocar:</strong> Retirar tu autorización en cualquier momento</li>
            </ul>

            <h6><i class="material-icons me-1">share</i> 5. TRANSFERENCIA DE DATOS</h6>
            <p>Tus datos personales:</p>
            <ul>
                <li>Se almacenan en servidores seguros en Colombia</li>
                <li>Solo se comparten con terceros cuando sea necesario para el servicio</li>
                <li>Nunca se venden a terceros</li>
                <li>Se protegen con medidas de seguridad apropiadas</li>
            </ul>

            <h6><i class="material-icons me-1">schedule</i> 6. CONSERVACIÓN DE DATOS</h6>
            <p>Conservamos tus datos personales:</p>
            <ul>
                <li>Mientras tengas una cuenta activa</li>
                <li>Por el tiempo requerido por la ley colombiana</li>
                <li>Hasta que solicites su eliminación (cuando sea procedente)</li>
            </ul>

            <h6><i class="material-icons me-1">security</i> 7. MEDIDAS DE SEGURIDAD</h6>
            <p>Implementamos medidas técnicas y administrativas para proteger tus datos:</p>
            <ul>
                <li>Encriptación de datos sensibles</li>
                <li>Acceso restringido a información personal</li>
                <li>Monitoreo de accesos y actividades</li>
                <li>Respaldos seguros y regulares</li>
            </ul>

            <h6><i class="material-icons me-1">contact_mail</i> 8. EJERCICIO DE DERECHOS</h6>
            <p>Para ejercer tus derechos, puedes contactarnos:</p>
            <ul>
                <li><strong>Email:</strong> datospersonales@gestordocs.edu.co</li>
                <li><strong>Asunto:</strong> "Ejercicio de Derechos - Datos Personales"</li>
                <li><strong>Tiempo de respuesta:</strong> Máximo 15 días hábiles</li>
            </ul>

            <h6><i class="material-icons me-1">update</i> 9. CAMBIOS EN LAS POLÍTICAS</h6>
            <p>Nos reservamos el derecho de actualizar estas políticas. Te notificaremos los cambios importantes por email con 30 días de anticipación.</p>

            <div class="alert alert-success">
                <i class="material-icons me-2">check_circle</i>
                <strong>Autorización:</strong> Al aceptar estas políticas, autorizas expresamente el tratamiento de tus datos personales conforme a lo establecido en la Ley 1581 de 2012.
            </div>

            <hr>
            <p class="text-center"><strong>SENA - Centro de Servicios Empresariales y Turísticos</strong><br>
            <small>Programa ADSO - Análisis y Desarrollo de Sistemas de Información</small><br>
            <small>Gaes 1 - 2025</small></p>
        </div>
    `;
    
    $('#contenidoPoliticas').html(contenido);
}

/**
 * Valida que se hayan aceptado los términos antes de enviar el formulario
 */
function validarTerminosAceptados() {
    const terminosAceptados = $('#aceptarTerminos').is(':checked');
    
    if (!terminosAceptados) {
        Swal.fire({
            icon: 'warning',
            title: 'Términos y Condiciones',
            text: 'Debes aceptar los términos y condiciones para continuar',
            confirmButtonText: 'Entendido'
        });
        return false;
    }
    
    return true;
}

// Exportar función para validación desde el formulario principal
window.validarTerminosAceptados = validarTerminosAceptados;