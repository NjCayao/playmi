#!/bin/bash
#================================================================
# PLAYMI - Script de Actualización del Sistema
# Módulo 6.6: pi-system/scripts/update-system.sh
# Descripción: Actualiza sistema operativo y software del Pi
# Nota: Solo actualizaciones críticas de seguridad
# Autor: Sistema PLAYMI
# Fecha: 2024
#================================================================

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m'

# Variables
LOG_FILE="/var/log/playmi/system-update.log"
BACKUP_DIR="/opt/playmi/backup/system"
CONFIG_DIR="/opt/playmi/config"
MAX_LOG_SIZE=104857600  # 100MB
UPDATE_LOCK="/var/run/playmi-update.lock"
REBOOT_REQUIRED=false

# Función de logging
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] INFO: $1" >> "$LOG_FILE"
}

log_success() {
    echo -e "${GREEN}[OK]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] SUCCESS: $1" >> "$LOG_FILE"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1" >> "$LOG_FILE"
}

log_warning() {
    echo -e "${YELLOW}[ADVERTENCIA]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1" >> "$LOG_FILE"
}

# 1. Verificar bloqueo
check_lock() {
    if [ -f "$UPDATE_LOCK" ]; then
        local pid=$(cat "$UPDATE_LOCK")
        if ps -p "$pid" > /dev/null 2>&1; then
            log_error "Actualización ya en proceso (PID: $pid)"
            exit 1
        else
            rm -f "$UPDATE_LOCK"
        fi
    fi
    echo $$ > "$UPDATE_LOCK"
}

cleanup() {
    rm -f "$UPDATE_LOCK"
}
trap cleanup EXIT

# 2. Verificar espacio disponible
check_disk_space() {
    log_info "Verificando espacio en disco..."
    
    local available_space=$(df / | awk 'NR==2 {print $4}')
    local required_space=1048576  # 1GB en KB
    
    if [ $available_space -lt $required_space ]; then
        log_error "Espacio insuficiente. Disponible: $(numfmt --to=iec-i --suffix=B $((available_space*1024))), Requerido: 1GB"
        
        # Intentar liberar espacio
        log_info "Intentando liberar espacio..."
        cleanup_system
        
        # Verificar nuevamente
        available_space=$(df / | awk 'NR==2 {print $4}')
        if [ $available_space -lt $required_space ]; then
            log_error "No se pudo liberar suficiente espacio"
            return 1
        fi
    fi
    
    log_success "Espacio suficiente: $(numfmt --to=iec-i --suffix=B $((available_space*1024))) disponibles"
    return 0
}

# 3. Crear backup del sistema crítico
create_system_backup() {
    log_info "Creando backup del sistema..."
    
    mkdir -p "$BACKUP_DIR"
    local backup_file="$BACKUP_DIR/backup-$(date +%Y%m%d-%H%M%S).tar.gz"
    
    # Lista de archivos críticos a respaldar
    local critical_files=(
        "/etc/apache2/sites-available"
        "/etc/hostapd/hostapd.conf"
        "/etc/dnsmasq.conf"
        "/etc/dhcpcd.conf"
        "/opt/playmi/config"
        "/var/www/playmi/portal/config.php"
    )
    
    # Crear backup
    tar -czf "$backup_file" "${critical_files[@]}" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        log_success "Backup creado: $backup_file"
        
        # Mantener solo los últimos 3 backups
        ls -t "$BACKUP_DIR"/backup-*.tar.gz | tail -n +4 | xargs -r rm
    else
        log_warning "No se pudo crear backup completo"
    fi
}

# 4. Actualizar lista de paquetes
update_package_list() {
    log_info "Actualizando lista de paquetes..."
    
    # Verificar conectividad
    if ! ping -c 1 -W 5 raspbian.raspberrypi.org > /dev/null 2>&1; then
        log_warning "Sin conexión a repositorios"
        return 1
    fi
    
    # Actualizar lista
    apt-get update >> "$LOG_FILE" 2>&1
    
    if [ $? -eq 0 ]; then
        log_success "Lista de paquetes actualizada"
        return 0
    else
        log_error "Error al actualizar lista de paquetes"
        return 1
    fi
}

# 5. Verificar actualizaciones disponibles
check_available_updates() {
    log_info "Verificando actualizaciones disponibles..."
    
    # Contar actualizaciones de seguridad
    local security_updates=$(apt-get -s upgrade | grep -c "^Inst.*security")
    local total_updates=$(apt-get -s upgrade | grep -c "^Inst")
    
    log_info "Actualizaciones disponibles: $total_updates (Seguridad: $security_updates)"
    
    # Listar actualizaciones críticas
    if [ $security_updates -gt 0 ]; then
        echo -e "${YELLOW}Actualizaciones de seguridad:${NC}"
        apt-get -s upgrade | grep "^Inst.*security" | awk '{print "  - " $2 " (" $3 " -> " $4 ")"}'
    fi
    
    return 0
}

