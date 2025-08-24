<?php
/**
 * passenger-portal/includes/header.php
 * Header reutilizable para todas las páginas del portal
 */

// Asegurar que solo se acceda desde el portal
if (!defined('PORTAL_ACCESS')) {
    die('Acceso directo no permitido');
}

// Obtener la página actual para marcar el menú activo
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Obtener configuración de la empresa si no está definida
if (!isset($companyConfig)) {
    $companyConfig = getCompanyConfig();
}
?>

<!-- Header del Portal -->
<header class="portal-header" id="portalHeader">
    <div class="header-content">
        <!-- Logo y navegación -->
        <div class="logo-section">
            <!-- Logo de la empresa o nombre -->
            <?php if (!empty($companyConfig['logo_url'])): ?>
                <a href="index.php" class="logo-link">
                    <img src="<?php echo htmlspecialchars($companyConfig['logo_url']); ?>" 
                         alt="<?php echo htmlspecialchars($companyConfig['company_name']); ?>" 
                         class="company-logo">
                </a>
            <?php else: ?>
                <a href="index.php" class="logo-link">
                    <h1 class="company-name"><?php echo htmlspecialchars($companyConfig['company_name']); ?></h1>
                </a>
            <?php endif; ?>
            
            <!-- Menú de navegación principal -->
            <nav class="nav-menu" id="mainNav">
                <a href="index.php" class="<?php echo $current_page === 'index' ? 'active' : ''; ?>">
                    <span class="nav-text">Inicio</span>
                </a>
                <a href="movies.php" class="<?php echo $current_page === 'movies' ? 'active' : ''; ?>">
                    <span class="nav-text">Películas</span>
                </a>
                <a href="music.php" class="<?php echo $current_page === 'music' ? 'active' : ''; ?>">
                    <span class="nav-text">Música</span>
                </a>
                <a href="games.php" class="<?php echo $current_page === 'games' ? 'active' : ''; ?>">
                    <span class="nav-text">Juegos</span>
                </a>
            </nav>
        </div>
        
        <!-- Acciones del header -->
        <div class="header-actions">
            <!-- Botón de búsqueda -->
            <button class="search-btn" id="searchBtn" title="Buscar" aria-label="Buscar contenido">
                <i class="fas fa-search"></i>
            </button>
            
            <!-- Menú de usuario (opcional para futuras versiones) -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-menu">
                    <button class="user-menu-btn" id="userMenuBtn">
                        <i class="fas fa-user-circle"></i>
                        <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="#profile" class="dropdown-item">
                            <i class="fas fa-user"></i> Mi Perfil
                        </a>
                        <a href="#settings" class="dropdown-item">
                            <i class="fas fa-cog"></i> Configuración
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#logout" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Botón de menú móvil -->
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Abrir menú">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</header>

<!-- Menú móvil -->
<nav class="mobile-nav" id="mobileNav">
    <ul class="mobile-nav-list">
        <li class="mobile-nav-item">
            <a href="index.php" class="mobile-nav-link <?php echo $current_page === 'index' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Inicio
            </a>
        </li>
        <li class="mobile-nav-item">
            <a href="movies.php" class="mobile-nav-link <?php echo $current_page === 'movies' ? 'active' : ''; ?>">
                <i class="fas fa-film"></i> Películas
            </a>
        </li>
        <li class="mobile-nav-item">
            <a href="music.php" class="mobile-nav-link <?php echo $current_page === 'music' ? 'active' : ''; ?>">
                <i class="fas fa-music"></i> Música
            </a>
        </li>
        <li class="mobile-nav-item">
            <a href="games.php" class="mobile-nav-link <?php echo $current_page === 'games' ? 'active' : ''; ?>">
                <i class="fas fa-gamepad"></i> Juegos
            </a>
        </li>
        <li class="mobile-nav-item">
            <a href="search.php" class="mobile-nav-link">
                <i class="fas fa-search"></i> Buscar
            </a>
        </li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li class="mobile-nav-item mobile-nav-divider"></li>
            <li class="mobile-nav-item">
                <a href="#profile" class="mobile-nav-link">
                    <i class="fas fa-user"></i> Mi Perfil
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="#settings" class="mobile-nav-link">
                    <i class="fas fa-cog"></i> Configuración
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="#logout" class="mobile-nav-link">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>

<!-- Overlay para menú móvil -->
<div class="mobile-nav-overlay" id="mobileNavOverlay"></div>

