<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/QRCode.php';
require_once __DIR__ . '/../models/Company.php';

class QRController extends BaseController
{
    private $qrModel;
    private $companyModel;
    public function __construct()
    {
        parent::__construct();
        $this->qrModel = new QRCode();
        $this->companyModel = new Company();
    }

    /**
     * MÓDULO 3.1: Gestión central de códigos QR
     * Lista todos los QR generados con filtros y estadísticas
     */
    public function index()
    {
        try {
            $this->requireAuth();

            // Obtener filtros
            $filters = [
                'company_id' => $_GET['company_id'] ?? null,
                'bus_number' => $_GET['bus_number'] ?? null,
                'status' => $_GET['status'] ?? null,
                'page' => $_GET['page'] ?? 1
            ];

            // Obtener QR codes con filtros
            $page = max(1, (int)$filters['page']);
            $perPage = PAGINATION_LIMIT ?? 25;

            // Obtener códigos QR
            $qrCodes = $this->qrModel->getWithFilters($filters, $perPage, ($page - 1) * $perPage);

            // Obtener total para paginación
            $totalQRs = $this->qrModel->countWithFilters($filters);

            // Obtener empresas para el filtro
            $companies = $this->companyModel->getActiveCompanies();

            // Obtener estadísticas
            $stats = $this->getQRStats();

            // Configurar paginación
            $totalPages = ceil($totalQRs / $perPage);
            $pagination = [
                'total' => $totalQRs,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages
            ];

            return [
                'qr_codes' => $qrCodes,
                'companies' => $companies,
                'filters' => $filters,
                'stats' => $stats,
                'pagination' => $pagination
            ];
        } catch (Exception $e) {
            $this->logError("Error en QRController::index - " . $e->getMessage());
            return [
                'qr_codes' => [],
                'companies' => [],
                'filters' => [],
                'stats' => [],
                'pagination' => []
            ];
        }
    }

    /**
     * MÓDULO 3.2: Formulario para generar QR codes
     * Prepara datos para el formulario de generación
     */
    public function generate()
    {
        try {
            $this->requireAuth();

            // Obtener empresas activas
            $companies = $this->companyModel->getActiveCompanies();

            // Configuraciones por defecto
            $defaultConfig = [
                'qr_size' => 300,
                'error_correction' => 'M',
                'margin' => 10,
                'format' => 'png'
            ];

            return [
                'companies' => $companies,
                'default_config' => $defaultConfig
            ];
        } catch (Exception $e) {
            $this->logError("Error en QRController::generate - " . $e->getMessage());
            return [
                'companies' => [],
                'default_config' => []
            ];
        }
    }

    /**
     * MÓDULO 3.3: Página de impresión de QR
     * Prepara vista optimizada para impresión
     */
    public function print($qrId = null)
    {
        try {
            $this->requireAuth();

            // Obtener ID de GET si no se pasó como parámetro
            if (!$qrId) {
                $qrId = $_GET['id'] ?? null;
            }

            if (!$qrId) {
                throw new Exception("ID de QR requerido");
            }

            // Obtener información del QR
            $qrCode = $this->qrModel->findById($qrId);
            if (!$qrCode) {
                throw new Exception("Código QR no encontrado");
            }

            // Obtener datos de la empresa
            $company = $this->companyModel->findById($qrCode['empresa_id']);

            // Generar instrucciones personalizadas
            $instructions = $this->generatePrintInstructions($qrCode, $company);

            return [
                'qr_code' => $qrCode,
                'company' => $company,
                'instructions' => $instructions
            ];
        } catch (Exception $e) {
            $this->logError("Error en QRController::print - " . $e->getMessage());
            $this->setMessage($e->getMessage(), MSG_ERROR);
            $this->redirect('qr-system/index.php');
        }
    }

