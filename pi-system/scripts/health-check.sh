#!/bin/bash
#================================================================
# PLAYMI - Script de Monitoreo de Salud del Sistema
# Módulo 6.7: pi-system/scripts/health-check.sh
# Descripción: Monitoreo continuo optimizado para ambiente vehicular
# Compatible con: Raspberry Pi 5 en condiciones de vibración y temperatura
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

# Variables de configuración
LOG_FILE="/var/log/playmi/health-check.log"
ALERT_LOG="/var/log/playmi/alerts.log"
STATUS_FILE="/opt/playmi/status/health-status.json"
CONFIG_FILE="/opt/playmi/config/company-config.json"

# Umbrales de alerta
MAX_TEMP_WARNING=70      # °C - Advertencia
MAX_TEMP_CRITICAL=80     # °C - Crítico
MAX_CPU_USAGE=85         # % - Uso de CPU
MAX_MEMORY_USAGE=85      # % - Uso de memoria
MIN_DISK_FREE=10         # % - Espacio libre mínimo
MAX_LOAD_AVG=4.0         # Load average máximo
MIN_VOLTAGE=4.7          # V - Voltaje mínimo (problema de alimentación)

# Contadores de errores
ERROR_COUNT=0
WARNING_COUNT=0
LAST_ALERT_TIME=0

# Estado de componentes
declare -A COMPONENT_STATUS
declare -A COMPONENT_ERRORS

# Función de logging
log_info() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] INFO: $1" >> "$LOG_FILE"
}

log_success() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] OK: $1" >> "$LOG_FILE"
}

log_warning() {
    echo -e "${YELLOW}[ADVERTENCIA]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1" >> "$LOG_FILE"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1" >> "$ALERT_LOG"
    ((WARNING_COUNT++))
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1" >> "$LOG_FILE"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1" >> "$ALERT_LOG"
    ((ERROR_COUNT++))
}

log_critical() {
    echo -e "${RED}[CRÍTICO]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] CRITICAL: $1" >> "$LOG_FILE"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] CRITICAL: $1" >> "$ALERT_LOG"
    ((ERROR_COUNT+=5))
}

# 1. Verificar temperatura del CPU
check_cpu_temperature() {
    local component="CPU_TEMP"
    
    if [ -f /sys/class/thermal/thermal_zone0/temp ]; then
        local temp=$(cat /sys/class/thermal/thermal_zone0/temp)
        local temp_c=$((temp/1000))
        
        COMPONENT_STATUS[$component]=$temp_c
        
        if [ $temp_c -gt $MAX_TEMP_CRITICAL ]; then
            log_critical "Temperatura CPU crítica: ${temp_c}°C"
            COMPONENT_ERRORS[$component]="CRITICAL"
            
            # Acciones de emergencia
            apply_thermal_throttling "critical"
            return 2
            
        elif [ $temp_c -gt $MAX_TEMP_WARNING ]; then
            log_warning "Temperatura CPU alta: ${temp_c}°C"
            COMPONENT_ERRORS[$component]="WARNING"
            
            # Throttling preventivo
            apply_thermal_throttling "warning"
            return 1
            
        else
            COMPONENT_ERRORS[$component]="OK"
            return 0
        fi
    else
        log_error "No se puede leer temperatura del CPU"
        COMPONENT_ERRORS[$component]="ERROR"
        return 1
    fi
}

# 2. Aplicar throttling térmico
apply_thermal_throttling() {
    local level=$1
    
    case "$level" in
        "critical")
            # Reducir frecuencia CPU al mínimo
            echo "powersave" > /sys/devices/system/cpu/cpu0/cpufreq/scaling_governor 2>/dev/null
            
            # Reducir calidad de streaming
            touch /var/www/playmi/THERMAL_THROTTLE_HIGH
            
            # Notificar al portal
            echo '{"throttle_level":"high","reason":"temperature"}' > /var/www/playmi/status/throttle.json
            ;;
            
        "warning")
            # Reducir frecuencia moderadamente
            echo "conservative" > /sys/devices/system/cpu/cpu0/cpufreq/scaling_governor 2>/dev/null
            
            # Reducir calidad moderadamente
            touch /var/www/playmi/THERMAL_THROTTLE_MEDIUM
            rm -f /var/www/playmi/THERMAL_THROTTLE_HIGH
            
            echo '{"throttle_level":"medium","reason":"temperature"}' > /var/www/playmi/status/throttle.json
            ;;
            
        *)
            # Restaurar performance normal
            echo "ondemand" > /sys/devices/system/cpu/cpu0/cpufreq/scaling_governor 2>/dev/null
            
            rm -f /var/www/playmi/THERMAL_THROTTLE_*
            echo '{"throttle_level":"none","reason":"normal"}' > /var/www/playmi/status/throttle.json
            ;;
    esac
}

