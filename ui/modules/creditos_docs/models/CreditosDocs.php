<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../models/Logger.php';
require_once __DIR__ . '/../../../utils/FileUploadManager.php';

class CreditosDocs {
    private $conn;
    private $logger;

    public function __construct() {
        $this->conn = getConnection();
        $this->logger = new Logger();
    }

    /**
     * Generar número de solicitud único
     */
    private function generarNumeroSolicitud(): string {
        $prefijo = 'CD';
        $fecha = date('Ymd');
        $secuencial = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefijo . $fecha . $secuencial;
    }

    /**
     * Crear nueva solicitud de crédito
     */
    public function crearSolicitud(array $datos): array {
        try {
            $this->conn->beginTransaction();

            // Generar número de solicitud único
            $numeroSolicitud = $this->generarNumeroSolicitud();
            
            // Verificar que el número sea único
            while ($this->existeNumeroSolicitud($numeroSolicitud)) {
                $numeroSolicitud = $this->generarNumeroSolicitud();
            }

            $sql = "INSERT INTO credito_docs_solicitudes (
                numero_solicitud, tipo_solicitante, nombre_solicitante, 
                numero_identificacion, numero_telefono, correo_electronico,
                monto_deseado, numero_cuotas_deseadas, desea_codeudor,
                creado_por
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $numeroSolicitud,
                $datos['tipo_solicitante'],
                $datos['nombre_solicitante'],
                $datos['numero_identificacion'],
                $datos['numero_telefono'],
                $datos['correo_electronico'],
                $datos['monto_deseado'],
                $datos['numero_cuotas_deseadas'],
                $datos['desea_codeudor'] ?? false,
                $datos['creado_por'] ?? null
            ]);

            $solicitudId = $this->conn->lastInsertId();

            $this->conn->commit();

            $this->logger->logCrear('creditos_docs', 'Solicitud de crédito creada', [
                'solicitud_id' => $solicitudId,
                'numero_solicitud' => $numeroSolicitud,
                'tipo_solicitante' => $datos['tipo_solicitante']
            ]);

            return [
                'success' => true,
                'message' => 'Solicitud creada exitosamente',
                'solicitud_id' => $solicitudId,
                'numero_solicitud' => $numeroSolicitud
            ];

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error creando solicitud: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al crear la solicitud: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verificar si existe un número de solicitud
     */
    private function existeNumeroSolicitud(string $numero): bool {
        $sql = "SELECT COUNT(*) FROM credito_docs_solicitudes WHERE numero_solicitud = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$numero]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Obtener solicitud por ID
     */
    public function obtenerSolicitud(int $id): ?array {
        $sql = "SELECT * FROM credito_docs_solicitudes WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Listar solicitudes con filtros
     */
    public function listarSolicitudes(array $filtros = []): array {
        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['estado'])) {
            $where[] = 'estado = ?';
            $params[] = $filtros['estado'];
        }

        if (!empty($filtros['etapa'])) {
            $where[] = 'etapa_actual = ?';
            $params[] = $filtros['etapa'];
        }

        if (!empty($filtros['tipo_solicitante'])) {
            $where[] = 'tipo_solicitante = ?';
            $params[] = $filtros['tipo_solicitante'];
        }

        if (!empty($filtros['numero_solicitud'])) {
            $where[] = 'numero_solicitud LIKE ?';
            $params[] = '%' . $filtros['numero_solicitud'] . '%';
        }

        $sql = "SELECT * FROM credito_docs_solicitudes 
                WHERE " . implode(' AND ', $where) . "
                ORDER BY fecha_creacion DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Actualizar solicitud
     */
    public function actualizarSolicitud(int $id, array $datos): array {
        try {
            $campos = [];
            $params = [];

            $camposPermitidos = [
                'tipo_solicitante', 'nombre_solicitante', 'numero_identificacion',
                'numero_telefono', 'correo_electronico', 'monto_deseado',
                'numero_cuotas_deseadas', 'desea_codeudor', 'tipo_codeudor',
                'estado', 'etapa_actual', 'numero_credito_sifone',
                'valor_real_desembolso', 'fecha_desembolso', 'plazo_desembolso',
                'comentarios_rechazo'
            ];

            foreach ($camposPermitidos as $campo) {
                if (isset($datos[$campo])) {
                    $campos[] = "$campo = ?";
                    $params[] = $datos[$campo];
                }
            }

            if (empty($campos)) {
                return ['success' => false, 'message' => 'No hay campos para actualizar'];
            }

            $params[] = $id;
            $sql = "UPDATE credito_docs_solicitudes SET " . implode(', ', $campos) . " WHERE id = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            $this->logger->logEditar('creditos_docs', 'Solicitud actualizada', ['id' => $id], $datos);

            return ['success' => true, 'message' => 'Solicitud actualizada exitosamente'];

        } catch (Exception $e) {
            error_log("Error actualizando solicitud: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al actualizar la solicitud'];
        }
    }

    /**
     * Obtener configuración de documentos por etapa
     */
    public function obtenerConfiguracionDocumentos(string $etapa, string $tipoSolicitante, bool $deseaCodeudor = false): array {
        $sql = "SELECT * FROM credito_docs_configuracion_documentos 
                WHERE etapa = ? AND estado_activo = TRUE
                ORDER BY orden_display ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$etapa]);
        $documentos = $stmt->fetchAll();

        // Filtrar documentos que aplican para el tipo de solicitante
        $documentosAplicables = [];
        foreach ($documentos as $doc) {
            $tiposAplicables = json_decode($doc['aplica_para_tipo_solicitante'], true);
            if (in_array($tipoSolicitante, $tiposAplicables)) {
                $documentosAplicables[] = $doc;
            }
        }

        return $documentosAplicables;
    }

