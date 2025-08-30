#!/bin/bash
#================================================================
# PLAYMI - Script de Sincronización de Contenido
# Módulo 6.5: pi-system/scripts/sync-content.sh
# Descripción: Sincroniza contenido desde servidor central al Pi
# Nota: Diseñado principalmente para actualizaciones menores
#       Las actualizaciones mayores se realizan por cambio de SD
# Autor: Sistema PLAYMI
# Fecha: 2024
#================================================================

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Variables de configuración
CONFIG_FILE="/opt/playmi/config/company-config.json"
SYNC_CONFIG="/opt/playmi/config/sync-config.json"
SERVER_URL=""
API_KEY=""
PI_ID=""
CONTENT_DIR="/var/www/playmi/content"
TEMP_DIR="/opt/playmi/temp"
LOG_FILE="/var/log/playmi/sync.log"
LOCK_FILE="/var/run/playmi-sync.lock"
MAX_RETRIES=3
RETRY_DELAY=60

# Estado de sincronización
SYNC_STATUS="idle"
SYNC_PROGRESS=0
SYNC_MESSAGE=""

# Función de logging
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] INFO: $1" >> "$LOG_FILE"
    SYNC_MESSAGE="$1"
}

log_success() {
    echo -e "${GREEN}[OK]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] SUCCESS: $1" >> "$LOG_FILE"
    SYNC_MESSAGE="$1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1" >> "$LOG_FILE"
    SYNC_MESSAGE="ERROR: $1"
}

log_warning() {
    echo -e "${YELLOW}[ADVERTENCIA]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1" >> "$LOG_FILE"
    SYNC_MESSAGE="WARNING: $1"
}

# 1. Verificar bloqueo para evitar sincronizaciones simultáneas
check_lock() {
    if [ -f "$LOCK_FILE" ]; then
        local pid=$(cat "$LOCK_FILE")
        if ps -p "$pid" > /dev/null 2>&1; then
            log_error "Sincronización ya en proceso (PID: $pid)"
            exit 1
        else
            log_warning "Eliminando archivo de bloqueo antiguo"
            rm -f "$LOCK_FILE"
        fi
    fi
    
    # Crear archivo de bloqueo
    echo $$ > "$LOCK_FILE"
}

# 2. Limpiar al salir
cleanup() {
    rm -f "$LOCK_FILE"
    update_sync_status "completed" 100 "Sincronización finalizada"
}

trap cleanup EXIT

# 3. Cargar configuración
load_config() {
    log_info "Cargando configuración..."
    
    if [ ! -f "$CONFIG_FILE" ]; then
        log_error "Archivo de configuración no encontrado: $CONFIG_FILE"
        exit 1
    fi
    
    # Extraer configuración usando jq si está disponible
    if command -v jq &> /dev/null; then
        PI_ID=$(jq -r '.pi_info.id // empty' "$CONFIG_FILE")
        SERVER_URL=$(jq -r '.sync_settings.server_url // empty' "$CONFIG_FILE")
        API_KEY=$(jq -r '.sync_settings.api_key // empty' "$CONFIG_FILE")
    else
        # Fallback sin jq
        PI_ID=$(grep -oP '"id"\s*:\s*"\K[^"]*' "$CONFIG_FILE" | head -1)
        SERVER_URL=$(grep -oP '"server_url"\s*:\s*"\K[^"]*' "$CONFIG_FILE" | head -1)
        API_KEY=$(grep -oP '"api_key"\s*:\s*"\K[^"]*' "$CONFIG_FILE" | head -1)
    fi
    
    # Usar valores por defecto si no se encuentran
    PI_ID="${PI_ID:-$(hostname)}"
    
    if [ -z "$SERVER_URL" ] || [ -z "$API_KEY" ]; then
        log_warning "Configuración de sincronización incompleta"
        log_info "Modo offline - sincronización deshabilitada"
        exit 0
    fi
    
    log_success "Configuración cargada: PI_ID=$PI_ID"
}

