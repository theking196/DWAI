<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = ['user_id', 'key', 'value', 'type'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) return $default;
        
        return match($setting->type) {
            'boolean' => $setting->value === 'true' || $setting->value === '1',
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public static function set(string $key, $value, string $type = 'string'): void
    {
        $storedValue = match($type) {
            'boolean' => $value ? 'true' : 'false',
            'json' => json_encode($value),
            default => $value,
        };

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $storedValue, 'type' => $type]
        );
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        return (bool) static::get($key, $default);
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return (int) static::get($key, $default);
    }

    public static function getJson(string $key, array $default = []): array
    {
        return (array) static::get($key, $default);
    }

    public static function forget(string $key): void
    {
        static::where('key', $key)->delete();
    }

    public static function all(): array
    {
        $settings = static::all();
        $result = [];
        
        foreach ($settings as $s) {
            $result[$s->key] = match($s->type) {
                'boolean' => $s->value === 'true',
                'integer' => (int) $s->value,
                'json' => json_decode($s->value, true),
                default => $s->value,
            };
        }
        
        return $result;
    }
}

    // ============================================================
    // DWAI Defaults
    // ============================================================

    public static function initDefaults(): void
    {
        // App
        self::set('app.name', 'DWAI Studio', 'string');
        self::set('app.local_mode', true, 'boolean');
        
        // AI Providers
        self::set('ai.text_provider', 'mock', 'string');
        self::set('ai.image_provider', 'mock', 'string');
        self::set('ai.storyboard_provider', 'mock', 'string');
        
        // API Keys (would be env in production)
        self::set('ai.openai_key', '', 'string');
        self::set('ai.replicate_key', '', 'string');
        
        // Storage
        self::set('storage.uploads_path', 'uploads', 'string');
        self::set('storage.exports_path', 'exports', 'string');
        
        // Visual Defaults
        self::set('visual.default_style', null, 'string');
        self::set('visual.default_aspect_ratio', '16:9', 'string');
        
        // Generation Defaults
        self::set('generation.text_max_tokens', 1000, 'integer');
        self::set('generation.image_count', 1, 'integer');
        self::set('generation.storyboard_frames', 4, 'integer');
        
        // Features
        self::set('features.semantic_search', true, 'boolean');
        self::set('features.auto_embedding', true, 'boolean');
        self::set('features.conflict_detection', true, 'boolean');
    }

    public static function getAIConfig(): array
    {
        return [
            'text' => [
                'provider' => self::get('ai.text_provider', 'mock'),
                'model' => self::get('ai.text_model', 'gpt-4'),
            ],
            'image' => [
                'provider' => self::get('ai.image_provider', 'mock'),
                'model' => self::get('ai.image_model', 'sdxl'),
            ],
            'storyboard' => [
                'provider' => self::get('ai.storyboard_provider', 'mock'),
                'model' => self::get('ai.storyboard_model', 'sdxl'),
            ],
        ];
    }

    public static function getGenerationDefaults(): array
    {
        return [
            'text_max_tokens' => self::getInt('generation.text_max_tokens', 1000),
            'image_count' => self::getInt('generation.image_count', 1),
            'storyboard_frames' => self::getInt('generation.storyboard_frames', 4),
        ];
    }

    // ============================================================
    // Local-Only Configuration
    // ============================================================

    /**
     * Ensure local-only mode is enforced.
     */
    public static function enforceLocalMode(): void
    {
        // Always ensure local mode is on for private studio
        self::set('app.local_mode', true, 'boolean');
        self::set('app.public_access', false, 'boolean');
        
        // Disable any cloud features by default
        self::set('features.cloud_sync', false, 'boolean');
        self::set('features.share_link', false, 'boolean');
    }

    /**
     * Check if running in local-only mode.
     */
    public static function isLocalMode(): bool
    {
        return self::getBool('app.local_mode', true);
    }

    /**
     * Check if public access is enabled (should be false).
     */
    public static function isPublicAccessEnabled(): bool
    {
        return self::getBool('app.public_access', false);
    }

    /**
     * Get security config for local-only.
     */
    public static function getSecurityConfig(): array
    {
        return [
            'local_mode' => self::isLocalMode(),
            'public_access' => self::isPublicAccessEnabled(),
            'cloud_sync' => self::getBool('features.cloud_sync', false),
            'share_links' => self::getBool('features.share_link', false),
            'api_auth_required' => true,
            'session_timeout_minutes' => self::getInt('security.session_timeout', 480),
        ];
    }

    // ============================================================
    // AI Provider Switching
    // ============================================================

    public static function configureAIProvider(string $type, string $provider, array $config = []): void
    {
        self::set("ai.{$type}_provider", $provider, 'string');
        
        // Store provider-specific config
        if (!empty($config)) {
            self::set("ai.{$type}_config", $config, 'json');
        }
    }

    public static function getAIProvider(string $type): string
    {
        return self::get("ai.{$type}_provider", 'mock');
    }

    public static function getAIProviderConfig(string $type): array
    {
        return self::getJson("ai.{$type}_config", []);
    }

    public static function setProviderAPIKey(string $provider, string $key): void
    {
        self::set("ai.{$provider}_key", $key, 'string');
    }

    public static function getProviderAPIKey(string $provider): ?string
    {
        $key = self::get("ai.{$provider}_key");
        return $key ?: null;
    }

    public static function isProviderConfigured(string $type): bool
    {
        $provider = self::getAIProvider($type);
        
        if ($provider === 'mock') return true;
        
        // Check if API key exists for real providers
        $key = self::getProviderAPIKey($provider);
        return !empty($key);
    }

    /**
     * Pre-configured provider presets.
     */
    public static function useMockProviders(): void
    {
        self::set('ai.text_provider', 'mock');
        self::set('ai.image_provider', 'mock');
        self::set('ai.storyboard_provider', 'mock');
    }

    public static function useOpenAI(string $apiKey, string $model = 'gpt-4'): void
    {
        self::configureAIProvider('text', 'openai', ['model' => $model]);
        self::setProviderAPIKey('openai', $apiKey);
    }

    public static function useReplicate(string $apiKey, string $model = 'sdxl'): void
    {
        self::configureAIProvider('image', 'replicate', ['model' => $model]);
        self::setProviderAPIKey('replicate', $apiKey);
    }

    // ============================================================
    // Provider Status
    // ============================================================

    public static function getProviderStatus(): array
    {
        return [
            'text' => [
                'provider' => self::getAIProvider('text'),
                'configured' => self::isProviderConfigured('text'),
                'config' => self::getAIProviderConfig('text'),
            ],
            'image' => [
                'provider' => self::getAIProvider('image'),
                'configured' => self::isProviderConfigured('image'),
                'config' => self::getAIProviderConfig('image'),
            ],
            'storyboard' => [
                'provider' => self::getAIProvider('storyboard'),
                'configured' => self::isProviderConfigured('storyboard'),
                'config' => self::getAIProviderConfig('storyboard'),
            ],
        ];
    }

    // ============================================================
    // Default Behavior Settings
    // ============================================================

    public static function initBehaviorDefaults(): void
    {
        // Default Project
        self::set('behavior.default_project', null, 'integer');
        
        // Default Visual Style
        self::set('behavior.default_style_id', null, 'integer');
        self::set('behavior.apply_style_to_sessions', true, 'boolean');
        
        // Auto-save
        self::set('behavior.auto_save_enabled', true, 'boolean');
        self::set('behavior.auto_save_interval_seconds', 30, 'integer');
        self::set('behavior.auto_save_draft', true, 'boolean');
        
        // Conflict Detection
        self::set('behavior.conflict_strictness', 'warning', 'string'); // error, warning, info, off
        self::set('behavior.auto_resolve_low_severity', false, 'boolean');
        
        // Memory Promotion
        self::set('behavior.promotion_requires_review', true, 'boolean');
        self::set('behavior.auto_promote_patterns', false, 'boolean');
        self::set('behavior.default_promotion_importance', 'minor', 'string'); // minor, moderate, important
    }

    public static function getDefaultProject(): ?int
    {
        return self::getInt('behavior.default_project');
    }

    public static function setDefaultProject(int $projectId): void
    {
        self::set('behavior.default_project', $projectId, 'integer');
    }

    public static function getAutoSaveConfig(): array
    {
        return [
            'enabled' => self::getBool('behavior.auto_save_enabled', true),
            'interval_seconds' => self::getInt('behavior.auto_save_interval_seconds', 30),
            'save_draft' => self::getBool('behavior.auto_save_draft', true),
        ];
    }

    public static function getConflictStrictness(): string
    {
        return self::get('behavior.conflict_strictness', 'warning');
    }

    public static function getPromotionConfig(): array
    {
        return [
            'requires_review' => self::getBool('behavior.promotion_requires_review', true),
            'auto_promote' => self::getBool('behavior.auto_promote_patterns', false),
            'default_importance' => self::get('behavior.default_promotion_importance', 'minor'),
        ];
    }

    public static function getBehaviorConfig(): array
    {
        return [
            'default_project' => self::getDefaultProject(),
            'auto_save' => self::getAutoSaveConfig(),
            'conflict_strictness' => self::getConflictStrictness(),
            'promotion' => self::getPromotionConfig(),
        ];
    }
