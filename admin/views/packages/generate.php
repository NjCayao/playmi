<?php
// Incluir configuración y controlador
require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../controllers/PackageController.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Crear instancia del controlador
$packageController = new PackageController();

// Obtener datos del controlador
$data = $packageController->generate();

// Extraer variables
$companies = $data['companies'] ?? [];
$content = $data['content'] ?? [];
$regenerateId = $_GET['regenerate'] ?? null;
$preselectedCompany = $_GET['company_id'] ?? null;

// Configuración de la página
$pageTitle = 'Generar Paquete - PLAYMI Admin';
$contentTitle = 'Generar Nuevo Paquete';
$contentSubtitle = 'Crear paquete personalizado para empresa';

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . 'index.php'],
    ['title' => 'Paquetes', 'url' => BASE_URL . 'views/packages/index.php'],
    ['title' => 'Generar Paquete', 'url' => '#']
];

// CSS adicional
$additionalCSS = [
    ASSETS_URL . 'plugins/select2/css/select2.min.css',
    ASSETS_URL . 'plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css',
    BASE_URL . 'views/packages/css/generate.css'
];

// JS adicional
$additionalJS = [
    ASSETS_URL . 'plugins/select2/js/select2.full.min.js',
    ASSETS_URL . 'plugins/jquery-validation/jquery.validate.min.js',
    ASSETS_URL . 'plugins/jquery-validation/additional-methods.min.js',
];

