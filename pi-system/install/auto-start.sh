#!/bin/bash
#================================================================
# PLAYMI - Configuración de Inicio Automático
# Módulo 6.4: pi-system/install/auto-start.sh
# Descripción: Configura servicios systemd para inicio automático
# Compatible con: Raspberry Pi 5 con systemd
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
SERVICE_DIR="/etc/systemd/system"
PLAYMI_DIR="/opt/playmi"
LOG_DIR="/var/log/playmi"
SCRIPTS_DIR="$PLAYMI_DIR/scripts"

# Logging
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] INFO: $1" >> "$LOG_DIR/autostart-setup.log"
}

log_success() {
    echo -e "${GREEN}[OK]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] SUCCESS: $1" >> "$LOG_DIR/autostart-setup.log"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1" >> "$LOG_DIR/autostart-setup.log"
}

# 1. Crear servicio principal de PLAYMI
create_playmi_service() {
    log_info "Creando servicio principal PLAYMI..."
    
    cat > "$SERVICE_DIR/playmi.service" << 'EOF'
[Unit]
Description=PLAYMI Entertainment System
Documentation=https://playmi.local/docs
After=network.target multi-user.target
Wants=playmi-wifi.service playmi-web.service playmi-monitor.service

[Service]
Type=oneshot
ExecStart=/opt/playmi/scripts/startup.sh
RemainAfterExit=yes
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

    log_success "Servicio principal creado"
}

# 2. Crear servicio para WiFi AP
create_wifi_service() {
    log_info "Creando servicio WiFi AP..."
    
    cat > "$SERVICE_DIR/playmi-wifi.service" << 'EOF'
[Unit]
Description=PLAYMI WiFi Access Point
After=network.target
Before=playmi.service

[Service]
Type=forking
ExecStartPre=/bin/sleep 10
ExecStart=/opt/playmi/scripts/wifi-start.sh
ExecStop=/opt/playmi/scripts/wifi-stop.sh
Restart=on-failure
RestartSec=30
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

    log_success "Servicio WiFi creado"
}

# 3. Crear servicio para servidor web
create_web_service() {
    log_info "Creando servicio Web optimizado..."
    
    cat > "$SERVICE_DIR/playmi-web.service" << 'EOF'
[Unit]
Description=PLAYMI Web Server Optimization
After=apache2.service
Requires=apache2.service

[Service]
Type=oneshot
ExecStart=/opt/playmi/scripts/web-optimize.sh
RemainAfterExit=yes
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

    log_success "Servicio Web creado"
}

# 4. Crear servicio de monitoreo
create_monitor_service() {
    log_info "Creando servicio de monitoreo..."
    
    cat > "$SERVICE_DIR/playmi-monitor.service" << 'EOF'
[Unit]
Description=PLAYMI System Monitor
After=playmi.service

[Service]
Type=simple
ExecStart=/opt/playmi/scripts/monitor.sh
Restart=always
RestartSec=60
StandardOutput=journal
StandardError=journal
CPUQuota=10%
MemoryLimit=128M

[Install]
WantedBy=multi-user.target
EOF

    log_success "Servicio de monitoreo creado"
}

# 5. Crear servicio de sincronización
create_sync_service() {
    log_info "Creando servicio de sincronización..."
    
    cat > "$SERVICE_DIR/playmi-sync.timer" << 'EOF'
[Unit]
Description=PLAYMI Sync Timer
Requires=playmi-sync.service

[Timer]
OnBootSec=30min
OnUnitActiveSec=1h
RandomizedDelaySec=15min
Persistent=true

[Install]
WantedBy=timers.target
EOF

    cat > "$SERVICE_DIR/playmi-sync.service" << 'EOF'
[Unit]
Description=PLAYMI Content Synchronization
After=network-online.target
Wants=network-online.target

[Service]
Type=oneshot
ExecStart=/opt/playmi/scripts/sync-content.sh
StandardOutput=journal
StandardError=journal
TimeoutStartSec=30min

[Install]
WantedBy=multi-user.target
EOF

    log_success "Servicio de sincronización creado"
}

