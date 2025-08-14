<?php

/**
 * Vista: Imprimir Código QR
 * Módulo 3.3: Página optimizada para impresión de QR codes
 */
require_once '../../config/system.php';
require_once '../../controllers/QRController.php';

$qrController = new QRController();
$data = $qrController->print($_GET['id'] ?? null);

// Extraer datos
$qrCode = $data['qr_code'];
$company = $data['company'];
$instructions = $data['instructions'];
// Esta página no usa el layout base para optimizar la impresión
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir QR - <?php echo htmlspecialchars($company['nombre']); ?></title>
    <style>
        /* Estilos optimizados para impresión */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: white;
            color: #333;
            line-height: 1.6;
        }

        .print-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #2563eb;
        }

        .company-logo {
            max-width: 200px;
            max-height: 80px;
            margin-bottom: 15px;
        }

        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }

        .service-name {
            font-size: 20px;
            color: #666;
        }

        .qr-section {
            text-align: center;
            margin: 40px 0;
        }

        .qr-code {
            display: inline-block;
            padding: 20px;
            background: white;
            border: 3px solid #2563eb;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .qr-code img {
            display: block;
            width: 300px;
            height: 300px;
        }

        .bus-number {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-top: 15px;
        }

        .instructions-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
        }

        .instructions-title {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 20px;
            text-align: center;
        }

        .instructions-list {
            counter-reset: step-counter;
            list-style: none;
            padding: 0;
        }

        .instructions-list li {
            counter-increment: step-counter;
            margin-bottom: 15px;
            padding-left: 50px;
            position: relative;
            font-size: 18px;
        }

        .instructions-list li::before {
            content: counter(step-counter);
            position: absolute;
            left: 0;
            top: 0;
            width: 40px;
            height: 40px;
            background: #2563eb;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
        }

        .wifi-info {
            background: white;
            border: 2px solid #2563eb;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }

        .wifi-label {
            font-size: 18px;
            color: #666;
            margin-bottom: 5px;
        }

        .wifi-name {
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
            font-family: 'Courier New', monospace;
        }

        .portal-url {
            background: #2563eb;
            color: white;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            letter-spacing: 2px;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            color: #666;
        }

        .support-info {
            margin-top: 10px;
            font-size: 16px;
        }

        .no-print {
            margin: 20px 0;
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 5px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .btn-secondary {
            background: #6c757d;
        }

        /* Estilos específicos para impresión */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .print-container {
                max-width: 100%;
                padding: 0;
            }

            .qr-section {
                page-break-inside: avoid;
            }

            .instructions-section {
                page-break-inside: avoid;
            }

            .qr-code {
                border: 2px solid #000;
                box-shadow: none;
            }
        }

        /* Diseño adicional para hacer más atractivo */
        .highlight-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin: 20px 0;
            font-size: 20px;
            font-weight: bold;
        }

        .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <!-- Botones de acción (no se imprimen) -->
    <div class="no-print">
        <a href="javascript:window.print()" class="btn">
            <span style="font-size: 20px;">🖨️</span> Imprimir
        </a>
        <a href="index.php" class="btn btn-secondary">
            <span style="font-size: 20px;">←</span> Volver
        </a>
    </div>
    <div class="print-container">
        <!-- Encabezado -->
        <div class="header">
            <?php if (!empty($company['logo_path']) && file_exists(COMPANIES_PATH . $company['logo_path'])): ?>
                <img src="<?php echo SITE_URL . 'companies/' . $company['logo_path']; ?>"
                    alt="<?php echo htmlspecialchars($company['nombre']); ?>"
                    class="company-logo">
            <?php endif; ?>

            <div class="company-name">
                <?php echo htmlspecialchars($company['nombre']); ?>
            </div>
            <div class="service-name">
                <?php echo htmlspecialchars($company['nombre_servicio'] ?? 'PLAYMI Entertainment'); ?>
            </div>
        </div>

        <!-- Código QR -->
        <div class="qr-section">
            <div class="qr-code">
                <img src="<?php echo str_replace(dirname(ROOT_PATH), SITE_URL, $qrCode['archivo_path']); ?>"
                    alt="Código QR">
                <div class="bus-number">
                    <?php echo htmlspecialchars($qrCode['numero_bus']); ?>
                </div>
            </div>
        </div>

        <!-- Información destacada -->
        <div class="highlight-box">
            <div class="icon">📱</div>
            ¡WiFi GRATIS + Entretenimiento!
        </div>

        <!-- Instrucciones -->
        <div class="instructions-section">
            <h2 class="instructions-title"><?php echo htmlspecialchars($instructions['title']); ?></h2>

            <ol class="instructions-list">
                <?php foreach ($instructions['steps'] as $step): ?>
                    <li><?php echo htmlspecialchars($step); ?></li>
                <?php endforeach; ?>
            </ol>

            <div class="wifi-info">
                <div class="wifi-label">Nombre de la red WiFi:</div>
                <div class="wifi-name"><?php echo htmlspecialchars($instructions['wifi_name']); ?></div>
            </div>

            <div class="portal-url">
                playmi.pe
            </div>
        </div>

        <!-- Pie de página -->
        <div class="footer">
            <strong>¡Disfruta tu viaje con PLAYMI!</strong>
            <div class="support-info">
                ¿Necesitas ayuda? <?php echo htmlspecialchars($instructions['support_contact']); ?>
            </div>
            <div style="margin-top: 10px; font-size: 14px;">
                Powered by PLAYMI Entertainment © <?php echo date('Y'); ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-imprimir si se especifica en la URL
        if (window.location.search.includes('autoprint=1')) {
            window.onload = function() {
                window.print();
            }
        }
    </script>
</body>

</html>