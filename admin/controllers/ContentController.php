<?php

/**
 * Controlador de Contenido PLAYMI
 * Maneja películas, música y juegos
 */

require_once 'BaseController.php';
require_once __DIR__ . '/../models/Content.php';

class ContentController extends BaseController
{
    private $contentModel;

    public function __construct()
    {
        parent::__construct();
        $this->contentModel = new Content();
    }

    /**
     * Listar contenido con filtros
     */
    public function index()
    {
        try {
            $this->requireAuth();

            // Obtener parámetros
            $page = (int)$this->getParam('page', 1);
            $search = $this->getParam('search', '');
            $tipo = $this->getParam('tipo', '');
            $estado = $this->getParam('estado', '');
            $categoria = $this->getParam('categoria', '');

            // Preparar filtros
            $filters = [];
            if (!empty($search)) $filters['search'] = $search;
            if (!empty($tipo)) $filters['tipo'] = $tipo;
            if (!empty($estado)) $filters['estado'] = $estado;
            if (!empty($categoria)) $filters['categoria'] = $categoria;

            // Obtener contenido
            $result = $this->contentModel->searchContent($filters, $page, RECORDS_PER_PAGE);

            // Estadísticas
            $stats = [
                'total' => $this->contentModel->count(),
                'movies' => $this->contentModel->count("tipo = 'pelicula'"),
                'music' => $this->contentModel->count("tipo = 'musica'"),
                'games' => $this->contentModel->count("tipo = 'juego'"),
                'active' => $this->contentModel->count("estado = 'activo'"),
                'processing' => $this->contentModel->count("estado = 'procesando'")
            ];

            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => true,
                    'data' => $result['data'],
                    'pagination' => $result,
                    'stats' => $stats
                ]);
            }

            return [
                'content' => $result['data'],
                'pagination' => $result,
                'filters' => $filters,
                'stats' => $stats
            ];
        } catch (Exception $e) {
            $this->logError("Error en content index: " . $e->getMessage());
            if ($this->isAjax()) {
                $this->jsonResponse(['error' => 'Error cargando contenido'], 500);
            }
            return ['error' => true];
        }
    }

    /**
     * Subir nuevo contenido
     */
    public function upload()
    {
        try {
            $this->requireAuth();

            if (!$this->isPost()) {
                return $this->showUploadForm();
            }

            // Validar datos básicos
            $data = $this->sanitizeInput($_POST);

            $errors = $this->validateInput($data, [
                'titulo' => [
                    'required' => true,
                    'label' => 'Título',
                    'max_length' => 200
                ],
                'tipo' => [
                    'required' => true,
                    'label' => 'Tipo de contenido'
                ]
            ]);

            if (!empty($errors)) {
                $this->jsonResponse(['error' => 'Datos inválidos', 'errors' => $errors], 400);
            }

            // Manejar archivo principal
            if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
                $this->jsonResponse(['error' => 'Archivo requerido'], 400);
            }

            // Configurar rutas según tipo
            $uploadPath = UPLOADS_PATH . $data['tipo'] . 's/';
            $allowedTypes = $this->getAllowedTypes($data['tipo']);
            $maxSize = $this->getMaxSize($data['tipo']);

            // Subir archivo principal
            $uploadResult = $this->handleFileUpload(
                $_FILES['archivo'],
                $uploadPath,
                $allowedTypes,
                $maxSize
            );

            if (!$uploadResult['success']) {
                $this->jsonResponse(['error' => $uploadResult['error']], 400);
            }

            // Preparar datos para guardar (CAMPOS ACTUALIZADOS)
            $contentData = [
                'titulo' => $data['titulo'],
                'descripcion' => $data['descripcion'] ?? null,
                'tipo' => $data['tipo'],
                'categoria' => $data['categoria'] ?? null,
                'genero' => $data['genero'] ?? null,
                'anio_lanzamiento' => $data['anio_lanzamiento'] ?? null,  // ACTUALIZADO
                'calificacion' => $data['calificacion'] ?? null,
                'archivo_path' => $data['tipo'] . 's/' . $uploadResult['filename'],
                'tamanio_archivo' => $uploadResult['size'],  // ACTUALIZADO
                'estado' => 'procesando',
                'archivo_hash' => hash_file('sha256', $uploadResult['path'])
            ];

            // Calcular duración para videos y música
            if (in_array($data['tipo'], ['pelicula', 'musica'])) {
                $contentData['duracion'] = $this->getMediaDuration($uploadResult['path']);
            }

            // Manejar thumbnail si se subió
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                $thumbResult = $this->handleFileUpload(
                    $_FILES['thumbnail'],
                    UPLOADS_PATH . 'thumbnails/',
                    ['jpg', 'jpeg', 'png', 'webp'],
                    MAX_IMAGE_SIZE
                );

                if ($thumbResult['success']) {
                    $contentData['thumbnail_path'] = 'thumbnails/' . $thumbResult['filename'];
                }
            }

            // Crear registro
            $contentId = $this->contentModel->create($contentData);

            if ($contentId) {
                // Registrar actividad
                $this->logActivity('upload', 'contenido', $contentId, null, $contentData);

                // Procesar en segundo plano (optimización, conversión, etc.)
                $this->queueContentProcessing($contentId);

                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Contenido subido exitosamente',
                    'content_id' => $contentId
                ]);
            } else {
                // Si falla, eliminar archivos subidos
                @unlink($uploadResult['path']);
                if (isset($thumbResult)) {
                    @unlink($thumbResult['path']);
                }

                $this->jsonResponse(['error' => 'Error al guardar contenido'], 500);
            }
        } catch (Exception $e) {
            $this->logError("Error en content upload: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error interno del sistema'], 500);
        }
    }

    /**
     * Editar contenido
     */
    public function edit($id)
    {
        try {
            $this->requireAuth();

            $content = $this->contentModel->findById($id);
            if (!$content) {
                $this->setMessage('Contenido no encontrado', MSG_ERROR);
                $this->redirect('views/content/index.php');
            }

            if ($this->isPost()) {
                return $this->update($id);
            }

            // Obtener categorías y géneros según tipo
            $categories = $this->contentModel->getCategoriesByType($content['tipo']);
            $genres = $this->contentModel->getGenresByType($content['tipo']);

            return [
                'content' => $content,
                'categories' => $categories,
                'genres' => $genres
            ];
        } catch (Exception $e) {
            $this->logError("Error en content edit: " . $e->getMessage());
            $this->setMessage('Error cargando contenido', MSG_ERROR);
            $this->redirect('views/content/index.php');
        }
    }

    /**
     * Actualizar contenido
     */
    public function update($id)
    {
        try {
            $this->requireAuth();

            $existingContent = $this->contentModel->findById($id);
            if (!$existingContent) {
                $this->jsonResponse(['error' => 'Contenido no encontrado'], 404);
            }

            // Validar datos
            $data = $this->sanitizeInput($_POST);

            $errors = $this->validateInput($data, [
                'titulo' => [
                    'required' => true,
                    'label' => 'Título',
                    'max_length' => 200
                ]
            ]);

            if (!empty($errors)) {
                $this->jsonResponse(['error' => 'Datos inválidos', 'errors' => $errors], 400);
            }

            // Preparar datos para actualizar (CAMPOS ACTUALIZADOS)
            $updateData = [
                'titulo' => $data['titulo'],
                'descripcion' => $data['descripcion'] ?? null,
                'categoria' => $data['categoria'] ?? null,
                'genero' => $data['genero'] ?? null,
                'anio_lanzamiento' => $data['anio_lanzamiento'] ?? null,  // ACTUALIZADO
                'calificacion' => $data['calificacion'] ?? null,
                'estado' => $data['estado'] ?? $existingContent['estado']
            ];

            // Manejar nuevo archivo si se subió
            if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
                $uploadPath = UPLOADS_PATH . $existingContent['tipo'] . 's/';
                $allowedTypes = $this->getAllowedTypes($existingContent['tipo']);
                $maxSize = $this->getMaxSize($existingContent['tipo']);

                $uploadResult = $this->handleFileUpload(
                    $_FILES['archivo'],
                    $uploadPath,
                    $allowedTypes,
                    $maxSize
                );

                if ($uploadResult['success']) {
                    // Eliminar archivo anterior
                    $oldPath = UPLOADS_PATH . $existingContent['archivo_path'];
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }

                    $updateData['archivo_path'] = $existingContent['tipo'] . 's/' . $uploadResult['filename'];
                    $updateData['tamanio_archivo'] = $uploadResult['size'];  // ACTUALIZADO
                    $updateData['archivo_hash'] = hash_file('sha256', $uploadResult['path']);

                    if (in_array($existingContent['tipo'], ['pelicula', 'musica'])) {
                        $updateData['duracion'] = $this->getMediaDuration($uploadResult['path']);
                    }
                }
            }

            // Actualizar
            $result = $this->contentModel->update($id, $updateData);

            if ($result) {
                $this->logActivity('update', 'contenido', $id, $existingContent, $updateData);

                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Contenido actualizado exitosamente'
                ]);
            } else {
                $this->jsonResponse(['error' => 'Error al actualizar contenido'], 500);
            }
        } catch (Exception $e) {
            $this->logError("Error en content update: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error interno del sistema'], 500);
        }
    }

    /**
     * Eliminar contenido
     */
    public function delete($id)
    {
        try {
            $this->requireAuth();

            if (!$this->isPost()) {
                $this->jsonResponse(['error' => 'Método no permitido'], 405);
            }

            $content = $this->contentModel->findById($id);
            if (!$content) {
                $this->jsonResponse(['error' => 'Contenido no encontrado'], 404);
            }

            // Eliminar archivos físicos
            $filesToDelete = [
                UPLOADS_PATH . $content['archivo_path'],
                UPLOADS_PATH . $content['thumbnail_path'],
                UPLOADS_PATH . $content['trailer_path']
            ];

            foreach ($filesToDelete as $file) {
                if ($file && file_exists($file)) {
                    @unlink($file);
                }
            }

            // Eliminar registro
            $result = $this->contentModel->delete($id);

            if ($result) {
                $this->logActivity('delete', 'contenido', $id, $content, null);

                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Contenido eliminado exitosamente'
                ]);
            } else {
                $this->jsonResponse(['error' => 'Error al eliminar contenido'], 500);
            }
        } catch (Exception $e) {
            $this->logError("Error en content delete: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error interno del sistema'], 500);
        }
    }

    /**
     * Mostrar formulario de subida
     */
    private function showUploadForm()
    {
        return [
            'content_types' => [
                'pelicula' => 'Película',
                'musica' => 'Música',
                'juego' => 'Juego'
            ],
            'categories' => [
                'pelicula' => ['Acción', 'Comedia', 'Drama', 'Terror', 'Ciencia Ficción', 'Documental'],
                'musica' => ['Pop', 'Rock', 'Salsa', 'Cumbia', 'Reggaeton', 'Folclore'],
                'juego' => ['Puzzle', 'Arcade', 'Aventura', 'Estrategia', 'Educativo']
            ],
            'ratings' => ['G', 'PG', 'PG-13', 'R', 'NC-17']
        ];
    }

    /**
     * Obtener tipos permitidos según contenido
     */
    private function getAllowedTypes($contentType)
    {
        $types = [
            'pelicula' => ['mp4', 'avi', 'mkv', 'mov', 'wmv', 'flv'],
            'musica' => ['mp3', 'wav', 'flac', 'm4a', 'mp4', 'avi', 'mkv'], // Agregados formatos de video
            'juego' => ['html', 'zip', 'rar', '7z']
        ];

        return $types[$contentType] ?? [];
    }

    /**
     * Obtener tamaño máximo según tipo
     */
    private function getMaxSize($contentType)
    {
        $sizes = [
            'pelicula' => MAX_VIDEO_SIZE,
            'musica' => MAX_AUDIO_SIZE,
            'juego' => MAX_GAME_SIZE // Los juegos pueden ser grandes
        ];

        return $sizes[$contentType] ?? MAX_UPLOAD_SIZE;
    }

    /**
     * Obtener duración de media
     */
    private function getMediaDuration($filePath)
    {
        // Implementar con ffprobe o getID3
        // Por ahora retornar 0
        return 0;
    }

    /**
     * Encolar procesamiento de contenido
     */
    private function queueContentProcessing($contentId)
    {
        // Aquí se puede implementar un sistema de colas
        // para procesar el contenido en segundo plano
        // (conversión de formatos, generación de thumbnails, etc.)
    }
}