# 3. Verificar voltaje (detectar problemas de alimentación)
check_power_supply() {
    local component="POWER"
    
    # Verificar voltaje si está disponible
    if command -v vcgencmd &> /dev/null; then
        local voltage=$(vcgencmd measure_volts core | grep -oP '[0-9.]+')
        
        COMPONENT_STATUS[$component]=$voltage
        
        if (( $(echo "$voltage < $MIN_VOLTAGE" | bc -l) )); then
            log_warning "Voltaje bajo detectado: ${voltage}V (mínimo: ${MIN_VOLTAGE}V)"
            log_warning "Posible problema con la alimentación del vehículo"
            COMPONENT_ERRORS[$component]="WARNING"
            return 1
        else
            COMPONENT_ERRORS[$component]="OK"
            return 0
        fi
    fi
    
    # Verificar throttling por voltaje
    if command -v vcgencmd &> /dev/null; then
        local throttled=$(vcgencmd get_throttled | cut -d'=' -f2)
        
        if [ "$throttled" != "0x0" ]; then
            # Decodificar flags de throttling
            if [ $((throttled & 0x1)) -ne 0 ]; then
                log_warning "Bajo voltaje detectado actualmente"
            fi
            if [ $((throttled & 0x2)) -ne 0 ]; then
                log_warning "Frecuencia CPU limitada por voltaje"
            fi
            if [ $((throttled & 0x4)) -ne 0 ]; then
                log_critical "Throttling activo por temperatura"
            fi
            
            COMPONENT_ERRORS[$component]="WARNING"
            return 1
        fi
    fi
    
    COMPONENT_ERRORS[$component]="OK"
    return 0
}

# 4. Verificar WiFi Access Point
check_wifi_ap() {
    local component="WIFI_AP"
    local wifi_ok=true
    
    # Verificar hostapd
    if ! systemctl is-active --quiet hostapd; then
        log_error "hostapd no está activo"
        wifi_ok=false
        
        # Intentar reiniciar
        log_info "Reiniciando hostapd..."
        systemctl restart hostapd
        sleep 3
        
        if systemctl is-active --quiet hostapd; then
            log_success "hostapd reiniciado correctamente"
        else
            log_critical "No se pudo reiniciar hostapd"
            COMPONENT_ERRORS[$component]="CRITICAL"
            return 2
        fi
    fi
    
    # Verificar dnsmasq
    if ! systemctl is-active --quiet dnsmasq; then
        log_error "dnsmasq no está activo"
        wifi_ok=false
        
        systemctl restart dnsmasq
        sleep 2
        
        if ! systemctl is-active --quiet dnsmasq; then
            COMPONENT_ERRORS[$component]="CRITICAL"
            return 2
        fi
    fi
    
    # Contar usuarios conectados
    local total_users=0
    for interface in $(ls /sys/class/net/ | grep wlan); do
        local users=$(iw dev "$interface" station dump 2>/dev/null | grep -c "Station" || echo 0)
        total_users=$((total_users + users))
    done
    
    COMPONENT_STATUS[$component]=$total_users
    
    if [ "$wifi_ok" = true ]; then
        COMPONENT_ERRORS[$component]="OK"
        return 0
    else
        COMPONENT_ERRORS[$component]="WARNING"
        return 1
    fi
}