// Iniciar buffer de contenido
ob_start();
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Gestión de Paquetes</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Paquetes</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Wizard Steps -->
<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-magic"></i> Wizard de Generación de Paquete
                </h3>
                <div class="card-tools">
                    <span class="badge badge-primary" id="stepIndicator">Paso 1 de 6</span>
                </div>
            </div>
            <div class="card-body">
                <!-- Indicador de progreso mejorado -->
                <div class="bs-stepper">

                    <div class="bs-stepper-content">
                        <form id="packageForm" method="POST" action="<?php echo API_URL; ?>packages/generate-package.php">
                            <!-- Step 1: Seleccionar Empresa -->
                            <div id="step1" class="content active">
                                <h4>Paso 1: Seleccionar Empresa</h4>
                                <p class="text-muted">Seleccione la empresa para la cual se generará el paquete</p>

                                <div class="form-group">
                                    <label for="empresa_id">Empresa <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="empresa_id" name="empresa_id" required>
                                        <option value="">Seleccione una empresa...</option>
                                        <?php foreach ($companies as $company): ?>
                                            <option value="<?php echo $company['id']; ?>"
                                                data-logo="<?php echo $company['logo_path']; ?>"
                                                data-primary="<?php echo $company['color_primario']; ?>"
                                                data-secondary="<?php echo $company['color_secundario']; ?>"
                                                data-buses="<?php echo $company['total_buses']; ?>"
                                                <?php echo $preselectedCompany == $company['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($company['nombre']); ?>
                                                (<?php echo $company['total_buses']; ?> buses)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div id="companyInfo" class="mt-3 animated-card" style="display: none;">
                                    <div class="callout callout-info">
                                        <h5><i class="fas fa-info-circle"></i> Información de la Empresa</h5>
                                        <div class="row">
                                            <div class="col-md-2">
                                                <img id="companyLogo" src="" alt="Logo" class="img-fluid">
                                            </div>
                                            <div class="col-md-10">
                                                <p><strong>Buses:</strong> <span id="companyBuses"></span></p>
                                                <p><strong>Colores actuales:</strong>
                                                    <span class="badge" id="primaryColorBadge">Primario</span>
                                                    <span class="badge" id="secondaryColorBadge">Secundario</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mt-3">
                                    <label for="nombre_paquete">Nombre del Paquete <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nombre_paquete" name="nombre_paquete"
                                        placeholder="Ej: Paquete_Enero_2025" required>
                                    <small class="form-text text-muted">
                                        Nombre descriptivo para identificar el paquete
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label for="version_paquete">Versión</label>
                                    <input type="text" class="form-control" id="version_paquete" name="version_paquete"
                                        value="1.0" placeholder="1.0">
                                </div>
                            </div>

                            <!-- Step 2: Configurar Branding -->
                            <div id="step2" class="content">
                                <h4>Paso 2: Configurar Branding</h4>
                                <p class="text-muted">Personalice la apariencia del portal para la empresa</p>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="color_primario">Color Primario</label>
                                            <div class="input-group">
                                                <input type="color" class="form-control" id="color_primario"
                                                    name="color_primario" value="#2563eb">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="button"
                                                        onclick="resetColor('primary')">
                                                        <i class="fas fa-undo"></i> Original
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="color_secundario">Color Secundario</label>
                                            <div class="input-group">
                                                <input type="color" class="form-control" id="color_secundario"
                                                    name="color_secundario" value="#64748b">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="button"
                                                        onclick="resetColor('secondary')">
                                                        <i class="fas fa-undo"></i> Original
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="usar_logo_empresa">Logo de la Empresa</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="usar_logo_empresa"
                                            name="usar_logo_empresa" checked>
                                        <label class="custom-control-label" for="usar_logo_empresa">
                                            Usar logo de la empresa en el portal
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="mensaje_bienvenida">Mensaje de Bienvenida</label>
                                    <textarea class="form-control" id="mensaje_bienvenida" name="mensaje_bienvenida"
                                        rows="3">Bienvenido a bordo! Disfruta del mejor entretenimiento durante tu viaje.</textarea>
                                </div>

                                <!-- Preview del portal -->
                                <div class="card card-outline card-info animated-card">
                                    <div class="card-header">
                                        <h5 class="card-title">Vista Previa del Portal</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="portalPreview" class="border rounded p-3"
                                            style="background-color: #1a1a1a; min-height: 300px;">
                                            <div class="text-center text-white">
                                                <img id="previewLogo" src="" alt="Logo" style="max-height: 60px;" class="mb-3">
                                                <h3 id="previewTitle" style="color: var(--preview-primary);">PLAYMI.PE <br> Entretenimiento que viaja contigo</h3>
                                                <p id="previewMessage" style="color: var(--preview-secondary);">
                                                    Bienvenido a bordo!
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: Seleccionar Contenido -->
                            <div id="step3" class="content">
                                <h4>Paso 3: Seleccionar Contenido</h4>
                                <p class="text-muted">Elija el contenido que se incluirá en el paquete</p>

                                <!-- Contadores mejorados -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <div class="info-box bg-gradient-danger elevation-3 content-counter">
                                            <span class="info-box-icon"><i class="fas fa-film"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Películas</span>
                                                <span class="info-box-number counter-animation" id="selectedMoviesCount">0</span>
                                                <div class="progress">
                                                    <div class="progress-bar bg-white" id="moviesProgress" style="width: 0%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box bg-gradient-success elevation-3 content-counter">
                                            <span class="info-box-icon"><i class="fas fa-music"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Música</span>
                                                <span class="info-box-number counter-animation" id="selectedMusicCount">0</span>
                                                <div class="progress">
                                                    <div class="progress-bar bg-white" id="musicProgress" style="width: 0%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box bg-gradient-warning elevation-3 content-counter">
                                            <span class="info-box-icon"><i class="fas fa-gamepad"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Juegos</span>
                                                <span class="info-box-number counter-animation" id="selectedGamesCount">0</span>
                                                <div class="progress">
                                                    <div class="progress-bar bg-white" id="gamesProgress" style="width: 0%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    Tamaño estimado del paquete: <strong id="estimatedSize">0 MB</strong>
                                    <div class="float-right">
                                        <small>Total seleccionado: <span id="totalContentSelected">0</span> archivos</small>
                                    </div>
                                </div>

                                <!-- Tabs para cada tipo de contenido -->
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-toggle="tab" href="#moviesTab">
                                            <i class="fas fa-film"></i> Películas
                                            <span class="badge badge-danger ml-2" id="moviesBadge" style="display: none;">0</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#musicTab">
                                            <i class="fas fa-music"></i> Música
                                            <span class="badge badge-success ml-2" id="musicBadge" style="display: none;">0</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#gamesTab">
                                            <i class="fas fa-gamepad"></i> Juegos
                                            <span class="badge badge-warning ml-2" id="gamesBadge" style="display: none;">0</span>
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content mt-3">
                                    <!-- Películas -->
                                    <div class="tab-pane fade show active" id="moviesTab">
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox mb-2">
                                                <input type="checkbox" class="custom-control-input" id="selectAllMovies">
                                                <label class="custom-control-label" for="selectAllMovies">
                                                    <strong>Seleccionar todas las películas</strong>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="row" id="moviesList">
                                            <?php foreach ($content['movies'] ?? [] as $movie): ?>
                                                <div class="col-md-4 mb-3">
                                                    <div class="content-item">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input content-checkbox movie-checkbox"
                                                                id="movie_<?php echo $movie['id']; ?>"
                                                                name="content_ids[]"
                                                                value="<?php echo $movie['id']; ?>"
                                                                data-size="<?php echo $movie['tamanio_archivo']; ?>"
                                                                data-type="movie">
                                                            <label class="custom-control-label" for="movie_<?php echo $movie['id']; ?>">
                                                                <?php echo htmlspecialchars($movie['titulo']); ?>
                                                                <small class="text-muted d-block">
                                                                    <i class="fas fa-hdd"></i> <?php echo formatFileSize($movie['tamanio_archivo']); ?>
                                                                </small>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Música -->
                                    <div class="tab-pane fade" id="musicTab">
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox mb-2">
                                                <input type="checkbox" class="custom-control-input" id="selectAllMusic">
                                                <label class="custom-control-label" for="selectAllMusic">
                                                    <strong>Seleccionar toda la música</strong>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="row" id="musicList">
                                            <?php foreach ($content['music'] ?? [] as $music): ?>
                                                <div class="col-md-4 mb-3">
                                                    <div class="content-item">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input content-checkbox music-checkbox"
                                                                id="music_<?php echo $music['id']; ?>"
                                                                name="content_ids[]"
                                                                value="<?php echo $music['id']; ?>"
                                                                data-size="<?php echo $music['tamanio_archivo']; ?>"
                                                                data-type="music">
                                                            <label class="custom-control-label" for="music_<?php echo $music['id']; ?>">
                                                                <?php echo htmlspecialchars($music['titulo']); ?>
                                                                <small class="text-muted d-block">
                                                                    <i class="fas fa-hdd"></i> <?php echo formatFileSize($music['tamanio_archivo']); ?>
                                                                </small>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Juegos -->
                                    <div class="tab-pane fade" id="gamesTab">
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox mb-2">
                                                <input type="checkbox" class="custom-control-input" id="selectAllGames">
                                                <label class="custom-control-label" for="selectAllGames">
                                                    <strong>Seleccionar todos los juegos</strong>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="row" id="gamesList">
                                            <?php foreach ($content['games'] ?? [] as $game): ?>
                                                <div class="col-md-4 mb-3">
                                                    <div class="content-item">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input content-checkbox game-checkbox"
                                                                id="game_<?php echo $game['id']; ?>"
                                                                name="content_ids[]"
                                                                value="<?php echo $game['id']; ?>"
                                                                data-size="<?php echo $game['tamanio_archivo']; ?>"
                                                                data-type="game">
                                                            <label class="custom-control-label" for="game_<?php echo $game['id']; ?>">
                                                                <?php echo htmlspecialchars($game['titulo']); ?>
                                                                <small class="text-muted d-block">
                                                                    <i class="fas fa-hdd"></i> <?php echo formatFileSize($game['tamanio_archivo']); ?>
                                                                </small>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 4: Configurar WiFi -->
                            <div id="step4" class="content">
                                <h4>Paso 4: Configurar WiFi</h4>
                                <p class="text-muted">Configure los parámetros de la red WiFi del bus</p>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="wifi_ssid">Nombre de Red (SSID) <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="wifi_ssid" name="wifi_ssid"
                                                placeholder="PLAYMI-BUS-001" required>
                                            <small class="form-text text-muted">
                                                Nombre que verán los pasajeros al buscar redes WiFi
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="wifi_password">Contraseña WiFi <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="wifi_password" name="wifi_password"
                                                    placeholder="Contraseña segura" required>
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="generatePassword()">
                                                        <i class="fas fa-key"></i> Generar
                                                    </button>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">
                                                Mínimo 8 caracteres. Use el botón para generar una contraseña segura.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="wifi_channel">Canal WiFi</label>
                                            <select class="form-control" id="wifi_channel" name="wifi_channel">
                                                <option value="auto">Automático (Recomendado)</option>
                                                <option value="1">Canal 1</option>
                                                <option value="6">Canal 6</option>
                                                <option value="11">Canal 11</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="max_connections">Máximo de Conexiones</label>
                                            <input type="number" class="form-control" id="max_connections"
                                                name="max_connections" value="50" min="10" max="100">
                                            <small class="form-text text-muted">
                                                Número máximo de dispositivos conectados simultáneamente
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class=" alert-info mt-3">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Nota:</strong> Se generará un código QR único que funcionará para todos los buses de la empresa.
                                    Este QR se podrá imprimir desde la sección "Sistema QR" después de generar el paquete.
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="wifi_hidden" name="wifi_hidden">
                                        <label class="custom-control-label" for="wifi_hidden">
                                            Red oculta (no visible en lista de redes)
                                        </label>
                                    </div>
                                </div>

                                <!-- Preview del QR Code e Instrucciones -->
                                <div class="card card-outline card-info animated-card">
                                    <div class="card-header">
                                        <h5 class="card-title">Vista Previa del QR Code e Instrucciones</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6 class="text-center mb-3">Código QR WiFi</h6>
                                                <div id="qrPreview" class="text-center mb-3">
                                                    <div id="qrPlaceholder" style="width: 200px; height: 200px; margin: 0 auto; background: #f8f9fa; border: 2px dashed #dee2e6; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                                                        <i class="fas fa-qrcode fa-4x text-muted mb-2"></i>
                                                        <small class="text-muted">Ingrese los datos WiFi para generar el QR</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6 class="text-center mb-3">Instrucciones para Usuarios</h6>
                                                <div class="instruction-preview" style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 2px solid #dee2e6;">
                                                    <h4 class="text-center text-primary mb-3">WIFI GRATIS + PELÍCULAS</h4>
                                                    <ol style="font-size: 16px;">
                                                        <li class="mb-2">
                                                            <strong>Escanea el código QR</strong><br>
                                                            <small class="text-muted wifi-info">Para conectarte al WiFi del bus</small>
                                                        </li>
                                                        <li class="mb-2">
                                                            <strong>Abre tu navegador y busca:</strong><br>
                                                            <div class="text-center my-2">
                                                                <span class="badge badge-primary" style="font-size: 20px; padding: 10px 20px;">playmi.pe</span>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <strong>¡Disfruta películas, música y juegos GRATIS!</strong>
                                                        </li>
                                                    </ol>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="alert alert-info mt-3">
                                            <i class="fas fa-info-circle"></i>
                                            <strong>Portal Cautivo Configurado:</strong> Los usuarios serán redirigidos automáticamente a <strong>playmi.pe</strong> al abrir cualquier navegador.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 5: Configurar Portal -->
                            <div id="step5" class="content">
                                <h4>Paso 5: Configurar Portal</h4>
                                <p class="text-muted">Configure las opciones del portal web para pasajeros</p>

                                <div class="form-group">
                                    <label for="portal_name">Nombre del Portal</label>
                                    <input type="text" class="form-control" id="portal_name" name="portal_name"
                                        placeholder="PLAYMI Entertainment" value="PLAYMI Entertainment">
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="enable_movies"
                                                    name="enable_movies" checked>
                                                <label class="custom-control-label" for="enable_movies">
                                                    <i class="fas fa-film"></i> Habilitar Películas
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="enable_music"
                                                    name="enable_music" checked>
                                                <label class="custom-control-label" for="enable_music">
                                                    <i class="fas fa-music"></i> Habilitar Música
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="enable_games"
                                                    name="enable_games" checked>
                                                <label class="custom-control-label" for="enable_games">
                                                    <i class="fas fa-gamepad"></i> Habilitar Juegos
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="analytics_enabled">Análisis de Uso</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="analytics_enabled"
                                            name="analytics_enabled" checked>
                                        <label class="custom-control-label" for="analytics_enabled">
                                            Habilitar recolección de estadísticas de uso (anónimas)
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="auto_sync">Sincronización Automática</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="auto_sync"
                                            name="auto_sync" checked>
                                        <label class="custom-control-label" for="auto_sync">
                                            Sincronizar automáticamente cuando haya conexión a internet
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="sync_interval">Intervalo de Sincronización</label>
                                    <select class="form-control" id="sync_interval" name="sync_interval">
                                        <option value="3600">Cada hora</option>
                                        <option value="7200">Cada 2 horas</option>
                                        <option value="21600">Cada 6 horas</option>
                                        <option value="43200">Cada 12 horas</option>
                                        <option value="86400">Una vez al día</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="portal_footer">Texto del Footer</label>
                                    <input type="text" class="form-control" id="portal_footer" name="portal_footer"
                                        placeholder="© 2025 PLAYMI Entertainment. Todos los derechos reservados."
                                        value="© 2025 PLAYMI Entertainment. Todos los derechos reservados.">
                                </div>
                            </div>

                            <!-- Step 6: Revisar y Generar -->
                            <div id="step6" class="content">
                                <h4>Paso 6: Revisar y Generar</h4>
                                <p class="text-muted">Revise la configuración antes de generar el paquete</p>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card animated-card">
                                            <div class="card-header bg-primary">
                                                <h5 class="card-title mb-0">
                                                    <i class="fas fa-info-circle"></i> Información General
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <dl class="row">
                                                    <dt class="col-sm-4">Empresa:</dt>
                                                    <dd class="col-sm-8" id="review_empresa">-</dd>

                                                    <dt class="col-sm-4">Paquete:</dt>
                                                    <dd class="col-sm-8" id="review_nombre">-</dd>

                                                    <dt class="col-sm-4">Versión:</dt>
                                                    <dd class="col-sm-8" id="review_version">-</dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card animated-card">
                                            <div class="card-header bg-info">
                                                <h5 class="card-title mb-0">
                                                    <i class="fas fa-wifi"></i> Configuración WiFi
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <dl class="row">
                                                    <dt class="col-sm-4">SSID:</dt>
                                                    <dd class="col-sm-8" id="review_ssid">-</dd>

                                                    <dt class="col-sm-4">Contraseña:</dt>
                                                    <dd class="col-sm-8" id="review_password">-</dd>

                                                    <dt class="col-sm-4">Max. Conexiones:</dt>
                                                    <dd class="col-sm-8" id="review_connections">-</dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <!-- Step 7: Publicidad -->
                            <div id="step7" class="content">
                                <h4>Paso 7: Configurar Publicidad</h4>
                                <p class="text-muted">Configure videos publicitarios y banners para monetización</p>

                                <!-- Estadísticas de publicidad disponible -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="info-box bg-gradient-info">
                                            <span class="info-box-icon"><i class="fas fa-video"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Videos Disponibles</span>
                                                <span class="info-box-number" id="availableVideosCount">0</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-box bg-gradient-warning">
                                            <span class="info-box-icon"><i class="fas fa-image"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Banners Disponibles</span>
                                                <span class="info-box-number" id="availableBannersCount">0</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Videos Publicitarios -->
                                <div class="card card-primary card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-video"></i> Videos Publicitarios
                                        </h5>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="video_inicio_id">Video al inicio (5 minutos)</label>
                                                    <select class="form-control select2" id="video_inicio_id" name="video_inicio_id">
                                                        <option value="">Sin publicidad al inicio</option>
                                                        <!-- Se llenará dinámicamente -->
                                                    </select>
                                                    <small class="form-text text-muted">
                                                        Se reproducirá a los 5 minutos de iniciada la película
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="video_mitad_id">Video a la mitad</label>
                                                    <select class="form-control select2" id="video_mitad_id" name="video_mitad_id">
                                                        <option value="">Sin publicidad a la mitad</option>
                                                        <!-- Se llenará dinámicamente -->
                                                    </select>
                                                    <small class="form-text text-muted">
                                                        Solo en contenido mayor a 30 minutos
                                                    </small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i>
                                            <strong>Comportamiento:</strong> El video principal se pausará automáticamente,
                                            se reproducirá la publicidad sin controles, y luego continuará automáticamente.
                                        </div>

                                        <div class="form-group">
                                            <label>Configuración de Videos</label>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="video_skip_allowed"
                                                    name="video_skip_allowed" checked>
                                                <label class="custom-control-label" for="video_skip_allowed">
                                                    Permitir saltar publicidad después de 5 segundos
                                                </label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="video_mute_allowed"
                                                    name="video_mute_allowed">
                                                <label class="custom-control-label" for="video_mute_allowed">
                                                    Permitir silenciar publicidad
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Banners -->
                                <div class="card card-warning card-outline mt-3">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-image"></i> Banners Publicitarios
                                        </h5>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="banner_header_id">Banner Superior</label>
                                                    <select class="form-control select2" id="banner_header_id" name="banner_header_id">
                                                        <option value="">Sin banner superior</option>
                                                        <!-- Se llenará dinámicamente -->
                                                    </select>
                                                    <small class="form-text text-muted">1920x200px</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="banner_footer_id">Banner Inferior</label>
                                                    <select class="form-control select2" id="banner_footer_id" name="banner_footer_id">
                                                        <option value="">Sin banner inferior</option>
                                                        <!-- Se llenará dinámicamente -->
                                                    </select>
                                                    <small class="form-text text-muted">1920x100px</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="banner_catalogo_id">Banner en Catálogo</label>
                                                    <select class="form-control select2" id="banner_catalogo_id" name="banner_catalogo_id">
                                                        <option value="">Sin banner en catálogo</option>
                                                        <!-- Se llenará dinámicamente -->
                                                    </select>
                                                    <small class="form-text text-muted">300x250px</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Frecuencia de Banners en Catálogo</label>
                                            <select class="form-control" name="banner_catalogo_frequency">
                                                <option value="3">Cada 3 elementos</option>
                                                <option value="5">Cada 5 elementos</option>
                                                <option value="10">Cada 10 elementos</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Preview de Publicidad -->
                                <div class="card card-info card-outline mt-3">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-eye"></i> Vista Previa de Ubicación
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="advertising-preview" style="background: #1a1a1a; padding: 20px; border-radius: 8px;">
                                            <!-- Header Banner Preview -->
                                            <div id="headerBannerPreview" class="banner-preview" style="height: 60px; background: #333; margin-bottom: 10px; display: none;">
                                                <img src="" alt="Header Banner" style="width: 100%; height: 100%; object-fit: contain;">
                                            </div>

                                            <!-- Content Area -->
                                            <div style="background: #222; padding: 20px; min-height: 300px; color: white; text-align: center;">
                                                <h4>Área de Contenido Principal</h4>
                                                <p>Películas, Música, Juegos</p>

                                                <!-- Catalog Banner Example -->
                                                <div id="catalogBannerPreview" class="banner-preview" style="width: 300px; height: 250px; background: #444; margin: 20px auto; display: none;">
                                                    <img src="" alt="Catalog Banner" style="width: 100%; height: 100%; object-fit: contain;">
                                                </div>
                                            </div>

                                            <!-- Footer Banner Preview -->
                                            <div id="footerBannerPreview" class="banner-preview" style="height: 40px; background: #333; margin-top: 10px; display: none;">
                                                <img src="" alt="Footer Banner" style="width: 100%; height: 100%; object-fit: contain;">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Métricas y Reportes -->
                                <div class="form-group mt-3">
                                    <label>Configuración de Métricas</label>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="track_impressions"
                                            name="track_impressions" checked>
                                        <label class="custom-control-label" for="track_impressions">
                                            Registrar impresiones de publicidad
                                        </label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="track_clicks"
                                            name="track_clicks" checked>
                                        <label class="custom-control-label" for="track_clicks">
                                            Registrar clics en banners
                                        </label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="track_completion"
                                            name="track_completion" checked>
                                        <label class="custom-control-label" for="track_completion">
                                            Registrar videos completados vs saltados
                                        </label>
                                    </div>
                                </div>
                            </div>


                            <div class="card mt-3 animated-card">
                                <div class="card-header bg-success">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-photo-video"></i> Contenido Seleccionado
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="info-box bg-danger">
                                                <span class="info-box-icon"><i class="fas fa-film"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Películas</span>
                                                    <span class="info-box-number" id="review_movies">0</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-box bg-success">
                                                <span class="info-box-icon"><i class="fas fa-music"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Música</span>
                                                    <span class="info-box-number" id="review_music">0</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-box bg-warning">
                                                <span class="info-box-icon"><i class="fas fa-gamepad"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Juegos</span>
                                                    <span class="info-box-number" id="review_games">0</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Tamaño total estimado:</strong> <span id="review_size">0 MB</span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="notas">Notas adicionales (opcional)</label>
                                <textarea class="form-control" id="notas" name="notas" rows="3"
                                    placeholder="Notas o comentarios sobre este paquete..."></textarea>
                            </div>

                            <div class="alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Importante:</strong> La generación del paquete puede tomar varios minutos
                                dependiendo de la cantidad de contenido seleccionado. No cierre esta ventana durante el proceso.
                            </div>

                            <div class="bs-stepper-header" role="tablist">
                                <div class="step active" data-target="#step1" data-step="1">
                                    <button type="button" class="step-trigger" role="tab">
                                        <span class="bs-stepper-circle">
                                            <i class="fas fa-building step-icon"></i>
                                            <i class="fas fa-check step-check" style="display: none;"></i>
                                        </span>
                                        <span class="bs-stepper-label">Empresa</span>
                                    </button>
                                </div>
                                <div class="line"></div>
                                <div class="step" data-target="#step2" data-step="2">
                                    <button type="button" class="step-trigger" role="tab">
                                        <span class="bs-stepper-circle">
                                            <i class="fas fa-palette step-icon"></i>
                                            <i class="fas fa-check step-check" style="display: none;"></i>
                                        </span>
                                        <span class="bs-stepper-label">Branding</span>
                                    </button>
                                </div>
                                <div class="line"></div>
                                <div class="step" data-target="#step3" data-step="3">
                                    <button type="button" class="step-trigger" role="tab">
                                        <span class="bs-stepper-circle">
                                            <i class="fas fa-photo-video step-icon"></i>
                                            <i class="fas fa-check step-check" style="display: none;"></i>
                                        </span>
                                        <span class="bs-stepper-label">Contenido</span>
                                    </button>
                                </div>
                                <div class="line"></div>
                                <div class="step" data-target="#step4" data-step="4">
                                    <button type="button" class="step-trigger" role="tab">
                                        <span class="bs-stepper-circle">
                                            <i class="fas fa-wifi step-icon"></i>
                                            <i class="fas fa-check step-check" style="display: none;"></i>
                                        </span>
                                        <span class="bs-stepper-label">WiFi</span>
                                    </button>
                                </div>
                                <div class="line"></div>
                                <div class="step" data-target="#step5" data-step="5">
                                    <button type="button" class="step-trigger" role="tab">
                                        <span class="bs-stepper-circle">
                                            <i class="fas fa-globe step-icon"></i>
                                            <i class="fas fa-check step-check" style="display: none;"></i>
                                        </span>
                                        <span class="bs-stepper-label">Portal</span>
                                    </button>
                                </div>
                                <div class="line"></div>
                                <div class="step" data-target="#step6" data-step="6">
                                    <button type="button" class="step-trigger" role="tab">
                                        <span class="bs-stepper-circle">
                                            <i class="fas fa-ad step-icon"></i>
                                            <i class="fas fa-check step-check" style="display: none;"></i>
                                        </span>
                                        <span class="bs-stepper-label">Publicidad</span>
                                    </button>
                                </div>
                                <div class="line"></div>
                                <div class="step" data-target="#step6" data-step="6">
                                    <button type="button" class="step-trigger" role="tab">
                                        <span class="bs-stepper-circle">
                                            <i class="fas fa-clipboard-check step-icon"></i>
                                            <i class="fas fa-check step-check" style="display: none;"></i>
                                        </span>
                                        <span class="bs-stepper-label">Revisar</span>
                                    </button>
                                </div>
                            </div>


                            <!-- Navegación del wizard -->
                            <div class="card-footer">
                                <button type="button" class="btn btn-secondary" id="prevBtn" onclick="changeStep(-1)" style="display: none;">
                                    <i class="fas fa-arrow-left"></i> Anterior
                                </button>
                                <button type="button" class="btn btn-primary float-right" id="nextBtn" onclick="changeStep(1)">
                                    Siguiente <i class="fas fa-arrow-right"></i>
                                </button>
                                <button type="submit" class="btn btn-success float-right btn-lg" id="submitBtn" style="display: none;">
                                    <i class="fas fa-check"></i> Generar Paquete
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de progreso -->
<div class="modal fade" id="progressModal" tabindex="-1" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generando Paquete</h5>
            </div>
            <div class="modal-body">
                <div class="progress mb-3" style="height: 25px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                        role="progressbar" style="width: 0%" id="progressBar">0%</div>
                </div>
                <p class="text-center" id="progressStatus">Iniciando generación...</p>

                <!-- Información adicional de progreso -->
                <div class="row mt-3">
                    <div class="col-6 text-center">
                        <small class="text-muted">Tiempo transcurrido</small>
                        <div class="font-weight-bold" id="elapsedTime">00:00</div>
                    </div>
                    <div class="col-6 text-center">
                        <small class="text-muted">Tiempo restante</small>
                        <div class="font-weight-bold text-primary" id="remainingTime">Calculando...</div>
                    </div>
                </div>

                <div class="mt-3 text-center">
                    <small class="text-muted">
                        <span id="filesProcessed">0</span> / <span id="totalFiles">0</span> archivos procesados
                    </small>
                </div>

                <div class="text-center mt-3">
                    <i class="fas fa-cog fa-spin fa-3x text-primary"></i>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal para previsualizar videos -->
<div class="modal fade" id="videoPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vista Previa del Video Publicitario</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" style="background: #000; padding: 0;">
                <div class="video-container" style="position: relative; width: 100%; height: 0; padding-bottom: 56.25%;">
                    <video id="previewVideoPlayer"
                        controls
                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
                        preload="metadata">
                        Tu navegador no soporta la reproducción de videos.
                    </video>
                </div>
                <div class="p-3 bg-light">
                    <p class="mb-1"><strong>Información del Video:</strong></p>
                    <span class="badge badge-info">Duración: <span id="videoDurationInfo">-</span>s</span>
                    <span class="badge badge-secondary">Tipo: <span id="videoTypeInfo">-</span></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
function formatFileSize($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;

    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }

    return round($bytes, 2) . ' ' . $units[$i];
}
?>