    /**
     * Obtener documentos de codeudor disponibles para subir (independiente de etapa)
     */
    public function obtenerDocumentosCodeudorDisponibles(string $tipoSolicitante): array {
        $sql = "SELECT * FROM credito_docs_configuracion_documentos 
                WHERE etapa = 'estudio' AND estado_activo = TRUE
                AND tipo_documento IN ('cedula_codeudor', 'codeudor_comprobante_1', 'codeudor_comprobante_2', 'codeudor_certificacion_laboral')
                ORDER BY orden_display ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $documentos = $stmt->fetchAll();

        // Filtrar documentos que aplican para el tipo de solicitante
        $documentosAplicables = [];
        foreach ($documentos as $doc) {
            $tiposAplicables = json_decode($doc['aplica_para_tipo_solicitante'], true);
            if (in_array($tipoSolicitante, $tiposAplicables)) {
                $documentosAplicables[] = $doc;
            }
        }

        return $documentosAplicables;
    }

    /**
     * Obtener documentos subidos de una solicitud
     */
    public function obtenerDocumentosSolicitud(int $solicitudId, string $etapa = null): array {
        $sql = "SELECT * FROM credito_docs_documentos WHERE solicitud_id = ?";
        $params = [$solicitudId];

        if ($etapa) {
            $sql .= " AND etapa = ?";
            $params[] = $etapa;
        }

        $sql .= " ORDER BY fecha_subida ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Subir documento
     */
    public function subirDocumento(int $solicitudId, string $etapa, string $tipoDocumento, array $archivo, int $usuarioId): array {
        try {
            // Obtener información de la solicitud
            $solicitud = $this->obtenerSolicitud($solicitudId);
            if (!$solicitud) {
                return ['success' => false, 'message' => 'Solicitud no encontrada'];
            }

            // Verificar si es un documento de codeudor
            $documentosCodeudor = ['cedula_codeudor', 'codeudor_comprobante_1', 'codeudor_comprobante_2', 'codeudor_certificacion_laboral'];
            $esDocumentoCodeudor = in_array($tipoDocumento, $documentosCodeudor);

            // Si es documento de codeudor, verificar que desea codeudor
            if ($esDocumentoCodeudor && !$solicitud['desea_codeudor']) {
                return ['success' => false, 'message' => 'Esta solicitud no requiere codeudor'];
            }

            // Si es documento de codeudor, permitir subir en cualquier etapa
            if ($esDocumentoCodeudor) {
                $etapa = 'estudio'; // Forzar etapa de estudio para documentos de codeudor
            }

            // Obtener configuración del documento
            $configDoc = $this->obtenerConfiguracionDocumento($etapa, $tipoDocumento);
            if (!$configDoc) {
                return ['success' => false, 'message' => 'Tipo de documento no válido'];
            }

            // Subir archivo usando FileUploadManager
            try {
                // Directorio base para créditos_docs - usar ruta absoluta correcta
                $baseDir = dirname(__DIR__, 3) . '/assets/uploads/creditos_docs';
                
                $resultadoArchivo = FileUploadManager::saveUploadedFile(
                    $archivo,
                    $baseDir,
                    [
                        'userId' => $usuarioId,
                        'prefix' => $tipoDocumento,
                        'webPath' => getBaseUrl() . 'assets/uploads/creditos_docs',
                        'createSubdirs' => true
                    ]
                );
            } catch (Exception $e) {
                return ['success' => false, 'message' => 'Error al subir el archivo: ' . $e->getMessage()];
            }

            // Guardar información del documento en BD
            $sql = "INSERT INTO credito_docs_documentos (
                solicitud_id, etapa, tipo_documento, nombre_archivo,
                ruta_archivo, tamaño_archivo, tipo_mime,
                es_obligatorio, es_opcional, es_trato_especial,
                aplica_para_tipo_solicitante, subido_por
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $solicitudId,
                $etapa,
                $tipoDocumento,
                $resultadoArchivo['originalName'],
                $resultadoArchivo['path'],
                $resultadoArchivo['size'],
                mime_content_type($resultadoArchivo['path']),
                $configDoc['es_obligatorio'],
                $configDoc['es_opcional'],
                $configDoc['es_trato_especial'],
                $configDoc['aplica_para_tipo_solicitante'],
                $usuarioId
            ]);

            $this->logger->logCrear('creditos_docs', 'Documento subido', [
                'solicitud_id' => $solicitudId,
                'tipo_documento' => $tipoDocumento,
                'etapa' => $etapa
            ]);

            return ['success' => true, 'message' => 'Documento subido exitosamente'];

        } catch (Exception $e) {
            error_log("Error subiendo documento: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al subir el documento'];
        }
    }

