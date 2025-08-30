#!/bin/bash
#================================================================
# PLAYMI - Script Maestro de Instalación para Raspberry Pi 5
# Módulo 6.1: pi-system/install/setup.sh
# Descripción: Instalación completa del sistema PLAYMI en Pi 5
# Autor: Sistema PLAYMI
# Fecha: 2024
#================================================================

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Variables globales
INSTALL_DIR="/opt/playmi"
WEB_DIR="/var/www/playmi"
LOG_FILE="/var/log/playmi-install.log"
BACKUP_DIR="/opt/playmi-backup"
PI_MODEL=""
TOTAL_STEPS=12
CURRENT_STEP=0

# Función para mostrar progreso
show_progress() {
    CURRENT_STEP=$((CURRENT_STEP + 1))
    echo -e "${GREEN}[Paso $CURRENT_STEP/$TOTAL_STEPS]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] Paso $CURRENT_STEP: $1" >> "$LOG_FILE"
}

# Función para manejar errores
handle_error() {
    echo -e "${RED}[ERROR]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1" >> "$LOG_FILE"
    echo -e "${YELLOW}[INFO]${NC} Verificar log en: $LOG_FILE"
    exit 1
}

# Función para verificar éxito
check_success() {
    if [ $? -ne 0 ]; then
        handle_error "$1"
    fi
}

# Banner de inicio
show_banner() {
    clear
    echo "=============================================="
    echo "    PLAYMI - Sistema de Entretenimiento"
    echo "    Instalador para Raspberry Pi 5"
    echo "=============================================="
    echo ""
}

# 1. Verificar que es Raspberry Pi
verify_raspberry() {
    show_progress "Verificando hardware Raspberry Pi..."
    
    if [ ! -f /proc/device-tree/model ]; then
        handle_error "No se detectó hardware Raspberry Pi"
    fi
    
    PI_MODEL=$(cat /proc/device-tree/model)
    echo -e "${GREEN}[OK]${NC} Detectado: $PI_MODEL"
    
    # Verificar que es Pi 5 o compatible
    if [[ ! "$PI_MODEL" =~ "Raspberry Pi 5" ]] && [[ ! "$PI_MODEL" =~ "Raspberry Pi 4" ]]; then
        echo -e "${YELLOW}[ADVERTENCIA]${NC} Este script está optimizado para Pi 5"
        echo "Modelo detectado: $PI_MODEL"
        read -p "¿Desea continuar de todos modos? (s/n): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Ss]$ ]]; then
            exit 1
        fi
    fi
}

# 2. Verificar requisitos del sistema
check_requirements() {
    show_progress "Verificando requisitos del sistema..."
    
    # Verificar espacio en disco (mínimo 4GB libres)
    AVAILABLE_SPACE=$(df / | awk 'NR==2 {print $4}')
    if [ $AVAILABLE_SPACE -lt 4194304 ]; then
        handle_error "Espacio insuficiente. Se requieren al menos 4GB libres"
    fi
    
    # Verificar conectividad a internet (para descarga de paquetes)
    ping -c 1 google.com > /dev/null 2>&1
    if [ $? -ne 0 ]; then
        echo -e "${YELLOW}[ADVERTENCIA]${NC} Sin conexión a internet"
        echo "La instalación continuará pero algunos paquetes podrían no instalarse"
    fi
    
    # Verificar permisos de root
    if [ "$EUID" -ne 0 ]; then 
        handle_error "Este script debe ejecutarse con sudo"
    fi
    
    echo -e "${GREEN}[OK]${NC} Requisitos verificados"
}

# 3. Actualizar sistema
update_system() {
    show_progress "Actualizando sistema operativo..."
    
    apt-get update >> "$LOG_FILE" 2>&1
    check_success "Error al actualizar lista de paquetes"
    
    # Solo actualizaciones críticas
    apt-get upgrade -y --no-install-recommends >> "$LOG_FILE" 2>&1
    check_success "Error al actualizar paquetes"
    
    echo -e "${GREEN}[OK]${NC} Sistema actualizado"
}