<!-- Script específico del header -->
<script>
// Funcionalidad del header
(function() {
    // Elementos
    const header = document.getElementById('portalHeader');
    const searchBtn = document.getElementById('searchBtn');
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileNav = document.getElementById('mobileNav');
    const mobileNavOverlay = document.getElementById('mobileNavOverlay');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');
    
    // Header scroll effect
    let lastScroll = 0;
    let scrollTimer = null;
    
    function handleScroll() {
        const currentScroll = window.pageYOffset;
        
        // Agregar/quitar clase scrolled
        if (currentScroll > 10) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        // Auto-ocultar en scroll down (solo en desktop)
        if (window.innerWidth > 768) {
            clearTimeout(scrollTimer);
            
            if (currentScroll > lastScroll && currentScroll > 100) {
                // Scrolling down
                header.style.transform = 'translateY(-100%)';
            } else {
                // Scrolling up
                header.style.transform = 'translateY(0)';
            }
            
            // Mostrar header después de dejar de scrollear
            scrollTimer = setTimeout(() => {
                header.style.transform = 'translateY(0)';
            }, 1000);
        }
        
        lastScroll = currentScroll;
    }
    
    window.addEventListener('scroll', handleScroll);
    
    // Búsqueda
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            // Si existe la función del portal principal
            if (window.Portal && window.Portal.toggleSearch) {
                window.Portal.toggleSearch();
            } else {
                // Redirigir a la página de búsqueda
                window.location.href = 'search.php';
            }
        });
    }
    
    // Menú móvil
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            toggleMobileMenu();
        });
    }
    
    if (mobileNavOverlay) {
        mobileNavOverlay.addEventListener('click', function() {
            closeMobileMenu();
        });
    }
    
    function toggleMobileMenu() {
        const isOpen = mobileNav.classList.contains('active');
        
        if (isOpen) {
            closeMobileMenu();
        } else {
            openMobileMenu();
        }
    }
    
    function openMobileMenu() {
        mobileNav.classList.add('active');
        mobileNavOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Cambiar ícono
        const icon = mobileMenuToggle.querySelector('i');
        icon.className = 'fas fa-times';
    }
    
    function closeMobileMenu() {
        mobileNav.classList.remove('active');
        mobileNavOverlay.classList.remove('active');
        document.body.style.overflow = '';
        
        // Cambiar ícono
        const icon = mobileMenuToggle.querySelector('i');
        icon.className = 'fas fa-bars';
    }
    
    // Menú de usuario
    if (userMenuBtn && userDropdown) {
        userMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
        });
        
        // Cerrar al hacer clic fuera
        document.addEventListener('click', function() {
            userDropdown.classList.remove('active');
        });
    }
    
    // Cerrar menú móvil al cambiar de página
    window.addEventListener('pageshow', function() {
        closeMobileMenu();
    });
    
    // Teclas de acceso rápido
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K para búsqueda
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            searchBtn.click();
        }
        
        // Escape para cerrar menús
        if (e.key === 'Escape') {
            closeMobileMenu();
            if (userDropdown) {
                userDropdown.classList.remove('active');
            }
        }
    });
})();
</script>

<!-- Estilos adicionales del header -->
<style>
/* Mejoras específicas del header */
.portal-header {
    transition: transform 0.3s ease, background 0.3s ease;
    will-change: transform;
}

.logo-link {
    text-decoration: none;
    color: inherit;
    display: flex;
    align-items: center;
}

.logo-link:hover .company-logo {
    opacity: 0.8;
}

/* Dropdown de usuario */
.user-menu {
    position: relative;
}

.user-menu-btn {
    background: none;
    border: none;
    color: white;
    font-size: 1.25rem;
    padding: 0.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.user-menu-btn:hover {
    color: var(--text-secondary);
}

.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--bg-card);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    min-width: 200px;
    margin-top: 0.5rem;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
}

.user-dropdown.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: var(--text-primary);
    text-decoration: none;
    transition: background 0.2s;
}

.dropdown-item:hover {
    background: var(--hover-bg);
}

.dropdown-item i {
    width: 20px;
    text-align: center;
    opacity: 0.7;
}

.dropdown-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.1);
    margin: 0.5rem 0;
}

/* Divider en menú móvil */
.mobile-nav-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.1);
    margin: 0.5rem 1rem;
}

/* Animación de entrada para menú móvil */
.mobile-nav.active .mobile-nav-item {
    animation: slideInLeft 0.3s ease forwards;
    opacity: 0;
}

.mobile-nav.active .mobile-nav-item:nth-child(1) { animation-delay: 0.05s; }
.mobile-nav.active .mobile-nav-item:nth-child(2) { animation-delay: 0.1s; }
.mobile-nav.active .mobile-nav-item:nth-child(3) { animation-delay: 0.15s; }
.mobile-nav.active .mobile-nav-item:nth-child(4) { animation-delay: 0.2s; }
.mobile-nav.active .mobile-nav-item:nth-child(5) { animation-delay: 0.25s; }

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Badge de notificaciones (para futuras implementaciones) */
.notification-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: var(--company-primary, #e50914);
    color: white;
    font-size: 0.625rem;
    padding: 2px 6px;
    border-radius: 10px;
    font-weight: 700;
}

/* Indicador de página activa mejorado */
.nav-menu a.active::after {
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        width: 0;
        opacity: 0;
    }
    to {
        width: 100%;
        opacity: 1;
    }
}
</style>