    /**
     * Obtener configuración de un documento específico
     */
    private function obtenerConfiguracionDocumento(string $etapa, string $tipoDocumento): ?array {
        $sql = "SELECT * FROM credito_docs_configuracion_documentos 
                WHERE etapa = ? AND tipo_documento = ? AND estado_activo = TRUE";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$etapa, $tipoDocumento]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Eliminar documento
     */
    public function eliminarDocumento(int $documentoId): array {
        try {
            // Obtener información del documento
            $sql = "SELECT * FROM credito_docs_documentos WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$documentoId]);
            $documento = $stmt->fetch();

            if (!$documento) {
                return ['success' => false, 'message' => 'Documento no encontrado'];
            }

            // Eliminar archivo físico
            if (file_exists($documento['ruta_archivo'])) {
                unlink($documento['ruta_archivo']);
            }

            // Eliminar registro de BD
            $sql = "DELETE FROM credito_docs_documentos WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$documentoId]);

            $this->logger->logEliminar('creditos_docs', 'Documento eliminado', [
                'documento_id' => $documentoId,
                'solicitud_id' => $documento['solicitud_id']
            ]);

            return ['success' => true, 'message' => 'Documento eliminado exitosamente'];

        } catch (Exception $e) {
            error_log("Error eliminando documento: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al eliminar el documento'];
        }
    }

    /**
     * Validar si una solicitud puede avanzar a la siguiente etapa
     */
    public function validarAvanceEtapa(int $solicitudId): array {
        $solicitud = $this->obtenerSolicitud($solicitudId);
        if (!$solicitud) {
            return ['success' => false, 'message' => 'Solicitud no encontrada'];
        }

        $etapaActual = $solicitud['etapa_actual'];
        $tipoSolicitante = $solicitud['tipo_solicitante'];

        // Obtener documentos requeridos para la etapa actual
        $documentosRequeridos = $this->obtenerConfiguracionDocumentos($etapaActual, $tipoSolicitante);
        
        // Obtener documentos subidos
        $documentosSubidos = $this->obtenerDocumentosSolicitud($solicitudId, $etapaActual);
        $tiposSubidos = array_column($documentosSubidos, 'tipo_documento');

        $errores = [];

        foreach ($documentosRequeridos as $doc) {
            // Validar documentos obligatorios
            if ($doc['es_obligatorio'] && !in_array($doc['tipo_documento'], $tiposSubidos)) {
                $errores[] = "Falta documento obligatorio: " . $doc['nombre_mostrar'];
            }
            
            // Validar documentos de trato especial (al menos uno debe estar presente)
            if ($doc['es_trato_especial'] && !in_array($doc['tipo_documento'], $tiposSubidos)) {
                // Verificar si hay otros documentos de trato especial del mismo grupo
                $otrosTratoEspecial = array_filter($documentosRequeridos, function($d) use ($doc) {
                    return $d['es_trato_especial'] && $d['tipo_documento'] !== $doc['tipo_documento'];
                });
                
                $tieneOtroTratoEspecial = false;
                foreach ($otrosTratoEspecial as $otroDoc) {
                    if (in_array($otroDoc['tipo_documento'], $tiposSubidos)) {
                        $tieneOtroTratoEspecial = true;
                        break;
                    }
                }
                
                if (!$tieneOtroTratoEspecial) {
                    $errores[] = "Falta documento de trato especial: " . $doc['nombre_mostrar'];
                }
            }
        }

        // Validaciones especiales
        $validacionesEspeciales = $this->validarReglasEspeciales($solicitudId, $etapaActual, $tipoSolicitante, $tiposSubidos);
        $errores = array_merge($errores, $validacionesEspeciales);

        if (empty($errores)) {
            return ['success' => true, 'message' => 'La solicitud puede avanzar a la siguiente etapa'];
        } else {
            return ['success' => false, 'message' => 'Faltan documentos requeridos', 'errores' => $errores];
        }
    }

    /**
     * Validar reglas especiales
     */
    private function validarReglasEspeciales(int $solicitudId, string $etapa, string $tipoSolicitante, array $tiposSubidos): array {
        $errores = [];

        // Validación para independientes: al menos uno de tres ingresos
        if ($tipoSolicitante === 'independiente' && $etapa === 'creacion') {
            $ingresosIndependientes = ['soporte_otros_ingresos_1', 'ingresos_certificados_contador', 'declaracion_renta'];
            $tieneAlMenosUno = false;
            
            foreach ($ingresosIndependientes as $tipo) {
                if (in_array($tipo, $tiposSubidos)) {
                    $tieneAlMenosUno = true;
                    break;
                }
            }
            
            if (!$tieneAlMenosUno) {
                $errores[] = "Para independientes debe subir al menos uno de: Soporte Otros Ingresos 1, Ingresos Certificados por Contador, o Declaración de Renta";
            }
        }

        // Validación para empleados: certificación laboral obligatoria
        if (in_array($tipoSolicitante, ['empleado_descuento_nomina', 'empleado_sin_descuento']) && $etapa === 'creacion') {
            if (!in_array('certificacion_laboral', $tiposSubidos)) {
                $errores[] = "Para empleados es obligatorio subir la Certificación Laboral";
            }
        }

        // Validación para estudiantes: orden de matrícula obligatoria
        if ($tipoSolicitante === 'estudiante' && $etapa === 'creacion') {
            if (!in_array('orden_matricula_universitaria', $tiposSubidos)) {
                $errores[] = "Para estudiantes es obligatorio subir la Orden de Matrícula Universitaria";
            }
        }

        // Validación general: al menos un extracto bancario
        if ($etapa === 'creacion') {
            $extractosBancarios = ['extracto_bancario_1', 'extracto_bancario_2', 'extracto_bancario_3'];
            $tieneAlMenosUno = false;
            
            foreach ($extractosBancarios as $tipo) {
                if (in_array($tipo, $tiposSubidos)) {
                    $tieneAlMenosUno = true;
                    break;
                }
            }
            
            if (!$tieneAlMenosUno) {
                $errores[] = "Debe subir al menos un Extracto Bancario de los últimos tres meses";
            }
        }

        // Validación para revisión: al menos uno de Datacredito o Aportes Trébol
        if ($etapa === 'revision') {
            $tieneDatacredito = in_array('estudio_datacredito', $tiposSubidos);
            $tieneAportes = in_array('certificado_aportes_trebol', $tiposSubidos);
            
            if (!$tieneDatacredito && !$tieneAportes) {
                $errores[] = "Debe subir al menos uno de: Estudio en Datacredito o Certificado de Aportes Trébol";
            }
        }

        return $errores;
    }

    /**
     * Avanzar solicitud a la siguiente etapa
     */
    public function avanzarEtapa(int $solicitudId): array {
        $validacion = $this->validarAvanceEtapa($solicitudId);
        if (!$validacion['success']) {
            return $validacion;
        }

        $solicitud = $this->obtenerSolicitud($solicitudId);
        $etapaActual = $solicitud['etapa_actual'];
        $estadoActual = $solicitud['estado'];

        $nuevaEtapa = $this->obtenerSiguienteEtapa($etapaActual);
        $nuevoEstado = $this->obtenerEstadoParaEtapa($nuevaEtapa);

        $datos = [
            'etapa_actual' => $nuevaEtapa,
            'estado' => $nuevoEstado
        ];

        $resultado = $this->actualizarSolicitud($solicitudId, $datos);
        
        if ($resultado['success']) {
            $this->logger->logEditar('creditos_docs', 'Solicitud avanzó de etapa', [
                'solicitud_id' => $solicitudId,
                'etapa_anterior' => $etapaActual,
                'etapa_nueva' => $nuevaEtapa,
                'estado_anterior' => $estadoActual,
                'estado_nuevo' => $nuevoEstado
            ]);
        }

        return $resultado;
    }

    /**
     * Obtener siguiente etapa
     */
    private function obtenerSiguienteEtapa(string $etapaActual): string {
        $etapas = ['creacion' => 'revision', 'revision' => 'estudio', 'estudio' => 'final'];
        return $etapas[$etapaActual] ?? $etapaActual;
    }

    /**
     * Obtener estado para etapa
     */
    private function obtenerEstadoParaEtapa(string $etapa): string {
        $estados = [
            'creacion' => 'solicitado',
            'revision' => 'revisado',
            'estudio' => 'con_estudio',
            'final' => 'desembolsado'
        ];
        return $estados[$etapa] ?? 'solicitado';
    }

    /**
     * Rechazar solicitud
     */
    public function rechazarSolicitud(int $solicitudId, string $comentarios): array {
        $datos = [
            'estado' => 'rechazado',
            'comentarios_rechazo' => $comentarios
        ];

        $resultado = $this->actualizarSolicitud($solicitudId, $datos);
        
        if ($resultado['success']) {
            $this->logger->logEditar('creditos_docs', 'Solicitud rechazada', [
                'solicitud_id' => $solicitudId,
                'comentarios' => $comentarios
            ]);
        }

        return $resultado;
    }

    /**
     * Reemplazar documento existente
     */
    public function reemplazarDocumento(int $documentoId, array $archivo, int $usuarioId): array {
        try {
            // Obtener información del documento existente
            $sql = "SELECT * FROM credito_docs_documentos WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$documentoId]);
            $documentoExistente = $stmt->fetch();

            if (!$documentoExistente) {
                return ['success' => false, 'message' => 'Documento no encontrado'];
            }

            // Obtener información de la solicitud para validar etapa actual
            $solicitud = $this->obtenerSolicitud($documentoExistente['solicitud_id']);
            if (!$solicitud) {
                return ['success' => false, 'message' => 'Solicitud no encontrada'];
            }

            // Solo permitir edición en etapa actual
            if ($documentoExistente['etapa'] !== $solicitud['etapa_actual']) {
                return ['success' => false, 'message' => 'Solo se pueden editar documentos de la etapa actual'];
            }

            // Eliminar archivo anterior
            if (file_exists($documentoExistente['ruta_archivo'])) {
                unlink($documentoExistente['ruta_archivo']);
            }

            // Subir nuevo archivo
            try {
                // Directorio base para créditos_docs - usar ruta absoluta correcta
                $baseDir = dirname(__DIR__, 3) . '/assets/uploads/creditos_docs';
                
                $resultadoArchivo = FileUploadManager::saveUploadedFile(
                    $archivo,
                    $baseDir,
                    [
                        'userId' => $usuarioId,
                        'prefix' => $documentoExistente['tipo_documento'],
                        'webPath' => getBaseUrl() . 'assets/uploads/creditos_docs',
                        'createSubdirs' => true
                    ]
                );
            } catch (Exception $e) {
                return ['success' => false, 'message' => 'Error al subir el archivo: ' . $e->getMessage()];
            }

            // Actualizar registro en BD
            $sql = "UPDATE credito_docs_documentos SET 
                    nombre_archivo = ?, 
                    ruta_archivo = ?, 
                    tamaño_archivo = ?, 
                    tipo_mime = ?,
                    fecha_subida = CURRENT_TIMESTAMP,
                    subido_por = ?
                    WHERE id = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $resultadoArchivo['originalName'],
                $resultadoArchivo['path'],
                $resultadoArchivo['size'],
                mime_content_type($resultadoArchivo['path']),
                $usuarioId,
                $documentoId
            ]);

            $this->logger->logEditar('creditos_docs', 'Documento reemplazado', [
                'documento_id' => $documentoId,
                'solicitud_id' => $documentoExistente['solicitud_id'],
                'tipo_documento' => $documentoExistente['tipo_documento']
            ]);

            return ['success' => true, 'message' => 'Documento reemplazado exitosamente'];

        } catch (Exception $e) {
            error_log("Error reemplazando documento: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al reemplazar el documento'];
        }
    }

    /**
     * Actualizar campos editables de la solicitud (solo etapa actual)
     */
    public function actualizarCamposEditables(int $solicitudId, array $datos): array {
        try {
            $solicitud = $this->obtenerSolicitud($solicitudId);
            if (!$solicitud) {
                return ['success' => false, 'message' => 'Solicitud no encontrada'];
            }

            // Solo permitir edición en etapa de creación
            if ($solicitud['etapa_actual'] !== 'creacion') {
                return ['success' => false, 'message' => 'Solo se pueden editar los datos básicos en la etapa de creación'];
            }

            // Campos editables solo en etapa de creación
            $camposEditables = [
                'nombre_solicitante', 'numero_telefono', 'correo_electronico',
                'monto_deseado', 'numero_cuotas_deseadas', 'desea_codeudor'
            ];

            $camposActualizar = [];
            $params = [];

            foreach ($camposEditables as $campo) {
                if (isset($datos[$campo])) {
                    $camposActualizar[] = "$campo = ?";
                    $params[] = $datos[$campo];
                }
            }

            if (empty($camposActualizar)) {
                return ['success' => false, 'message' => 'No hay campos para actualizar'];
            }

            $params[] = $solicitudId;
            $sql = "UPDATE credito_docs_solicitudes SET " . implode(', ', $camposActualizar) . " WHERE id = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            $this->logger->logEditar('creditos_docs', 'Campos editables actualizados', [
                'solicitud_id' => $solicitudId,
                'campos' => array_keys($datos)
            ]);

            return ['success' => true, 'message' => 'Datos actualizados exitosamente'];

        } catch (Exception $e) {
            error_log("Error actualizando campos editables: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al actualizar los datos'];
        }
    }

