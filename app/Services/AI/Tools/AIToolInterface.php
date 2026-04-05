<?php

namespace App\Services\AI\Tools;

interface AIToolInterface
{
    /**
     * Get tool name.
     */
    public function getName(): string;

    /**
     * Get tool description.
     */
    public function getDescription(): string;

    /**
     * Get input schema.
     */
    public function getInputSchema(): array;

    /**
     * Execute tool with input.
     */
    public function execute(array $input, array $context = []): array;
}
