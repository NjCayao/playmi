<?php
// Incluir configuración y controlador
require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../controllers/PackageController.php';

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
    ASSETS_URL . 'plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css'
];

// JS adicional
$additionalJS = [
    ASSETS_URL . 'plugins/select2/js/select2.full.min.js',
    ASSETS_URL . 'plugins/jquery-validation/jquery.validate.min.js',
    ASSETS_URL . 'plugins/jquery-validation/additional-methods.min.js'
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

                                <div class="alert alert-warning">
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
                                                <i class="fas fa-clipboard-check step-icon"></i>
                                                <i class="fas fa-check step-check" style="display: none;"></i>
                                            </span>
                                            <span class="bs-stepper-label">Revisar</span>
                                        </button>
                                    </div>
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
                <div class="text-center">
                    <i class="fas fa-cog fa-spin fa-3x text-primary"></i>
                </div>
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
    const totalSteps = 6;
    let selectedCompany = null;
    let selectedContent = {
        movies: [],
        music: [],
        games: []
    };
    let completedSteps = new Set();

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

            // Preparar datos del formulario
            const formData = new FormData(this);

            // Simular progreso mientras esperamos respuesta
            let progressInterval = setInterval(() => {
                let currentProgress = parseInt($('#progressBar').css('width'));
                if (currentProgress < 90) {
                    updateProgress(currentProgress + 10, 'Generando paquete...');
                }
            }, 1000);

            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    clearInterval(progressInterval);

                    if (response.success) {
                        updateProgress(100, '¡Paquete generado exitosamente!');

                        // Mostrar información del paquete generado
                        Swal.fire({
                            icon: 'success',
                            title: 'Paquete Generado',
                            html: `
                                <p><strong>ID:</strong> ${response.package_id}</p>
                                <p><strong>Tamaño:</strong> ${(response.size / 1024 / 1024).toFixed(2)} MB</p>
                                <p><strong>Contenido:</strong> ${response.content_count} archivos</p>
                                <a href="${response.download_url}" class="btn btn-success mt-3">
                                    <i class="fas fa-download"></i> Descargar Paquete
                                </a>
                            `,
                            showCancelButton: true,
                            cancelButtonText: 'Cerrar',
                            confirmButtonText: 'Ir a Paquetes'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = '<?php echo BASE_URL; ?>views/packages/index.php';
                            } else {
                                $('#progressModal').modal('hide');
                            }
                        });
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
                    clearInterval(progressInterval);
                    $('#progressModal').modal('hide');

                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Conexión',
                        text: error
                    });
                }
            });
        });

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

        if (ssid && password && password.length >= 8) {
            const hidden = $('#wifi_hidden').is(':checked') ? 'true' : 'false';
            const qrUrl = `<?php echo API_URL; ?>qr/generate-wifi-qr.php?ssid=${encodeURIComponent(ssid)}&password=${encodeURIComponent(password)}&hidden=${hidden}`;

            $('#qrPreview').html(`
                <img src="${qrUrl}" alt="WiFi QR Code" style="max-width: 200px; border: 1px solid #ddd; padding: 10px; background: white;" class="fade-in">
                <div class="mt-2">
                    <small class="text-success"><i class="fas fa-check-circle"></i> QR generado correctamente</small>
                </div>
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
    /* Estilos para el wizard mejorado */
    .bs-stepper-header {
        display: flex;
        align-items: center;
        margin-bottom: 2rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 0.5rem;
    }

    .step {
        flex: 1;
        text-align: center;
        position: relative;
    }

    .step-trigger {
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        width: 100%;
    }

    .bs-stepper-circle {
        width: 50px;
        height: 50px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 500;
        color: #6c757d;
        background-color: #e9ecef;
        border: 2px solid #dee2e6;
        border-radius: 50%;
        margin: 0 auto 0.5rem;
        transition: all 0.3s ease;
    }

    .step.active .bs-stepper-circle {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
        transform: scale(1.1);
    }

    .step.completed .bs-stepper-circle {
        background-color: #28a745;
        border-color: #28a745;
        color: white;
    }

    .step.completed .step-trigger {
        cursor: pointer;
    }

    .bs-stepper-label {
        display: block;
        color: #6c757d;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .step.active .bs-stepper-label {
        color: #007bff;
        font-weight: 600;
    }

    .step.completed .bs-stepper-label {
        color: #28a745;
    }

    .line {
        flex: 1;
        height: 2px;
        background-color: #dee2e6;
        margin: 0 1rem;
        position: relative;
        top: -25px;
    }

    /* Estilos para contenido seleccionado */
    .content-item {
        padding: 0.5rem;
        border: 2px solid transparent;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .content-item:hover {
        background-color: #f8f9fa;
        transform: translateY(-2px);
    }

    .content-item.selected {
        background-color: #e3f2fd;
        border-color: #2196f3;
        box-shadow: 0 2px 8px rgba(33, 150, 243, 0.2);
    }

    .content-item .custom-control-label {
        cursor: pointer;
        width: 100%;
    }

    /* Contadores animados */
    .content-counter {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .content-counter:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }

    .counter-animation {
        font-size: 2rem;
        font-weight: bold;
    }

    @keyframes bounce {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.2);
        }
    }

    .bounce-animation {
        animation: bounce 0.5s ease;
    }

    /* Animaciones generales */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .fade-in {
        animation: fadeIn 0.5s ease;
    }

    .animated-card {
        animation: fadeIn 0.5s ease;
    }

    @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        25% {
            transform: translateX(-5px);
        }

        75% {
            transform: translateX(5px);
        }
    }

    .shake-animation {
        animation: shake 0.5s ease;
    }

    @keyframes flash {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
            background-color: #fff3cd;
        }
    }

    .flash-animation {
        animation: flash 0.5s ease;
    }

    /* Botón de generar paquete */
    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
        }
    }

    .pulse-animation {
        animation: pulse 2s infinite;
    }

    /* Preview del portal */
    #portalPreview {
        position: relative;
        overflow: hidden;
    }

    /* Instrucciones preview */
    .instruction-preview {
        font-family: Arial, sans-serif;
        position: relative;
    }

    .instruction-preview h4 {
        font-weight: bold;
    }

    .instruction-preview ol {
        padding-left: 20px;
    }

    .instruction-preview .badge {
        display: inline-block;
        font-family: monospace;
    }

    /* Progress bar en info boxes */
    .info-box .progress {
        height: 5px;
        margin-top: 5px;
        background-color: rgba(0, 0, 0, 0.1);
    }

    .info-box .progress-bar {
        transition: width 0.6s ease;
    }

    /* Badges en tabs */
    .nav-tabs .badge {
        transition: all 0.3s ease;
    }

    /* Validación visual */
    .form-control.is-invalid {
        border-color: #dc3545;
        padding-right: calc(1.5em + 0.75rem);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }

    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .bs-stepper-header {
            flex-direction: column;
        }

        .line {
            width: 2px;
            height: 30px;
            margin: 0.5rem auto;
            top: 0;
        }

        .bs-stepper-circle {
            width: 40px;
            height: 40px;
            font-size: 0.9rem;
        }

        .content-item {
            margin-bottom: 0.5rem;
        }
    }
</style>