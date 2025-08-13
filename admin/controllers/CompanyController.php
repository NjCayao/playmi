<?php

/**
 * Controlador de Empresas PLAYMI
 * Maneja todo el CRUD de empresas clientes
 */

require_once 'BaseController.php';
require_once __DIR__ . '/../models/Company.php';

class CompanyController extends BaseController
{
    private $companyModel;

    public function __construct()
    {
        parent::__construct();
        $this->companyModel = new Company();
    }

    /**
     * Mostrar lista de empresas con filtros
     */
    public function index()
    {
        try {
            $this->requireAuth();

            // Obtener filtros de la URL
            $filters = [
                'search' => trim($_GET['search'] ?? ''),
                'estado' => $_GET['estado'] ?? '',
                'tipo_paquete' => $_GET['tipo_paquete'] ?? '',
                'proximas_vencer' => isset($_GET['proximas_vencer']) ? true : false
            ];

            // PAGINACIÓN - Usar la constante PAGINATION_LIMIT
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = PAGINATION_LIMIT; // Ahora usa 25 de la constante
            $offset = ($page - 1) * $perPage;

            // Obtener total de empresas (para calcular páginas)
            $totalCompanies = $this->companyModel->countWithFilters($filters);

            // Obtener empresas con filtros y paginación
            $companies = $this->companyModel->findWithFilters($filters, $perPage, $offset);

            // Obtener estadísticas
            $stats = [
                'total' => $this->companyModel->count(),
                'active' => $this->companyModel->count("estado = 'activo'"),        // ← 'active' no 'activo'
                'suspended' => $this->companyModel->count("estado = 'suspendido'"), // ← 'suspended' no 'suspendido'
                'expired' => $this->companyModel->count("estado = 'vencido'")       // ← 'expired' no 'vencido'
            ];

            // Información de paginación
            $totalPages = ceil($totalCompanies / $perPage);
            $pagination = [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalCompanies,
                'total_pages' => $totalPages,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages,
                'previous_page' => $page > 1 ? $page - 1 : null,
                'next_page' => $page < $totalPages ? $page + 1 : null
            ];

            return [
                'companies' => $companies,
                'filters' => $filters,
                'stats' => $stats,
                'pagination' => $pagination
            ];
        } catch (Exception $e) {
            error_log("Error in CompanyController::index(): " . $e->getMessage());

            return [
                'companies' => [],
                'filters' => [],
                'stats' => ['total' => 0, 'active' => 0, 'suspended' => 0, 'expired' => 0],
                'pagination' => ['current_page' => 1, 'per_page' => $perPage ?? 25, 'total' => 0, 'total_pages' => 0]
            ];
        }
    }

    /**
     * Mostrar formulario de crear empresa
     */
    public function create()
    {
        try {
            $this->requireAuth();

            // Calcular fechas por defecto
            $defaultStartDate = date('Y-m-d');
            $defaultEndDate = date('Y-m-d', strtotime('+' . DEFAULT_LICENSE_MONTHS . ' months'));

            return [
                'default_start_date' => $defaultStartDate,
                'default_end_date' => $defaultEndDate,
                'package_types' => [
                    PACKAGE_BASIC => 'Básico',
                    PACKAGE_INTERMEDIATE => 'Intermedio',
                    PACKAGE_PREMIUM => 'Premium'
                ],
                'default_prices' => [
                    PACKAGE_BASIC => DEFAULT_BASIC_PRICE,
                    PACKAGE_INTERMEDIATE => DEFAULT_INTERMEDIATE_PRICE,
                    PACKAGE_PREMIUM => DEFAULT_PREMIUM_PRICE
                ]
            ];
        } catch (Exception $e) {
            $this->logError("Error en companies create: " . $e->getMessage());
            $this->setMessage('Error cargando formulario', MSG_ERROR);
            return ['error' => true];
        }
    }

