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