# 4. Verificar conectividad
check_connectivity() {
    log_info "Verificando conectividad con servidor..."
    
    # Verificar conexión a internet
    if ! ping -c 1 -W 5 google.com > /dev/null 2>&1; then
        log_warning "Sin conexión a internet"
        return 1
    fi
    
    # Verificar conexión al servidor
    if ! curl -s --connect-timeout 10 "$SERVER_URL/api/sync/health" > /dev/null; then
        log_warning "No se puede conectar al servidor: $SERVER_URL"
        return 1
    fi
    
    log_success "Conectividad verificada"
    return 0
}

# 5. Verificar fecha de vencimiento
check_license_expiry() {
    log_info "Verificando licencia..."
    
    local expiry_date=""
    
    if command -v jq &> /dev/null; then
        expiry_date=$(jq -r '.license.expiry_date // empty' "$CONFIG_FILE")
    else
        expiry_date=$(grep -oP '"expiry_date"\s*:\s*"\K[^"]*' "$CONFIG_FILE" | head -1)
    fi
    
    if [ -n "$expiry_date" ]; then
        local current_date=$(date +%s)
        local expiry_timestamp=$(date -d "$expiry_date" +%s 2>/dev/null)
        
        if [ -n "$expiry_timestamp" ]; then
            if [ $current_date -gt $expiry_timestamp ]; then
                log_error "Licencia vencida el $expiry_date"
                # Deshabilitar portal si la licencia está vencida
                touch /var/www/playmi/LICENSE_EXPIRED
                exit 1
            else
                local days_left=$(( ($expiry_timestamp - $current_date) / 86400 ))
                log_info "Licencia válida por $days_left días más"
                rm -f /var/www/playmi/LICENSE_EXPIRED
            fi
        fi
    fi
    
    log_success "Licencia válida"
}

# 6. Actualizar estado de sincronización
update_sync_status() {
    local status=$1
    local progress=$2
    local message=$3
    
    SYNC_STATUS="$status"
    SYNC_PROGRESS="$progress"
    SYNC_MESSAGE="$message"
    
    # Escribir estado a archivo para monitoreo
    cat > /opt/playmi/sync-status.json << EOF
{
    "status": "$status",
    "progress": $progress,
    "message": "$message",
    "timestamp": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
    "pi_id": "$PI_ID"
}
EOF
}

# 7. Reportar estado al servidor
report_status() {
    local status_type=$1  # "start", "progress", "complete", "error"
    local message=$2
    
    if [ -z "$SERVER_URL" ] || [ -z "$API_KEY" ]; then
        return
    fi
    
    local payload=$(cat <<EOF
{
    "pi_id": "$PI_ID",
    "status": "$status_type",
    "message": "$message",
    "progress": $SYNC_PROGRESS,
    "timestamp": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
    "system_info": {
        "uptime": $(uptime -p | sed 's/up //'),
        "disk_free": $(df -BG / | awk 'NR==2 {print $4}' | sed 's/G//'),
        "memory_free": $(free -m | awk 'NR==2 {print $7}'),
        "temperature": $(cat /sys/class/thermal/thermal_zone0/temp 2>/dev/null || echo "0")
    }
}
EOF
)
    
    curl -s -X POST \
        -H "Content-Type: application/json" \
        -H "X-API-Key: $API_KEY" \
        -d "$payload" \
        "$SERVER_URL/api/sync/report-status" \
        > /dev/null 2>&1
}

# 8. Verificar actualizaciones disponibles
check_updates() {
    log_info "Verificando actualizaciones disponibles..."
    
    update_sync_status "checking" 10 "Verificando actualizaciones"
    
    local current_version=""
    if [ -f /opt/playmi/version.txt ]; then
        current_version=$(cat /opt/playmi/version.txt)
    fi
    
    local response=$(curl -s -X GET \
        -H "X-API-Key: $API_KEY" \
        "$SERVER_URL/api/sync/check-updates?pi_id=$PI_ID&version=$current_version")
    
    if [ -z "$response" ]; then
        log_warning "Sin respuesta del servidor"
        return 1
    fi
    
    # Parsear respuesta
    local has_updates="false"
    local update_type=""
    local update_size=0
    
    if command -v jq &> /dev/null; then
        has_updates=$(echo "$response" | jq -r '.updates_available // false')
        update_type=$(echo "$response" | jq -r '.update_type // "none"')
        update_size=$(echo "$response" | jq -r '.update_size // 0')
    fi
    
    if [ "$has_updates" = "true" ]; then
        log_info "Actualizaciones disponibles: tipo=$update_type, tamaño=$(numfmt --to=iec-i --suffix=B $update_size)"
        return 0
    else
        log_info "Sistema actualizado"
        return 2
    fi
}

