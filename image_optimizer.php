<?php
/**
 * Sistema de Otimização de Imagens
 * Redimensiona e comprime imagens automaticamente
 */

class ImageOptimizer {
    private $max_width = 800;
    private $max_height = 600;
    private $quality = 85;
    private $webp_quality = 80;
    
    public function __construct($max_width = 800, $max_height = 600, $quality = 85) {
        $this->max_width = $max_width;
        $this->max_height = $max_height;
        $this->quality = $quality;
    }
    
    /**
     * Otimiza uma imagem
     */
    public function optimize($source_path, $destination_path = null, $create_webp = true) {
        if (!file_exists($source_path)) {
            return false;
        }
        
        $destination_path = $destination_path ?: $source_path;
        $info = getimagesize($source_path);
        
        if (!$info) {
            return false;
        }
        
        $width = $info[0];
        $height = $info[1];
        $mime_type = $info['mime'];
        
        // Se a imagem já é pequena, apenas comprime
        if ($width <= $this->max_width && $height <= $this->max_height) {
            return $this->compress($source_path, $destination_path, $mime_type);
        }
        
        // Calcula novas dimensões mantendo proporção
        $ratio = min($this->max_width / $width, $this->max_height / $height);
        $new_width = intval($width * $ratio);
        $new_height = intval($height * $ratio);
        
        // Cria imagem redimensionada
        $source_image = $this->createImageFromFile($source_path, $mime_type);
        if (!$source_image) {
            return false;
        }
        
        $resized_image = imagecreatetruecolor($new_width, $new_height);
        
        // Preserva transparência para PNG
        if ($mime_type === 'image/png') {
            imagealphablending($resized_image, false);
            imagesavealpha($resized_image, true);
            $transparent = imagecolorallocatealpha($resized_image, 255, 255, 255, 127);
            imagefilledrectangle($resized_image, 0, 0, $new_width, $new_height, $transparent);
        }
        
        imagecopyresampled(
            $resized_image, $source_image,
            0, 0, 0, 0,
            $new_width, $new_height, $width, $height
        );
        
        // Salva imagem otimizada
        $result = $this->saveImage($resized_image, $destination_path, $mime_type);
        
        // Cria versão WebP se solicitado
        if ($create_webp && function_exists('imagewebp')) {
            $webp_path = $this->getWebpPath($destination_path);
            imagewebp($resized_image, $webp_path, $this->webp_quality);
        }
        
        // Limpa memória
        imagedestroy($source_image);
        imagedestroy($resized_image);
        
        return $result;
    }
    
    /**
     * Apenas comprime uma imagem sem redimensionar
     */
    public function compress($source_path, $destination_path, $mime_type = null) {
        if (!$mime_type) {
            $info = getimagesize($source_path);
            $mime_type = $info['mime'];
        }
        
        $source_image = $this->createImageFromFile($source_path, $mime_type);
        if (!$source_image) {
            return false;
        }
        
        $result = $this->saveImage($source_image, $destination_path, $mime_type);
        imagedestroy($source_image);
        
        return $result;
    }
    
    /**
     * Cria imagem a partir de arquivo
     */
    private function createImageFromFile($path, $mime_type) {
        switch ($mime_type) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            case 'image/webp':
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }
    
    /**
     * Salva imagem no formato correto
     */
    private function saveImage($image, $path, $mime_type) {
        switch ($mime_type) {
            case 'image/jpeg':
                return imagejpeg($image, $path, $this->quality);
            case 'image/png':
                return imagepng($image, $path, intval(9 - ($this->quality / 10)));
            case 'image/gif':
                return imagegif($image, $path);
            case 'image/webp':
                return imagewebp($image, $path, $this->webp_quality);
            default:
                return false;
        }
    }
    
    /**
     * Gera caminho para versão WebP
     */
    private function getWebpPath($original_path) {
        $path_info = pathinfo($original_path);
        return $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
    }
    
    /**
     * Processa upload de imagem com otimização
     */
    public function processUpload($file, $upload_dir, $filename = null) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mime_type = mime_content_type($file['tmp_name']);
        
        if (!in_array($mime_type, $allowed_types)) {
            return false;
        }
        
        // Gera nome único se não fornecido
        if (!$filename) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('img_', true) . '.' . $extension;
        }
        
        $upload_path = rtrim($upload_dir, '/') . '/' . $filename;
        
        // Cria diretório se não existir
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Move arquivo temporário
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            return false;
        }
        
        // Otimiza imagem
        $this->optimize($upload_path, $upload_path, true);
        
        return $filename;
    }
    
    /**
     * Gera imagem responsiva com múltiplos tamanhos
     */
    public function generateResponsiveImages($source_path, $base_name, $sizes = [400, 800, 1200]) {
        $results = [];
        
        foreach ($sizes as $size) {
            $optimizer = new ImageOptimizer($size, $size, $this->quality);
            $filename = $base_name . '_' . $size . 'w.jpg';
            $destination = dirname($source_path) . '/' . $filename;
            
            if ($optimizer->optimize($source_path, $destination, true)) {
                $results[$size] = $filename;
            }
        }
        
        return $results;
    }
}

// Função helper para uso fácil
function optimizeImage($source_path, $destination_path = null, $max_width = 800, $max_height = 600) {
    $optimizer = new ImageOptimizer($max_width, $max_height);
    return $optimizer->optimize($source_path, $destination_path);
}

// Função para processar upload com otimização
function processImageUpload($file, $upload_dir, $filename = null) {
    $optimizer = new ImageOptimizer();
    return $optimizer->processUpload($file, $upload_dir, $filename);
}
?>