<?php
// Capturar contenido
$content = ob_get_clean();

// Incluir layout base
require_once __DIR__ . '/../layouts/base.php';
?>

<script>
    // Variables globales
    let currentStep = 1;
    const totalSteps = 7;
    let selectedCompany = null;
    let selectedContent = {
        movies: [],
        music: [],
        games: []
    };
    let completedSteps = new Set();

    window.currentProgressInterval = null;

    $(document).ready(function() {
        // Inicializar Select2
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%', // Importante para responsive
            dropdownAutoWidth: false,
            dropdownParent: $('body') // Para evitar problemas de overflow
        });

        // Si hay empresa preseleccionada
        <?php if ($preselectedCompany): ?>
            $('#empresa_id').trigger('change');
        <?php endif; ?>

        // Cambio de empresa
        $('#empresa_id').on('change', function() {
            const selected = $(this).find(':selected');
            if (selected.val()) {
                selectedCompany = {
                    id: selected.val(),
                    name: selected.text(),
                    logo: selected.data('logo'),
                    primaryColor: selected.data('primary'),
                    secondaryColor: selected.data('secondary'),
                    buses: selected.data('buses')
                };

                // Mostrar información con animación
                $('#companyInfo').fadeIn(300);
                $('#companyLogo').attr('src', '<?php echo BASE_URL; ?>../companies/data/' + selectedCompany.logo);
                $('#companyBuses').text(selectedCompany.buses);
                $('#primaryColorBadge').css('background-color', selectedCompany.primaryColor);
                $('#secondaryColorBadge').css('background-color', selectedCompany.secondaryColor);

                // Actualizar colores por defecto
                $('#color_primario').val(selectedCompany.primaryColor);
                $('#color_secundario').val(selectedCompany.secondaryColor);

                // Actualizar nombre del paquete sugerido
                const date = new Date();
                const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
                ];
                const suggestedName = `Paquete_${selected.text().replace(/\s+/g, '_')}_${monthNames[date.getMonth()]}_${date.getFullYear()}`;
                $('#nombre_paquete').val(suggestedName);

                // Sugerir SSID
                $('#wifi_ssid').val(`PLAYMI-${selected.text().replace(/\s+/g, '-').toUpperCase()}`);
            } else {
                $('#companyInfo').fadeOut(300);
            }

            if (selectedCompany && selectedCompany.id) {
                loadCompanyAdvertising(selectedCompany.id);
            }

        });


        // Preview de colores
        $('#color_primario, #color_secundario').on('input', function() {
            updatePortalPreview();
        });

        $('#mensaje_bienvenida').on('input', function() {
            updatePortalPreview();
        });

        // Selección de contenido con efectos visuales
        $('.content-checkbox').on('change', function() {
            const $item = $(this).closest('.content-item');
            if ($(this).is(':checked')) {
                $item.addClass('selected');
            } else {
                $item.removeClass('selected');
            }
            updateContentCount();
            calculatePackageSize();
        });

        // Seleccionar todo
        $('#selectAllMovies').on('change', function() {
            $('.movie-checkbox').prop('checked', $(this).is(':checked')).trigger('change');
        });

        $('#selectAllMusic').on('change', function() {
            $('.music-checkbox').prop('checked', $(this).is(':checked')).trigger('change');
        });

        $('#selectAllGames').on('change', function() {
            $('.game-checkbox').prop('checked', $(this).is(':checked')).trigger('change');
        });

        // Validación del formulario
        $('#packageForm').validate({
            rules: {
                empresa_id: 'required',
                nombre_paquete: 'required',
                wifi_ssid: 'required',
                wifi_password: {
                    required: true,
                    minlength: 8
                }
            },
            messages: {
                empresa_id: 'Seleccione una empresa',
                nombre_paquete: 'Ingrese un nombre para el paquete',
                wifi_ssid: 'Ingrese el nombre de la red WiFi',
                wifi_password: {
                    required: 'Ingrese una contraseña',
                    minlength: 'La contraseña debe tener al menos 8 caracteres'
                }
            },
            errorElement: 'span',
            errorPlacement: function(error, element) {
                error.addClass('invalid-feedback');
                element.closest('.form-group').append(error);
            },
            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalid');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalid');
            }
        });

        // Submit del formulario
        $('#packageForm').on('submit', function(e) {
            e.preventDefault();

            if (!$(this).valid()) {
                toastr.error('Por favor complete todos los campos requeridos');
                return;
            }

            // Verificar que hay contenido seleccionado
            if ($('.content-checkbox:checked').length === 0) {
                toastr.error('Debe seleccionar al menos un elemento de contenido');
                return;
            }

            // Mostrar modal de progreso
            $('#progressModal').modal('show');
            updateProgress(0, 'Iniciando generación del paquete...');

            // Variables para el tracking del progreso
            let packageId = null;
            let startTime = Date.now();
            let progressInterval = null;

            // Preparar datos del formulario
            const formData = new FormData(this);

            // Asegurarse de que los content_ids estén incluidos
            $('.content-checkbox:checked').each(function() {
                formData.append('content_ids[]', $(this).val());
            });

            // Iniciar generación
            $.ajax({
                url: '<?php echo API_URL; ?>packages/generate-package.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    console.log('Respuesta inicial:', response);

                    if (response.success && response.package_id) {
                        packageId = response.package_id;

                        // Iniciar monitoreo de progreso real
                        window.currentProgressInterval = setInterval(() => {
                            checkRealProgress(packageId, startTime);
                        }, 1000); // Verificar cada segundo

                    } else {
                        $('#progressModal').modal('hide');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.error || 'Error al generar el paquete'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    $('#progressModal').modal('hide');
                    console.error('Error AJAX:', error);
                    console.log('Respuesta:', xhr.responseText);

                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Conexión',
                        text: error
                    });
                }
            });
        });

        // Función para verificar el progreso real
        function checkRealProgress(packageId, startTime) {
            $.ajax({
                url: '<?php echo API_URL; ?>packages/check-progress.php',
                method: 'GET',
                data: {
                    package_id: packageId
                },
                dataType: 'json',
                success: function(data) {
                    console.log('Progreso:', data); // Debug

                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }

                    // Actualizar barra de progreso
                    const progress = data.progress || 0;
                    updateProgress(progress, data.message || 'Procesando...');

                    // Actualizar contadores de archivos
                    $('#filesProcessed').text(data.files_processed || 0);
                    $('#totalFiles').text(data.total_files || 0);

                    // Calcular tiempos
                    updateTimeInfo(startTime, progress);

                    // Si está completo o hay error
                    if (data.status === 'listo' || data.status === 'error') {
                        if (window.currentProgressInterval) {
                            clearInterval(window.currentProgressInterval);
                            window.currentProgressInterval = null;
                        }

                        if (data.status === 'listo') {
                            setTimeout(() => {
                                handleSuccess(data, packageId);
                            }, 500);
                        } else {
                            handleError(data.error || 'Error al generar el paquete');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error verificando progreso:', error);
                    console.log('Respuesta:', xhr.responseText);
                }
            });
        }

        // Función para actualizar información de tiempo
        function updateTimeInfo(startTime, progress) {
            const now = Date.now();
            const elapsed = (now - startTime) / 1000; // en segundos

            // Formato de tiempo transcurrido
            const elapsedMinutes = Math.floor(elapsed / 60);
            const elapsedSeconds = Math.floor(elapsed % 60);
            $('#elapsedTime').text(
                String(elapsedMinutes).padStart(2, '0') + ':' +
                String(elapsedSeconds).padStart(2, '0')
            );

            // Calcular tiempo restante
            if (progress > 0 && progress < 100) {
                const totalEstimated = (elapsed / progress) * 100;
                const remaining = totalEstimated - elapsed;

                if (remaining > 0) {
                    const remainingMinutes = Math.floor(remaining / 60);
                    const remainingSeconds = Math.floor(remaining % 60);

                    if (remainingMinutes > 0) {
                        $('#remainingTime').text(remainingMinutes + 'm ' + remainingSeconds + 's');
                    } else {
                        $('#remainingTime').text(remainingSeconds + ' segundos');
                    }
                } else {
                    $('#remainingTime').text('Finalizando...');
                }
            } else if (progress === 0) {
                $('#remainingTime').text('Calculando...');
            } else {
                $('#remainingTime').text('Completado');
            }
        }

        // Función para manejar éxito
        function handleSuccess(data, packageId) {
            updateProgress(100, '¡Paquete generado exitosamente!');

            setTimeout(() => {
                $('#progressModal').modal('hide');

                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    html: `
                <p>El paquete se ha generado correctamente.</p>
                <p><strong>ID:</strong> ${packageId}</p>
                <p><strong>Tamaño:</strong> ${data.package_size || 'N/A'}</p>
                ${data.installation_key ? `<p><strong>Clave de instalación:</strong> <code>${data.installation_key}</code></p>` : ''}
            `,
                    showCancelButton: true,
                    confirmButtonText: 'Descargar',
                    cancelButtonText: 'Ver paquetes'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = data.download_url;
                    } else {
                        window.location.href = '<?php echo BASE_URL; ?>views/packages/index.php';
                    }
                });
            }, 1000);
        }


        // Función para manejar errores
        function handleError(errorMessage) {
            $('#progressModal').modal('hide');

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMessage
            });
        }



        // Actualizar QR cuando cambien los campos WiFi
        $('#wifi_ssid, #wifi_password, #wifi_hidden').on('input change', function() {
            clearTimeout(window.qrUpdateTimeout);
            window.qrUpdateTimeout = setTimeout(updateQRPreview, 500);
        });

        // Click en pasos completados
        $('.step-trigger').on('click', function(e) {
            e.preventDefault();
            const targetStep = $(this).closest('.step').data('step');
            if (completedSteps.has(targetStep) || targetStep < currentStep) {
                goToStep(targetStep);
            }
        });
    });

    // Función para cargar publicidad de la empresa
    function loadCompanyAdvertising(companyId) {
        console.log('Cargando publicidad para empresa:', companyId); // Debug

        $.ajax({
            url: '<?php echo API_URL; ?>advertising/get-company-ads.php',
            method: 'GET',
            data: {
                company_id: companyId
            },
            success: function(response) {
                console.log('Respuesta publicidad:', response); // Debug

                if (response.success) {
                    // Llenar selects de videos
                    fillAdvertisingSelect('#video_inicio_id', response.videos.filter(v => v.tipo_video === 'inicio'), 'video');
                    fillAdvertisingSelect('#video_mitad_id', response.videos.filter(v => v.tipo_video === 'mitad'), 'video');

                    // Llenar selects de banners
                    fillAdvertisingSelect('#banner_header_id', response.banners.filter(b => b.tipo_banner === 'header'), 'banner');
                    fillAdvertisingSelect('#banner_footer_id', response.banners.filter(b => b.tipo_banner === 'footer'), 'banner');
                    fillAdvertisingSelect('#banner_catalogo_id', response.banners.filter(b => b.tipo_banner === 'catalogo'), 'banner');

                    // Actualizar contadores
                    $('#availableVideosCount').text(response.videos.length);
                    $('#availableBannersCount').text(response.banners.length);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error cargando publicidad:', error); // Debug
                console.log('Respuesta:', xhr.responseText); // Debug
            }
        });
    }

    // Función para llenar selects de publicidad
    function fillAdvertisingSelect(selector, items, type) {
        const $select = $(selector);
        const emptyText = $select.find('option:first').text();

        $select.empty().append(`<option value="">${emptyText}</option>`);

        items.forEach(item => {
            const text = type === 'video' ?
                `${item.id} - Duración: ${item.duracion}s` :
                `${item.id} - ${item.ancho}x${item.alto}px`;

            $select.append(`<option value="${item.id}" data-path="${item.archivo_path || item.imagen_path}">${text}</option>`);
        });
    }

    // Preview de banners cuando se seleccionen
    $('#banner_header_id, #banner_footer_id, #banner_catalogo_id').on('change', function() {
        const bannerId = $(this).val();
        const bannerPath = $(this).find(':selected').data('path');
        const bannerType = this.id.replace('banner_', '').replace('_id', '');

        // Actualizar la vista previa en la sección "Vista Previa de Ubicación"
        const previewId = bannerType + 'BannerPreview';

        if (bannerId && bannerPath) {
            // Construir la URL correcta de la imagen
            const imageUrl = '<?php echo BASE_URL; ?>../content/' + bannerPath;

            // Mostrar el banner en la vista previa
            $(`#${previewId}`).show().find('img').attr('src', imageUrl);

            // También actualizar en la sección principal si existe
            const mainPreviewId = this.id.replace('_id', 'Preview');
            if ($(`#${mainPreviewId}`).length) {
                $(`#${mainPreviewId}`).show().find('img').attr('src', imageUrl);
            }
        } else {
            $(`#${previewId}`).hide();
        }
    });

    // Preview de videos (agregar información visual)
    $('#video_inicio_id, #video_mitad_id').on('change', function() {
        const videoId = $(this).val();
        const videoPath = $(this).find(':selected').data('path');
        const videoDuration = $(this).find(':selected').data('duracion') || $(this).find(':selected').text().match(/(\d+)s/)?.[1];

        // Limpiar indicadores anteriores
        $('#videoInicioIndicator, #videoMitadIndicator').remove();

        if (videoId && videoDuration) {
            const videoType = this.id.includes('inicio') ? 'inicio' : 'mitad';
            const videoTypeText = videoType === 'inicio' ? 'al inicio' : 'a la mitad';

            // Crear indicador con botón de preview
            const indicator = `
            <div id="video${videoType.charAt(0).toUpperCase() + videoType.slice(1)}Indicator" 
                 class="text-center mb-2 p-2 bg-warning rounded">
                <i class="fas fa-play-circle"></i> 
                Video de ${videoDuration}s ${videoTypeText} de la película
                <button type="button" class="btn btn-sm btn-primary ml-2" 
                        onclick="previewVideo('${videoPath}', '${videoDuration}', '${videoTypeText}')">
                    <i class="fas fa-eye"></i> Ver Video
                </button>
            </div>
        `;

            if (videoType === 'inicio') {
                $('#headerBannerPreview').before(indicator);
            } else {
                $('#catalogBannerPreview').after(indicator);
            }
        }
    });

    // Función para previsualizar video
    function previewVideo(videoPath, duration, type) {

        // Verificar información del video
        $.get('<?php echo API_URL; ?>utils/video-info.php?path=' + encodeURIComponent(videoPath), function(info) {
            console.log('Información del archivo de video:', info);
        });

        console.log('BASE_URL:', '<?php echo BASE_URL; ?>');
        console.log('videoPath:', videoPath);

        if (!videoPath) {
            toastr.error('No se puede cargar el video');
            return;
        }

        // Construir URL correctamente
        const videoUrl = '<?php echo str_replace('/admin/', '/', BASE_URL); ?>content/' + videoPath;
        console.log('URL final:', videoUrl);

        // Obtener el contenedor del video
        const videoContainer = document.querySelector('.video-container');

        // Limpiar el contenedor
        videoContainer.innerHTML = '';

        // Crear un nuevo elemento de video
        const newVideo = document.createElement('video');
        newVideo.id = 'previewVideoPlayer';
        newVideo.controls = true;
        newVideo.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain; background: #000;';

        // CAMBIO IMPORTANTE: Asignar la URL directamente al video
        newVideo.src = videoUrl;

        // Agregar el nuevo video al contenedor
        videoContainer.appendChild(newVideo);

        // Configurar eventos
        newVideo.addEventListener('error', function(e) {
            // Ignorar errores si no hay src (cuando se está cerrando)
            if (!newVideo.src || newVideo.src === '') {
                return;
            }

            console.error('Error del video:', {
                error: newVideo.error,
                networkState: newVideo.networkState,
                readyState: newVideo.readyState
            });

            // Mostrar mensaje de error más detallado
            let errorMessage = 'Error desconocido';
            if (newVideo.error) {
                switch (newVideo.error.code) {
                    case 1:
                        errorMessage = 'La carga del video fue abortada';
                        break;
                    case 2:
                        errorMessage = 'Error de red al cargar el video';
                        break;
                    case 3:
                        errorMessage = 'Error al decodificar el video (formato no soportado)';
                        break;
                    case 4:
                        errorMessage = 'Formato de video no soportado';
                        break;
                }
            }

            toastr.error('Error al cargar el video: ' + errorMessage);
        });

        newVideo.addEventListener('canplay', function() {
            console.log('Video listo para reproducir');
        });

        newVideo.addEventListener('error', function(e) {
            console.error('Error del video:', {
                error: newVideo.error,
                networkState: newVideo.networkState,
                readyState: newVideo.readyState
            });

            // Mostrar mensaje de error más detallado
            let errorMessage = 'Error desconocido';
            if (newVideo.error) {
                switch (newVideo.error.code) {
                    case 1:
                        errorMessage = 'La carga del video fue abortada';
                        break;
                    case 2:
                        errorMessage = 'Error de red al cargar el video';
                        break;
                    case 3:
                        errorMessage = 'Error al decodificar el video (formato no soportado)';
                        break;
                    case 4:
                        errorMessage = 'Formato de video no soportado';
                        break;
                }
            }

            toastr.error('Error al cargar el video: ' + errorMessage);

            // Mostrar información de debug
            videoContainer.innerHTML = `
            <div class="alert alert-danger m-3">
                <h5>Error al cargar video</h5>
                <p>Ruta: ${videoPath}</p>
                <p>URL: ${videoUrl}</p>
                <p>Error: ${errorMessage}</p>
            </div>
        `;
        });

        // Cargar el video
        newVideo.load();

        // Actualizar información
        $('#videoDurationInfo').text(duration);
        $('#videoTypeInfo').text(type);

        // Mostrar modal
        $('#videoPreviewModal').modal('show');

        // Limpiar cuando se cierre el modal
        $('#videoPreviewModal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
            // Pausar el video
            if (newVideo) {
                newVideo.pause();

                // Remover event listeners para evitar errores falsos
                newVideo.removeEventListener('error', null);

                // Limpiar el contenedor sin generar errores
                videoContainer.innerHTML = '';
            }
        });
    }

    // Cambiar paso del wizard
    function changeStep(direction) {
        // Validar paso actual antes de avanzar
        if (direction > 0 && !validateCurrentStep()) {
            return;
        }

        // Marcar paso actual como completado si avanzamos
        if (direction > 0) {
            markStepAsCompleted(currentStep);
        }

        // Ocultar paso actual con animación
        $(`#step${currentStep}`).fadeOut(200, function() {
            $(this).removeClass('active');

            // Cambiar al nuevo paso
            currentStep += direction;

            // Mostrar nuevo paso con animación
            $(`#step${currentStep}`).fadeIn(200).addClass('active');

            // Actualizar indicadores
            updateStepIndicators();
            updateButtons();
            $('#stepIndicator').text(`Paso ${currentStep} de ${totalSteps}`);

            // Si es el último paso, actualizar resumen
            if (currentStep === totalSteps) {
                updateReviewStep();
            }
        });
    }

    // Ir a un paso específico
    function goToStep(stepNumber) {
        if (stepNumber === currentStep) return;

        $(`#step${currentStep}`).fadeOut(200, function() {
            $(this).removeClass('active');
            currentStep = stepNumber;
            $(`#step${currentStep}`).fadeIn(200).addClass('active');
            updateStepIndicators();
            updateButtons();
            $('#stepIndicator').text(`Paso ${currentStep} de ${totalSteps}`);
        });
    }

    // Marcar paso como completado
    function markStepAsCompleted(stepNumber) {
        completedSteps.add(stepNumber);
        const $step = $(`.step[data-step="${stepNumber}"]`);
        $step.addClass('completed');
        $step.find('.step-icon').hide();
        $step.find('.step-check').show();
    }

    // Actualizar indicadores de pasos
    function updateStepIndicators() {
        $('.step').removeClass('active');
        $(`.step[data-step="${currentStep}"]`).addClass('active');
    }

    // Validar paso actual
    function validateCurrentStep() {
        let isValid = true;

        switch (currentStep) {
            case 1:
                if (!$('#empresa_id').val()) {
                    toastr.error('Seleccione una empresa');
                    $('#empresa_id').focus();
                    isValid = false;
                } else if (!$('#nombre_paquete').val()) {
                    toastr.error('Ingrese un nombre para el paquete');
                    $('#nombre_paquete').focus();
                    isValid = false;
                }
                break;
            case 3:
                if ($('.content-checkbox:checked').length === 0) {
                    toastr.error('Seleccione al menos un elemento de contenido');
                    // Hacer highlight en las tabs
                    $('.nav-tabs').addClass('shake-animation');
                    setTimeout(() => $('.nav-tabs').removeClass('shake-animation'), 500);
                    isValid = false;
                }
                break;
            case 4:
                if (!$('#wifi_ssid').val()) {
                    toastr.error('Ingrese el nombre de la red WiFi');
                    $('#wifi_ssid').focus();
                    isValid = false;
                } else if (!$('#wifi_password').val() || $('#wifi_password').val().length < 8) {
                    toastr.error('La contraseña debe tener al menos 8 caracteres');
                    $('#wifi_password').focus();
                    isValid = false;
                }
                break;
        }

        return isValid;
    }

    // Actualizar botones de navegación
    function updateButtons() {
        $('#prevBtn').toggle(currentStep > 1);
        $('#nextBtn').toggle(currentStep < totalSteps);
        $('#submitBtn').toggle(currentStep === totalSteps);

        if (currentStep === totalSteps) {
            $('#submitBtn').addClass('pulse-animation');
        }
    }

    // Actualizar preview del portal
    function updatePortalPreview() {
        const primaryColor = $('#color_primario').val();
        const secondaryColor = $('#color_secundario').val();
        const message = $('#mensaje_bienvenida').val() || 'Bienvenido a bordo!';

        $('#portalPreview').css('--preview-primary', primaryColor);
        $('#portalPreview').css('--preview-secondary', secondaryColor);
        $('#previewTitle').css('color', primaryColor);
        $('#previewMessage').css('color', secondaryColor).text(message);

        if (selectedCompany && selectedCompany.logo) {
            $('#previewLogo').attr('src', '<?php echo BASE_URL; ?>../companies/data/' + selectedCompany.logo);
        }
    }

    // Resetear color
    function resetColor(type) {
        if (selectedCompany) {
            if (type === 'primary') {
                $('#color_primario').val(selectedCompany.primaryColor).trigger('input');
            } else {
                $('#color_secundario').val(selectedCompany.secondaryColor).trigger('input');
            }
        }
    }

    // Actualizar conteo de contenido con animación
    function updateContentCount() {
        const moviesCount = $('.movie-checkbox:checked').length;
        const musicCount = $('.music-checkbox:checked').length;
        const gamesCount = $('.game-checkbox:checked').length;
        const totalCount = moviesCount + musicCount + gamesCount;

        // Actualizar contadores con animación
        animateCounter('#selectedMoviesCount', moviesCount);
        animateCounter('#selectedMusicCount', musicCount);
        animateCounter('#selectedGamesCount', gamesCount);
        animateCounter('#totalContentSelected', totalCount);

        // Actualizar badges en tabs
        updateTabBadge('#moviesBadge', moviesCount);
        updateTabBadge('#musicBadge', musicCount);
        updateTabBadge('#gamesBadge', gamesCount);

        // Actualizar barras de progreso
        const totalMovies = $('.movie-checkbox').length;
        const totalMusic = $('.music-checkbox').length;
        const totalGames = $('.game-checkbox').length;

        $('#moviesProgress').css('width', (moviesCount / totalMovies * 100) + '%');
        $('#musicProgress').css('width', (musicCount / totalMusic * 100) + '%');
        $('#gamesProgress').css('width', (gamesCount / totalGames * 100) + '%');

        selectedContent.movies = moviesCount;
        selectedContent.music = musicCount;
        selectedContent.games = gamesCount;
    }

    // Animar contador
    function animateCounter(selector, newValue) {
        const $counter = $(selector);
        const currentValue = parseInt($counter.text()) || 0;

        $({
            count: currentValue
        }).animate({
            count: newValue
        }, {
            duration: 300,
            step: function() {
                $counter.text(Math.round(this.count));
            },
            complete: function() {
                $counter.text(newValue);
                if (newValue > currentValue) {
                    $counter.addClass('bounce-animation');
                    setTimeout(() => $counter.removeClass('bounce-animation'), 500);
                }
            }
        });
    }

    // Actualizar badge de tab
    function updateTabBadge(selector, count) {
        const $badge = $(selector);
        if (count > 0) {
            $badge.text(count).fadeIn();
        } else {
            $badge.fadeOut();
        }
    }

    // Calcular tamaño del paquete
    function calculatePackageSize() {
        let totalSize = 0;

        $('.content-checkbox:checked').each(function() {
            totalSize += parseInt($(this).data('size')) || 0;
        });

        // Convertir a MB
        const sizeMB = (totalSize / 1024 / 1024).toFixed(2);
        $('#estimatedSize').text(sizeMB + ' MB');
    }

    // Generar contraseña aleatoria
    function generatePassword() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%';
        let password = '';
        for (let i = 0; i < 12; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        $('#wifi_password').val(password).addClass('flash-animation');
        setTimeout(() => $('#wifi_password').removeClass('flash-animation'), 500);
        updateQRPreview();
    }

    // Actualizar QR preview
    function updateQRPreview() {
        const ssid = $('#wifi_ssid').val();
        const password = $('#wifi_password').val();
        const companyId = $('#empresa_id').val();

        if (ssid && password && password.length >= 8) {
            const hidden = $('#wifi_hidden').is(':checked') ? 'true' : 'false';

            const qrUrl = `<?php echo API_URL; ?>qr/generate-wifi-qr.php?` +
                `ssid=${encodeURIComponent(ssid)}` +
                `&password=${encodeURIComponent(password)}` +
                `&hidden=${hidden}` +
                `&company_id=${companyId}` +
                `&_t=${Date.now()}`;

            $('#qrPreview').html(`
            <img src="${qrUrl}" 
                 alt="WiFi QR Code" 
                 style="max-width: 280px;" 
                 class="fade-in">            
        `);

            $('.wifi-info').html(`WiFi: <strong>${ssid}</strong>`);
        } else {
            $('#qrPreview').html(`
            <div id="qrPlaceholder" style="width: 200px; height: 200px; margin: 0 auto; background: #f8f9fa; border: 2px dashed #dee2e6; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                <i class="fas fa-qrcode fa-4x text-muted mb-2"></i>
                <small class="text-muted">${!ssid ? 'Ingrese el SSID' : 'Contraseña mínimo 8 caracteres'}</small>
            </div>
        `);

            $('.wifi-info').html('Para conectarte al WiFi del bus');
        }
    }

    // Actualizar paso de revisión
    function updateReviewStep() {
        // Información general
        $('#review_empresa').text($('#empresa_id option:selected').text());
        $('#review_nombre').text($('#nombre_paquete').val());
        $('#review_version').text($('#version_paquete').val() || '1.0');

        // WiFi
        $('#review_ssid').text($('#wifi_ssid').val() || 'No configurado');
        $('#review_password').text($('#wifi_password').val() || 'No configurada');
        $('#review_connections').text($('#max_connections').val() || '50');

        // Contenido
        $('#review_movies').text(selectedContent.movies || '0');
        $('#review_music').text(selectedContent.music || '0');
        $('#review_games').text(selectedContent.games || '0');
        $('#review_size').text($('#estimatedSize').text() || '0 MB');

        // Agregar información de publicidad
        const videoInicio = $('#video_inicio_id option:selected').text();
        const videoMitad = $('#video_mitad_id option:selected').text();
        const bannerHeader = $('#banner_header_id option:selected').text();
        const bannerFooter = $('#banner_footer_id option:selected').text();
        const bannerCatalogo = $('#banner_catalogo_id option:selected').text();

        // Mostrar en el resumen (agregar estos elementos al HTML del paso 6)
        $('#review_video_inicio').text(videoInicio !== 'Sin publicidad al inicio' ? videoInicio : 'No');
        $('#review_video_mitad').text(videoMitad !== 'Sin publicidad a la mitad' ? videoMitad : 'No');
        $('#review_banners').text(
            (bannerHeader !== 'Sin banner superior' ||
                bannerFooter !== 'Sin banner inferior' ||
                bannerCatalogo !== 'Sin banner en catálogo') ? 'Sí' : 'No'
        );
    }

    // Actualizar progreso
    function updateProgress(percent, status) {
        $('#progressBar')
            .css('width', percent + '%')
            .attr('aria-valuenow', percent)
            .text(Math.round(percent) + '%');
        $('#progressStatus').html('<i class="fas fa-cog fa-spin"></i> ' + status);
    }
</script>

<style>
    /* Asegurar que el video sea visible */
    #videoPreviewModal .modal-body {
        background-color: #000 !important;
        padding: 0 !important;
    }

    #videoPreviewModal video {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        background-color: #000 !important;
        z-index: 1 !important;
    }

    .video-container {
        background: #000 !important;
        position: relative !important;
        overflow: hidden !important;
    }

    /* Forzar que el video use todo el espacio */
    .video-container video {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        object-fit: contain !important;
    }
</style>