#!/bin/bash
#================================================================
# PLAYMI - Configuración de Servidor Web Apache + PHP
# Módulo 6.3: pi-system/install/web-server.sh
# Descripción: Configura Apache y PHP optimizado para Raspberry Pi 5
# Compatible con: 70 usuarios simultáneos streaming de contenido
# Autor: Sistema PLAYMI
# Fecha: 2024
#================================================================

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Variables
WEB_ROOT="/var/www/playmi"
APACHE_CONF="/etc/apache2/sites-available/playmi.conf"
PHP_INI="/etc/php/8.1/apache2/php.ini"
PHP_CLI_INI="/etc/php/8.1/cli/php.ini"
LOG_DIR="/var/log/playmi"

# Función de logging
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] INFO: $1" >> "$LOG_DIR/web-server-setup.log"
}

log_success() {
    echo -e "${GREEN}[OK]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] SUCCESS: $1" >> "$LOG_DIR/web-server-setup.log"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1" >> "$LOG_DIR/web-server-setup.log"
}

# 1. Crear estructura de directorios
create_web_structure() {
    log_info "Creando estructura de directorios web..."
    
    # Directorios principales
    mkdir -p "$WEB_ROOT"/{portal,content,api,assets,temp,cache}
    mkdir -p "$WEB_ROOT"/content/{movies,music,games}
    mkdir -p "$WEB_ROOT"/assets/{css,js,images,fonts}
    mkdir -p "$WEB_ROOT"/api/{sync,content,games}
    mkdir -p "$LOG_DIR"/{apache,php,app}
    
    # Permisos
    chown -R www-data:www-data "$WEB_ROOT"
    chmod -R 755 "$WEB_ROOT"
    chmod -R 777 "$WEB_ROOT"/{temp,cache}
    chmod -R 777 "$WEB_ROOT"/content
    chmod -R 755 "$LOG_DIR"
    
    log_success "Estructura de directorios creada"
}

# 2. Configurar Apache optimizado para Pi 5
configure_apache() {
    log_info "Configurando Apache para alto rendimiento..."
    
    # Habilitar módulos necesarios
    log_info "Habilitando módulos Apache..."
    a2enmod rewrite headers expires deflate filter mime setenvif ssl proxy proxy_http >> "$LOG_DIR/web-server-setup.log" 2>&1
    
    # Crear configuración del sitio
    cat > "$APACHE_CONF" << 'EOF'
<VirtualHost *:80>
    ServerName playmi.local
    ServerAlias 192.168.4.1
    DocumentRoot /var/www/playmi
    
    # Configuración de logs
    ErrorLog ${APACHE_LOG_DIR}/playmi-error.log
    CustomLog ${APACHE_LOG_DIR}/playmi-access.log combined
    
    # Configuración de directorios
    <Directory /var/www/playmi>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Rewrite rules
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ /index.php?route=$1 [QSA,L]
    </Directory>
    
    # Configuración específica para contenido multimedia
    <Directory /var/www/playmi/content>
        Options -Indexes
        
        # Headers para streaming
        Header set Accept-Ranges bytes
        Header set Cache-Control "public, max-age=3600"
        
        # CORS para contenido
        Header set Access-Control-Allow-Origin "*"
        Header set Access-Control-Allow-Methods "GET, OPTIONS"
    </Directory>
    
    # Configuración para videos
    <FilesMatch "\.(mp4|webm|ogv)$">
        SetHandler default-handler
        Header set Cache-Control "public, max-age=7200"
        Header set X-Content-Type-Options "nosniff"
    </FilesMatch>
    
    # Configuración para audio
    <FilesMatch "\.(mp3|ogg|wav|m4a)$">
        Header set Cache-Control "public, max-age=7200"
    </FilesMatch>
    
    # Compresión
    <IfModule mod_deflate.c>
        SetOutputFilter DEFLATE
        SetEnvIfNoCase Request_URI \.(?:gif|jpe?g|png|mp4|mp3|zip|gz|bz2)$ no-gzip
    </IfModule>
    
    # Cache de assets estáticos
    <FilesMatch "\.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg|eot)$">
        Header set Cache-Control "public, max-age=31536000"
    </FilesMatch>
    
    # Seguridad básica
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set X-Content-Type-Options "nosniff"
    
    # Límites para upload
    LimitRequestBody 2147483648
    
    # Performance
    EnableSendfile On
    EnableMMAP On
</VirtualHost>
EOF
    
    # Configurar MPM para Pi 5 (optimizado para 70 usuarios)
    cat > /etc/apache2/mods-available/mpm_prefork.conf << 'EOF'
<IfModule mpm_prefork_module>
    StartServers             5
    MinSpareServers          5
    MaxSpareServers          10
    MaxRequestWorkers        100
    MaxConnectionsPerChild   1000
    ServerLimit              100
</IfModule>
EOF
    
    # Deshabilitar sitio default y habilitar playmi
    a2dissite 000-default.conf >> "$LOG_DIR/web-server-setup.log" 2>&1
    a2ensite playmi.conf >> "$LOG_DIR/web-server-setup.log" 2>&1
    
    log_success "Apache configurado para alto rendimiento"
}

