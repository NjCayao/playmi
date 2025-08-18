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
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WiFi Gratis - <?php echo htmlspecialchars($company['nombre']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: white;
            color: #333;
            padding: 10px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border: 2px solid <?php echo $company['color_primario'] ?? '#2563eb'; ?>;
            border-radius: 15px;
            overflow: hidden;
        }

        .header {
            background: <?php echo $company['color_primario'] ?? '#2563eb'; ?>;
            color: white;
            padding: 12px;
            text-align: center;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 3px;
        }

        .header .subtitle {
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            display: flex;
            min-height: 450px;
        }

        .method {
            flex: 1;
            padding: 20px;
            position: relative;
        }

        .method-qr {
            border-right: 2px dashed #ddd;
            background: #f0f7ff;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .method-manual {
            background: #fff9f0;
        }

        .method-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
            color: <?php echo $company['color_primario'] ?? '#2563eb'; ?>;
            width: 100%;
        }

        /* Layout para QR: QR a la izquierda, pasos a la derecha */
        .qr-layout {
            display: flex;
            align-items: center;
            gap: 30px;
            width: 100%;
        }

        .qr-container {
            flex-shrink: 0;
            padding-top: 35px;
        }

        .qr-container img {
            width: 280px;
            height: 280px;
            border: 2px solid <?php echo $company['color_primario'] ?? '#2563eb'; ?>;
            border-radius: 10px;
            padding: 5px;
            background: white;
        }

        .qr-steps {
            flex: 1;
        }

        /* Pasos más compactos para método QR */
        .step-compact {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            background: white;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            width: 250px;
        }

        .step-number {
            width: 35px;
            height: 35px;
            background: <?php echo $company['color_primario'] ?? '#2563eb'; ?>;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
            margin-right: 10px;
            flex-shrink: 0;
        }

        .step-content {
            flex: 1;
        }

        .step-text {
            font-size: 14px;
            line-height: 1.4;
        }

        /* Flecha horizontal entre pasos */
        .arrow-right {
            color: <?php echo $company['color_primario'] ?? '#2563eb'; ?>;
            margin: 0 10px;
            font-size: 20px;
        }

        /* Pasos del método manual */
        .manual-steps {
            display: flex;
            gap: 15px;
            flex-direction: column;
            align-content: flex-start;
            align-items: center;
        }

        .step-highlight {
            background: #fffacd;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
            font-weight: bold;
            margin: 3px 0;
            display: inline-block;
            border: 1px solid #ffd700;
        }

        .or-divider {
            position: absolute;
            top: 50%;
            left: -25px;
            transform: translateY(-50%);
            background: white;
            padding: 8px;
            border: 2px solid #ddd;
            border-radius: 50%;
            font-weight: bold;
            color: #666;
            z-index: 10;
            font-size: 14px;
        }

        .footer {
            background: #f8f9fa;
            padding: 10px;
            text-align: center;
            border-top: 2px solid <?php echo $company['color_primario'] ?? '#2563eb'; ?>;
            font-size: 12px;
        }

        .company-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 5px;
        }

        .company-logo {
            max-height: 25px;
        }

        .no-print {
            text-align: center;
            margin-bottom: 10px;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 5px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-secondary {
            background: #6c757d;
        }

        /* Estilos para impresión */
        @media print {
            @page {
                size: A4 landscape;
                margin: 5mm;
            }

            body {
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            .container {
                border-width: 2px;
                max-width: 100%;
            }

            .step-compact,
            .step {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }

        /* Para el paso final más grande */
        .final-step {
            background: <?php echo $company['color_primario'] ?? '#2563eb'; ?>;
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-top: 20px;
        }

        .final-step .step-highlight {
            background: white;
            color: <?php echo $company['color_primario'] ?? '#2563eb'; ?>;
            font-size: 20px;
            padding: 8px 20px;
        }
    </style>
</head>

<body>
    <!-- Botones de acción (no se imprimen) -->
    <div class="no-print">
        <a href="javascript:window.print()" class="btn">
            🖨️ Imprimir
        </a>
        <a href="index.php" class="btn btn-secondary">
            ← Volver
        </a>
    </div>

    <div class="container">
        <!-- Encabezado -->
        <div class="header">
            <h1>📶 WiFi GRATIS + Películas, Música y Juegos 🎬</h1>
            <div class="subtitle">¡Entretenimiento ilimitado durante tu viaje!</div>
        </div>

        <!-- Contenido principal -->
        <div class="content">
            <!-- Método QR -->
            <div class="method method-qr">
                <h2 class="method-title">✨ MÉTODO RÁPIDO</h2>

                <div class="qr-layout">
                    <!-- QR a la izquierda -->
                    <div class="qr-container">
                        <?php
                        $ssid = urlencode($qrCode['wifi_ssid']);
                        $password = urlencode($qrCode['wifi_password']);
                        $companyId = $qrCode['empresa_id'];
                        $prettyQrUrl = API_URL . "qr/generate-wifi-qr.php?ssid={$ssid}&password={$password}&hidden=false&company_id={$companyId}";
                        $savedQrUrl = SITE_URL . $qrCode['archivo_path'];
                        ?>
                        <img src="<?php echo $prettyQrUrl; ?>"
                            alt="Código QR"
                            onerror="this.onerror=null; this.src='<?php echo $savedQrUrl; ?>';">
                    </div>

                    <!-- Pasos a la derecha -->
                    <div class="qr-steps">
                        <div class="step-compact">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <div class="step-text">
                                    <strong>Escanea el código QR</strong><br>
                                    <small>con la cámara de tu celular</small>
                                </div>
                            </div>
                        </div>

                        <!-- <div class="step-compact">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <div class="step-text">
                                    <strong>Conéctate al WiFi</strong><br>
                                    <small>automáticamente</small>
                                </div>
                            </div>
                        </div> -->

                        <div class="step-compact">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <div class="step-text">
                                    <strong>Abre tu navegador y escribe:</strong><br>
                                    <div class="step-highlight">playmi.pe</div>
                                </div>
                            </div>
                        </div>

                        <!-- <div class="final-step">
                            <strong>3. Abre tu navegador y escribe:</strong><br>
                            <div class="step-highlight">playmi.pe</div>
                        </div> -->
                    </div>
                </div>

                <div class="or-divider">O</div>
            </div>

            <!-- Método Manual -->
            <div class="method method-manual">
                <h2 class="method-title">📱 MÉTODO MANUAL</h2>

                <div class="manual-steps">
                    <div class="step-compact">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <div class="step-text">
                                <strong>Busca el WiFi:</strong><br>
                                <div class="step-highlight"><?php echo htmlspecialchars($qrCode['wifi_ssid']); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="step-compact">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <div class="step-text">
                                <strong>Ingresa la contraseña:</strong><br>
                                <div class="step-highlight"><?php echo htmlspecialchars($qrCode['wifi_password']); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="step-compact">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <div class="step-text">
                                <strong>Abre tu navegador y escribe:</strong><br>
                                <div class="step-highlight">playmi.pe</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie de página -->
        <div class="footer">
            <div class="company-info">
                <?php if (!empty($company['logo_path'])): ?>
                    <img src="<?php echo SITE_URL . 'companies/data/' . $company['logo_path']; ?>"
                        alt="<?php echo htmlspecialchars($company['nombre']); ?>"
                        class="company-logo">
                <?php endif; ?>
                <strong><?php echo htmlspecialchars($company['nombre']); ?></strong>
                <span>•</span>
                <span>¡Playmi - Entretenimiento que viaja contigo! 🎉</span>
            </div>
            <div style="color: #666;">
                ¿Necesitas ayuda? Pregunta al personal del bus • Powered by PLAYMI Entertainment
            </div>
        </div>
    </div>
</body>

</html>