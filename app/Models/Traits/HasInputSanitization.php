<?php

namespace App\Models\Traits;

use Illuminate\Support\Str;

trait HasInputSanitization
{
    /**
     * Sanitize text input to prevent potential security issues
     */
    protected function sanitizeTextInput(?string $input, int $maxLength = 2048): ?string
    {
        if ($input === null) {
            return null;
        }

        // Remove HTML tags and limit length
        $sanitized = strip_tags(trim($input));
        return Str::limit($sanitized, $maxLength);
    }

    /**
     * Sanitize short text input (names, titles, etc.)
     */
    protected function sanitizeShortTextInput(?string $input, int $maxLength = 255): ?string
    {
        if ($input === null) {
            return null;
        }

        // Remove any potentially dangerous characters and limit length
        $sanitized = preg_replace('/[^\w\s\-\.]/', '', trim($input));
        return Str::limit($sanitized, $maxLength);
    }

    /**
     * Sanitize search input to prevent potential injection
     */
    protected function sanitizeSearchInput(string $input, int $maxLength = 100): string
    {
        // Remove any potentially dangerous characters and limit length
        // Remove script tags and their content, then clean remaining dangerous chars
        $cleaned = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $input);
        $sanitized = preg_replace('/[^a-zA-Z0-9\s\-\.\,\!\?\&\@\#\$\%\*\(\)\[\]\{\}]/', '', trim($cleaned));
        return Str::limit($sanitized, $maxLength);
    }

    /**
     * Sanitize URL input
     */
    protected function sanitizeUrlInput(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        // Basic URL validation and sanitization
        $sanitized = filter_var(trim($input), FILTER_SANITIZE_URL);

        // Ensure it's a valid URL
        if (filter_var($sanitized, FILTER_VALIDATE_URL)) {
            return $sanitized;
        }

        return null;
    }

    /**
     * Sanitize array of strings
     */
    protected function sanitizeStringArray(array $array, int $maxLength = 255): array
    {
        return array_map(function ($item) use ($maxLength) {
            if (is_string($item)) {
                return $this->sanitizeShortTextInput($item, $maxLength);
            }
            return $item;
        }, array_filter($array));
    }

    /**
     * Check for dangerous patterns in input
     */
    protected function hasDangerousPatterns(string $input): bool
    {
        $dangerousPatterns = config('security.input_sanitization.dangerous_patterns', [
            'javascript:',
            'vbscript:',
            'data:',
            'onload=',
            'onerror=',
            'onclick=',
        ]);

        foreach ($dangerousPatterns as $pattern) {
            if (stripos($input, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Comprehensive input sanitization
     */
    protected function sanitizeInput(string $input, string $type = 'text', int $maxLength = 2048): string
    {
        // Check for dangerous patterns first
        if ($this->hasDangerousPatterns($input)) {
            return '';
        }

        return match ($type) {
            'text' => $this->sanitizeTextInput($input, $maxLength),
            'short' => $this->sanitizeShortTextInput($input, $maxLength),
            'search' => $this->sanitizeSearchInput($input, $maxLength),
            'url' => $this->sanitizeUrlInput($input) ?? '',
            default => $this->sanitizeTextInput($input, $maxLength),
        };
    }
}
