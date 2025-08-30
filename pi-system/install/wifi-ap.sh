#!/bin/bash
#================================================================
# PLAYMI - Configuración WiFi Access Point Multi-Adaptador
# Módulo 6.2: pi-system/install/wifi-ap.sh
# Descripción: Configura Pi 5 como AP con soporte para múltiples adaptadores
# Soporta: WiFi interno + hasta 2 adaptadores USB para balanceo de carga
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
CONFIG_FILE="/opt/playmi/config/wifi-config.json"
HOSTAPD_CONF="/etc/hostapd/hostapd.conf"
DNSMASQ_CONF="/etc/dnsmasq.conf"
DHCPCD_CONF="/etc/dhcpcd.conf"
BRIDGE_NAME="br0"
MAX_USERS_PER_AP=35  # Máximo de usuarios por adaptador

# Variables detectadas
WIFI_INTERFACES=("wlan1")
USB_WIFI_COUNT=0

# Función para logging
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] INFO: $1" >> /var/log/playmi/wifi-setup.log
}

log_success() {
    echo -e "${GREEN}[OK]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] SUCCESS: $1" >> /var/log/playmi/wifi-setup.log
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1" >> /var/log/playmi/wifi-setup.log
}

log_warning() {
    echo -e "${YELLOW}[ADVERTENCIA]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1" >> /var/log/playmi/wifi-setup.log
}

