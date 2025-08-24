<?php
/**
 * passenger-portal/includes/footer.php
 * Footer reutilizable para todas las páginas del portal
 */

// Asegurar que solo se acceda desde el portal
if (!defined('PORTAL_ACCESS')) {
    die('Acceso directo no permitido');
}
?>

    </main>
    <!-- Fin del contenedor principal -->
    
    <!-- Footer -->
    <footer class="portal-footer">
        <div class="footer-content">
            <div class="footer-brand">
                <h3><?php echo htmlspecialchars($companyConfig['service_name'] ?? $companyConfig['company_name']); ?></h3>
                <p class="footer-tagline">Tu entretenimiento a bordo</p>
            </div>
            
            <div class="footer-links">
                <div class="footer-section">
                    <h4>Contenido</h4>
                    <ul>
                        <li><a href="movies.php">Películas</a></li>
                        <li><a href="music.php">Música</a></li>
                        <li><a href="games.php">Juegos</a></li>
                        <li><a href="search.php">Buscar</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Ayuda</h4>
                    <ul>
                        <li><a href="#" onclick="showHelp()">Cómo usar</a></li>
                        <li><a href="#" onclick="showFAQ()">Preguntas frecuentes</a></li>
                        <li><a href="#" onclick="reportProblem()">Reportar problema</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Información</h4>
                    <ul>
                        <li><a href="#" onclick="showAbout()">Acerca de</a></li>
                        <li><a href="#" onclick="showTerms()">Términos de uso</a></li>
                        <li><a href="#" onclick="showPrivacy()">Privacidad</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($companyConfig['company_name']); ?>. Todos los derechos reservados.</p>
                <p class="footer-powered">Desarrollado por <strong>PLAYMI</strong></p>
            </div>
        </div>
    </footer>
    
    <!-- Modal genérico para información -->
    <div class="info-modal" id="infoModal">
        <div class="info-modal-content">
            <button class="info-modal-close" onclick="closeInfoModal()">
                <i class="fas fa-times"></i>
            </button>
            <h2 id="infoModalTitle">Información</h2>
            <div id="infoModalContent">
                <!-- El contenido se cargará dinámicamente -->
            </div>
        </div>
    </div>
    
    <!-- Scripts base -->
    <script src="assets/js/portal-main.js"></script>
    
    <!-- Scripts adicionales según página -->
    <?php if (isset($additionalJS) && is_array($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Script del footer -->
    <script>
        // Funciones del footer
        function showHelp() {
            showInfoModal('Cómo usar el portal', `
                <div class="help-content">
                    <h3>Navegación</h3>
                    <p>Usa el menú superior para navegar entre las diferentes secciones: Películas, Música y Juegos.</p>
                    
                    <h3>Reproducir contenido</h3>
                    <p>Haz clic en cualquier contenido para comenzar a reproducirlo. Los controles aparecerán automáticamente.</p>
                    
                    <h3>Buscar</h3>
                    <p>Usa el icono de búsqueda para encontrar contenido específico por título, género o categoría.</p>
                    
                    <h3>Controles táctiles</h3>
                    <p>En dispositivos móviles, desliza para navegar y toca para seleccionar.</p>
                </div>
            `);
        }
        
        function showFAQ() {
            showInfoModal('Preguntas frecuentes', `
                <div class="faq-content">
                    <div class="faq-item">
                        <h4>¿Necesito conexión a internet?</h4>
                        <p>No, todo el contenido está disponible localmente a través del WiFi del bus.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h4>¿Puedo descargar contenido?</h4>
                        <p>El contenido está disponible para streaming mientras estés en el bus.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h4>¿Por qué veo publicidad?</h4>
                        <p>La publicidad ayuda a mantener este servicio gratuito para todos los pasajeros.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h4>¿Hay límite de uso?</h4>
                        <p>No, puedes disfrutar de todo el contenido durante tu viaje sin límites.</p>
                    </div>
                </div>
            `);
        }
        
        function reportProblem() {
            showInfoModal('Reportar un problema', `
                <div class="report-content">
                    <p>Si experimentas algún problema con el servicio, por favor informa al personal del bus.</p>
                    
                    <div class="problem-options">
                        <button class="problem-btn" onclick="reportIssue('video')">
                            <i class="fas fa-film"></i>
                            <span>Problema con video</span>
                        </button>
                        <button class="problem-btn" onclick="reportIssue('audio')">
                            <i class="fas fa-music"></i>
                            <span>Problema con audio</span>
                        </button>
                        <button class="problem-btn" onclick="reportIssue('game')">
                            <i class="fas fa-gamepad"></i>
                            <span>Problema con juegos</span>
                        </button>
                        <button class="problem-btn" onclick="reportIssue('connection')">
                            <i class="fas fa-wifi"></i>
                            <span>Problema de conexión</span>
                        </button>
                    </div>
                </div>
            `);
        }
        
        function showAbout() {
            showInfoModal('Acerca de', `
                <div class="about-content">
                    <p><strong><?php echo htmlspecialchars($companyConfig['service_name'] ?? $companyConfig['company_name']); ?></strong> 
                    es tu portal de entretenimiento a bordo.</p>
                    
                    <p>Disfruta de películas, música y juegos durante tu viaje, todo disponible a través de nuestro sistema WiFi local.</p>
                    
                    <div class="about-stats">
                        <div class="stat">
                            <i class="fas fa-film"></i>
                            <span>Películas</span>
                        </div>
                        <div class="stat">
                            <i class="fas fa-music"></i>
                            <span>Música</span>
                        </div>
                        <div class="stat">
                            <i class="fas fa-gamepad"></i>
                            <span>Juegos</span>
                        </div>
                    </div>
                    
                    <p class="about-footer">Sistema desarrollado por PLAYMI Entertainment Systems</p>
                </div>
            `);
        }
        
        function showTerms() {
            showInfoModal('Términos de uso', `
                <div class="terms-content">
                    <p>Al usar este servicio, aceptas los siguientes términos:</p>
                    
                    <ol>
                        <li>Este servicio es exclusivo para pasajeros del bus.</li>
                        <li>El contenido es solo para streaming, no para descarga.</li>
                        <li>No compartas contenido inapropiado o ilegal.</li>
                        <li>Respeta a otros pasajeros al usar el servicio.</li>
                        <li>El servicio puede incluir publicidad.</li>
                    </ol>
                    
                    <p>El mal uso del servicio puede resultar en la restricción del acceso.</p>
                </div>
            `);
        }
        
        function showPrivacy() {
            showInfoModal('Política de privacidad', `
                <div class="privacy-content">
                    <p>Tu privacidad es importante para nosotros.</p>
                    
                    <h4>Información que recopilamos</h4>
                    <ul>
                        <li>Estadísticas anónimas de uso</li>
                        <li>Preferencias de contenido</li>
                        <li>Información del dispositivo</li>
                    </ul>
                    
                    <h4>Cómo usamos la información</h4>
                    <ul>
                        <li>Para mejorar el servicio</li>
                        <li>Para ofrecer contenido relevante</li>
                        <li>Para estadísticas de uso</li>
                    </ul>
                    
                    <p>No compartimos información personal con terceros.</p>
                </div>
            `);
        }
        
        // Modal de información
        function showInfoModal(title, content) {
            const modal = document.getElementById('infoModal');
            document.getElementById('infoModalTitle').textContent = title;
            document.getElementById('infoModalContent').innerHTML = content;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeInfoModal() {
            const modal = document.getElementById('infoModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // Reportar problema
        function reportIssue(type) {
            // Registrar el problema
            if (window.Portal) {
                Portal.trackInteraction('problem_reported', {
                    type: type,
                    timestamp: new Date().toISOString()
                });
            }
            
            closeInfoModal();
            
            // Mostrar confirmación
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>Problema reportado. El personal será notificado.</span>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // Cerrar modal con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeInfoModal();
            }
        });
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('infoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeInfoModal();
            }
        });
    </script>
    
    <!-- Estilos del footer -->
    <style>
        /* Footer styles */
        .portal-footer {
            background: var(--bg-secondary, #000);
            color: var(--text-secondary);
            padding: 3rem 4%;
            margin-top: 6rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .footer-brand {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .footer-brand h3 {
            color: var(--company-primary);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .footer-tagline {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .footer-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .footer-section h4 {
            color: var(--text-primary);
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        
        .footer-section ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-section li {
            margin-bottom: 0.5rem;
        }
        
        .footer-section a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s;
        }
        
        .footer-section a:hover {
            color: var(--text-primary);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.875rem;
        }
        
        .footer-powered {
            margin-top: 0.5rem;
            opacity: 0.7;
        }
        
        /* Modal de información */
        .info-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            padding: 1rem;
        }
        
        .info-modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .info-modal-content {
            background: var(--bg-card, #1a1a1a);
            border-radius: 12px;
            max-width: 600px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            padding: 2rem;
            position: relative;
            transform: scale(0.9);
            transition: transform 0.3s;
        }
        
        .info-modal.active .info-modal-content {
            transform: scale(1);
        }
        
        .info-modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            transition: color 0.2s;
        }
        
        .info-modal-close:hover {
            color: var(--text-primary);
        }
        
        #infoModalTitle {
            margin-bottom: 1.5rem;
            color: var(--company-primary);
        }
        
        /* Estilos de contenido del modal */
        .help-content h3,
        .faq-item h4 {
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .faq-item {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .faq-item:last-child {
            border-bottom: none;
        }
        
        .problem-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .problem-btn {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--text-primary);
            padding: 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        
        .problem-btn:hover {
            background: var(--hover-bg);
            border-color: var(--company-primary);
            transform: translateY(-2px);
        }
        
        .problem-btn i {
            font-size: 1.5rem;
            color: var(--company-primary);
        }
        
        .about-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat i {
            font-size: 2rem;
            color: var(--company-primary);
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .about-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            opacity: 0.7;
        }
        
        .terms-content ol,
        .privacy-content ul {
            margin: 1rem 0;
            padding-left: 1.5rem;
        }
        
        .terms-content li,
        .privacy-content li {
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }
        
        /* Toast notification */
        .toast-notification {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: var(--bg-card);
            color: var(--text-primary);
            padding: 1rem 2rem;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s;
            z-index: 10001;
        }
        
        .toast-notification.show {
            transform: translateX(-50%) translateY(0);
        }
        
        .toast-notification i {
            color: #4caf50;
            font-size: 1.25rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .portal-footer {
                padding: 2rem 4%;
                margin-top: 3rem;
            }
            
            .footer-links {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .info-modal-content {
                padding: 1.5rem;
            }
            
            .problem-options {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
    
    <!-- Script de inicialización final -->
    <script>
        // Inicializar Portal si existe
        if (window.Portal) {
            Portal.init(window.portalConfig);
        }
        
        // Log de página vista
        if (window.Portal && window.Portal.trackInteraction) {
            Portal.trackInteraction('page_view', {
                page: '<?php echo $currentPage; ?>',
                title: document.title
            });
        }
    </script>
</body>
</html>