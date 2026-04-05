<?php

namespace App\Services\AI\Tools;

use Illuminate\Support\Facades\Log;

class ToolRegistry
{
    protected static array $tools = [];
    protected static bool $initialized = false;

    public static function register(AIToolInterface $tool): void
    {
        self::$tools[$tool->getName()] = $tool;
    }

    public static function all(): array
    {
        if (!self::$initialized) {
            self::registerDefaults();
            self::$initialized = true;
        }
        return self::$tools;
    }

    public static function get(string $name): ?AIToolInterface
    {
        self::all();
        return self::$tools[$name] ?? null;
    }

    public static function has(string $name): bool
    {
        self::all();
        return isset(self::$tools[$name]);
    }

    public static function getToolList(): array
    {
        self::all();
        $list = [];
        foreach (self::$tools as $name => $tool) {
            $list[$name] = ['description' => $tool->getDescription(), 'input_schema' => $tool->getInputSchema()];
        }
        return $list;
    }

    public static function execute(string $name, array $input, array $context = []): array
    {
        $tool = self::get($name);
        if (!$tool) return ['success' => false, 'error' => "Tool not found: {$name}"];
        return $tool->execute($input, $context);
    }

    protected static function registerDefaults(): void
    {
        self::register(new SearchCanonTool());
        self::register(new GetProjectContextTool());
        self::register(new GetReferenceImagesTool());
        self::register(new GenerateImageTool());
        self::register(new GenerateTextTool());
        self::register(new UpdateCanonTool());
        self::register(new AddCanonTool());
        self::register(new ValidateTimelineTool());
    }
}