# 1. Detectar interfaces WiFi disponibles
detect_wifi_interfaces() {
    log_info "Detectando interfaces WiFi..."
    
    # Listar todas las interfaces wireless
    for interface in $(iw dev | grep Interface | cut -d ' ' -f2); do
        if [[ "$interface" == wlan* ]] && [[ "$interface" != "wlan0" ]]; then
            WIFI_INTERFACES+=("$interface")
        fi
    done
    
    if [ ${#WIFI_INTERFACES[@]} -eq 0 ]; then
        log_error "No se detectaron interfaces WiFi"
        exit 1
    fi
    
    log_success "Detectadas ${#WIFI_INTERFACES[@]} interfaces WiFi"
    
    # Contar adaptadores USB
    USB_WIFI_COUNT=$(lsusb | grep -i "wireless\|wifi\|802.11" | wc -l)
    log_info "Adaptadores WiFi USB detectados: $USB_WIFI_COUNT"
}

# 2. Configurar modo monitor/managed
configure_wifi_mode() {
    log_info "Configurando interfaces en modo AP..."
    
    # Desbloquear WiFi si está bloqueado
    rfkill unblock wifi >> /var/log/playmi/wifi-setup.log 2>&1
    
    for interface in "${WIFI_INTERFACES[@]}"; do
        log_info "Configurando $interface..."
        
        # Detener interface
        ip link set "$interface" down >> /var/log/playmi/wifi-setup.log 2>&1
        
        # Configurar modo
        iw "$interface" set type __ap >> /var/log/playmi/wifi-setup.log 2>&1
        
        # Levantar interface
        ip link set "$interface" up >> /var/log/playmi/wifi-setup.log 2>&1
        
        if [ $? -eq 0 ]; then
            log_success "$interface configurada correctamente"
        else
            log_warning "Problema al configurar $interface"
        fi
    done
}

# 3. Crear configuración de bridge
create_bridge_config() {
    log_info "Creando bridge de red para balanceo de carga..."
    
    # Crear bridge si no existe
    brctl addbr "$BRIDGE_NAME" 2>/dev/null || log_info "Bridge $BRIDGE_NAME ya existe"
    
    # Configurar bridge
    ip addr flush dev "$BRIDGE_NAME"
    ip addr add 192.168.4.1/24 dev "$BRIDGE_NAME"
    ip link set "$BRIDGE_NAME" up
    
    log_success "Bridge $BRIDGE_NAME configurado"
}

# 4. Generar configuración hostapd multi-interface
generate_hostapd_config() {
    log_info "Generando configuración hostapd para múltiples interfaces..."
    
    # Leer configuración del paquete si existe
    local SSID="PLAYMI_WiFi"
    local PASSWORD="playmi123456"
    local CHANNEL=6
    
    if [ -f "$CONFIG_FILE" ]; then
        SSID=$(jq -r '.wifi_settings.ssid // "PLAYMI_WiFi"' "$CONFIG_FILE")
        PASSWORD=$(jq -r '.wifi_settings.password // "playmi123456"' "$CONFIG_FILE")
        CHANNEL=$(jq -r '.wifi_settings.channel // 6' "$CONFIG_FILE")
    fi
    
    # Crear configuración base
    cat > "$HOSTAPD_CONF" << EOF
# PLAYMI - Configuración Multi-Interface WiFi AP
# Generado: $(date)

# Interface principal (WiFi interno)
interface=${WIFI_INTERFACES[0]}
bridge=$BRIDGE_NAME

# Configuración WiFi
driver=nl80211
ssid=$SSID
hw_mode=g
channel=$CHANNEL
ieee80211n=1
wmm_enabled=1
ht_capab=[HT40][SHORT-GI-20][DSSS_CCK-40]

# Seguridad
auth_algs=1
wpa=2
wpa_key_mgmt=WPA-PSK
rsn_pairwise=CCMP
wpa_passphrase=$PASSWORD

# Configuración de performance
max_num_sta=$MAX_USERS_PER_AP
ap_isolate=0
ignore_broadcast_ssid=0

# Logging
logger_syslog=-1
logger_syslog_level=2
EOF

    # Agregar interfaces adicionales si existen
    if [ ${#WIFI_INTERFACES[@]} -gt 1 ]; then
        log_info "Configurando interfaces adicionales para balanceo..."
        
        for ((i=1; i<${#WIFI_INTERFACES[@]}; i++)); do
            local CHANNEL_OFFSET=$((i * 5))
            local NEW_CHANNEL=$(( (CHANNEL + CHANNEL_OFFSET - 1) % 11 + 1 ))
            
            cat >> "$HOSTAPD_CONF" << EOF

# Interface adicional $i (${WIFI_INTERFACES[$i]})
bss=${WIFI_INTERFACES[$i]}
bridge=$BRIDGE_NAME
ssid=$SSID
channel=$NEW_CHANNEL
wpa=2
wpa_key_mgmt=WPA-PSK
rsn_pairwise=CCMP
wpa_passphrase=$PASSWORD
max_num_sta=$MAX_USERS_PER_AP
EOF
        done
    fi
    
    log_success "Configuración hostapd generada para ${#WIFI_INTERFACES[@]} interfaces"
}

# 5. Configurar DHCP con dnsmasq
configure_dnsmasq() {
    log_info "Configurando servidor DHCP..."
    
    # Backup configuración original
    cp "$DNSMASQ_CONF" "$DNSMASQ_CONF.backup" 2>/dev/null
    
    # Calcular rango DHCP según número de interfaces
    local TOTAL_CAPACITY=$((${#WIFI_INTERFACES[@]} * MAX_USERS_PER_AP))
    local DHCP_END=$((10 + TOTAL_CAPACITY))
    
    if [ $DHCP_END -gt 254 ]; then
        DHCP_END=254
    fi
    
    cat > "$DNSMASQ_CONF" << EOF
# PLAYMI - Configuración DHCP
# Capacidad total: $TOTAL_CAPACITY usuarios

# Interface
interface=$BRIDGE_NAME
bind-interfaces

# Rango DHCP
dhcp-range=192.168.4.10,192.168.4.$DHCP_END,255.255.255.0,12h

# DNS
dhcp-option=3,192.168.4.1
dhcp-option=6,192.168.4.1

# Resolución local
address=/#/192.168.4.1
domain=playmi.local
local=/playmi.local/

# Performance
dhcp-authoritative
cache-size=1000
dhcp-lease-max=$TOTAL_CAPACITY

# Logging
log-queries
log-dhcp
log-facility=/var/log/playmi/dnsmasq.log
EOF
    
    log_success "DHCP configurado para $TOTAL_CAPACITY usuarios máximo"
}

# 6. Configurar interfaces de red
configure_network_interfaces() {
    log_info "Configurando interfaces de red..."
    
    # Backup dhcpcd.conf
    cp "$DHCPCD_CONF" "$DHCPCD_CONF.backup" 2>/dev/null
    
    # Agregar configuración al final de dhcpcd.conf
    cat >> "$DHCPCD_CONF" << EOF

# PLAYMI - Configuración de red
interface $BRIDGE_NAME
static ip_address=192.168.4.1/24
nohook wpa_supplicant

EOF

    # Deshabilitar wpa_supplicant en interfaces AP
    for interface in "${WIFI_INTERFACES[@]}"; do
        echo "denyinterfaces $interface" >> "$DHCPCD_CONF"
    done
    
    log_success "Interfaces de red configuradas"
}

# 7. Configurar reglas iptables para NAT (opcional)
configure_nat() {
    log_info "Configurando NAT para compartir internet (si está disponible)..."
    
    # Habilitar forwarding
    echo 1 > /proc/sys/net/ipv4/ip_forward
    
    # Hacer permanente
    sed -i 's/#net.ipv4.ip_forward=1/net.ipv4.ip_forward=1/' /etc/sysctl.conf
    
    # Reglas NAT
    iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
    iptables -A FORWARD -i "$BRIDGE_NAME" -o eth0 -j ACCEPT
    iptables -A FORWARD -i eth0 -o "$BRIDGE_NAME" -m state --state RELATED,ESTABLISHED -j ACCEPT
    
    # Guardar reglas
    netfilter-persistent save >> /var/log/playmi/wifi-setup.log 2>&1
    
    log_success "NAT configurado"
}

# 8. Crear script de monitoreo
create_monitoring_script() {
    log_info "Creando script de monitoreo de WiFi..."
    
    cat > /opt/playmi/scripts/wifi-monitor.sh << 'EOF'
#!/bin/bash
# PLAYMI - Monitor de estado WiFi

BRIDGE="br0"
LOG_FILE="/var/log/playmi/wifi-monitor.log"

# Función para contar usuarios conectados
count_connected_users() {
    local interface=$1
    iw dev "$interface" station dump | grep -c "Station"
}

# Función para obtener estadísticas
get_wifi_stats() {
    echo "=== WiFi Stats - $(date) ===" >> "$LOG_FILE"
    
    for interface in $(ls /sys/class/net/ | grep wlan); do
        local users=$(count_connected_users "$interface")
        local tx_bytes=$(cat /sys/class/net/"$interface"/statistics/tx_bytes)
        local rx_bytes=$(cat /sys/class/net/"$interface"/statistics/rx_bytes)
        
        echo "$interface: $users usuarios, TX: $tx_bytes, RX: $rx_bytes" >> "$LOG_FILE"
    done
    
    # Estado de servicios
    echo "hostapd: $(systemctl is-active hostapd)" >> "$LOG_FILE"
    echo "dnsmasq: $(systemctl is-active dnsmasq)" >> "$LOG_FILE"
}

# Loop principal
while true; do
    get_wifi_stats
    sleep 60
done
EOF
    
    chmod +x /opt/playmi/scripts/wifi-monitor.sh
    
    log_success "Script de monitoreo creado"
}

# 9. Habilitar servicios
enable_services() {
    log_info "Habilitando servicios WiFi..."
    
    # Configurar hostapd
    sed -i 's/^#DAEMON_CONF=""/DAEMON_CONF="\/etc\/hostapd\/hostapd.conf"/' /etc/default/hostapd
    
    # Habilitar servicios
    systemctl unmask hostapd >> /var/log/playmi/wifi-setup.log 2>&1
    systemctl enable hostapd >> /var/log/playmi/wifi-setup.log 2>&1
    systemctl enable dnsmasq >> /var/log/playmi/wifi-setup.log 2>&1
    
    # Reiniciar servicios
    systemctl restart dhcpcd >> /var/log/playmi/wifi-setup.log 2>&1
    sleep 2
    systemctl restart hostapd >> /var/log/playmi/wifi-setup.log 2>&1
    systemctl restart dnsmasq >> /var/log/playmi/wifi-setup.log 2>&1
    
    log_success "Servicios WiFi habilitados"
}

# 10. Verificar configuración
verify_wifi_setup() {
    log_info "Verificando configuración WiFi..."
    
    local errors=0
    
    # Verificar bridge
    if ip addr show "$BRIDGE_NAME" > /dev/null 2>&1; then
        log_success "Bridge $BRIDGE_NAME activo"
    else
        log_error "Bridge $BRIDGE_NAME no encontrado"
        ((errors++))
    fi
    
    # Verificar servicios
    if systemctl is-active --quiet hostapd; then
        log_success "hostapd activo"
    else
        log_error "hostapd no está activo"
        ((errors++))
    fi
    
    if systemctl is-active --quiet dnsmasq; then
        log_success "dnsmasq activo"
    else
        log_error "dnsmasq no está activo"
        ((errors++))
    fi
    
    # Mostrar resumen
    echo ""
    echo "========================================"
    echo "RESUMEN DE CONFIGURACIÓN WiFi"
    echo "========================================"
    echo "SSID: $(grep -m1 "^ssid=" "$HOSTAPD_CONF" | cut -d= -f2)"
    echo "Interfaces configuradas: ${#WIFI_INTERFACES[@]}"
    echo "Capacidad total: $((${#WIFI_INTERFACES[@]} * MAX_USERS_PER_AP)) usuarios"
    echo "Rango IP: 192.168.4.10 - 192.168.4.$DHCP_END"
    echo "========================================"
    
    if [ $errors -eq 0 ]; then
        log_success "WiFi AP configurado correctamente"
        return 0
    else
        log_error "Se encontraron $errors errores en la configuración"
        return 1
    fi
}

# Función principal
main() {
    echo "========================================"
    echo "PLAYMI - Configuración WiFi Multi-AP"
    echo "========================================"
    
    # Verificar root
    if [ "$EUID" -ne 0 ]; then 
        log_error "Este script debe ejecutarse con sudo"
        exit 1
    fi
    
    # Crear directorio de logs
    mkdir -p /var/log/playmi
    
    # Ejecutar configuración
    detect_wifi_interfaces
    configure_wifi_mode
    create_bridge_config
    generate_hostapd_config
    configure_dnsmasq
    configure_network_interfaces
    configure_nat
    create_monitoring_script
    enable_services
    
    # Esperar a que los servicios inicien
    sleep 5
    
    # Verificar
    verify_wifi_setup
    
    echo ""
    log_info "Para ver logs en tiempo real use:"
    echo "  sudo journalctl -fu hostapd"
    echo "  sudo tail -f /var/log/playmi/dnsmasq.log"
}

# Ejecutar
main