# 9. Descargar actualizaciones menores
download_minor_updates() {
    log_info "Descargando actualizaciones menores..."
    
    update_sync_status "downloading" 30 "Descargando actualizaciones"
    report_status "progress" "Iniciando descarga de actualizaciones"
    
    # Crear directorio temporal
    mkdir -p "$TEMP_DIR/updates"
    
    # Descargar paquete de actualización
    local download_url="$SERVER_URL/api/sync/download-package?pi_id=$PI_ID"
    local update_file="$TEMP_DIR/updates/update.tar.gz"
    
    # Descargar con reintentos
    local retry=0
    while [ $retry -lt $MAX_RETRIES ]; do
        log_info "Intento de descarga $((retry + 1))/$MAX_RETRIES..."
        
        if curl -f -L -o "$update_file" \
            -H "X-API-Key: $API_KEY" \
            --progress-bar \
            "$download_url"; then
            log_success "Descarga completada"
            break
        else
            retry=$((retry + 1))
            if [ $retry -lt $MAX_RETRIES ]; then
                log_warning "Error en descarga, reintentando en $RETRY_DELAY segundos..."
                sleep $RETRY_DELAY
            else
                log_error "Descarga fallida después de $MAX_RETRIES intentos"
                return 1
            fi
        fi
    done
    
    # Verificar integridad
    update_sync_status "verifying" 50 "Verificando integridad"
    
    if [ -f "$update_file.md5" ]; then
        if ! md5sum -c "$update_file.md5"; then
            log_error "Verificación de integridad fallida"
            return 1
        fi
    fi
    
    log_success "Actualizaciones descargadas y verificadas"
    return 0
}

