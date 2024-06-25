<?php defined('BASEPATH') or exit('No direct script access allowed');

class TextAnalyzer
{

    public function sanitizeText($text)
    {
        // Remove tags HTML
        $text = strip_tags($text);

        // Decodifica entidades HTML
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $text;
    }

    public function tokenizeText($text)
    {
        // Usar regex para separar o texto em palavras, incluindo palavras com hífen
        preg_match_all('/\b[\w\p{L}\p{M}-]+\b/u', $text, $matches);
        return $matches[0];
    }

    public function identifyWords($text)
    {
        // Sanitizar o texto
        $sanitizedText = $this->sanitizeText($text);

        // Tokenizar o texto
        $tokens = $this->tokenizeText($sanitizedText);

        // Array para armazenar as palavras identificadas
        $words = [];

        // Loop para identificar palavras simples, compostas e com hífen
        foreach ($tokens as $token) {
            if (strpos($token, '-') !== false) {
                // Palavra com hífen
                $words['com_hifen'][] = $token;
            } elseif (strpos($token, ' ') !== false) {
                // Palavra composta por múltiplas palavras
                $words['compostas'][] = $token;
            } else {
                // Palavra simples
                $words['simples'][] = $token;
            }
        }

        return $words;
    }

    public function findWordInText($text, $word)
    {
        // Sanitizar o texto
        $sanitizedText = $this->sanitizeText($text);

        // Verificar se a palavra está presente no texto sanitizado
        if (stripos($sanitizedText, $word) !== false) {
            return true;
        }

        return false;
    }

    public function findWordInLinks($text, $word)
    {
        // Regex para encontrar URLs
        $urlPattern = '/\bhttps?:\/\/[^\s<>\'"]+\b/i';

        // Encontrar todas as ocorrências de URLs no texto
        preg_match_all($urlPattern, $text, $matches);

        foreach ($matches[0] as $url) {
            if (stripos($url, $word) !== false) {
                return true;
            }
        }

        return false;
    }

    public function findPhraseInText($text, $phrase)
    {
        // Sanitizar o texto
        $sanitizedText = $this->sanitizeText($text);

        // Verificar se a frase está presente no texto sanitizado
        if (stripos($sanitizedText, $phrase) !== false) {
            return true;
        }

        return false;
    }

    public function findPhraseInLinks($text, $phrase)
    {
        // Regex para encontrar URLs
        $urlPattern = '/\bhttps?:\/\/[^\s<>\'"]+\b/i';

        // Encontrar todas as ocorrências de URLs no texto
        preg_match_all($urlPattern, $text, $matches);

        foreach ($matches[0] as $url) {
            if (stripos($url, $phrase) !== false) {
                return true;
            }
        }

        return false;
    }
}
