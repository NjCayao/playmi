<?php

/**
 * Generación rápida de paquetes - Versión simplificada para testing
 */

require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../controllers/PackageController.php';

$packageController = new PackageController();
$data = $packageController->generate();

$companies = $data['companies'] ?? [];
$content = $data['content'] ?? [];

// Layout simplificado
?>
<!DOCTYPE html>
<html>

<head>
    <title>Generación Rápida - PLAYMI</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>plugins/toastr/toastr.min.css">
    <script src="<?php echo ASSETS_URL; ?>plugins/jquery/jquery.min.js"></script>
    <script src="<?php echo ASSETS_URL; ?>plugins/toastr/toastr.min.js"></script>
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Generación Rápida de Paquete</h3>
                    </div>
                    <div class="card-body">
                        <form id="quickForm" method="POST" action="<?php echo API_URL; ?>packages/generate-package.php">
                            <!-- Empresa -->
                            <div class="form-group">
                                <label>Empresa *</label>
                                <select class="form-control" name="empresa_id" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?php echo $company['id']; ?>">
                                            <?php echo htmlspecialchars($company['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Nombre paquete -->
                            <div class="form-group">
                                <label>Nombre del Paquete *</label>
                                <input type="text" class="form-control" name="nombre_paquete"
                                    value="Paquete_Test_<?php echo date('His'); ?>" required>
                            </div>

                            <!-- WiFi -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>WiFi SSID *</label>
                                        <input type="text" class="form-control" name="wifi_ssid"
                                            value="PLAYMI-TEST" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>WiFi Password *</label>
                                        <input type="text" class="form-control" name="wifi_password"
                                            value="12345678" required minlength="8">
                                    </div>
                                </div>
                            </div>

                            <!-- Contenido rápido -->
                            <div class="form-group">
                                <label>Selección Rápida de Contenido:</label>
                                <div class="form-check">
                                    <input class="form-check-input select-all" type="checkbox" id="selectAll">
                                    <label class="form-check-label" for="selectAll">
                                        <strong>Seleccionar los primeros 3 de cada tipo</strong>
                                    </label>
                                </div>
                            </div>

                            <!-- Contenido -->
                            <div class="row" style="max-height: 300px; overflow-y: auto;">
                                <?php
                                $count = 0;
                                foreach ($content['movies'] ?? [] as $movie):
                                    if ($count++ >= 3) break;
                                ?>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input content-item" type="checkbox"
                                                name="content_ids[]" value="<?php echo $movie['id']; ?>"
                                                id="movie_<?php echo $movie['id']; ?>">
                                            <label class="form-check-label" for="movie_<?php echo $movie['id']; ?>">
                                                🎬 <?php echo substr($movie['titulo'], 0, 20); ?>...
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <?php
                                $count = 0;
                                foreach ($content['music'] ?? [] as $music):
                                    if ($count++ >= 3) break;
                                ?>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input content-item" type="checkbox"
                                                name="content_ids[]" value="<?php echo $music['id']; ?>"
                                                id="music_<?php echo $music['id']; ?>">
                                            <label class="form-check-label" for="music_<?php echo $music['id']; ?>">
                                                🎵 <?php echo substr($music['titulo'], 0, 20); ?>...
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <?php
                                $count = 0;
                                foreach ($content['games'] ?? [] as $game):
                                    if ($count++ >= 3) break;
                                ?>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input content-item" type="checkbox"
                                                name="content_ids[]" value="<?php echo $game['id']; ?>"
                                                id="game_<?php echo $game['id']; ?>">
                                            <label class="form-check-label" for="game_<?php echo $game['id']; ?>">
                                                🎮 <?php echo substr($game['titulo'], 0, 20); ?>...
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Valores por defecto ocultos -->
                            <input type="hidden" name="version_paquete" value="1.0">
                            <input type="hidden" name="portal_name" value="PLAYMI Entertainment">
                            <input type="hidden" name="color_primario" value="#2563eb">
                            <input type="hidden" name="color_secundario" value="#64748b">
                            <input type="hidden" name="mensaje_bienvenida" value="Bienvenido a bordo!">
                            <input type="hidden" name="enable_movies" value="1">
                            <input type="hidden" name="enable_music" value="1">
                            <input type="hidden" name="enable_games" value="1">

                            <hr>

                            <button type="submit" class="btn btn-success btn-lg btn-block">
                                <i class="fas fa-rocket"></i> Generar Paquete Rápido
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Progress -->
                <div class="card mt-3" id="progressCard" style="display: none;">
                    <div class="card-body">
                        <h5>Progreso de Generación</h5>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated"
                                id="progressBar" style="width: 0%">0%</div>
                        </div>
                        <p class="mt-2 mb-0" id="progressStatus">Iniciando...</p>
                    </div>
                </div>

                <!-- Result -->
                <div class="card mt-3" id="resultCard" style="display: none;">
                    <div class="card-body">
                        <h5 class="text-success">✅ Paquete Generado!</h5>
                        <div id="resultInfo"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Select all
            $('#selectAll').on('change', function() {
                $('.content-item').prop('checked', $(this).is(':checked'));
            });

            // Form submit
            $('#quickForm').on('submit', function(e) {
                e.preventDefault();

                // Validar contenido
                if ($('.content-item:checked').length === 0) {
                    alert('Seleccione al menos un contenido');
                    return;
                }

                // Mostrar progreso
                $('#progressCard').show();
                $('#resultCard').hide();

                const formData = new FormData(this);

                // Simular progreso
                let progress = 0;
                const progressInterval = setInterval(() => {
                    progress += 10;
                    if (progress <= 90) {
                        updateProgress(progress, 'Generando paquete...');
                    }
                }, 500);

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        clearInterval(progressInterval);
                        updateProgress(100, 'Completado!');

                        // Si la respuesta es string, intentar parsearla
                        if (typeof response === 'string') {
                            // Buscar el JSON en la respuesta (ignorar el Notice)
                            const jsonMatch = response.match(/\{.*\}$/);
                            if (jsonMatch) {
                                response = JSON.parse(jsonMatch[0]);
                            }
                        }

                        if (response && response.success) {
                            $('#resultCard').show();
                            $('#resultInfo').html(`
                                <p><strong>ID:</strong> ${response.package_id}</p>
                                <p><strong>Tamaño:</strong> ${(response.size / 1024 / 1024).toFixed(2)} MB</p>
                                <p><strong>Archivos:</strong> ${response.content_count}</p>
                                <a href="${response.download_url}" class="btn btn-primary">
                                    Descargar Paquete
                                </a>
                            `);
                            toastr.success('Paquete generado exitosamente!');
                        } else {
                            toastr.error(response.error || 'Error al generar');
                            $('#progressCard').hide();
                        }
                    },
                    error: function(xhr) {
                        clearInterval(progressInterval);
                        $('#progressCard').hide();
                        toastr.error('Error de conexión');
                        console.error(xhr.responseText);
                    }
                });
            });

            function updateProgress(percent, status) {
                $('#progressBar').css('width', percent + '%').text(percent + '%');
                $('#progressStatus').text(status);
            }
        });
    </script>
</body>

</html>