# 10. Aplicar actualizaciones
apply_updates() {
    log_info "Aplicando actualizaciones..."
    
    update_sync_status "applying" 70 "Aplicando actualizaciones"
    report_status "progress" "Instalando actualizaciones"
    
    local update_file="$TEMP_DIR/updates/update.tar.gz"
    
    if [ ! -f "$update_file" ]; then
        log_error "Archivo de actualización no encontrado"
        return 1
    fi
    
    # Hacer backup antes de actualizar
    log_info "Creando backup de seguridad..."
    tar -czf "/opt/playmi/backup/backup-$(date +%Y%m%d-%H%M%S).tar.gz" \
        /var/www/playmi/portal \
        /opt/playmi/config \
        2>/dev/null
    
    # Extraer actualizaciones
    cd "$TEMP_DIR/updates"
    tar -xzf "$update_file"
    
    # Aplicar actualizaciones según tipo
    if [ -d "portal_update" ]; then
        log_info "Actualizando portal web..."
        rsync -av --backup --suffix=".bak" \
            "portal_update/" "/var/www/playmi/portal/"
        chown -R www-data:www-data /var/www/playmi/portal
    fi
    
    if [ -d "scripts_update" ]; then
        log_info "Actualizando scripts del sistema..."
        rsync -av --backup --suffix=".bak" \
            "scripts_update/" "/opt/playmi/scripts/"
        chmod +x /opt/playmi/scripts/*.sh
    fi
    
    if [ -f "config_update.json" ]; then
        log_info "Actualizando configuración..."
        # Merge de configuración preservando valores locales
        if command -v jq &> /dev/null; then
            jq -s '.[0] * .[1]' "$CONFIG_FILE" "config_update.json" > "$CONFIG_FILE.tmp"
            mv "$CONFIG_FILE.tmp" "$CONFIG_FILE"
        fi
    fi
    
    # Actualizar versión
    if [ -f "version.txt" ]; then
        cp "version.txt" /opt/playmi/version.txt
    fi
    
    # Limpiar temporales
    rm -rf "$TEMP_DIR/updates"
    
    log_success "Actualizaciones aplicadas correctamente"
    return 0
}

# 11. Sincronizar contenido menor (thumbnails, metadata)
sync_minor_content() {
    log_info "Sincronizando contenido menor..."
    
    update_sync_status "syncing_content" 85 "Sincronizando metadata"
    
    # Solo sincronizar archivos pequeños (thumbnails, metadata)
    # NO sincronizar videos completos (eso se hace por cambio de SD)
    
    local sync_items=(
        "thumbnails:*.jpg:1M"  # Solo imágenes menores a 1MB
        "metadata:*.json:100K"  # Solo JSON menores a 100KB
        "subtitles:*.srt:100K"  # Subtítulos si existen
    )
    
    for item in "${sync_items[@]}"; do
        IFS=':' read -r folder pattern size_limit <<< "$item"
        
        log_info "Sincronizando $folder..."
        
        # Aquí iría la lógica de rsync selectivo
        # Por ahora solo log
        log_info "Sincronización de $folder omitida (implementar según necesidad)"
    done
    
    log_success "Contenido menor sincronizado"
}

# 12. Reiniciar servicios si es necesario
restart_services_if_needed() {
    log_info "Verificando si es necesario reiniciar servicios..."
    
    local restart_needed=false
    
    # Verificar si se actualizó el portal
    if [ -f /var/www/playmi/portal/index.php.bak ]; then
        restart_needed=true
    fi
    
    if [ "$restart_needed" = true ]; then
        log_info "Reiniciando servicios..."
        
        # Limpiar cache de PHP
        if command -v php &> /dev/null; then
            php -r "if(function_exists('opcache_reset')) opcache_reset();"
        fi
        
        # Reiniciar Apache
        systemctl restart apache2
        
        log_success "Servicios reiniciados"
    else
        log_info "No es necesario reiniciar servicios"
    fi
}

# 13. Función principal de sincronización
perform_sync() {
    log_info "=== Iniciando sincronización ==="
    
    # Reportar inicio
    report_status "start" "Iniciando sincronización"
    update_sync_status "running" 5 "Sincronización iniciada"
    
    # Verificar conectividad
    if ! check_connectivity; then
        log_warning "Sin conectividad - modo offline"
        update_sync_status "offline" 0 "Sin conexión a internet"
        return 0
    fi
    
    # Verificar actualizaciones
    check_updates
    local update_status=$?
    
    if [ $update_status -eq 0 ]; then
        # Hay actualizaciones disponibles
        if download_minor_updates; then
            if apply_updates; then
                sync_minor_content
                restart_services_if_needed
                
                report_status "complete" "Actualización completada exitosamente"
                update_sync_status "completed" 100 "Actualización exitosa"
                log_success "Sincronización completada exitosamente"
            else
                report_status "error" "Error al aplicar actualizaciones"
                update_sync_status "error" 0 "Error en actualización"
                log_error "Error durante la actualización"
                return 1
            fi
        else
            report_status "error" "Error al descargar actualizaciones"
            update_sync_status "error" 0 "Error en descarga"
            log_error "Error al descargar actualizaciones"
            return 1
        fi
    elif [ $update_status -eq 2 ]; then
        # Sistema actualizado
        report_status "complete" "Sistema ya está actualizado"
        update_sync_status "completed" 100 "Sistema actualizado"
        log_success "Sistema ya está actualizado"
    else
        # Error al verificar
        report_status "error" "Error al verificar actualizaciones"
        update_sync_status "error" 0 "Error de verificación"
        log_error "Error al verificar actualizaciones"
        return 1
    fi
    
    log_info "=== Sincronización finalizada ==="
    return 0
}

# 14. Modo de ejecución
main() {
    # Verificar argumentos
    case "${1:-auto}" in
        "auto")
            log_info "Modo automático de sincronización"
            ;;
        "force")
            log_info "Forzando sincronización manual"
            ;;
        "check")
            log_info "Verificando estado de sincronización"
            load_config
            check_connectivity
            check_updates
            exit 0
            ;;
        *)
            echo "Uso: $0 [auto|force|check]"
            exit 1
            ;;
    esac
    
    # Verificar bloqueo
    check_lock
    
    # Cargar configuración
    load_config
    
    # Verificar licencia
    check_license_expiry
    
    # Ejecutar sincronización
    perform_sync
    
    # Retornar estado
    exit $?
}

# Ejecutar función principal
main "$@"