# 5. Verificar servidor web
check_web_server() {
    local component="WEB_SERVER"
    
    # Verificar Apache
    if ! systemctl is-active --quiet apache2; then
        log_error "Apache2 no está activo"
        
        # Intentar reiniciar
        systemctl restart apache2
        sleep 3
        
        if ! systemctl is-active --quiet apache2; then
            COMPONENT_ERRORS[$component]="CRITICAL"
            return 2
        fi
    fi
    
    # Verificar respuesta del portal
    local response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost --connect-timeout 5)
    
    if [ "$response" = "200" ] || [ "$response" = "301" ] || [ "$response" = "302" ]; then
        COMPONENT_ERRORS[$component]="OK"
        return 0
    else
        log_error "Portal web no responde correctamente (HTTP $response)"
        COMPONENT_ERRORS[$component]="ERROR"
        return 1
    fi
}

# 6. Verificar uso de recursos
check_system_resources() {
    local component="RESOURCES"
    local issues=0
    
    # CPU Usage
    local cpu_usage=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
    COMPONENT_STATUS["CPU_USAGE"]=${cpu_usage%.*}
    
    if (( $(echo "$cpu_usage > $MAX_CPU_USAGE" | bc -l) )); then
        log_warning "Uso alto de CPU: ${cpu_usage}%"
        ((issues++))
    fi
    
    # Memory Usage
    local memory_used=$(free | grep Mem | awk '{printf "%.1f", ($2-$7)/$2 * 100}')
    COMPONENT_STATUS["MEMORY_USAGE"]=$memory_used
    
    if (( $(echo "$memory_used > $MAX_MEMORY_USAGE" | bc -l) )); then
        log_warning "Uso alto de memoria: ${memory_used}%"
        
        # Limpiar cache si es crítico
        if (( $(echo "$memory_used > 95" | bc -l) )); then
            sync && echo 1 > /proc/sys/vm/drop_caches
            log_info "Cache de memoria limpiado"
        fi
        ((issues++))
    fi
    
    # Disk Usage
    local disk_used=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
    local disk_free=$((100 - disk_used))
    COMPONENT_STATUS["DISK_FREE"]=$disk_free
    
    if [ $disk_free -lt $MIN_DISK_FREE ]; then
        log_warning "Espacio en disco bajo: ${disk_free}% libre"
        
        # Limpiar automáticamente si es crítico
        if [ $disk_free -lt 5 ]; then
            emergency_cleanup
        fi
        ((issues++))
    fi
    
    # Load Average
    local load_avg=$(uptime | awk -F'load average:' '{print $2}' | cut -d, -f1 | xargs)
    COMPONENT_STATUS["LOAD_AVG"]=$load_avg
    
    if (( $(echo "$load_avg > $MAX_LOAD_AVG" | bc -l) )); then
        log_warning "Carga del sistema alta: $load_avg"
        ((issues++))
    fi
    
    if [ $issues -eq 0 ]; then
        COMPONENT_ERRORS[$component]="OK"
        return 0
    else
        COMPONENT_ERRORS[$component]="WARNING"
        return 1
    fi
}

# 7. Limpieza de emergencia
emergency_cleanup() {
    log_warning "Ejecutando limpieza de emergencia..."
    
    # Eliminar logs antiguos
    find /var/log -name "*.log" -type f -size +50M -exec truncate -s 0 {} \;
    find /var/log/playmi -name "*.log" -type f -mtime +1 -delete
    
    # Eliminar archivos temporales
    find /tmp -type f -mtime +1 -delete 2>/dev/null
    find /var/www/playmi/temp -type f -delete 2>/dev/null
    find /var/www/playmi/cache -type f -mtime +1 -delete 2>/dev/null
    
    # Comprimir logs antiguos
    find /var/log -name "*.log.*" -type f ! -name "*.gz" -exec gzip {} \;
    
    log_info "Limpieza de emergencia completada"
}