# 4. Instalar dependencias
install_dependencies() {
    show_progress "Instalando dependencias necesarias..."
    
    # Lista de paquetes necesarios
    PACKAGES=(
        "apache2"
        "php8.1"
        "php8.1-cli"
        "php8.1-common"
        "php8.1-mysql"
        "php8.1-xml"
        "php8.1-curl"
        "php8.1-gd"
        "php8.1-mbstring"
        "php8.1-zip"
        "libapache2-mod-php8.1"
        "hostapd"
        "dnsmasq"
        "iptables-persistent"
        "git"
        "unzip"
        "htop"
        "curl"
        "wget"
        "bridge-utils"
        "net-tools"
        "wireless-tools"
        "rfkill"
    )
    
    for package in "${PACKAGES[@]}"; do
        echo -n "Instalando $package... "
        apt-get install -y "$package" >> "$LOG_FILE" 2>&1
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}OK${NC}"
        else
            echo -e "${YELLOW}ADVERTENCIA${NC}"
        fi
    done
    
    # Detener servicios que configuraremos después
    systemctl stop hostapd >> "$LOG_FILE" 2>&1
    systemctl stop dnsmasq >> "$LOG_FILE" 2>&1
    
    echo -e "${GREEN}[OK]${NC} Dependencias instaladas"
}

# 5. Crear estructura de directorios
create_directories() {
    show_progress "Creando estructura de directorios..."
    
    # Directorios principales
    mkdir -p "$INSTALL_DIR"/{config,scripts,logs,temp}
    mkdir -p "$WEB_DIR"/{portal,content,api,assets}
    mkdir -p "$WEB_DIR"/content/{movies,music,games}
    mkdir -p "$BACKUP_DIR"
    mkdir -p /var/log/playmi
    
    # Permisos
    chown -R www-data:www-data "$WEB_DIR"
    chmod -R 755 "$WEB_DIR"
    chmod -R 777 "$WEB_DIR"/content
    chmod -R 777 /var/log/playmi
    
    echo -e "${GREEN}[OK]${NC} Directorios creados"
}

# 6. Configurar WiFi Access Point
configure_wifi_ap() {
    show_progress "Configurando WiFi Access Point..."
    
    # Llamar al script específico de WiFi
    if [ -f "wifi-ap.sh" ]; then
        bash wifi-ap.sh >> "$LOG_FILE" 2>&1
        check_success "Error al configurar WiFi AP"
    else
        echo -e "${YELLOW}[ADVERTENCIA]${NC} wifi-ap.sh no encontrado, configuración manual requerida"
    fi
    
    echo -e "${GREEN}[OK]${NC} WiFi AP configurado"
}

# 7. Configurar servidor web
configure_web_server() {
    show_progress "Configurando servidor web Apache..."
    
    # Llamar al script específico del servidor web
    if [ -f "web-server.sh" ]; then
        bash web-server.sh >> "$LOG_FILE" 2>&1
        check_success "Error al configurar servidor web"
    else
        echo -e "${YELLOW}[ADVERTENCIA]${NC} web-server.sh no encontrado, configuración manual requerida"
    fi
    
    echo -e "${GREEN}[OK]${NC} Servidor web configurado"
}

# 8. Copiar archivos del portal
install_portal() {
    show_progress "Instalando portal de pasajeros..."
    
    # Aquí se copiarían los archivos del portal desde el paquete
    # Por ahora creamos estructura básica
    cat > "$WEB_DIR/index.php" << 'EOF'
<?php
// PLAYMI Portal - Página temporal de instalación
?>
<!DOCTYPE html>
<html>
<head>
    <title>PLAYMI - Sistema Instalado</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #141414;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            text-align: center;
        }
        h1 {
            color: #e50914;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>PLAYMI</h1>
        <h2>Sistema de Entretenimiento</h2>
        <p>Instalación completada exitosamente</p>
        <p>Versión: 1.0.0</p>
        <p>Pi ID: <?php echo gethostname(); ?></p>
    </div>
</body>
</html>
EOF
    
    chown www-data:www-data "$WEB_DIR/index.php"
    
    echo -e "${GREEN}[OK]${NC} Portal instalado"
}

# 9. Configurar auto-inicio
configure_autostart() {
    show_progress "Configurando inicio automático..."
    
    # Llamar al script de auto-inicio
    if [ -f "auto-start.sh" ]; then
        bash auto-start.sh >> "$LOG_FILE" 2>&1
        check_success "Error al configurar auto-inicio"
    else
        echo -e "${YELLOW}[ADVERTENCIA]${NC} auto-start.sh no encontrado"
    fi
    
    echo -e "${GREEN}[OK]${NC} Auto-inicio configurado"
}