    /**
     * Procesar creación de empresa
     */
    public function store()
    {
        try {
            $this->requireAuth();

            if (!$this->isPost()) {
                $this->jsonResponse(['error' => 'Método no permitido'], 405);
            }

            // Sanitizar datos de entrada
            $data = $this->sanitizeInput($_POST);

            // Validar datos
            $errors = $this->validateInput($data, [
                'nombre' => [
                    'required' => true,
                    'label' => 'Nombre de la empresa',
                    'max_length' => 100
                ],
                'ruc' => [
                    'required' => true,
                    'label' => 'RUC',
                    'pattern' => '/^[0-9]{11}$/',
                    'message' => 'El RUC debe tener 11 dígitos numéricos'
                ],
                'email_contacto' => [
                    'required' => true,
                    'label' => 'Email de contacto',
                    'email' => true,
                    'max_length' => 100
                ],
                'telefono' => [
                    'label' => 'Teléfono',
                    'max_length' => 20
                ],
                'persona_contacto' => [
                    'label' => 'Persona de contacto',
                    'max_length' => 100
                ],
                'tipo_paquete' => [
                    'required' => true,
                    'label' => 'Tipo de paquete'
                ],
                'fecha_inicio' => [
                    'required' => true,
                    'label' => 'Fecha de inicio',
                    'date' => true
                ],
                'fecha_vencimiento' => [
                    'required' => true,
                    'label' => 'Fecha de vencimiento',
                    'date' => true
                ],
                'total_buses' => [
                    'label' => 'Total de buses',
                    'numeric' => true
                ],
                'costo_mensual' => [
                    'label' => 'Costo mensual',
                    'numeric' => true
                ]
            ]);

            // Validaciones adicionales
            if (empty($errors)) {
                // Validar RUC formato correcto
                if (!$this->companyModel->validateRuc($data['ruc'])) {
                    $errors['ruc'] = 'El RUC ingresado no es válido';
                }

                // Verificar que RUC no exista
                if ($this->companyModel->rucExists($data['ruc'])) {
                    $errors['ruc'] = 'Ya existe una empresa registrada con este RUC';
                }

                // Verificar que nombre no exista
                if ($this->companyModel->nameExists($data['nombre'])) {
                    $errors['nombre'] = 'Ya existe una empresa con este nombre';
                }

                // Verificar que email no exista
                if ($this->companyModel->emailExists($data['email_contacto'])) {
                    $errors['email_contacto'] = 'Ya existe una empresa con este email';
                }

                // Verificar fechas
                if (strtotime($data['fecha_vencimiento']) <= strtotime($data['fecha_inicio'])) {
                    $errors['fecha_vencimiento'] = 'La fecha de vencimiento debe ser posterior a la fecha de inicio';
                }

                // Verificar tipo de paquete válido
                $validPackages = [PACKAGE_BASIC, PACKAGE_INTERMEDIATE, PACKAGE_PREMIUM];
                if (!in_array($data['tipo_paquete'], $validPackages)) {
                    $errors['tipo_paquete'] = 'Tipo de paquete no válido';
                }
            }

            if (!empty($errors)) {
                $this->jsonResponse(['error' => 'Datos inválidos', 'errors' => $errors], 400);
            }

            // Manejar subida de logo si existe
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = $this->handleFileUpload(
                    $_FILES['logo'],
                    COMPANIES_PATH,
                    ALLOWED_IMAGE_EXT,
                    MAX_IMAGE_SIZE
                );

                if ($uploadResult['success']) {
                    $data['logo_path'] = $uploadResult['filename'];
                } else {
                    $this->jsonResponse(['error' => 'Error subiendo logo: ' . $uploadResult['error']], 400);
                }
            }

            // Crear empresa
            $companyId = $this->companyModel->create($data);

            if ($companyId) {
                // Registrar actividad
                $this->logActivity('create', 'companies', $companyId, null, $data);

                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Empresa creada exitosamente',
                    'company_id' => $companyId,
                    'redirect' => BASE_URL . 'views/companies/index.php'
                ]);
            } else {
                $this->jsonResponse(['error' => 'Error creando la empresa'], 500);
            }
        } catch (Exception $e) {
            $this->logError("Error en companies store: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error interno del sistema'], 500);
        }
    }

    /**
     * Mostrar detalles de empresa
     */
    public function show()
    {
        try {
            $this->requireAuth();

            $companyId = (int)($_GET['id'] ?? 0);

            if (!$companyId) {
                $this->setMessage('ID de empresa requerido', MSG_ERROR);
                $this->redirect('views/companies/index.php');
            }

            $company = $this->companyModel->findById($companyId);

            if (!$company) {
                $this->setMessage('Empresa no encontrada', MSG_ERROR);
                $this->redirect('views/companies/index.php');
            }

            // Obtener estadísticas de la empresa
            $stats = [
                'total_content' => 0, // TODO: Implementar cuando tengamos ContentModel
                'total_downloads' => 0,
                'total_size' => 0,
                'last_access' => null
            ];

            // Obtener logs de actividad
            require_once __DIR__ . '/../models/ActivityLog.php';
            $activityLog = new ActivityLog();
            $logs = $activityLog->getByRecord('companies', $companyId, 20);

            // Formatear logs para timeline
            $formattedLogs = [];
            foreach ($logs as $log) {
                $formattedLogs[] = [
                    'action' => $log['accion'],
                    'description' => $log['descripcion'] ?? '',
                    'user' => $log['username'] ?? 'Sistema',
                    'created_at' => $log['created_at'],
                    'icon' => $this->getActionIcon($log['accion']),
                    'color' => $this->getActionColor($log['accion'])
                ];
            }

            return [
                'company' => $company,
                'stats' => $stats,
                'logs' => $formattedLogs
            ];
        } catch (Exception $e) {
            $this->setMessage('Error al cargar los detalles', MSG_ERROR);
            $this->redirect('views/companies/index.php');
        }
    }

    /**
     * Validar datos de empresa (para create y update)
     */
    private function validateCompanyData($data, $excludeId = null)
    {
        $errors = [];

        // Validar nombre
        if (empty(trim($data['nombre'] ?? ''))) {
            $errors['nombre'] = 'El nombre es requerido';
        } elseif (strlen(trim($data['nombre'])) < 3) {
            $errors['nombre'] = 'El nombre debe tener al menos 3 caracteres';
        } else {
            // Verificar duplicados - el modelo ya excluye el ID actual
            $existing = $this->companyModel->findByName(trim($data['nombre']), $excludeId);
            if ($existing) {
                $errors['nombre'] = 'Ya existe una empresa con este nombre';
            }
        }

        // Validar RUC
        if (empty(trim($data['ruc'] ?? ''))) {
            $errors['ruc'] = 'El RUC es requerido';
        } elseif (!preg_match('/^[0-9]{11}$/', trim($data['ruc']))) {
            $errors['ruc'] = 'El RUC debe tener exactamente 11 dígitos numéricos';
        } elseif (!in_array(substr(trim($data['ruc']), 0, 2), ['10', '15', '17', '20'])) {
            $errors['ruc'] = 'El RUC debe empezar con 10 (Persona Natural), 15 (Sucesión), 17 (Régimen Especial) o 20 (Empresa)';
        } else {
            // Verificar duplicados (excluyendo la empresa actual en edición)
            $existing = $this->companyModel->findByRuc(trim($data['ruc']), $excludeId);
            if ($existing) {
                $errors['ruc'] = 'Ya existe una empresa registrada con este RUC';
            }
        }

        // Validar email
        if (empty(trim($data['email_contacto'] ?? ''))) {
            $errors['email_contacto'] = 'El email es requerido';
        } elseif (!filter_var(trim($data['email_contacto']), FILTER_VALIDATE_EMAIL)) {
            $errors['email_contacto'] = 'El email no es válido';
        } else {
            // Verificar duplicados - el modelo ya excluye el ID actual
            $existing = $this->companyModel->findByEmail(trim($data['email_contacto']), $excludeId);
            if ($existing) {
                $errors['email_contacto'] = 'Ya existe una empresa con este email';
            }
        }

        // Validar tipo de paquete
        if (empty($data['tipo_paquete'] ?? '')) {
            $errors['tipo_paquete'] = 'El tipo de paquete es requerido';
        } elseif (!in_array($data['tipo_paquete'], ['basico', 'intermedio', 'premium'])) {
            $errors['tipo_paquete'] = 'Tipo de paquete inválido';
        }

        // Validar fechas
        if (empty($data['fecha_inicio'] ?? '')) {
            $errors['fecha_inicio'] = 'La fecha de inicio es requerida';
        }

        if (empty($data['fecha_vencimiento'] ?? '')) {
            $errors['fecha_vencimiento'] = 'La fecha de vencimiento es requerida';
        }

        // Validar que fecha de vencimiento sea posterior a fecha de inicio
        if (!empty($data['fecha_inicio']) && !empty($data['fecha_vencimiento'])) {
            try {
                $fechaInicio = new DateTime($data['fecha_inicio']);
                $fechaVencimiento = new DateTime($data['fecha_vencimiento']);

                if ($fechaVencimiento <= $fechaInicio) {
                    $errors['fecha_vencimiento'] = 'La fecha de vencimiento debe ser posterior a la fecha de inicio';
                }
            } catch (Exception $e) {
                $errors['fecha_inicio'] = 'Formato de fecha inválido';
            }
        }

        // Validar costo mensual
        if (!empty($data['costo_mensual']) && !is_numeric($data['costo_mensual'])) {
            $errors['costo_mensual'] = 'El costo mensual debe ser un número válido';
        }

        // Validar total de buses
        if (!empty($data['total_buses']) && (!is_numeric($data['total_buses']) || $data['total_buses'] < 0)) {
            $errors['total_buses'] = 'El total de buses debe ser un número positivo';
        }

        return $errors;
    }

    /**
     * Obtener icono para acción del log
     */
    private function getActionIcon($action)
    {
        $icons = [
            'create' => 'fas fa-plus',
            'update' => 'fas fa-edit',
            'delete' => 'fas fa-trash',
            'update_logo' => 'fas fa-image',
            'extend_license' => 'fas fa-calendar-plus',
            'change_status' => 'fas fa-toggle-on'
        ];

        return $icons[$action] ?? 'fas fa-info';
    }

    /**
     * Obtener color para acción del log
     */
    private function getActionColor($action)
    {
        $colors = [
            'create' => 'success',
            'update' => 'info',
            'delete' => 'danger',
            'update_logo' => 'warning',
            'extend_license' => 'primary',
            'change_status' => 'secondary'
        ];

        return $colors[$action] ?? 'info';
    }


    /**
     * Mostrar formulario de edición
     */
    public function edit()
    {
        try {
            $this->requireAuth();

            $companyId = (int)($_GET['id'] ?? 0);

            if (!$companyId) {
                $this->setMessage('ID de empresa requerido', MSG_ERROR);
                $this->redirect('views/companies/index.php');
            }

            $company = $this->companyModel->findById($companyId);

            if (!$company) {
                $this->setMessage('Empresa no encontrada', MSG_ERROR);
                $this->redirect('views/companies/index.php');
            }

            return [
                'company' => $company
            ];
        } catch (Exception $e) {
            $this->setMessage('Error al cargar la empresa', MSG_ERROR);
            $this->redirect('views/companies/index.php');
        }
    }

    /**
     * Procesar actualización de empresa
     */
    public function update()
    {
        try {
            $this->requireAuth();

            // Obtener ID de la empresa
            $companyId = (int)($_POST['company_id'] ?? 0);

            if (!$companyId) {
                echo json_encode([
                    'success' => false,
                    'error' => 'ID de empresa requerido'
                ]);
                exit;
            }

            // Verificar que la empresa existe
            $existingCompany = $this->companyModel->findById($companyId);
            if (!$existingCompany) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Empresa no encontrada'
                ]);
                exit;
            }

            // 🔍 DEBUG FORZADO - Mostrar información antes de validar
            $debugInfo = [
                'company_id' => $companyId,
                'post_ruc' => $_POST['ruc'] ?? 'NO_RUC',
                'post_email' => $_POST['email_contacto'] ?? 'NO_EMAIL',
                'existing_company' => $this->companyModel->findById($companyId),
                'ruc_validation' => $this->companyModel->validateRuc(trim($_POST['ruc'] ?? '')),
                'email_check' => $this->companyModel->findByEmail(trim($_POST['email_contacto'] ?? ''), $companyId),
                'ruc_check' => $this->companyModel->findByRuc(trim($_POST['ruc'] ?? ''), $companyId),
                'all_post_data' => $_POST
            ];


            // Validar datos de entrada
            $errors = $this->validateCompanyData($_POST, $companyId);

            if (!empty($errors)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Datos inválidos',
                    'errors' => $errors,
                    'debug' => $debugInfo  // 🔍 Incluir debug temporalmente
                ]);
                exit;
            }

            // Preparar datos para actualización
            $updateData = [
                'nombre' => trim($_POST['nombre']),
                'ruc' => trim($_POST['ruc']),
                'email_contacto' => trim($_POST['email_contacto']),
                'persona_contacto' => trim($_POST['persona_contacto'] ?? ''),
                'telefono' => trim($_POST['telefono'] ?? ''),
                'color_primario' => $_POST['color_primario'] ?? '#000000',
                'color_secundario' => $_POST['color_secundario'] ?? '#FFFFFF',
                'nombre_servicio' => trim($_POST['nombre_servicio'] ?? ''),
                'tipo_paquete' => $_POST['tipo_paquete'],
                'total_buses' => (int)($_POST['total_buses'] ?? 0),
                'costo_mensual' => (float)($_POST['costo_mensual'] ?? 0),
                'fecha_inicio' => $_POST['fecha_inicio'],
                'fecha_vencimiento' => $_POST['fecha_vencimiento'],
                'estado' => $_POST['estado'] ?? 'activo',
                'notas' => trim($_POST['notas'] ?? ''),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Actualizar empresa
            $result = $this->companyModel->update($companyId, $updateData);

            if ($result) {
                // Registrar en log
                $currentUser = $this->getCurrentUser();

                require_once __DIR__ . '/../models/ActivityLog.php';
                $activityLog = new ActivityLog();
                $activityLog->create([
                    'user_id' => $currentUser['id'],
                    'action' => 'update',
                    'table_name' => 'companies',
                    'record_id' => $companyId,
                    'description' => 'Empresa actualizada: ' . $updateData['nombre'],
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Empresa actualizada exitosamente',
                    'redirect' => BASE_URL . 'views/companies/view.php?id=' . $companyId
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Error al actualizar la empresa'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Eliminar empresa
     */
    public function delete($id)
    {
        try {
            $this->requireAuth();

            if (!$this->isPost()) {
                $this->jsonResponse(['error' => 'Método no permitido'], 405);
            }

            $company = $this->companyModel->findById($id);
            if (!$company) {
                $this->jsonResponse(['error' => 'Empresa no encontrada'], 404);
            }

            // Verificar si se puede eliminar (no tiene paquetes activos, etc.)
            require_once __DIR__ . '/../models/Package.php';
            $packageModel = new Package();
            $activePackages = $packageModel->count("empresa_id = $id AND estado IN ('listo', 'instalado')");

            if ($activePackages > 0) {
                $this->jsonResponse([
                    'error' => 'No se puede eliminar la empresa porque tiene paquetes activos'
                ], 400);
            }

            $result = $this->companyModel->delete($id);

            if ($result) {
                // Eliminar logo si existe
                if ($company['logo_path']) {
                    $logoPath = COMPANIES_PATH . $company['logo_path'];
                    if (file_exists($logoPath)) {
                        unlink($logoPath);
                    }
                }

                // Registrar actividad
                $this->logActivity('delete', 'companies', $id, $company, null);

                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Empresa eliminada exitosamente'
                ]);
            } else {
                $this->jsonResponse(['error' => 'Error eliminando la empresa'], 500);
            }
        } catch (Exception $e) {
            $this->logError("Error en companies delete: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error interno del sistema'], 500);
        }
    }

    /**
     * Actualizar estado de empresa
     */
    public function updateStatus($id)
    {
        try {
            $this->requireAuth();

            if (!$this->isPost()) {
                $this->jsonResponse(['error' => 'Método no permitido'], 405);
            }

            $newStatus = $this->postParam('status');
            $result = $this->companyModel->updateStatus($id, $newStatus);

            if ($result['success'] ?? false) {
                $this->logActivity('status_update', 'companies', $id, null, ['new_status' => $newStatus]);
                $this->jsonResponse($result);
            } else {
                $this->jsonResponse($result, 400);
            }
        } catch (Exception $e) {
            $this->logError("Error en companies updateStatus: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error interno del sistema'], 500);
        }
    }

    /**
     * Extender licencia de empresa
     */
    public function extendLicense($id)
    {
        try {
            $this->requireAuth();

            if (!$this->isPost()) {
                $this->jsonResponse(['error' => 'Método no permitido'], 405);
            }

            $months = (int)$this->postParam('months', 12);
            $result = $this->companyModel->extendLicense($id, $months);

            if ($result['success'] ?? false) {
                $this->logActivity('license_extended', 'companies', $id, null, ['months' => $months]);
                $this->jsonResponse($result);
            } else {
                $this->jsonResponse($result, 400);
            }
        } catch (Exception $e) {
            $this->logError("Error en companies extendLicense: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error interno del sistema'], 500);
        }
    }

    /**
     * Eliminar empresa (método alternativo para compatibilidad)
     */
    public function destroy($id = null)
    {
        try {
            $this->requireAuth();

            // Obtener ID si no se pasó como parámetro
            if ($id === null) {
                $id = (int)($_GET['id'] ?? $_POST['company_id'] ?? 0);
            }

            if (!$id) {
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $this->setMessage('ID de empresa requerido', MSG_ERROR);
                    $this->redirect('views/companies/index.php');
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => 'ID de empresa requerido'
                    ]);
                    exit;
                }
            }

            // Verificar que la empresa existe
            $company = $this->companyModel->findById($id);
            if (!$company) {
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $this->setMessage('Empresa no encontrada', MSG_ERROR);
                    $this->redirect('views/companies/index.php');
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Empresa no encontrada'
                    ]);
                    exit;
                }
            }

            // Eliminar logo si existe
            if ($company['logo_path']) {
                $logoPath = ROOT_PATH . '/companies/data/' . $company['logo_path'];
                if (file_exists($logoPath)) {
                    unlink($logoPath);
                }
            }

            // Eliminar empresa
            $result = $this->companyModel->delete($id);

            if ($result) {
                // Registrar en log
                $currentUser = $this->getCurrentUser();

                require_once __DIR__ . '/../models/ActivityLog.php';
                $activityLog = new ActivityLog();
                $activityLog->create([
                    'user_id' => $currentUser['id'],
                    'action' => 'delete',
                    'table_name' => 'companies',
                    'record_id' => $id,
                    'description' => 'Empresa eliminada: ' . $company['nombre'],
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);

                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $this->setMessage('Empresa eliminada exitosamente', MSG_SUCCESS);
                    $this->redirect('views/companies/index.php');
                } else {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Empresa eliminada exitosamente'
                    ]);
                    exit;
                }
            } else {
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $this->setMessage('Error al eliminar la empresa', MSG_ERROR);
                    $this->redirect('views/companies/index.php');
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Error al eliminar la empresa'
                    ]);
                    exit;
                }
            }
        } catch (Exception $e) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->setMessage('Error interno del servidor', MSG_ERROR);
                $this->redirect('views/companies/index.php');
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Error interno del servidor'
                ]);
                exit;
            }
        }
    }
}
