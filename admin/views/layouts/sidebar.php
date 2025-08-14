<?php

/**
 * Sidebar PLAYMI Admin
 * Menú lateral de navegación
 */

// Obtener la página actual para marcar el menú activo
$currentPage = basename($_SERVER['REQUEST_URI']);
$currentPath = $_SERVER['REQUEST_URI'];

// Función helper para determinar si un menú está activo
function isMenuActive($menuPath, $currentPath)
{
    return strpos($currentPath, $menuPath) !== false;
}

// Función helper para determinar si un menú padre debe estar abierto
function isMenuOpen($menuPaths, $currentPath)
{
    foreach ($menuPaths as $path) {
        if (strpos($currentPath, $path) !== false) {
            return true;
        }
    }
    return false;
}
?>

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="<?php echo BASE_URL; ?>index.php" class="brand-link">
        <img src="<?php echo ASSETS_URL; ?>images/playmi-logo-white.png"
            alt="PLAYMI Logo"
            class="brand-image"
            style="opacity: .8; float: none !important; max-height: 45px !important;">
        <!-- <span class="brand-text font-weight-light"> PLAYMI</span> -->
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="<?php echo ASSETS_URL; ?>images/default-avatar.png"
                    class="img-circle elevation-2"
                    alt="<?php echo htmlspecialchars($currentUser['nombre_completo'] ?? $currentUser['username']); ?>">
            </div>
            <div class="info">
                <a href="#" class="d-block">
                    <?php echo htmlspecialchars($currentUser['nombre_completo'] ?? $currentUser['username']); ?>
                </a>
                <small class="text-muted">
                    <i class="fas fa-circle text-success" style="font-size: 0.6rem;"></i>
                    En línea
                </small>
            </div>
        </div>

        <!-- SidebarSearch Form -->
        <div class="form-inline">
            <div class="input-group" data-widget="sidebar-search">
                <input class="form-control form-control-sidebar" type="search" placeholder="Buscar..." aria-label="Search">
                <div class="input-group-append">
                    <button class="btn btn-sidebar">
                        <i class="fas fa-search fa-fw"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>index.php"
                        class="nav-link <?php echo ($currentPage == 'index.php' || $currentPage == '') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- Empresas -->
                <li class="nav-item <?php echo isMenuOpen(['companies'], $currentPath) ? 'menu-open' : ''; ?>">
                    <a href="#" class="nav-link <?php echo isMenuActive('companies', $currentPath) ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-building"></i>
                        <p>
                            Empresas
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/companies/index.php"
                                class="nav-link <?php echo isMenuActive('companies/index.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Lista de Empresas</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/companies/create.php"
                                class="nav-link <?php echo isMenuActive('companies/create.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Nueva Empresa</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/companies/index.php?filter=expiring"
                                class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Próximas a Vencer
                                    <?php if (isset($expiringCompanies) && count($expiringCompanies) > 0): ?>
                                        <span class="badge badge-warning right"><?php echo count($expiringCompanies); ?></span>
                                    <?php endif; ?>
                                </p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Contenido -->
                <li class="nav-item <?php echo isMenuOpen(['content'], $currentPath) ? 'menu-open' : ''; ?>">
                    <a href="#" class="nav-link <?php echo isMenuActive('content', $currentPath) ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-film"></i>
                        <p>
                            Contenido
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/content/index.php"
                                class="nav-link <?php echo isMenuActive('content/index.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Todo el Contenido</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/content/movies.php"
                                class="nav-link <?php echo isMenuActive('content/movies.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Películas</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/content/music.php"
                                class="nav-link <?php echo isMenuActive('content/music.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Música</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/content/games.php"
                                class="nav-link <?php echo isMenuActive('content/games.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Juegos</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/content/upload.php"
                                class="nav-link <?php echo isMenuActive('content/upload.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-plus-square nav-icon"></i>
                                <p>Subir Contenido</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Publicidad -->
                <li class="nav-item <?php echo isMenuOpen(['advertising'], $currentPath) ? 'menu-open' : ''; ?>">
                    <a href="#" class="nav-link <?php echo isMenuActive('advertising', $currentPath) ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-ad"></i>
                        <p>
                            Publicidad
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/advertising/videos.php"
                                class="nav-link <?php echo isMenuActive('advertising/videos.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Videos Publicitarios</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/advertising/banners.php"
                                class="nav-link <?php echo isMenuActive('advertising/banners.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Banners</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Paquetes -->
                <li class="nav-item <?php echo isMenuOpen(['packages'], $currentPath) ? 'menu-open' : ''; ?>">
                    <a href="#" class="nav-link <?php echo isMenuActive('packages', $currentPath) ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-box"></i>
                        <p>
                            Paquetes
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/packages/index.php"
                                class="nav-link <?php echo isMenuActive('packages/index.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Lista de Paquetes</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/packages/generate.php"
                                class="nav-link <?php echo isMenuActive('packages/generate.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-plus-square nav-icon"></i>
                                <p>Generar Paquete</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/packages/history.php"
                                class="nav-link <?php echo isMenuActive('packages/history.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Historial</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <!-- Sistema QR -->
                <li class="nav-item <?php echo isMenuOpen(['qr-system'], $currentPath) ? 'menu-open' : ''; ?>">
                    <a href="#" class="nav-link <?php echo isMenuActive('qr-system', $currentPath) ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-qrcode"></i>
                        <p>
                            Sistema QR
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/qr-system/index.php"
                                class="nav-link <?php echo isMenuActive('qr-system/index.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Lista de QR</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/qr-system/generate.php"
                                class="nav-link <?php echo isMenuActive('qr-system/generate.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-plus-square nav-icon"></i>
                                <p>Generar QR</p>
                            </a>
                        </li>
                    </ul>
                </li>


                <!-- Notificaciones -->
                <li class="nav-item <?php echo isMenuOpen(['notifications'], $currentPath) ? 'menu-open' : ''; ?>">
                    <a href="#" class="nav-link <?php echo isMenuActive('notifications', $currentPath) ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-envelope"></i>
                        <p>
                            Notificaciones
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/notifications/index.php"
                                class="nav-link <?php echo isMenuActive('notifications/index.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Historial</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/notifications/send.php"
                                class="nav-link <?php echo isMenuActive('notifications/send.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-paper-plane nav-icon"></i>
                                <p>Enviar Notificación</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/notifications/templates.php"
                                class="nav-link <?php echo isMenuActive('notifications/templates.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Templates</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/notifications/config.php"
                                class="nav-link <?php echo isMenuActive('notifications/config.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-cog nav-icon"></i>
                                <p>Configuración SMTP</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Reportes -->
                <li class="nav-item <?php echo isMenuOpen(['reports'], $currentPath) ? 'menu-open' : ''; ?>">
                    <a href="#" class="nav-link <?php echo isMenuActive('reports', $currentPath) ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <p>
                            Reportes
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/reports/analytics.php"
                                class="nav-link <?php echo isMenuActive('reports/analytics.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Analytics</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/reports/usage.php"
                                class="nav-link <?php echo isMenuActive('reports/usage.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Uso del Sistema</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>views/reports/revenue.php"
                                class="nav-link <?php echo isMenuActive('reports/revenue.php', $currentPath) ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Ingresos</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Separador -->
                <li class="nav-header">SISTEMA</li>

                <!-- Configuración -->
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>views/settings/index.php"
                        class="nav-link <?php echo isMenuActive('settings', $currentPath) ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>Configuración</p>
                    </a>
                </li>

                <!-- Logs -->
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>views/logs/index.php"
                        class="nav-link <?php echo isMenuActive('logs', $currentPath) ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-list-alt"></i>
                        <p>Logs del Sistema</p>
                    </a>
                </li>

                <!-- Cerrar Sesión -->
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>logout.php" class="nav-link text-danger">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Cerrar Sesión</p>
                    </a>
                </li>

            </ul>
        </nav>
    </div>
</aside>