# 6. Instalar solo actualizaciones de seguridad
install_security_updates() {
    log_info "Instalando actualizaciones de seguridad..."
    
    # Configurar para instalar solo actualizaciones de seguridad
    export DEBIAN_FRONTEND=noninteractive
    
    # Instalar actualizaciones de seguridad
    apt-get -y upgrade \
        -o Dpkg::Options::="--force-confdef" \
        -o Dpkg::Options::="--force-confold" \
        $(apt-get -s upgrade | grep "^Inst.*security" | awk '{print $2}') \
        >> "$LOG_FILE" 2>&1
    
    if [ $? -eq 0 ]; then
        log_success "Actualizaciones de seguridad instaladas"
        
        # Verificar si se requiere reinicio
        if [ -f /var/run/reboot-required ]; then
            REBOOT_REQUIRED=true
            log_warning "Se requiere reinicio del sistema"
        fi
    else
        log_error "Error al instalar actualizaciones"
        return 1
    fi
    
    return 0
}

# 7. Actualizar software PLAYMI si existe nueva versión
update_playmi_software() {
    log_info "Verificando actualizaciones de PLAYMI..."
    
    # Esta función normalmente se ejecutaría desde sync-content.sh
    # Aquí solo verificamos si hay actualizaciones pendientes
    
    if [ -f "/opt/playmi/updates/pending-update.flag" ]; then
        log_info "Actualización de PLAYMI pendiente"
        
        # Aplicar actualización si existe
        if [ -f "/opt/playmi/updates/apply-update.sh" ]; then
            bash /opt/playmi/updates/apply-update.sh
            rm -f /opt/playmi/updates/pending-update.flag
            log_success "Actualización de PLAYMI aplicada"
        fi
    else
        log_info "PLAYMI está actualizado"
    fi
}

# 8. Verificar y reparar sistema de archivos
check_filesystem() {
    log_info "Verificando integridad del sistema de archivos..."
    
    # Verificar disco principal
    local root_device=$(mount | grep " / " | cut -d' ' -f1)
    
    # Solo verificar, no reparar (requiere desmonte)
    if command -v fsck &> /dev/null; then
        # Programar verificación en próximo reinicio si hay errores
        touch /forcefsck 2>/dev/null
        log_info "Verificación del sistema de archivos programada para próximo reinicio"
    fi
    
    # Verificar y reparar permisos importantes
    log_info "Verificando permisos..."
    
    # Permisos críticos
    chmod 755 /opt/playmi
    chmod 755 /opt/playmi/scripts
    chmod +x /opt/playmi/scripts/*.sh
    chown -R www-data:www-data /var/www/playmi
    chmod -R 755 /var/www/playmi
    chmod -R 777 /var/www/playmi/temp /var/www/playmi/cache
    
    log_success "Permisos verificados"
}

# 9. Limpiar sistema
cleanup_system() {
    log_info "Limpiando sistema..."
    
    local space_before=$(df / | awk 'NR==2 {print $4}')
    
    # Limpiar logs antiguos
    log_info "Limpiando logs antiguos..."
    find /var/log -name "*.log" -type f -mtime +30 -delete 2>/dev/null
    find /var/log -name "*.gz" -type f -mtime +7 -delete 2>/dev/null
    find /var/log/playmi -name "*.log" -type f -size +100M -exec truncate -s 0 {} \;
    
    # Limpiar cache de paquetes
    log_info "Limpiando cache de paquetes..."
    apt-get clean >> "$LOG_FILE" 2>&1
    apt-get autoremove -y >> "$LOG_FILE" 2>&1
    
    # Limpiar archivos temporales
    log_info "Limpiando archivos temporales..."
    find /tmp -type f -atime +2 -delete 2>/dev/null
    find /var/tmp -type f -atime +2 -delete 2>/dev/null
    find /var/www/playmi/temp -type f -mtime +1 -delete 2>/dev/null
    find /var/www/playmi/cache -type f -mtime +7 -delete 2>/dev/null
    
    # Limpiar thumbnails antiguos
    find /var/www/playmi/content/*/thumbnails -name "*.jpg" -type f -atime +30 -size +1M -delete 2>/dev/null
    
    # Limpiar journald
    if command -v journalctl &> /dev/null; then
        journalctl --vacuum-time=7d >> "$LOG_FILE" 2>&1
    fi
    
    # Limpiar cache de memoria si es necesario
    sync
    echo 1 > /proc/sys/vm/drop_caches
    
    local space_after=$(df / | awk 'NR==2 {print $4}')
    local space_freed=$((space_after - space_before))
    
    if [ $space_freed -gt 0 ]; then
        log_success "Espacio liberado: $(numfmt --to=iec-i --suffix=B $((space_freed*1024)))"
    else
        log_info "No se liberó espacio adicional"
    fi
}

# 10. Optimizar base de datos local si existe
optimize_database() {
    log_info "Optimizando base de datos local..."
    
    # Si hay SQLite local
    if [ -f "/var/www/playmi/data/playmi.db" ]; then
        sqlite3 /var/www/playmi/data/playmi.db "VACUUM;" 2>/dev/null
        sqlite3 /var/www/playmi/data/playmi.db "ANALYZE;" 2>/dev/null
        log_success "Base de datos SQLite optimizada"
    fi
    
    # Limpiar logs de uso antiguos
    if [ -d "/var/www/playmi/data/logs" ]; then
        find /var/www/playmi/data/logs -name "*.json" -mtime +30 -delete
    fi
}

