<?php

namespace App\Service\Search;

class Highlighter
{
    public function highlight(string $text, string $query): string
    {
        if (trim($query) === '') {
            return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $words = array_filter(array_map('trim', preg_split('/\s+/', $query) ?: []));
        if (empty($words)) {
            return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $safe = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        foreach ($words as $word) {
            $safeWord = htmlspecialchars($word, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $pattern = '/(' . preg_quote($safeWord, '/') . ')/iu';
            $safe = preg_replace($pattern, '<mark>$1</mark>', $safe) ?? $safe;
        }

        return $safe;
    }
}