    /**
     * Obtener información del ciclo de vida
     */
    public function obtenerInfoCicloVida(): array {
        return [
            'etapas' => [
                [
                    'nombre' => 'Creación',
                    'descripcion' => 'Datos básicos del solicitante y documentos iniciales',
                    'estado' => 'solicitado',
                    'icono' => 'fas fa-plus-circle',
                    'color' => 'primary'
                ],
                [
                    'nombre' => 'Revisión',
                    'descripcion' => 'Documentos de análisis crediticio y estudios',
                    'estado' => 'revisado',
                    'icono' => 'fas fa-search',
                    'color' => 'info'
                ],
                [
                    'nombre' => 'Estudio',
                    'descripcion' => 'Evaluación del crédito y documentos de codeudor',
                    'estado' => 'con_estudio',
                    'icono' => 'fas fa-chart-line',
                    'color' => 'warning'
                ],
                [
                    'nombre' => 'Final',
                    'descripcion' => 'Documentos de desembolso y finalización',
                    'estado' => 'desembolsado',
                    'icono' => 'fas fa-check-circle',
                    'color' => 'success'
                ]
            ],
                'reglas' => [
                    'Solo se pueden editar datos y documentos de la etapa actual',
                    'Los documentos de codeudor se pueden subir en cualquier etapa si se marcó "Sí" en "Desea codeudor"',
                    'Para avanzar a la siguiente etapa deben completarse todos los documentos requeridos',
                    'Una vez desembolsado, el crédito no puede ser modificado',
                    'En cualquier momento se puede rechazar la solicitud con comentarios'
                ]
        ];
    }
}
