<?php
/**
 * Sistema de Cache Simples
 * Armazena dados em arquivos para melhorar performance
 */

class Cache {
    private $cache_dir;
    private $default_ttl = 300; // 5 minutos por padrão
    
    public function __construct($cache_dir = 'cache/') {
        $this->cache_dir = $cache_dir;
        
        // Criar diretório de cache se não existir
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    /**
     * Armazena dados no cache
     */
    public function set($key, $data, $ttl = null) {
        $ttl = $ttl ?? $this->default_ttl;
        $cache_file = $this->getCacheFile($key);
        
        $cache_data = [
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        return file_put_contents($cache_file, serialize($cache_data)) !== false;
    }
    
    /**
     * Recupera dados do cache
     */
    public function get($key) {
        $cache_file = $this->getCacheFile($key);
        
        if (!file_exists($cache_file)) {
            return null;
        }
        
        $cache_data = unserialize(file_get_contents($cache_file));
        
        // Verificar se o cache expirou
        if ($cache_data['expires'] < time()) {
            $this->delete($key);
            return null;
        }
        
        return $cache_data['data'];
    }
    
    /**
     * Verifica se uma chave existe no cache
     */
    public function has($key) {
        return $this->get($key) !== null;
    }
    
    /**
     * Remove uma chave do cache
     */
    public function delete($key) {
        $cache_file = $this->getCacheFile($key);
        if (file_exists($cache_file)) {
            return unlink($cache_file);
        }
        return true;
    }
    
    /**
     * Limpa todo o cache
     */
    public function clear() {
        $files = glob($this->cache_dir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }
    
    /**
     * Gera o caminho do arquivo de cache
     */
    private function getCacheFile($key) {
        return $this->cache_dir . md5($key) . '.cache';
    }
    
    /**
     * Cache com callback - executa função se não estiver em cache
     */
    public function remember($key, $callback, $ttl = null) {
        $data = $this->get($key);
        
        if ($data === null) {
            $data = $callback();
            $this->set($key, $data, $ttl);
        }
        
        return $data;
    }
}

// Instância global do cache
$cache = new Cache();
?>