# 8. Verificar conectividad de red
check_network_connectivity() {
    local component="NETWORK"
    
    # Verificar interfaz de red principal
    if ip link show eth0 &> /dev/null && ip link show eth0 | grep -q "state UP"; then
        COMPONENT_STATUS["ETH0"]="UP"
    else
        COMPONENT_STATUS["ETH0"]="DOWN"
    fi
    
    # Verificar conectividad a internet (si está configurado)
    if ping -c 1 -W 2 8.8.8.8 > /dev/null 2>&1; then
        COMPONENT_STATUS["INTERNET"]="OK"
        COMPONENT_ERRORS[$component]="OK"
        return 0
    else
        COMPONENT_STATUS["INTERNET"]="OFFLINE"
        # No es crítico estar offline
        COMPONENT_ERRORS[$component]="INFO"
        return 0
    fi
}

# 9. Verificar integridad del contenido
check_content_integrity() {
    local component="CONTENT"
    
    # Verificar que exista contenido
    local movie_count=$(find /var/www/playmi/content/movies -name "*.mp4" 2>/dev/null | wc -l)
    local music_count=$(find /var/www/playmi/content/music -name "*.mp3" 2>/dev/null | wc -l)
    local game_count=$(find /var/www/playmi/content/games -type d -name "game_*" 2>/dev/null | wc -l)
    
    COMPONENT_STATUS["MOVIES"]=$movie_count
    COMPONENT_STATUS["MUSIC"]=$music_count
    COMPONENT_STATUS["GAMES"]=$game_count
    
    local total_content=$((movie_count + music_count + game_count))
    
    if [ $total_content -eq 0 ]; then
        log_error "No se encontró contenido multimedia"
        COMPONENT_ERRORS[$component]="ERROR"
        return 1
    else
        COMPONENT_ERRORS[$component]="OK"
        return 0
    fi
}

# 10. Verificar licencia y fecha
check_license_status() {
    local component="LICENSE"
    
    # Verificar archivo de licencia expirada
    if [ -f /var/www/playmi/LICENSE_EXPIRED ]; then
        log_error "Licencia expirada - Portal deshabilitado"
        COMPONENT_ERRORS[$component]="CRITICAL"
        return 2
    fi
    
    # Verificar fecha del sistema (importante si no hay RTC)
    local current_year=$(date +%Y)
    if [ $current_year -lt 2024 ]; then
        log_warning "Fecha del sistema incorrecta: $(date)"
        
        # Intentar restaurar fecha desde archivo
        if [ -f /opt/playmi/last-date ]; then
            date -s "$(cat /opt/playmi/last-date)"
            log_info "Fecha restaurada desde archivo"
        fi
        
        COMPONENT_ERRORS[$component]="WARNING"
        return 1
    fi
    
    COMPONENT_ERRORS[$component]="OK"
    return 0
}

# 11. Generar reporte de estado JSON
generate_status_report() {
    local overall_status="OK"
    local critical_count=0
    local warning_count=0
    
    # Contar errores por tipo
    for component in "${!COMPONENT_ERRORS[@]}"; do
        case "${COMPONENT_ERRORS[$component]}" in
            "CRITICAL")
                ((critical_count++))
                overall_status="CRITICAL"
                ;;
            "ERROR")
                ((warning_count++))
                if [ "$overall_status" != "CRITICAL" ]; then
                    overall_status="ERROR"
                fi
                ;;
            "WARNING")
                ((warning_count++))
                if [ "$overall_status" = "OK" ]; then
                    overall_status="WARNING"
                fi
                ;;
        esac
    done
    
    # Crear directorio si no existe
    mkdir -p $(dirname "$STATUS_FILE")
    
    # Generar JSON
    cat > "$STATUS_FILE" << EOF
{
    "timestamp": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
    "hostname": "$(hostname)",
    "overall_status": "$overall_status",
    "critical_issues": $critical_count,
    "warnings": $warning_count,
    "uptime": "$(uptime -p)",
    "components": {
        "cpu_temperature": ${COMPONENT_STATUS[CPU_TEMP]:-0},
        "cpu_usage": ${COMPONENT_STATUS[CPU_USAGE]:-0},
        "memory_usage": ${COMPONENT_STATUS[MEMORY_USAGE]:-0},
        "disk_free": ${COMPONENT_STATUS[DISK_FREE]:-0},
        "load_average": ${COMPONENT_STATUS[LOAD_AVG]:-0},
        "wifi_users": ${COMPONENT_STATUS[WIFI_AP]:-0},
        "content_movies": ${COMPONENT_STATUS[MOVIES]:-0},
        "content_music": ${COMPONENT_STATUS[MUSIC]:-0},
        "content_games": ${COMPONENT_STATUS[GAMES]:-0}
    },
    "component_status": {
EOF
    
    # Agregar estado de cada componente
    local first=true
    for component in "${!COMPONENT_ERRORS[@]}"; do
        if [ "$first" = true ]; then
            first=false
        else
            echo "," >> "$STATUS_FILE"
        fi
        echo -n "        \"$component\": \"${COMPONENT_ERRORS[$component]}\"" >> "$STATUS_FILE"
    done
    
    cat >> "$STATUS_FILE" << EOF

    }
}
EOF
    
    # Hacer accesible para el portal web
    chmod 644 "$STATUS_FILE"
}