# 6. Crear servicio de mantenimiento
create_maintenance_service() {
    log_info "Creando servicio de mantenimiento..."
    
    cat > "$SERVICE_DIR/playmi-maintenance.timer" << 'EOF'
[Unit]
Description=PLAYMI Daily Maintenance Timer

[Timer]
OnCalendar=daily
OnBootSec=2h
RandomizedDelaySec=30min
Persistent=true

[Install]
WantedBy=timers.target
EOF

    cat > "$SERVICE_DIR/playmi-maintenance.service" << 'EOF'
[Unit]
Description=PLAYMI System Maintenance
After=multi-user.target

[Service]
Type=oneshot
ExecStart=/opt/playmi/scripts/maintenance.sh
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

    log_success "Servicio de mantenimiento creado"
}

# 7. Crear scripts de apoyo
create_support_scripts() {
    log_info "Creando scripts de apoyo..."
    
    # Script de inicio principal
    cat > "$SCRIPTS_DIR/startup.sh" << 'EOF'
#!/bin/bash
# PLAYMI - Script de inicio principal

echo "[$(date)] PLAYMI iniciando..." >> /var/log/playmi/startup.log

# Verificar sistema
/opt/playmi/scripts/system-check.sh

# Configurar fecha si no hay RTC
if [ ! -e /dev/rtc0 ]; then
    echo "[$(date)] Sin RTC, configurando fecha desde archivo..." >> /var/log/playmi/startup.log
    if [ -f /opt/playmi/last-date ]; then
        date -s "$(cat /opt/playmi/last-date)"
    fi
fi

# Limpiar archivos temporales antiguos
find /var/www/playmi/temp -type f -mtime +1 -delete 2>/dev/null
find /var/www/playmi/cache -type f -mtime +7 -delete 2>/dev/null

echo "[$(date)] PLAYMI iniciado correctamente" >> /var/log/playmi/startup.log
EOF

    # Script de inicio WiFi
    cat > "$SCRIPTS_DIR/wifi-start.sh" << 'EOF'
#!/bin/bash
# PLAYMI - Iniciar WiFi AP

# Esperar a que el sistema esté listo
sleep 5

# Verificar interfaces
rfkill unblock wifi
ip link set wlan0 down 2>/dev/null
ip link set wlan1 down 2>/dev/null

# Iniciar servicios
systemctl restart hostapd
systemctl restart dnsmasq

# Verificar que iniciaron correctamente
sleep 3
if systemctl is-active --quiet hostapd && systemctl is-active --quiet dnsmasq; then
    echo "[$(date)] WiFi AP iniciado correctamente" >> /var/log/playmi/wifi.log
    exit 0
else
    echo "[$(date)] Error al iniciar WiFi AP" >> /var/log/playmi/wifi.log
    exit 1
fi
EOF

    # Script de parada WiFi
    cat > "$SCRIPTS_DIR/wifi-stop.sh" << 'EOF'
#!/bin/bash
# PLAYMI - Detener WiFi AP

systemctl stop hostapd
systemctl stop dnsmasq
ip link set wlan0 down 2>/dev/null
ip link set wlan1 down 2>/dev/null

echo "[$(date)] WiFi AP detenido" >> /var/log/playmi/wifi.log
EOF

    # Script de optimización web
    cat > "$SCRIPTS_DIR/web-optimize.sh" << 'EOF'
#!/bin/bash
# PLAYMI - Optimizaciones del servidor web al inicio

# Limpiar cache de PHP OPcache
if [ -f /usr/lib/php/*/fpm/pool.d/www.conf ]; then
    echo "opcache_reset();" | php
fi

# Pre-cargar contenido frecuente en memoria (si hay suficiente RAM)
available_memory=$(free -m | awk 'NR==2{print $7}')
if [ $available_memory -gt 1000 ]; then
    # Pre-cargar thumbnails
    find /var/www/playmi/content/*/thumbnails -name "*.jpg" -type f | head -100 | xargs -I {} cat {} > /dev/null 2>&1
fi

echo "[$(date)] Optimizaciones web aplicadas" >> /var/log/playmi/web-optimize.log
EOF

    # Script de verificación del sistema
    cat > "$SCRIPTS_DIR/system-check.sh" << 'EOF'
#!/bin/bash
# PLAYMI - Verificación del sistema al inicio

check_result() {
    if [ $1 -eq 0 ]; then
        echo "✓ $2"
    else
        echo "✗ $2"
    fi
}

echo "=== PLAYMI System Check - $(date) ===" >> /var/log/playmi/system-check.log

# Verificar servicios críticos
systemctl is-active --quiet apache2
check_result $? "Apache2"

systemctl is-active --quiet hostapd
check_result $? "Hostapd"

systemctl is-active --quiet dnsmasq
check_result $? "Dnsmasq"

# Verificar espacio en disco
disk_usage=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $disk_usage -lt 90 ]; then
    echo "✓ Espacio en disco: ${disk_usage}% usado"
else
    echo "✗ Espacio en disco crítico: ${disk_usage}% usado"
fi

# Verificar temperatura
if [ -f /sys/class/thermal/thermal_zone0/temp ]; then
    temp=$(cat /sys/class/thermal/thermal_zone0/temp)
    temp_c=$((temp/1000))
    if [ $temp_c -lt 70 ]; then
        echo "✓ Temperatura: ${temp_c}°C"
    else
        echo "✗ Temperatura alta: ${temp_c}°C"
    fi
fi
EOF

    # Script principal de monitoreo
    cat > "$SCRIPTS_DIR/monitor.sh" << 'EOF'
#!/bin/bash
# PLAYMI - Monitor principal del sistema

PLAYMI_DIR="/opt/playmi"
LOG_FILE="/var/log/playmi/monitor.log"

# Función para guardar fecha actual (para sistemas sin RTC)
save_current_date() {
    date '+%Y-%m-%d %H:%M:%S' > "$PLAYMI_DIR/last-date"
}

# Loop principal
while true; do
    # Guardar fecha actual
    save_current_date
    
    # Ejecutar monitores específicos
    $PLAYMI_DIR/scripts/wifi-monitor.sh &
    $PLAYMI_DIR/scripts/web-monitor.sh &
    
    # Verificar servicios cada 5 minutos
    sleep 300
    
    # Reiniciar servicios si es necesario
    if ! systemctl is-active --quiet apache2; then
        echo "[$(date)] Reiniciando Apache..." >> "$LOG_FILE"
        systemctl restart apache2
    fi
    
    if ! systemctl is-active --quiet hostapd; then
        echo "[$(date)] Reiniciando Hostapd..." >> "$LOG_FILE"
        systemctl restart hostapd
    fi
done
EOF

    # Script de mantenimiento
    cat > "$SCRIPTS_DIR/maintenance.sh" << 'EOF'
#!/bin/bash
# PLAYMI - Mantenimiento diario del sistema

LOG_DIR="/var/log/playmi"
echo "[$(date)] Iniciando mantenimiento diario..." >> "$LOG_DIR/maintenance.log"

# 1. Rotar logs manualmente si logrotate falla
find "$LOG_DIR" -name "*.log" -size +100M -exec mv {} {}.old \;

# 2. Limpiar archivos temporales antiguos
find /var/www/playmi/temp -type f -mtime +1 -delete
find /var/www/playmi/cache -type f -mtime +7 -delete
find /tmp -name "php*" -mtime +1 -delete

# 3. Verificar y reparar permisos
chown -R www-data:www-data /var/www/playmi
chmod -R 755 /var/www/playmi
chmod -R 777 /var/www/playmi/temp /var/www/playmi/cache

# 4. Limpiar memoria si es necesario
memory_used=$(free | grep Mem | awk '{print ($2-$7)/$2 * 100.0}')
if (( $(echo "$memory_used > 90" | bc -l) )); then
    sync && echo 1 > /proc/sys/vm/drop_caches
    echo "[$(date)] Cache de memoria limpiado" >> "$LOG_DIR/maintenance.log"
fi

# 5. Verificar integridad del contenido
if [ -f /opt/playmi/config/content-checksums.txt ]; then
    cd /var/www/playmi/content
    md5sum -c /opt/playmi/config/content-checksums.txt --quiet || \
        echo "[$(date)] ALERTA: Cambios detectados en archivos de contenido" >> "$LOG_DIR/maintenance.log"
fi

echo "[$(date)] Mantenimiento completado" >> "$LOG_DIR/maintenance.log"
EOF

    # Hacer todos los scripts ejecutables
    chmod +x "$SCRIPTS_DIR"/*.sh
    
    log_success "Scripts de apoyo creados"
}

# 8. Habilitar servicios
enable_services() {
    log_info "Habilitando servicios de inicio automático..."
    
    # Recargar systemd
    systemctl daemon-reload
    
    # Habilitar servicios principales
    systemctl enable playmi.service
    systemctl enable playmi-wifi.service  
    systemctl enable playmi-web.service
    systemctl enable playmi-monitor.service
    
    # Habilitar timers
    systemctl enable playmi-sync.timer
    systemctl enable playmi-maintenance.timer
    
    # Iniciar timers
    systemctl start playmi-sync.timer
    systemctl start playmi-maintenance.timer
    
    log_success "Servicios habilitados para inicio automático"
}

# 9. Configurar watchdog (opcional)
configure_watchdog() {
    log_info "Configurando watchdog del sistema..."
    
    # Verificar si existe módulo watchdog
    if [ -e /dev/watchdog ]; then
        cat > "$SERVICE_DIR/playmi-watchdog.service" << 'EOF'
[Unit]
Description=PLAYMI Hardware Watchdog
After=multi-user.target

[Service]
Type=simple
ExecStart=/opt/playmi/scripts/watchdog.sh
Restart=always

[Install]
WantedBy=multi-user.target
EOF

        cat > "$SCRIPTS_DIR/watchdog.sh" << 'EOF'
#!/bin/bash
# PLAYMI - Watchdog para reinicio automático en caso de fallo

while true; do
    # Verificar servicios críticos
    if systemctl is-active --quiet apache2 && \
       systemctl is-active --quiet hostapd && \
       systemctl is-active --quiet dnsmasq; then
        # Sistema OK - alimentar watchdog
        echo "1" > /dev/watchdog
    fi
    sleep 10
done
EOF
        
        chmod +x "$SCRIPTS_DIR/watchdog.sh"
        systemctl enable playmi-watchdog.service
        
        log_success "Watchdog configurado"
    else
        log_info "Watchdog hardware no detectado"
    fi
}

# 10. Verificar configuración
verify_autostart() {
    log_info "Verificando configuración de inicio automático..."
    
    local errors=0
    
    # Verificar servicios
    for service in playmi playmi-wifi playmi-web playmi-monitor; do
        if systemctl is-enabled --quiet $service.service; then
            log_success "$service.service habilitado"
        else
            log_error "$service.service no habilitado"
            ((errors++))
        fi
    done
    
    # Verificar timers
    for timer in playmi-sync playmi-maintenance; do
        if systemctl is-enabled --quiet $timer.timer; then
            log_success "$timer.timer habilitado"
        else
            log_error "$timer.timer no habilitado"
            ((errors++))
        fi
    done
    
    # Resumen
    echo ""
    echo "========================================"
    echo "CONFIGURACIÓN DE INICIO AUTOMÁTICO"
    echo "========================================"
    echo "Servicios principales: 4"
    echo "Timers programados: 2"
    echo "Scripts de soporte: $(ls -1 $SCRIPTS_DIR/*.sh 2>/dev/null | wc -l)"
    echo "========================================"
    
    if [ $errors -eq 0 ]; then
        log_success "Inicio automático configurado correctamente"
        echo ""
        echo "El sistema se iniciará automáticamente al encender el Pi"
        return 0
    else
        log_error "Se encontraron $errors errores"
        return 1
    fi
}

# Función principal
main() {
    echo "========================================"
    echo "PLAYMI - Configuración Inicio Automático"
    echo "========================================"
    
    # Verificar root
    if [ "$EUID" -ne 0 ]; then 
        log_error "Este script debe ejecutarse con sudo"
        exit 1
    fi
    
    # Crear directorios necesarios
    mkdir -p "$LOG_DIR"
    mkdir -p "$SCRIPTS_DIR"
    
    # Ejecutar configuración
    create_playmi_service
    create_wifi_service
    create_web_service
    create_monitor_service
    create_sync_service
    create_maintenance_service
    create_support_scripts
    enable_services
    configure_watchdog
    
    # Verificar
    verify_autostart
}

# Ejecutar
main