# 3. Optimizar PHP para streaming
configure_php() {
    log_info "Optimizando PHP para Pi 5..."
    
    # Backup de php.ini
    cp "$PHP_INI" "$PHP_INI.backup"
    
    # Configuraciones optimizadas para Pi 5
    cat > /tmp/php-optimizations.conf << 'EOF'
; PLAYMI - Optimizaciones PHP para Pi 5

; Memoria (Pi 5 tiene 4-8GB)
memory_limit = 256M

; Límites de ejecución
max_execution_time = 300
max_input_time = 300

; Upload para contenido
upload_max_filesize = 2048M
post_max_size = 2048M
max_file_uploads = 20

; Sesiones
session.gc_maxlifetime = 3600
session.cookie_httponly = 1

; Output buffering para streaming
output_buffering = Off
implicit_flush = Off

; OPcache para performance
opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 60
opcache.fast_shutdown = 1

; Realpath cache
realpath_cache_size = 4096K
realpath_cache_ttl = 600

; Zona horaria
date.timezone = America/Lima

; Error handling
display_errors = Off
log_errors = On
error_log = /var/log/playmi/php/error.log

; Seguridad
expose_php = Off
allow_url_fopen = On
allow_url_include = Off
EOF
    
    # Aplicar optimizaciones a Apache PHP
    while IFS= read -r line; do
        if [[ ! -z "$line" && ! "$line" =~ ^[\;] ]]; then
            key=$(echo "$line" | cut -d= -f1 | xargs)
            value=$(echo "$line" | cut -d= -f2 | xargs)
            sed -i "s/^$key = .*/$key = $value/" "$PHP_INI"
        fi
    done < /tmp/php-optimizations.conf
    
    # Aplicar también a CLI PHP
    cp /tmp/php-optimizations.conf /tmp/php-cli-optimizations.conf
    sed -i 's/opcache.enable_cli = 1/opcache.enable_cli = 0/' /tmp/php-cli-optimizations.conf
    
    while IFS= read -r line; do
        if [[ ! -z "$line" && ! "$line" =~ ^[\;] ]]; then
            key=$(echo "$line" | cut -d= -f1 | xargs)
            value=$(echo "$line" | cut -d= -f2 | xargs)
            sed -i "s/^$key = .*/$key = $value/" "$PHP_CLI_INI"
        fi
    done < /tmp/php-cli-optimizations.conf
    
    # Limpiar
    rm -f /tmp/php-optimizations.conf /tmp/php-cli-optimizations.conf
    
    log_success "PHP optimizado para streaming"
}

