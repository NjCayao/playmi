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
     * Listar empresas con filtros y paginación
     */
    public function index()
    {
        try {
            $this->requireAuth();

            // Obtener parámetros de filtro
            $page = (int)$this->getParam('page', 1);
            $search = $this->getParam('search', '');
            $estado = $this->getParam('estado', '');
            $tipoPaquete = $this->getParam('tipo_paquete', '');
            $proximasVencer = $this->getParam('proximas_vencer', '');

            // Preparar filtros
            $filters = [];
            if (!empty($search)) $filters['search'] = $search;
            if (!empty($estado)) $filters['estado'] = $estado;
            if (!empty($tipoPaquete)) $filters['tipo_paquete'] = $tipoPaquete;
            if (!empty($proximasVencer)) $filters['proximas_vencer'] = true;

            // Obtener empresas con paginación
            $result = $this->companyModel->searchCompanies($filters, $page, RECORDS_PER_PAGE);

            // Si es petición AJAX, devolver JSON
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => true,
                    'data' => $result['data'],
                    'pagination' => [
                        'current_page' => $result['page'],
                        'total_pages' => $result['pages'],
                        'total_records' => $result['total'],
                        'per_page' => $result['limit']
                    ]
                ]);
            }

            // Obtener estadísticas adicionales
            $stats = [
                'total' => $this->companyModel->count(),
                'active' => $this->companyModel->count("estado = 'activo'"),
                'suspended' => $this->companyModel->count("estado = 'suspendido'"),
                'expired' => $this->companyModel->count("estado = 'vencido'")
            ];

            return [
                'companies' => $result['data'],
                'pagination' => $result,
                'filters' => $filters,
                'stats' => $stats
            ];
        } catch (Exception $e) {
            $this->logError("Error en companies index: " . $e->getMessage());

            if ($this->isAjax()) {
                $this->jsonResponse(['error' => 'Error cargando empresas'], 500);
            }

            $this->setMessage('Error cargando las empresas', MSG_ERROR);
            return ['error' => true];
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
                $this->logActivity('create', 'empresas', $companyId, null, $data);

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
    public function show($id)
    {
        try {
            $this->requireAuth();

            $company = $this->companyModel->findById($id);

            if (!$company) {
                if ($this->isAjax()) {
                    $this->jsonResponse(['error' => 'Empresa no encontrada'], 404);
                }

                $this->setMessage('Empresa no encontrada', MSG_ERROR);
                $this->redirect('views/companies/index.php');
            }

            // Obtener información adicional
            $additionalInfo = [
                'dias_restantes' => max(0, (strtotime($company['fecha_vencimiento']) - time()) / (60 * 60 * 24)),
                'paquetes_generados' => $this->getCompanyPackages($id),
                'historial_publicidad' => $this->getCompanyAds($id)
            ];

            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => true,
                    'company' => $company,
                    'additional_info' => $additionalInfo
                ]);
            }

            return [
                'company' => $company,
                'additional_info' => $additionalInfo
            ];
        } catch (Exception $e) {
            $this->logError("Error en companies show: " . $e->getMessage());

            if ($this->isAjax()) {
                $this->jsonResponse(['error' => 'Error cargando empresa'], 500);
            }

            $this->setMessage('Error cargando la empresa', MSG_ERROR);
            $this->redirect('views/companies/index.php');
        }
    }

    /**
     * Mostrar formulario de editar empresa
     */
    public function edit($id)
    {
        try {
            $this->requireAuth();

            $company = $this->companyModel->findById($id);

            if (!$company) {
                $this->setMessage('Empresa no encontrada', MSG_ERROR);
                $this->redirect('views/companies/index.php');
            }

            return [
                'company' => $company,
                'package_types' => [
                    PACKAGE_BASIC => 'Básico',
                    PACKAGE_INTERMEDIATE => 'Intermedio',
                    PACKAGE_PREMIUM => 'Premium'
                ],
                'status_options' => [
                    STATUS_ACTIVE => 'Activo',
                    STATUS_SUSPENDED => 'Suspendido',
                    STATUS_EXPIRED => 'Vencido'
                ]
            ];
        } catch (Exception $e) {
            $this->logError("Error en companies edit: " . $e->getMessage());
            $this->setMessage('Error cargando empresa para editar', MSG_ERROR);
            $this->redirect('views/companies/index.php');
        }
    }

    /**
     * Procesar actualización de empresa
     */
    public function update($id)
    {
        try {
            $this->requireAuth();

            if (!$this->isPost()) {
                $this->jsonResponse(['error' => 'Método no permitido'], 405);
            }

            // Verificar que la empresa existe
            $existingCompany = $this->companyModel->findById($id);
            if (!$existingCompany) {
                $this->jsonResponse(['error' => 'Empresa no encontrada'], 404);
            }

            // Sanitizar datos
            $data = $this->sanitizeInput($_POST);

            // Validar datos (similar a store pero excluyendo el ID actual)
            $errors = $this->validateInput($data, [
                'nombre' => [
                    'required' => true,
                    'label' => 'Nombre de la empresa',
                    'max_length' => 100
                ],
                'email_contacto' => [
                    'required' => true,
                    'label' => 'Email de contacto',
                    'email' => true,
                    'max_length' => 100
                ],
                // ... resto de validaciones
            ]);

            // Validaciones adicionales excluyendo el registro actual
            if (empty($errors)) {
                if ($this->companyModel->nameExists($data['nombre'], $id)) {
                    $errors['nombre'] = 'Ya existe otra empresa con este nombre';
                }

                if ($this->companyModel->emailExists($data['email_contacto'], $id)) {
                    $errors['email_contacto'] = 'Ya existe otra empresa con este email';
                }
            }

            if (!empty($errors)) {
                $this->jsonResponse(['error' => 'Datos inválidos', 'errors' => $errors], 400);
            }

            // Manejar nuevo logo si se subió
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = $this->handleFileUpload(
                    $_FILES['logo'],
                    COMPANIES_PATH,
                    ALLOWED_IMAGE_EXT,
                    MAX_IMAGE_SIZE
                );

                if ($uploadResult['success']) {
                    // Eliminar logo anterior si existe
                    if ($existingCompany['logo_path']) {
                        $oldLogoPath = COMPANIES_PATH . $existingCompany['logo_path'];
                        if (file_exists($oldLogoPath)) {
                            unlink($oldLogoPath);
                        }
                    }

                    $data['logo_path'] = $uploadResult['filename'];
                } else {
                    $this->jsonResponse(['error' => 'Error subiendo logo: ' . $uploadResult['error']], 400);
                }
            }

            // Actualizar empresa
            $result = $this->companyModel->update($id, $data);

            if ($result) {
                // Registrar actividad
                $this->logActivity('update', 'empresas', $id, $existingCompany, $data);

                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Empresa actualizada exitosamente'
                ]);
            } else {
                $this->jsonResponse(['error' => 'Error actualizando la empresa'], 500);
            }
        } catch (Exception $e) {
            $this->logError("Error en companies update: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error interno del sistema'], 500);
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
            $activePackages = $this->packageModel->count("empresa_id = $id AND estado IN ('listo', 'instalado')");
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
                $this->logActivity('delete', 'empresas', $id, $company, null);

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
                $this->logActivity('status_update', 'empresas', $id, null, ['new_status' => $newStatus]);
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
                $this->logActivity('license_extended', 'empresas', $id, null, ['months' => $months]);
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
     * Obtener paquetes de una empresa
     */
    private function getCompanyPackages($companyId)
    {
        require_once __DIR__ . '/../models/Package.php';
        $packageModel = new Package();
        return $packageModel->getByCompany($companyId, 5); // Últimos 5 paquetes
    }

    /**
     * Obtener publicidad de una empresa
     */
    private function getCompanyAds($companyId)
    {
        try {
            $sql = "SELECT * FROM publicidad_empresa WHERE empresa_id = ? ORDER BY created_at DESC LIMIT 10";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$companyId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Eliminar empresa
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
                $this->activityLog->create([
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
