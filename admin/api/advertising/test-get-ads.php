<?php
session_start();
$_SESSION['admin_logged_in'] = true; // Simular login

require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../models/Advertising.php';

$companyId = $_GET['company_id'] ?? 1;
$advertisingModel = new Advertising();

echo "<h2>Test de publicidad para empresa ID: $companyId</h2>";

echo "<h3>Videos:</h3>";
$videos = $advertisingModel->getVideos($companyId);
echo "<pre>";
print_r($videos);
echo "</pre>";

echo "<h3>Banners:</h3>";
$banners = $advertisingModel->getBanners($companyId);
echo "<pre>";
print_r($banners);
echo "</pre>";

echo "<h3>Videos activos:</h3>";
$activeVideos = array_filter($videos, function($v) { return $v['activo'] == 1; });
echo "<pre>";
print_r($activeVideos);
echo "</pre>";

echo "<h3>Banners activos:</h3>";
$activeBanners = array_filter($banners, function($b) { return $b['activo'] == 1; });
echo "<pre>";
print_r($activeBanners);
echo "</pre>";
?>