# 11. Verificar servicios después de actualización
verify_services() {
    log_info "Verificando servicios críticos..."
    
    local services=("apache2" "hostapd" "dnsmasq")
    local all_ok=true
    
    for service in "${services[@]}"; do
        if systemctl is-active --quiet "$service"; then
            log_success "$service activo"
        else
            log_error "$service no está activo"
            
            # Intentar reiniciar
            log_info "Intentando reiniciar $service..."
            systemctl restart "$service" >> "$LOG_FILE" 2>&1
            sleep 2
            
            if systemctl is-active --quiet "$service"; then
                log_success "$service reiniciado correctamente"
            else
                log_error "No se pudo reiniciar $service"
                all_ok=false
            fi
        fi
    done
    
    if [ "$all_ok" = true ]; then
        log_success "Todos los servicios funcionando correctamente"
        return 0
    else
        log_error "Algunos servicios presentan problemas"
        return 1
    fi
}

# 12. Generar reporte de actualización
generate_update_report() {
    local report_file="/opt/playmi/reports/update-$(date +%Y%m%d-%H%M%S).txt"
    mkdir -p /opt/playmi/reports
    
    cat > "$report_file" << EOF
========================================
PLAYMI - Reporte de Actualización
========================================
Fecha: $(date)
Hostname: $(hostname)

Resumen de Actualización:
-------------------------
$(grep "SUCCESS\|ERROR\|WARNING" "$LOG_FILE" | tail -20)

Estado de Servicios:
-------------------
Apache2: $(systemctl is-active apache2)
Hostapd: $(systemctl is-active hostapd)
Dnsmasq: $(systemctl is-active dnsmasq)

Uso de Recursos:
---------------
Disco: $(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')% usado
Memoria: $(free -m | awk 'NR==2 {printf "%.1f%%", ($2-$7)/$2*100}') usado
CPU Load: $(uptime | awk -F'load average:' '{print $2}')
Temperatura: $(cat /sys/class/thermal/thermal_zone0/temp 2>/dev/null | awk '{printf "%.1f°C", $1/1000}')

Reinicio Requerido: $REBOOT_REQUIRED
========================================
EOF
    
    log_info "Reporte guardado en: $report_file"
}

# 13. Función principal
main() {
    echo "========================================"
    echo "PLAYMI - Actualización del Sistema"
    echo "========================================"
    echo ""
    
    # Verificar root
    if [ "$EUID" -ne 0 ]; then 
        log_error "Este script debe ejecutarse con sudo"
        exit 1
    fi
    
    # Crear directorios necesarios
    mkdir -p /var/log/playmi
    mkdir -p "$BACKUP_DIR"
    
    # Verificar bloqueo
    check_lock
    
    # Verificar modo de ejecución
    local mode="${1:-check}"
    
    case "$mode" in
        "check")
            log_info "Modo: Verificación de actualizaciones"
            if update_package_list; then
                check_available_updates
            fi
            ;;
            
        "security")
            log_info "Modo: Instalar solo actualizaciones de seguridad"
            if check_disk_space; then
                create_system_backup
                if update_package_list; then
                    check_available_updates
                    install_security_updates
                    update_playmi_software
                    verify_services
                fi
            fi
            ;;
            
        "full")
            log_info "Modo: Actualización completa del sistema"
            if check_disk_space; then
                create_system_backup
                if update_package_list; then
                    check_available_updates
                    
                    # Actualizar todo
                    apt-get -y upgrade >> "$LOG_FILE" 2>&1
                    
                    update_playmi_software
                    check_filesystem
                    cleanup_system
                    optimize_database
                    verify_services
                fi
            fi
            ;;
            
        "cleanup")
            log_info "Modo: Solo limpieza del sistema"
            cleanup_system
            optimize_database
            ;;
            
        *)
            echo "Uso: $0 [check|security|full|cleanup]"
            echo ""
            echo "  check    - Solo verificar actualizaciones disponibles"
            echo "  security - Instalar solo actualizaciones de seguridad (recomendado)"
            echo "  full     - Actualización completa del sistema"
            echo "  cleanup  - Solo limpiar archivos temporales y logs"
            exit 1
            ;;
    esac
    
    # Generar reporte
    generate_update_report
    
    # Mostrar resumen
    echo ""
    echo "========================================"
    echo "Resumen de Actualización:"
    echo "========================================"
    
    if [ "$REBOOT_REQUIRED" = true ]; then
        echo -e "${YELLOW}⚠ Se requiere reiniciar el sistema${NC}"
        echo "  Use: sudo reboot"
    else
        echo -e "${GREEN}✓ No se requiere reinicio${NC}"
    fi
    
    echo ""
    echo "Ver log completo en: $LOG_FILE"
    echo "Ver reporte en: /opt/playmi/reports/"
}

# Ejecutar
main "$@"