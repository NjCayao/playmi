#!/bin/bash
#================================================================
# PLAYMI - Guía Rápida de Preparación de SD
# Para uso en Windows (con herramientas) o Linux
#================================================================

cat << 'GUIA'
========================================
PLAYMI - PREPARACIÓN RÁPIDA DE SD CARDS
========================================

=== HERRAMIENTAS NECESARIAS ===

WINDOWS:
1. Raspberry Pi Imager: https://www.raspberrypi.com/software/
2. Balena Etcher: https://www.balena.io/etcher/
3. WinSCP o FileZilla: Para copiar archivos
4. PuTTY: Para conexión SSH (opcional)

LINUX:
- dd, gzip (ya incluidos en la mayoría de distros)

=== PROCESO PASO A PASO ===

1. PREPARAR SD BASE (Primera vez solamente)
========================================
a) Descargar Raspberry Pi OS Lite (64-bit)
   https://www.raspberrypi.com/software/operating-systems/

b) Escribir imagen con Raspberry Pi Imager:
   - Elegir OS: Raspberry Pi OS Lite (64-bit)
   - Elegir Storage: Tu SD Card
   - Settings (engranaje):
     ✓ Set hostname: playmi
     ✓ Enable SSH
     ✓ Set username/password: pi / [tu-password]
     ✓ Configure WiFi (temporal para instalación)
   - Write

c) Insertar SD en Pi y encender

d) Conectar por SSH:
   ssh pi@playmi.local

e) Ejecutar instalación PLAYMI:
   sudo apt update
   sudo apt install git -y
   git clone [tu-repo-playmi]
   cd playmi/pi-system/install
   sudo ./setup.sh

f) Apagar Pi cuando termine:
   sudo shutdown -h now

g) CREAR IMAGEN MAESTRA (guardar para futuros usos)


2. ACTUALIZACIÓN MENSUAL DE CONTENIDO
========================================

OPCIÓN A - En la misma Pi (más simple):
---------------------------------------
a) Encender Pi con SD base
b) Conectar por SSH
c) Copiar contenido nuevo:
   scp -r peliculas/* pi@playmi.local:/var/www/playmi/content/movies/
   scp -r musica/* pi@playmi.local:/var/www/playmi/content/music/
   scp -r juegos/* pi@playmi.local:/var/www/playmi/content/games/

d) Actualizar fecha vencimiento:
   sudo nano /opt/playmi/config/company-config.json
   # Cambiar "expiry_date": "2024-09-30"

e) Limpiar y apagar:
   sudo rm -rf /var/www/playmi/temp/*
   sudo rm -rf /var/log/*.log
   sudo shutdown -h now

f) Clonar SD para cada bus


OPCIÓN B - Montando SD en PC Linux:
-----------------------------------
a) Insertar SD en lector USB
b) Montar particiones:
   sudo mkdir -p /mnt/pi-root
   sudo mount /dev/sdb2 /mnt/pi-root

c) Copiar contenido:
   sudo cp -r peliculas/* /mnt/pi-root/var/www/playmi/content/movies/
   sudo cp -r musica/* /mnt/pi-root/var/www/playmi/content/music/
   sudo cp -r juegos/* /mnt/pi-root/var/www/playmi/content/games/

d) Actualizar configuración:
   sudo nano /mnt/pi-root/opt/playmi/config/company-config.json

e) Desmontar:
   sudo umount /mnt/pi-root


3. CLONACIÓN RÁPIDA DE SD
========================================

WINDOWS - Usando Win32DiskImager:
---------------------------------
1. Leer SD original → archivo .img
2. Escribir archivo .img → SD nuevas

LINUX - Comando directo:
-----------------------
# Clonar SD a archivo:
sudo dd if=/dev/sdb of=playmi-mes.img bs=4M status=progress

# Escribir a nueva SD:
sudo dd if=playmi-mes.img of=/dev/sdb bs=4M status=progress

MÚLTIPLES SD - Hub USB:
----------------------
# Escribir a varias SD al mismo tiempo:
for dev in /dev/sdb /dev/sdc /dev/sdd; do
    sudo dd if=playmi-mes.img of=$dev bs=4M &
done
wait


4. CHECKLIST ANTES DE ENTREGAR
========================================
□ WiFi se crea correctamente (SSID: TransportesABC_WiFi)
□ Portal carga en http://192.168.4.1
□ Películas se reproducen
□ Música suena correctamente  
□ Juegos funcionan
□ Fecha de vencimiento actualizada
□ Etiqueta en SD con mes/año


5. ESTRUCTURA DE CARPETAS PARA CONTENIDO
========================================
contenido-2024-08/
├── peliculas/
│   ├── accion/
│   │   ├── pelicula1.mp4
│   │   ├── pelicula1_thumb.jpg
│   │   └── pelicula1.json (metadata)
│   └── comedia/
│       └── ...
├── musica/
│   ├── rock/
│   │   ├── cancion1.mp3
│   │   └── cancion1_thumb.jpg
│   └── pop/
│       └── ...
└── juegos/
    ├── game_tetris/
    │   ├── index.html
    │   └── assets/
    └── game_puzzle/
        └── ...


6. TIEMPOS ESTIMADOS
========================================
- Preparar SD base inicial: 45 minutos
- Actualizar contenido: 15-30 minutos
- Clonar 1 SD: 15 minutos
- Clonar 10 SD (con hub): 20 minutos
- Verificación rápida: 5 minutos


7. SOLUCIÓN DE PROBLEMAS COMUNES
========================================

WiFi no aparece:
- Verificar que hostapd está activo: sudo systemctl status hostapd
- Reiniciar servicios: sudo systemctl restart hostapd dnsmasq

Portal no carga:
- Verificar Apache: sudo systemctl status apache2
- Ver logs: sudo tail -f /var/log/apache2/error.log

Sin contenido:
- Verificar permisos: sudo chown -R www-data:www-data /var/www/playmi
- Verificar rutas: ls -la /var/www/playmi/content/

Fecha incorrecta:
- Configurar manualmente: sudo date -s "2024-08-12 10:00:00"


8. COMANDOS ÚTILES
========================================

# Ver estado del sistema:
sudo /opt/playmi/scripts/health-check.sh status

# Ver usuarios conectados:
sudo iw dev wlan0 station dump | grep Station

# Ver logs en tiempo real:
sudo journalctl -f -u hostapd

# Espacio disponible:
df -h

# Temperatura:
vcgencmd measure_temp

========================================
NOTAS IMPORTANTES:
- Mantener imagen base actualizada mensualmente
- Etiquetar claramente cada SD con fecha
- Probar al menos 1 SD antes de clonar masivamente
- Guardar backup de imágenes mensuales
========================================
GUIA DE CLONACIÓN RÁPIDA DE SD