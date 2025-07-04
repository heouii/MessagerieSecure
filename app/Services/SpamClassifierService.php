<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SpamClassifierService
{
    private string $spamApiUrl;
    private int $timeout;
    
    public function __construct()
    {
        $this->spamApiUrl = config('services.spam_classifier.url', 'http://spam_classifier:8081');
        $this->timeout = config('services.spam_classifier.timeout', 5);
    }
    
    /**
     * Classifier un email comme spam ou ham
     */
    public function classifyEmail(string $emailContent): array
    {
        $cacheKey = 'spam_check_' . md5($emailContent);
        
        // Vérifier le cache (5 minutes)
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        try {
            Log::info('🤖 Classification spam démarrée', [
                'content_length' => strlen($emailContent)
            ]);
            
            $response = Http::timeout($this->timeout)
                ->post("{$this->spamApiUrl}/classify", [
                    'text' => $emailContent
                ]);
            
            if ($response->successful()) {
                $result = $response->json();
                
                $classification = [
                    'is_spam' => $result['is_spam'] ?? false,
                    'spam_probability' => $result['spam_probability'] ?? 0.0,
                    'confidence' => $result['confidence'] ?? 'unknown',
                    'service_available' => true,
                    'processed_at' => now()
                ];
                
                // Mettre en cache pour 5 minutes
                Cache::put($cacheKey, $classification, 300);
                
                Log::info('✅ Classification réussie', $classification);
                
                return $classification;
            }
            
            Log::warning('⚠️ API spam indisponible', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return $this->getFallbackResult();
            
        } catch (\Exception $e) {
            Log::error('❌ Erreur classification spam', [
                'error' => $e->getMessage()
            ]);
            
            return $this->getFallbackResult();
        }
    }
    
    /**
     * Vérifier que le service spam est disponible
     */
    public function isServiceAvailable(): bool
    {
        try {
            $response = Http::timeout(2)->get("{$this->spamApiUrl}/health");
            
            if ($response->successful()) {
                $data = $response->json();
                return ($data['status'] ?? '') === 'ok' && ($data['model_loaded'] ?? false);
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::debug('Service spam indisponible', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Résultat de secours si l'API n'est pas disponible
     */
    private function getFallbackResult(): array
    {
        return [
            'is_spam' => false,
            'spam_probability' => 0.0,
            'confidence' => 'fallback',
            'service_available' => false,
            'processed_at' => now()
        ];
    }
    
    /**
     * Classification avec détails pour l'admin
     */
    public function classifyWithDetails(string $emailContent, string $subject = '', string $fromEmail = ''): array
    {
        $classification = $this->classifyEmail($emailContent);
        
        // Ajouter des détails contextuels
        $classification['details'] = [
            'subject_length' => strlen($subject),
            'content_length' => strlen($emailContent),
            'from_domain' => $fromEmail ? substr(strrchr($fromEmail, "@"), 1) : null,
            'suspicious_patterns' => $this->detectSuspiciousPatterns($emailContent, $subject),
        ];
        
        return $classification;
    }
    
    /**
     * Détecter des patterns suspects (backup si API indisponible)
     */
    private function detectSuspiciousPatterns(string $content, string $subject): array
    {
        $patterns = [];
        $suspiciousWords = ['free', 'urgent', 'click here', 'money', 'win', 'lottery', 'viagra'];
        
        foreach ($suspiciousWords as $word) {
            if (stripos($content . ' ' . $subject, $word) !== false) {
                $patterns[] = $word;
            }
        }
        
        return $patterns;
    }
}