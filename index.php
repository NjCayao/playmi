<?php
// index.php en la raíz de /playmi/
// En desarrollo, redirigir al portal de pasajeros
// En producción, el DNS del Pi manejará esto automáticamente
header('Location: passenger-portal/');
exit;
?>