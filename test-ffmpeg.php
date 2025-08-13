<?php
// test-ffmpeg.php
$output = shell_exec('ffmpeg -version 2>&1');
echo "<pre>";
echo "FFmpeg output:\n";
echo $output;
echo "</pre>";

if (strpos($output, 'version') !== false) {
    echo "✅ FFmpeg está instalado";
} else {
    echo "❌ FFmpeg NO está instalado o no está en el PATH";
}
?>