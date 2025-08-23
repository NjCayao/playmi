# PLAYMI SISTEMA 
ACTUALIZAR XAMPP php.ini:
ini; Configuración para archivos grandes
upload_max_filesize = 15G
post_max_size = 15G
max_execution_time = 7200  ; 2 horas
max_input_time = 7200      ; 2 horas
memory_limit = 2G

# usuario: admin, contraseña: password

 complete la fase 2.1

# para el pi 
Configuración en el Pi:
bash# dnsmasq.conf
address=/playmi.pe/192.168.4.1
Diseño del adhesivo para el bus:
┌─────────────────────────────────┐
│ Playmi tus peliculas favoritas  |
│                                 │
│  1. Escanea este código QR      │
│     [QR CODE WIFI]              │
│                                 │
│  2. Abre tu navegador y busca:  │
│     ╔═══════════════╗           │
│     ║  playmi.pe    ║           │
│     ╚═══════════════╝           │
│                                 │
│  ¡Disfruta películas, música    │
│   y juegos GRATIS!              │
└─────────────────────────────────┘

# ESTRUCTURA DE CARPETAS - PASSENGER PORTAL
 * Crear estas carpetas en la raíz del proyecto PLAYMI


 passenger-portal/
 ├── index.php                    # Página principal tipo Netflix
 ├── movies.php                   # Catálogo de películas
 ├── music.php                    # Reproductor de música
 ├── games.php                    # Catálogo de juegos
 ├── player/
 │   ├── video-player.php         # Reproductor de video con publicidad
 │   ├── music-player.php         # Reproductor de audio
 │   └── game-launcher.php        # Lanzador de juegos
 ├── assets/
 │   ├── css/
 │   │   ├── netflix-style.css    # Estilos principales tipo Netflix
 │   │   ├── player.css           # Estilos para reproductores
 │   │   └── mobile.css           # Optimizaciones móviles
 │   ├── js/
 │   │   ├── portal-main.js       # JavaScript principal
 │   │   ├── video-player.js      # Control de video + publicidad
 │   │   ├── music-player.js      # Control de música
 │   │   └── touch-controls.js    # Gestos táctiles
 │   └── images/
 │       ├── logo-default.png     # Logo por defecto
 │       └── icons/               # Iconos del sistema
 ├── api/
 │   ├── get-content.php          # API para obtener contenido
 │   ├── get-advertising.php      # API para publicidad
 │   ├── track-usage.php          # Tracking de uso
 │   └── company-branding.php     # Obtener personalización
 └── config/
     └── portal-config.php        # Configuración del portal