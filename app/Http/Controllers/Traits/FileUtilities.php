<?php

namespace App\Http\Controllers\Traits;

trait FileUtilities
{
    /**
     * Nettoie le nom de fichier pour éviter les problèmes d'URL
     */
    protected function cleanFilename(string $filename): string
    {
        // Supprimer les accents
        $clean = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $filename);
        
        // Remplacer les caractères problématiques par des underscores
        $clean = preg_replace('/[^a-zA-Z0-9._-]/', '_', $clean);
        
        // Éviter les underscores multiples
        $clean = preg_replace('/_+/', '_', $clean);
        
        // Supprimer les underscores en début/fin
        $clean = trim($clean, '_');
        
        return $clean ?: 'file_' . time();
    }

    /**
     * Formate la taille d'un fichier
     */
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        
        $k = 1024;
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }

    /**
     * Vérifie si un fichier est autorisé
     */
    protected function isAllowedFileType(string $mimeType): bool
    {
        $allowedTypes = [
            // Documents
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            
            // Images
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            
            // Archives
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            
            // Autres
            'application/json',
            'application/xml',
        ];

        return in_array($mimeType, $allowedTypes);
    }

    /**
     * Génère un nom de fichier unique
     */
    protected function generateUniqueFilename(string $originalName, string $prefix = ''): string
    {
        $cleanName = $this->cleanFilename($originalName);
        $timestamp = time();
        $uniqid = uniqid();
        
        return $prefix ? "{$prefix}_{$timestamp}_{$uniqid}_{$cleanName}" : "{$timestamp}_{$uniqid}_{$cleanName}";
    }
}