#!/bin/bash
#================================================================
# PLAYMI - Script de Clonación de SD Cards
# Descripción: Prepara múltiples SD cards con contenido actualizado
# Compatible con: Linux/Mac (Windows usar Balena Etcher)
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
WORK_DIR="$HOME/playmi-sd-factory"
IMAGES_DIR="$WORK_DIR/images"
CONTENT_DIR="$WORK_DIR/content"
TEMP_MOUNT="$WORK_DIR/temp_mount"
LOG_FILE="$WORK_DIR/cloning-$(date +%Y%m%d).log"

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

# 1. Verificar dependencias
check_dependencies() {
    log_info "Verificando dependencias..."
    
    local deps=("dd" "fdisk" "losetup" "mkfs.vfat" "mkfs.ext4" "rsync")
    local missing=()
    
    for cmd in "${deps[@]}"; do
        if ! command -v $cmd &> /dev/null; then
            missing+=($cmd)
        fi
    done
    
    if [ ${#missing[@]} -gt 0 ]; then
        log_error "Comandos faltantes: ${missing[*]}"
        echo "Instalar con: sudo apt-get install ${missing[*]}"
        exit 1
    fi
    
    log_success "Todas las dependencias instaladas"
}

# 2. Crear estructura de directorios
setup_directories() {
    log_info "Creando estructura de directorios..."
    
    mkdir -p "$IMAGES_DIR"/{base,custom,final}
    mkdir -p "$CONTENT_DIR"/{movies,music,games}
    mkdir -p "$TEMP_MOUNT"/{boot,root}
    mkdir -p "$WORK_DIR"/{logs,config,scripts}
    
    log_success "Directorios creados"
}

# 3. Crear imagen base desde SD existente
create_base_image() {
    log_info "=== CREAR IMAGEN BASE ==="
    echo ""
    echo "Este proceso creará una imagen base desde una SD ya configurada"
    echo ""
    
    # Listar dispositivos
    echo "Dispositivos disponibles:"
    lsblk -d -o NAME,SIZE,MODEL | grep -E "sd|mmcblk"
    echo ""
    
    read -p "Ingrese el dispositivo de la SD origen (ej: /dev/sdb): " source_device
    
    if [ ! -b "$source_device" ]; then
        log_error "Dispositivo no válido: $source_device"
        return 1
    fi
    
    local image_name="playmi-base-$(date +%Y%m%d).img"
    local image_path="$IMAGES_DIR/base/$image_name"
    
    # Confirmar
    echo ""
    echo "Se creará imagen desde: $source_device"
    echo "Archivo destino: $image_path"
    echo "Tamaño estimado: $(lsblk -b -d -o SIZE $source_device | tail -1 | numfmt --to=iec)"
    echo ""
    read -p "¿Continuar? (s/n): " -n 1 -r
    echo
    
    if [[ ! $REPLY =~ ^[Ss]$ ]]; then
        return 1
    fi
    
    # Crear imagen
    log_info "Creando imagen (esto puede tomar 10-30 minutos)..."
    
    sudo dd if="$source_device" of="$image_path" bs=4M status=progress conv=fsync
    
    if [ $? -eq 0 ]; then
        log_success "Imagen base creada: $image_path"
        
        # Comprimir para ahorrar espacio
        log_info "Comprimiendo imagen..."
        gzip -c "$image_path" > "$image_path.gz"
        rm "$image_path"
        
        log_success "Imagen comprimida: $image_path.gz"
    else
        log_error "Error al crear imagen"
        return 1
    fi
}

# 4. Preparar imagen con contenido nuevo
prepare_custom_image() {
    log_info "=== PREPARAR IMAGEN PERSONALIZADA ==="
    
    # Seleccionar imagen base
    echo "Imágenes base disponibles:"
    ls -lh "$IMAGES_DIR/base/"*.img.gz 2>/dev/null
    echo ""
    
    read -p "Ingrese nombre de imagen base (o ruta completa): " base_image
    
    if [ ! -f "$base_image" ] && [ ! -f "$IMAGES_DIR/base/$base_image" ]; then
        log_error "Imagen base no encontrada"
        return 1
    fi
    
    # Configuración de la empresa
    echo ""
    read -p "ID de empresa: " company_id
    read -p "Nombre de empresa: " company_name
    read -p "Mes del contenido (ej: 2024-08): " content_month
    
    local custom_image="playmi-${company_id}-${content_month}.img"
    local work_image="$IMAGES_DIR/custom/$custom_image"
    
    # Descomprimir imagen base
    log_info "Descomprimiendo imagen base..."
    if [[ "$base_image" == *.gz ]]; then
        gunzip -c "$base_image" > "$work_image"
    else
        cp "$base_image" "$work_image"
    fi
    
    # Montar imagen
    log_info "Montando imagen para modificación..."
    
    # Obtener información de particiones
    local loop_device=$(sudo losetup -f)
    sudo losetup -P "$loop_device" "$work_image"
    
    # Montar particiones (asumiendo estructura estándar Pi)
    sudo mount "${loop_device}p1" "$TEMP_MOUNT/boot"  # Boot partition
    sudo mount "${loop_device}p2" "$TEMP_MOUNT/root"  # Root partition
    
    # Actualizar contenido
    log_info "Actualizando contenido multimedia..."
    
    # Limpiar contenido anterior
    sudo rm -rf "$TEMP_MOUNT/root/var/www/playmi/content/"*
    
    # Copiar contenido nuevo
    if [ -d "$CONTENT_DIR/movies" ] && [ "$(ls -A $CONTENT_DIR/movies)" ]; then
        log_info "Copiando películas..."
        sudo cp -r "$CONTENT_DIR/movies/"* "$TEMP_MOUNT/root/var/www/playmi/content/movies/"
    fi
    
    if [ -d "$CONTENT_DIR/music" ] && [ "$(ls -A $CONTENT_DIR/music)" ]; then
        log_info "Copiando música..."
        sudo cp -r "$CONTENT_DIR/music/"* "$TEMP_MOUNT/root/var/www/playmi/content/music/"
    fi
    
    if [ -d "$CONTENT_DIR/games" ] && [ "$(ls -A $CONTENT_DIR/games)" ]; then
        log_info "Copiando juegos..."
        sudo cp -r "$CONTENT_DIR/games/"* "$TEMP_MOUNT/root/var/www/playmi/content/games/"
    fi
    
    # Actualizar configuración
    log_info "Actualizando configuración..."
    
    # Actualizar company-config.json
    local config_file="$TEMP_MOUNT/root/opt/playmi/config/company-config.json"
    if [ -f "$config_file" ]; then
        # Actualizar fecha
        sudo sed -i "s/\"_generated\": \".*\"/\"_generated\": \"$(date -u +%Y-%m-%dT%H:%M:%SZ)\"/" "$config_file"
        # Actualizar ID empresa si es necesario
        sudo sed -i "s/\"id\": \"company_.*\"/\"id\": \"$company_id\"/" "$config_file"
    fi
    
    # Actualizar fecha de vencimiento (último día del mes siguiente)
    local expiry_date=$(date -d "$content_month-01 +2 months -1 day" +%Y-%m-%d)
    sudo sed -i "s/\"expiry_date\": \".*\"/\"expiry_date\": \"$expiry_date\"/" "$config_file"
    
    # Copiar archivo SQL de base de datos si existe
    log_info "Verificando base de datos..."
    local db_file="$WORK_DIR/database/sd_${company_id}_${content_month}.sql"
    if [ -f "$db_file" ]; then
        log_info "Copiando base de datos actualizada..."
        sudo cp "$db_file" "$TEMP_MOUNT/root/opt/playmi/database/"
        
        # Crear script de primera ejecución para importar BD
        cat << 'EOF' | sudo tee "$TEMP_MOUNT/root/opt/playmi/scripts/first-boot-import.sh"
#!/bin/bash
# Importar BD en el primer arranque
DB_FILE="/opt/playmi/database/sd_*.sql"
if [ -f $DB_FILE ]; then
    echo "Importando base de datos..." >> /var/log/playmi/first-boot.log
    mysql -uroot -pplaymi2024 playmi < $DB_FILE
    if [ $? -eq 0 ]; then
        echo "BD importada exitosamente" >> /var/log/playmi/first-boot.log
        rm -f $DB_FILE  # Eliminar después de importar
    fi
fi
EOF
        sudo chmod +x "$TEMP_MOUNT/root/opt/playmi/scripts/first-boot-import.sh"
        
        # Agregar al inicio automático
        echo "/opt/playmi/scripts/first-boot-import.sh" | sudo tee -a "$TEMP_MOUNT/root/etc/rc.local"
    else
        log_warning "No se encontró archivo de BD para $company_id-$content_month"
        log_warning "La BD no se actualizará en esta imagen"
    fi
    
    # Limpiar logs y temporales
    log_info "Limpiando archivos temporales..."
    sudo rm -rf "$TEMP_MOUNT/root/var/log/"*.log
    sudo rm -rf "$TEMP_MOUNT/root/var/www/playmi/temp/"*
    sudo rm -rf "$TEMP_MOUNT/root/var/www/playmi/cache/"*
    
    # Crear archivo de versión
    echo "$content_month" | sudo tee "$TEMP_MOUNT/root/opt/playmi/version.txt"
    
    # Desmontar
    log_info "Finalizando modificaciones..."
    sudo umount "$TEMP_MOUNT/boot"
    sudo umount "$TEMP_MOUNT/root"
    sudo losetup -d "$loop_device"
    
    log_success "Imagen personalizada lista: $work_image"
    
    # Comprimir imagen final
    log_info "Comprimiendo imagen final..."
    gzip -c "$work_image" > "$IMAGES_DIR/final/$custom_image.gz"
    rm "$work_image"
    
    log_success "Imagen final: $IMAGES_DIR/final/$custom_image.gz"
}

# 5. Escribir imagen a SD
write_to_sd() {
    log_info "=== ESCRIBIR IMAGEN A SD ==="
    
    # Listar imágenes disponibles
    echo "Imágenes disponibles:"
    ls -lh "$IMAGES_DIR/final/"*.img.gz 2>/dev/null
    echo ""
    
    read -p "Seleccione imagen a escribir: " image_file
    
    if [ ! -f "$IMAGES_DIR/final/$image_file" ]; then
        log_error "Imagen no encontrada"
        return 1
    fi
    
    # Listar dispositivos
    echo ""
    echo "Dispositivos disponibles:"
    lsblk -d -o NAME,SIZE,MODEL | grep -E "sd|mmcblk"
    echo ""
    
    read -p "Ingrese el dispositivo destino (ej: /dev/sdb): " target_device
    
    if [ ! -b "$target_device" ]; then
        log_error "Dispositivo no válido"
        return 1
    fi
    
    # Confirmar
    echo ""
    echo -e "${YELLOW}¡ADVERTENCIA!${NC}"
    echo "Se escribirá: $image_file"
    echo "Al dispositivo: $target_device"
    echo "TODOS LOS DATOS EN $target_device SERÁN ELIMINADOS"
    echo ""
    read -p "¿Está seguro? Escriba 'SI' en mayúsculas: " confirm
    
    if [ "$confirm" != "SI" ]; then
        log_info "Operación cancelada"
        return 1
    fi
    
    # Desmontar particiones si están montadas
    sudo umount "$target_device"* 2>/dev/null
    
    # Escribir imagen
    log_info "Escribiendo imagen (10-20 minutos)..."
    
    gunzip -c "$IMAGES_DIR/final/$image_file" | sudo dd of="$target_device" bs=4M status=progress conv=fsync
    
    if [ $? -eq 0 ]; then
        log_success "SD escrita correctamente"
        
        # Eject seguro
        sudo sync
        sudo eject "$target_device"
        
        echo ""
        echo -e "${GREEN}SD lista para usar${NC}"
        echo "Puede retirar la SD de forma segura"
    else
        log_error "Error al escribir SD"
        return 1
    fi
}

# 6. Clonación masiva
batch_clone() {
    log_info "=== CLONACIÓN MASIVA ==="
    
    echo "Esta función permite clonar a múltiples SD simultáneamente"
    echo "Requiere un hub USB con múltiples lectores de SD"
    echo ""
    
    # Listar imagen a clonar
    read -p "Imagen a clonar: " source_image
    
    if [ ! -f "$IMAGES_DIR/final/$source_image" ]; then
        log_error "Imagen no encontrada"
        return 1
    fi
    
    # Detectar dispositivos SD
    echo "Detectando dispositivos SD..."
    local sd_devices=()
    
    for dev in /dev/sd[b-z]; do
        if [ -b "$dev" ] && lsblk "$dev" 2>/dev/null | grep -q "disk"; then
            local size=$(lsblk -b -d -o SIZE "$dev" | tail -1)
            # Solo considerar dispositivos entre 8GB y 128GB (típico de SD)
            if [ $size -gt 8000000000 ] && [ $size -lt 137438953472 ]; then
                sd_devices+=("$dev")
            fi
        fi
    done
    
    echo ""
    echo "SD cards detectadas: ${#sd_devices[@]}"
    for dev in "${sd_devices[@]}"; do
        echo "  - $dev ($(lsblk -d -o SIZE,MODEL $dev | tail -1))"
    done
    
    echo ""
    read -p "¿Escribir a todas estas SD? (s/n): " -n 1 -r
    echo
    
    if [[ ! $REPLY =~ ^[Ss]$ ]]; then
        return 1
    fi
    
    # Clonar en paralelo
    log_info "Iniciando clonación masiva..."
    
    for dev in "${sd_devices[@]}"; do
        (
            log_info "Clonando a $dev..."
            gunzip -c "$IMAGES_DIR/final/$source_image" | sudo dd of="$dev" bs=4M conv=fsync 2>&1 | \
                grep -o '[0-9]\+\s\+bytes' | tail -1
            sync
            log_success "Completado: $dev"
        ) &
    done
    
    # Esperar a que terminen todos
    wait
    
    log_success "Clonación masiva completada"
}

# 7. Verificar SD
verify_sd() {
    log_info "=== VERIFICAR SD ==="
    
    read -p "Dispositivo a verificar (ej: /dev/sdb): " device
    
    if [ ! -b "$device" ]; then
        log_error "Dispositivo no válido"
        return 1
    fi
    
    # Montar temporalmente
    local temp_verify="$WORK_DIR/verify_mount"
    mkdir -p "$temp_verify"
    
    # Montar partición root
    sudo mount "${device}2" "$temp_verify" 2>/dev/null || sudo mount "${device}p2" "$temp_verify"
    
    if [ $? -eq 0 ]; then
        # Verificaciones
        echo ""
        echo "=== Verificación de SD ==="
        
        # Verificar archivos críticos
        local critical_files=(
            "opt/playmi/scripts/startup.sh"
            "var/www/playmi/index.php"
            "opt/playmi/config/company-config.json"
        )
        
        for file in "${critical_files[@]}"; do
            if [ -f "$temp_verify/$file" ]; then
                echo -e "${GREEN}✓${NC} $file"
            else
                echo -e "${RED}✗${NC} $file"
            fi
        done
        
        # Verificar contenido
        echo ""
        echo "Contenido encontrado:"
        echo "  Películas: $(find "$temp_verify/var/www/playmi/content/movies" -name "*.mp4" 2>/dev/null | wc -l)"
        echo "  Música: $(find "$temp_verify/var/www/playmi/content/music" -name "*.mp3" 2>/dev/null | wc -l)"
        echo "  Juegos: $(find "$temp_verify/var/www/playmi/content/games" -type d -name "game_*" 2>/dev/null | wc -l)"
        
        # Verificar espacio
        echo ""
        echo "Uso de espacio:"
        df -h "$temp_verify" | tail -1
        
        # Verificar versión
        if [ -f "$temp_verify/opt/playmi/version.txt" ]; then
            echo ""
            echo "Versión: $(cat $temp_verify/opt/playmi/version.txt)"
        fi
        
        sudo umount "$temp_verify"
        
        echo ""
        echo -e "${GREEN}Verificación completada${NC}"
    else
        log_error "No se pudo montar la SD para verificación"
    fi
}

# 8. Menú principal
show_menu() {
    clear
    echo "=========================================="
    echo "   PLAYMI - Fábrica de SD Cards"
    echo "=========================================="
    echo ""
    echo "1) Crear imagen base desde SD existente"
    echo "2) Preparar imagen con contenido nuevo"
    echo "3) Escribir imagen a una SD"
    echo "4) Clonación masiva (múltiples SD)"
    echo "5) Verificar SD"
    echo "6) Ver instrucciones"
    echo "0) Salir"
    echo ""
}

# 9. Instrucciones
show_instructions() {
    clear
    echo "=========================================="
    echo "INSTRUCCIONES DE USO"
    echo "=========================================="
    echo ""
    echo "PREPARACIÓN INICIAL:"
    echo "1. Tener una SD con PLAYMI ya instalado y funcionando"
    echo "2. Crear imagen base con opción 1"
    echo ""
    echo "ACTUALIZACIÓN MENSUAL:"
    echo "1. Copiar nuevo contenido a: $CONTENT_DIR"
    echo "   - Películas en: $CONTENT_DIR/movies/"
    echo "   - Música en: $CONTENT_DIR/music/"
    echo "   - Juegos en: $CONTENT_DIR/games/"
    echo "2. Usar opción 2 para preparar imagen del mes"
    echo "3. Usar opciones 3 o 4 para escribir a SD"
    echo ""
    echo "ESTRUCTURA DE NOMBRES:"
    echo "- Imagen base: playmi-base-YYYYMMDD.img.gz"
    echo "- Imagen mes: playmi-[empresa]-YYYY-MM.img.gz"
    echo ""
    echo "TIEMPOS ESTIMADOS:"
    echo "- Crear imagen base: 15-30 minutos"
    echo "- Preparar imagen: 10-20 minutos"
    echo "- Escribir SD: 10-20 minutos por unidad"
    echo ""
    echo "Presione ENTER para continuar..."
    read
}

# Main
main() {
    # Verificar root para algunas operaciones
    if [ "$EUID" -ne 0 ] && [ "$1" != "menu" ]; then 
        echo "Algunas operaciones requieren sudo"
        echo "Ejecute: sudo $0"
        echo ""
        echo "O use: $0 menu (para ver opciones)"
        exit 1
    fi
    
    # Crear directorios
    setup_directories
    
    # Verificar dependencias
    check_dependencies
    
    # Loop del menú
    while true; do
        show_menu
        read -p "Seleccione opción: " option
        
        case $option in
            1) create_base_image ;;
            2) prepare_custom_image ;;
            3) write_to_sd ;;
            4) batch_clone ;;
            5) verify_sd ;;
            6) show_instructions ;;
            0) 
                echo "Saliendo..."
                exit 0
                ;;
            *)
                echo "Opción no válida"
                ;;
        esac
        
        echo ""
        echo "Presione ENTER para continuar..."
        read
    done
}

# Ejecutar
main "$@"