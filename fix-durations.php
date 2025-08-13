<?php
require_once 'admin/config/system.php';
require_once 'admin/config/database.php';
require_once 'admin/models/Content.php';
require_once 'admin/vendor/autoload.php';

$db = Database::getInstance()->getConnection();
$getID3 = new getID3;

// Obtener todas las películas con duración 0
$stmt = $db->query("SELECT id, archivo_path FROM contenido WHERE tipo = 'pelicula' AND duracion = 0");
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
echo "Películas encontradas con duración 0: " . count($movies) . "\n\n";

foreach ($movies as $movie) {
    // Corregir la ruta - quitar el path absoluto que está mal guardado
    $archivo_path = $movie['archivo_path'];
    
    // Si tiene path absoluto de Windows, limpiarlo
    if (strpos($archivo_path, 'C:\\') !== false) {
        $archivo_path = 'movies/originals/' . basename($archivo_path);
        // Actualizar la ruta en la BD
        $db->prepare("UPDATE contenido SET archivo_path = ? WHERE id = ?")->execute([$archivo_path, $movie['id']]);
    }
    
    $filePath = __DIR__ . '/content/' . $archivo_path;
    
    echo "Procesando ID {$movie['id']}: ";
    
    if (file_exists($filePath)) {
        $info = $getID3->analyze($filePath);
        $duration = isset($info['playtime_seconds']) ? intval($info['playtime_seconds']) : 0;
        
        if ($duration > 0) {
            $stmt = $db->prepare("UPDATE contenido SET duracion = ? WHERE id = ?");
            $stmt->execute([$duration, $movie['id']]);
            echo "✅ Duración: " . gmdate("H:i:s", $duration) . "\n";
        } else {
            echo "❌ No se pudo obtener duración\n";
        }
    } else {
        echo "❌ Archivo no encontrado: $filePath\n";
    }
}
echo "</pre>";
?>