    /**
     * MÓDULO 3.4: Generar código QR (API)
     * Genera el código QR y lo guarda
     */
    public function generateQR()
    {
        try {
            $this->requireAuth();

            // Validar método POST
            if (!$this->isPost()) {
                return $this->jsonResponse(['error' => 'Método no permitido'], 405);
            }

            // Obtener datos del formulario
            $data = [
                'empresa_id' => $_POST['empresa_id'] ?? null,
                'numero_bus' => $_POST['numero_bus'] ?? null,
                'wifi_ssid' => $_POST['wifi_ssid'] ?? null,
                'wifi_password' => $_POST['wifi_password'] ?? null,
                'qr_size' => $_POST['qr_size'] ?? 300,
                'error_correction' => $_POST['error_correction'] ?? 'M',
                'include_logo' => $_POST['include_logo'] ?? false,
                'generate_bulk' => $_POST['generate_bulk'] ?? false,
                'bulk_quantity' => $_POST['bulk_quantity'] ?? 1
            ];

            // Validar datos
            $validation = $this->validateQRData($data);
            if (!$validation['valid']) {
                return $this->jsonResponse([
                    'success' => false,
                    'errors' => $validation['errors']
                ], 400);
            }

            // Generar QR único o masivo
            if ($data['generate_bulk']) {
                $result = $this->generateBulkQR($data);
            } else {
                $result = $this->generateSingleQR($data);
            }

            return $this->jsonResponse($result);
        } catch (Exception $e) {
            $this->logError("Error en QRController::generateQR - " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Error al generar el código QR'
            ], 500);
        }
    }

    /**
     * MÓDULO 3.5: Configuración WiFi personalizada
     * Genera configuraciones WiFi para diferentes plataformas
     */
    public function wifiConfig()
    {
        try {
            $this->requireAuth();

            // Obtener empresa
            $companyId = $_REQUEST['company_id'] ?? null;
            if (!$companyId) {
                return $this->jsonResponse(['error' => 'Empresa requerida'], 400);
            }

            $company = $this->companyModel->findById($companyId);
            if (!$company) {
                return $this->jsonResponse(['error' => 'Empresa no encontrada'], 404);
            }

            // Generar configuraciones
            $configs = $this->generateWifiConfigurations($company);

            return $this->jsonResponse([
                'success' => true,
                'configurations' => $configs
            ]);
        } catch (Exception $e) {
            $this->logError("Error en QRController::wifiConfig - " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Error al generar configuración WiFi'
            ], 500);
        }
    }

    /**
     * Actualizar estado de un QR
     */
    public function updateStatus()
    {
        try {
            $this->requireAuth();

            if (!$this->isPost()) {
                return $this->jsonResponse(['error' => 'Método no permitido'], 405);
            }

            $qrId = $_POST['qr_id'] ?? null;
            $newStatus = $_POST['status'] ?? null;

            if (!$qrId || !$newStatus) {
                return $this->jsonResponse(['error' => 'Datos incompletos'], 400);
            }

            $result = $this->qrModel->updateStatus($qrId, $newStatus);

            if ($result) {
                $this->logActivity('qr_status_update', 'qr_codes', $qrId, null, [
                    'new_status' => $newStatus
                ]);

                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Estado actualizado correctamente'
                ]);
            }

            return $this->jsonResponse(['error' => 'Error al actualizar estado'], 500);
        } catch (Exception $e) {
            $this->logError("Error en QRController::updateStatus - " . $e->getMessage());
            return $this->jsonResponse(['error' => 'Error interno'], 500);
        }
    }

    /**
     * Descargar QR code
     */
    public function download($qrId = null)
    {
        try {
            $this->requireAuth();

            if (!$qrId) {
                $qrId = $_GET['id'] ?? null;
            }

            if (!$qrId) {
                throw new Exception("ID de QR requerido");
            }

            $qrCode = $this->qrModel->findById($qrId);
            if (!$qrCode || !file_exists($qrCode['archivo_path'])) {
                throw new Exception("Archivo QR no encontrado");
            }

            // Incrementar contador de descargas
            $this->qrModel->incrementDownloadCount($qrId);

            // Preparar descarga
            $filename = "QR_Bus_" . $qrCode['numero_bus'] . "_" . date('Ymd') . ".png";

            header('Content-Type: image/png');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($qrCode['archivo_path']));

            readfile($qrCode['archivo_path']);
            exit;
        } catch (Exception $e) {
            $this->logError("Error en QRController::download - " . $e->getMessage());
            $this->setMessage('Error al descargar el QR', MSG_ERROR);
            $this->redirect('qr-system/index.php');
        }
    }

    /**
     * Métodos privados auxiliares
     */

    private function getQRStats()
    {
        try {
            $stats = $this->qrModel->getStats();

            // Agregar estadísticas adicionales
            $stats['active_companies'] = $this->qrModel->countActiveCompanies();
            $stats['today_scans'] = $this->qrModel->getTodayScans();
            $stats['week_scans'] = $this->qrModel->getWeekScans();
            $stats['most_scanned'] = $this->qrModel->getMostScanned(5);

            return $stats;
        } catch (Exception $e) {
            $this->logError("Error en getQRStats: " . $e->getMessage());
            return [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
                'active_companies' => 0,
                'today_scans' => 0,
                'week_scans' => 0,
                'most_scanned' => []
            ];
        }
    }

    private function validateQRData($data)
    {
        $errors = [];

        // Validar empresa
        if (empty($data['empresa_id'])) {
            $errors['empresa_id'] = 'Debe seleccionar una empresa';
        }

        // Validar número de bus (solo para QR individual)
        if (!$data['generate_bulk'] && empty($data['numero_bus'])) {
            $errors['numero_bus'] = 'El número de bus es requerido';
        }

        // Validar SSID
        if (empty($data['wifi_ssid'])) {
            $errors['wifi_ssid'] = 'El nombre del WiFi es requerido';
        } elseif (strlen($data['wifi_ssid']) > 32) {
            $errors['wifi_ssid'] = 'El SSID no puede tener más de 32 caracteres';
        }

        // Validar contraseña
        if (empty($data['wifi_password'])) {
            $errors['wifi_password'] = 'La contraseña es requerida';
        } elseif (strlen($data['wifi_password']) < 8) {
            $errors['wifi_password'] = 'La contraseña debe tener al menos 8 caracteres';
        }

        // Validar cantidad para generación masiva
        if ($data['generate_bulk']) {
            $quantity = (int)$data['bulk_quantity'];
            if ($quantity < 1 || $quantity > 100) {
                $errors['bulk_quantity'] = 'La cantidad debe estar entre 1 y 100';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    private function generateSingleQR($data)
    {
        try {
            // Obtener datos de la empresa
            $company = $this->companyModel->findById($data['empresa_id']);

            // Generar URL del portal
            $portalUrl = $this->generatePortalUrl($company['id'], $data['numero_bus']);

            // Crear string WiFi para el QR
            $wifiString = $this->createWifiString($data['wifi_ssid'], $data['wifi_password']);

            // Contenido completo del QR
            $qrContent = $wifiString . "\n" . $portalUrl;

            // Generar imagen QR
            $qrPath = $this->createQRImage($qrContent, $data, $company);

            // Guardar en base de datos
            $qrId = $this->qrModel->create([
                'empresa_id' => $data['empresa_id'],
                'numero_bus' => $data['numero_bus'],
                'wifi_ssid' => $data['wifi_ssid'],
                'wifi_password' => $data['wifi_password'],
                'portal_url' => $portalUrl,
                'archivo_path' => $qrPath,
                'tamano_qr' => $data['qr_size'],
                'nivel_correccion' => $data['error_correction'],
                'estado' => 'activo'
            ]);

            // Registrar actividad
            $this->logActivity('qr_generated', 'qr_codes', $qrId, null, [
                'bus_number' => $data['numero_bus'],
                'company' => $company['nombre']
            ]);

            return [
                'success' => true,
                'message' => 'Código QR generado correctamente',
                'qr_id' => $qrId,
                'preview_url' => $this->getQRPreviewUrl($qrPath)
            ];
        } catch (Exception $e) {
            throw new Exception("Error generando QR: " . $e->getMessage());
        }
    }

    private function generateBulkQR($data)
    {
        try {
            $generated = [];
            $errors = [];
            $quantity = (int)$data['bulk_quantity'];

            // Obtener último número de bus usado
            $lastBusNumber = $this->qrModel->getLastBusNumber($data['empresa_id']);
            $startNumber = $lastBusNumber + 1;

            for ($i = 0; $i < $quantity; $i++) {
                try {
                    $busNumber = $startNumber + $i;
                    $data['numero_bus'] = "BUS-" . str_pad($busNumber, 3, '0', STR_PAD_LEFT);

                    $result = $this->generateSingleQR($data);
                    if ($result['success']) {
                        $generated[] = [
                            'bus_number' => $data['numero_bus'],
                            'qr_id' => $result['qr_id']
                        ];
                    }
                } catch (Exception $e) {
                    $errors[] = "Error en bus {$data['numero_bus']}: " . $e->getMessage();
                }
            }

            return [
                'success' => count($generated) > 0,
                'message' => "Se generaron " . count($generated) . " códigos QR",
                'generated' => $generated,
                'errors' => $errors
            ];
        } catch (Exception $e) {
            throw new Exception("Error en generación masiva: " . $e->getMessage());
        }
    }

    private function createWifiString($ssid, $password)
    {
        // Formato estándar para QR WiFi
        // WIFI:T:WPA;S:mynetwork;P:mypass;H:false;;
        return sprintf(
            "WIFI:T:WPA;S:%s;P:%s;H:false;;",
            $this->escapeWifiString($ssid),
            $this->escapeWifiString($password)
        );
    }

    private function escapeWifiString($string)
    {
        // Escapar caracteres especiales según estándar WiFi QR
        $special = ['\\', ';', ',', '"', ':'];
        $escaped = ['\\\\', '\\;', '\\,', '\\"', '\\:'];
        return str_replace($special, $escaped, $string);
    }

    private function generatePortalUrl($companyId, $busNumber)
    {
        // URL del portal en el Pi
        // En producción, esto sería la IP local del Pi
        return "http://192.168.4.1/portal/?company={$companyId}&bus=" . urlencode($busNumber);
    }

    private function createQRImage($content, $config, $company)
    {
        try {
            // Incluir librería QR (usando phpqrcode)
            require_once dirname(dirname(__DIR__)) . '/libs/phpqrcode/qrlib.php';

            // Crear directorio si no existe
            $qrDir = COMPANIES_PATH . $company['id'] . '/qr-codes/';
            if (!is_dir($qrDir)) {
                mkdir($qrDir, 0755, true);
            }

            // Generar nombre único
            $filename = 'qr_' . uniqid() . '_' . time() . '.png';
            $filepath = $qrDir . $filename;

            // Niveles de corrección: L (7%), M (15%), Q (25%), H (30%)
            $errorCorrection = constant('QR_ECLEVEL_' . $config['error_correction']);

            // Generar QR
            QRcode::png(
                $content,
                $filepath,
                $errorCorrection,
                $config['qr_size'] / 25, // Tamaño relativo
                2 // Margen
            );

            // Si se requiere logo
            if ($config['include_logo'] && !empty($company['logo_path'])) {
                $this->addLogoToQR($filepath, $company['logo_path']);
            }

            return $filepath;
        } catch (Exception $e) {
            throw new Exception("Error creando imagen QR: " . $e->getMessage());
        }
    }

    private function addLogoToQR($qrPath, $logoPath)
    {
        try {
            // Cargar imágenes
            $qr = imagecreatefrompng($qrPath);
            $logo = imagecreatefromstring(file_get_contents($logoPath));

            if (!$qr || !$logo) {
                return; // No agregar logo si hay error
            }

            // Obtener dimensiones
            $qrWidth = imagesx($qr);
            $qrHeight = imagesy($qr);
            $logoWidth = imagesx($logo);
            $logoHeight = imagesy($logo);

            // Calcular tamaño del logo (20% del QR)
            $logoQrWidth = $qrWidth / 5;
            $logoQrHeight = $logoHeight * ($logoQrWidth / $logoWidth);

            // Posición centrada
            $logoX = ($qrWidth - $logoQrWidth) / 2;
            $logoY = ($qrHeight - $logoQrHeight) / 2;

            // Crear fondo blanco para el logo
            $white = imagecolorallocate($qr, 255, 255, 255);
            imagefilledrectangle(
                $qr,
                $logoX - 5,
                $logoY - 5,
                $logoX + $logoQrWidth + 5,
                $logoY + $logoQrHeight + 5,
                $white
            );

            // Insertar logo
            imagecopyresampled(
                $qr,
                $logo,
                $logoX,
                $logoY,
                0,
                0,
                $logoQrWidth,
                $logoQrHeight,
                $logoWidth,
                $logoHeight
            );

            // Guardar imagen
            imagepng($qr, $qrPath);

            // Liberar memoria
            imagedestroy($qr);
            imagedestroy($logo);
        } catch (Exception $e) {
            $this->logError("Error agregando logo al QR: " . $e->getMessage());
        }
    }

    private function getQRPreviewUrl($path)
    {
        // Convertir path absoluto a URL relativa
        $relativePath = str_replace(dirname(ROOT_PATH) . '/', '', $path);
        return SITE_URL . $relativePath;
    }

    private function generatePrintInstructions($qrCode, $company)
    {
        return [
            'title' => 'WiFi Gratuito - ' . $company['nombre'],
            'steps' => [
                'Escanea este código QR con tu celular',
                'Se conectará automáticamente al WiFi del bus',
                'Disfruta del entretenimiento gratuito',
                'No se requieren datos móviles'
            ],
            'wifi_name' => $qrCode['wifi_ssid'],
            'support_contact' => $company['telefono'] ?? 'Consulta con el conductor'
        ];
    }

    private function generateWifiConfigurations($company)
    {
        $baseConfig = [
            'ssid' => 'PLAYMI_' . preg_replace('/[^A-Za-z0-9]/', '', $company['nombre']),
            'password' => $this->generateSecurePassword(),
            'security' => 'WPA2',
            'hidden' => false
        ];

        return [
            'automatic' => [
                'ssid' => $baseConfig['ssid'],
                'password' => $baseConfig['password'],
                'description' => 'Configuración automática basada en el nombre de la empresa'
            ],
            'custom' => [
                'ssid' => '',
                'password' => '',
                'description' => 'Personaliza completamente el SSID y contraseña'
            ],
            'secure' => [
                'ssid' => $baseConfig['ssid'] . '_' . date('Y'),
                'password' => $this->generateSecurePassword(true),
                'description' => 'Configuración con contraseña extra segura'
            ],
            'temporal' => [
                'ssid' => $baseConfig['ssid'] . '_TEMP',
                'password' => $this->generateTemporalPassword(),
                'expiry' => date('Y-m-d', strtotime('+30 days')),
                'description' => 'Configuración temporal válida por 30 días'
            ]
        ];
    }

    private function generateSecurePassword($extraSecure = false)
    {
        $length = $extraSecure ? 16 : 12;
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';

        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }

        return $password;
    }

    private function generateTemporalPassword()
    {
        // Contraseña simple para uso temporal
        return 'PLAYMI' . date('Ym') . rand(1000, 9999);
    }
}