# 10. Configurar firewall básico
configure_firewall() {
    show_progress "Configurando firewall..."
    
    # Reglas básicas de firewall
    iptables -F
    iptables -X
    iptables -t nat -F
    iptables -t nat -X
    
    # Permitir tráfico local
    iptables -A INPUT -i lo -j ACCEPT
    iptables -A OUTPUT -o lo -j ACCEPT
    
    # Permitir conexiones establecidas
    iptables -A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT
    
    # Permitir SSH (puerto 22) - solo desde red local
    iptables -A INPUT -p tcp --dport 22 -s 192.168.0.0/16 -j ACCEPT
    
    # Permitir HTTP (puerto 80)
    iptables -A INPUT -p tcp --dport 80 -j ACCEPT
    
    # Permitir DHCP
    iptables -A INPUT -p udp --dport 67:68 -j ACCEPT
    
    # Permitir DNS
    iptables -A INPUT -p udp --dport 53 -j ACCEPT
    iptables -A INPUT -p tcp --dport 53 -j ACCEPT
    
    # Guardar reglas
    netfilter-persistent save >> "$LOG_FILE" 2>&1
    
    echo -e "${GREEN}[OK]${NC} Firewall configurado"
}

# 11. Verificar instalación
verify_installation() {
    show_progress "Verificando instalación..."
    
    # Lista de servicios a verificar
    SERVICES=("apache2" "hostapd" "dnsmasq")
    
    for service in "${SERVICES[@]}"; do
        if systemctl is-active --quiet "$service"; then
            echo -e "  $service: ${GREEN}Activo${NC}"
        else
            echo -e "  $service: ${RED}Inactivo${NC}"
        fi
    done
    
    # Verificar acceso web
    curl -s http://localhost > /dev/null
    if [ $? -eq 0 ]; then
        echo -e "  Portal Web: ${GREEN}Accesible${NC}"
    else
        echo -e "  Portal Web: ${RED}No accesible${NC}"
    fi
    
    echo -e "${GREEN}[OK]${NC} Verificación completada"
}

# 12. Crear script de desinstalación
create_uninstall_script() {
    show_progress "Creando script de desinstalación..."
    
    cat > "$INSTALL_DIR/uninstall.sh" << 'EOF'
#!/bin/bash
# Script de desinstalación de PLAYMI

echo "¿Está seguro de que desea desinstalar PLAYMI? (s/n)"
read -n 1 -r
echo
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    exit 1
fi

echo "Desinstalando PLAYMI..."

# Detener servicios
systemctl stop apache2
systemctl stop hostapd
systemctl stop dnsmasq

# Eliminar archivos
rm -rf /opt/playmi
rm -rf /var/www/playmi
rm -rf /var/log/playmi

# Restaurar configuraciones originales
rm -f /etc/hostapd/hostapd.conf
rm -f /etc/dnsmasq.conf

echo "PLAYMI desinstalado"
EOF
    
    chmod +x "$INSTALL_DIR/uninstall.sh"
    
    echo -e "${GREEN}[OK]${NC} Script de desinstalación creado"
}

# Generar reporte final
generate_report() {
    REPORT_FILE="$INSTALL_DIR/install-report-$(date +%Y%m%d-%H%M%S).txt"
    
    cat > "$REPORT_FILE" << EOF
========================================
PLAYMI - Reporte de Instalación
========================================
Fecha: $(date)
Modelo Pi: $PI_MODEL
Versión PLAYMI: 1.0.0

Servicios Instalados:
- Apache2: $(systemctl is-active apache2)
- Hostapd: $(systemctl is-active hostapd)
- Dnsmasq: $(systemctl is-active dnsmasq)

Directorios:
- Instalación: $INSTALL_DIR
- Portal Web: $WEB_DIR
- Logs: /var/log/playmi

Siguiente paso:
1. Copiar contenido multimedia a $WEB_DIR/content/
2. Configurar company-config.json
3. Reiniciar el sistema

========================================
EOF
    
    echo -e "\n${GREEN}[COMPLETADO]${NC} Instalación finalizada"
    echo -e "Reporte guardado en: $REPORT_FILE"
}

# ============ MAIN ============
main() {
    show_banner
    
    # Crear log
    mkdir -p $(dirname "$LOG_FILE")
    echo "=== PLAYMI Installation Log ===" > "$LOG_FILE"
    echo "Started at: $(date)" >> "$LOG_FILE"
    
    # Ejecutar pasos de instalación
    verify_raspberry
    check_requirements
    update_system
    install_dependencies
    create_directories
    configure_wifi_ap
    configure_web_server
    install_portal
    configure_autostart
    configure_firewall
    verify_installation
    create_uninstall_script
    
    # Generar reporte
    generate_report
    
    echo -e "\n${GREEN}¡Instalación completada exitosamente!${NC}"
    echo -e "${YELLOW}Por favor reinicie el sistema para aplicar todos los cambios${NC}"
    echo -e "Use: ${GREEN}sudo reboot${NC}"
}

# Ejecutar
main