# 12. Ejecutar acción correctiva si es necesario
take_corrective_action() {
    local critical_count=0
    
    # Contar componentes críticos
    for component in "${!COMPONENT_ERRORS[@]}"; do
        if [ "${COMPONENT_ERRORS[$component]}" = "CRITICAL" ]; then
            ((critical_count++))
        fi
    done
    
    # Si hay múltiples componentes críticos, considerar reinicio
    if [ $critical_count -gt 2 ]; then
        log_critical "Múltiples componentes críticos ($critical_count) - Considerando reinicio"
        
        # Guardar estado antes de reiniciar
        cp "$STATUS_FILE" "$STATUS_FILE.pre-reboot"
        
        # Notificar al sistema
        echo "CRITICAL_STATE" > /opt/playmi/status/system-state
        
        # En producción, aquí se podría programar un reinicio
        # shutdown -r +5 "Reinicio programado por estado crítico del sistema"
    fi
}

# 13. Función principal de chequeo
perform_health_check() {
    # Reset contadores
    ERROR_COUNT=0
    WARNING_COUNT=0
    
    # Ejecutar todas las verificaciones
    check_cpu_temperature
    check_power_supply
    check_wifi_ap
    check_web_server
    check_system_resources
   check_network_connectivity
   check_content_integrity
   check_license_status
   
   # Generar reporte
   generate_status_report
   
   # Tomar acciones si es necesario
   take_corrective_action
   
   # Log resumen
   local total_issues=$((ERROR_COUNT + WARNING_COUNT))
   if [ $total_issues -eq 0 ]; then
       log_info "Health check completado - Sistema OK"
   else
       log_info "Health check completado - $ERROR_COUNT errores, $WARNING_COUNT advertencias"
   fi
}

# 14. Función principal
main() {
   # Crear directorios necesarios
   mkdir -p /var/log/playmi
   mkdir -p /opt/playmi/status
   
   # Rotar log si es muy grande
   if [ -f "$LOG_FILE" ] && [ $(stat -c%s "$LOG_FILE") -gt 10485760 ]; then
       mv "$LOG_FILE" "$LOG_FILE.old"
       gzip "$LOG_FILE.old"
   fi
   
   # Modo de ejecución
   case "${1:-single}" in
       "single")
           # Ejecución única
           perform_health_check
           ;;
           
       "monitor")
           # Monitoreo continuo
           log_info "Iniciando monitoreo continuo de salud del sistema"
           
           while true; do
               perform_health_check
               
               # Esperar 5 minutos o menos si hay problemas
               if [ $ERROR_COUNT -gt 0 ]; then
                   sleep 60  # 1 minuto si hay errores
               else
                   sleep 300  # 5 minutos normal
               fi
           done
           ;;
           
       "status")
           # Solo mostrar estado actual
           if [ -f "$STATUS_FILE" ]; then
               cat "$STATUS_FILE" | jq '.'
           else
               echo "No hay información de estado disponible"
               exit 1
           fi
           ;;
           
       *)
           echo "Uso: $0 [single|monitor|status]"
           echo ""
           echo "  single  - Ejecutar verificación única"
           echo "  monitor - Monitoreo continuo (daemon)"
           echo "  status  - Mostrar último estado conocido"
           exit 1
           ;;
   esac
}

# Ejecutar
main "$@"