# 4. Crear página de prueba
create_test_page() {
    log_info "Creando página de prueba..."
    
    cat > "$WEB_ROOT/info.php" << 'EOF'
<?php
// PLAYMI - Página de información del sistema

// Verificar que solo se acceda localmente
$allowed_ips = ['127.0.0.1', '192.168.4.1', '::1'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
    header('HTTP/1.0 403 Forbidden');
    exit('Acceso denegado');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>PLAYMI - Info del Sistema</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f0f0f0; }
        .container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #e50914; }
        .info-box { background: #f9f9f9; padding: 10px; margin: 10px 0; border-left: 4px solid #e50914; }
        .success { color: green; }
        .warning { color: orange; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>PLAYMI - Sistema de Información</h1>
        
        <div class="info-box">
            <h3>Información del Servidor</h3>
            <p><strong>Hostname:</strong> <?php echo gethostname(); ?></p>
            <p><strong>IP Local:</strong> <?php echo $_SERVER['SERVER_ADDR']; ?></p>
            <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
            <p><strong>Servidor:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
            <p><strong>Documento Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
        </div>
        
        <div class="info-box">
            <h3>Estado de Módulos PHP</h3>
            <?php
            $required_modules = ['curl', 'gd', 'mbstring', 'xml', 'zip', 'json'];
            foreach ($required_modules as $module) {
                $status = extension_loaded($module) ? 
                    '<span class="success">✓ Instalado</span>' : 
                    '<span class="error">✗ No instalado</span>';
                echo "<p><strong>$module:</strong> $status</p>";
            }
            ?>
        </div>
        
        <div class="info-box">
            <h3>Permisos de Directorios</h3>
            <?php
            $directories = [
                '/var/www/playmi/content' => 'Contenido',
                '/var/www/playmi/temp' => 'Temporal',
                '/var/www/playmi/cache' => 'Cache'
            ];
            
            foreach ($directories as $dir => $name) {
                $writable = is_writable($dir) ? 
                    '<span class="success">✓ Escritura OK</span>' : 
                    '<span class="error">✗ Sin permisos de escritura</span>';
                echo "<p><strong>$name ($dir):</strong> $writable</p>";
            }
            ?>
        </div>
        
        <div class="info-box">
            <h3>Información de Sistema</h3>
            <?php
            // Memoria
            $memory = round(memory_get_usage() / 1024 / 1024, 2);
            $memory_limit = ini_get('memory_limit');
            echo "<p><strong>Uso de Memoria:</strong> $memory MB / $memory_limit</p>";
            
            // Espacio en disco
            $free = round(disk_free_space('/') / 1024 / 1024 / 1024, 2);
            $total = round(disk_total_space('/') / 1024 / 1024 / 1024, 2);
            echo "<p><strong>Espacio en Disco:</strong> $free GB libres de $total GB</p>";
            
            // Load average
            $load = sys_getloadavg();
            echo "<p><strong>Carga del Sistema:</strong> " . implode(', ', $load) . "</p>";
            ?>
        </div>
        
        <div class="info-box">
            <p><a href="/index.php">Volver al Portal</a> | <a href="/phpinfo.php">PHP Info Completo</a></p>
        </div>
    </div>
</body>
</html>
EOF
    
    # Crear phpinfo (solo para debugging)
    echo "<?php if(in_array(\$_SERVER['REMOTE_ADDR'], ['127.0.0.1', '192.168.4.1', '::1'])) phpinfo(); else exit('Forbidden');" > "$WEB_ROOT/phpinfo.php"
    
    chown www-data:www-data "$WEB_ROOT"/{info.php,phpinfo.php}
    
    log_success "Páginas de prueba creadas"
}

# 5. Configurar logs rotación
configure_log_rotation() {
    log_info "Configurando rotación de logs..."
    
    cat > /etc/logrotate.d/playmi << 'EOF'
/var/log/playmi/*.log {
    daily
    missingok
    rotate 7
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        systemctl reload apache2 > /dev/null 2>&1 || true
    endscript
}

/var/log/apache2/playmi-*.log {
    daily
    missingok
    rotate 7
    compress
    delaycompress
    notifempty
    create 0640 root adm
    sharedscripts
    postrotate
        systemctl reload apache2 > /dev/null 2>&1 || true
    endscript
}
EOF
    
    log_success "Rotación de logs configurada"
}

# 6. Optimizaciones adicionales del sistema
system_optimizations() {
    log_info "Aplicando optimizaciones del sistema..."
    
    # Aumentar límites del sistema
    cat >> /etc/security/limits.conf << 'EOF'

# PLAYMI - Límites optimizados
www-data soft nofile 65535
www-data hard nofile 65535
www-data soft nproc 1024
www-data hard nproc 1024
EOF
    
    # Optimizaciones de kernel para red
    cat >> /etc/sysctl.conf << 'EOF'

# PLAYMI - Optimizaciones de red
net.core.somaxconn = 1024
net.ipv4.tcp_max_syn_backlog = 2048
net.ipv4.tcp_synack_retries = 2
net.ipv4.tcp_syn_retries = 2
net.ipv4.tcp_fin_timeout = 15
net.ipv4.tcp_keepalive_time = 300
net.ipv4.tcp_keepalive_probes = 5
net.ipv4.tcp_keepalive_intvl = 15
EOF
    
    # Aplicar cambios
    sysctl -p >> "$LOG_DIR/web-server-setup.log" 2>&1
    
    log_success "Optimizaciones del sistema aplicadas"
}

# 7. Crear script de monitoreo
create_monitoring_script() {
    log_info "Creando script de monitoreo web..."
    
    cat > /opt/playmi/scripts/web-monitor.sh << 'EOF'
#!/bin/bash
# PLAYMI - Monitor de servidor web

LOG_FILE="/var/log/playmi/web-monitor.log"
MAX_LOAD=4.0
MAX_MEMORY=80

check_apache() {
    if ! systemctl is-active --quiet apache2; then
        echo "[$(date)] ERROR: Apache no está activo, reiniciando..." >> "$LOG_FILE"
        systemctl restart apache2
    fi
}

check_system_resources() {
    # CPU Load
    load=$(uptime | awk -F'load average:' '{print $2}' | cut -d, -f1 | xargs)
    if (( $(echo "$load > $MAX_LOAD" | bc -l) )); then
        echo "[$(date)] WARNING: Carga alta del sistema: $load" >> "$LOG_FILE"
    fi
    
    # Memoria
    memory_used=$(free | grep Mem | awk '{print ($2-$7)/$2 * 100.0}')
    if (( $(echo "$memory_used > $MAX_MEMORY" | bc -l) )); then
        echo "[$(date)] WARNING: Uso alto de memoria: ${memory_used}%" >> "$LOG_FILE"
        # Limpiar cache si es necesario
        sync && echo 3 > /proc/sys/vm/drop_caches
    fi
}

check_disk_space() {
    disk_used=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
    if [ "$disk_used" -gt 90 ]; then
        echo "[$(date)] WARNING: Espacio en disco bajo: ${disk_used}%" >> "$LOG_FILE"
        # Limpiar logs antiguos
        find /var/log/playmi -name "*.log" -mtime +30 -delete
    fi
}

# Loop principal
while true; do
    check_apache
    check_system_resources
    check_disk_space
    sleep 300  # Cada 5 minutos
done
EOF
    
    chmod +x /opt/playmi/scripts/web-monitor.sh
    
    log_success "Script de monitoreo creado"
}

# 8. Reiniciar servicios
restart_services() {
    log_info "Reiniciando servicios web..."
    
    systemctl restart apache2 >> "$LOG_DIR/web-server-setup.log" 2>&1
    
    if systemctl is-active --quiet apache2; then
        log_success "Apache reiniciado correctamente"
    else
        log_error "Error al reiniciar Apache"
        return 1
    fi
}

# 9. Verificar instalación
verify_web_setup() {
    log_info "Verificando configuración del servidor web..."
    
    local errors=0
    
    # Verificar Apache
    if systemctl is-active --quiet apache2; then
        log_success "Apache activo"
    else
        log_error "Apache no está activo"
        ((errors++))
    fi
    
    # Verificar módulos PHP
    php -m | grep -q "curl" || { log_error "Módulo PHP curl no instalado"; ((errors++)); }
    php -m | grep -q "gd" || { log_error "Módulo PHP gd no instalado"; ((errors++)); }
    
    # Verificar acceso web
    if curl -s -o /dev/null -w "%{http_code}" http://localhost | grep -q "200\|301\|302"; then
        log_success "Servidor web respondiendo"
    else
        log_error "Servidor web no responde"
        ((errors++))
    fi
    
    # Mostrar resumen
    echo ""
    echo "========================================"
    echo "CONFIGURACIÓN DEL SERVIDOR WEB"
    echo "========================================"
    echo "Document Root: $WEB_ROOT"
    echo "PHP Version: $(php -v | head -n1)"
    echo "Memory Limit: $(php -r 'echo ini_get("memory_limit");')"
    echo "Max Upload: $(php -r 'echo ini_get("upload_max_filesize");')"
    echo "========================================"
    
    if [ $errors -eq 0 ]; then
        log_success "Servidor web configurado correctamente"
        echo ""
        echo "URLs de prueba:"
        echo "  http://192.168.4.1/info.php"
        echo "  http://playmi.local/info.php"
        return 0
    else
        log_error "Se encontraron $errors errores"
        return 1
    fi
}

# Función principal
main() {
    echo "========================================"
    echo "PLAYMI - Configuración Servidor Web"
    echo "========================================"
    
    # Verificar root
    if [ "$EUID" -ne 0 ]; then 
        log_error "Este script debe ejecutarse con sudo"
        exit 1
    fi
    
    # Crear directorio de logs
    mkdir -p "$LOG_DIR"/{apache,php,app}
    
    # Ejecutar configuración
    create_web_structure
    configure_apache
    configure_php
    create_test_page
    configure_log_rotation
    system_optimizations
    create_monitoring_script
    restart_services
    
    # Verificar
    verify_web_setup
}

# Ejecutar
main