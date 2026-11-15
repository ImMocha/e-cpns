<?php

declare(strict_types=1);

namespace App;

class QuestionExplanation
{
    public const MAX_IMAGES = 8;

    public const IMAGE_ID_PATTERN = '[a-f0-9]{32}';

    /** @return list<string> */
    public static function imageIds(?string $html): array
    {
        $ids = [];
        self::transform($html, function (string $id) use (&$ids): null {
            $ids[$id] = true;

            return null;
        });

        return array_keys($ids);
    }

    /** @param callable(string): ?string $resolveImage */
    public static function transform(?string $html, callable $resolveImage): string
    {
        return ArticleContent::sanitize($html, function (string $src) use ($resolveImage): ?string {
            if (preg_match('~^/question-explanation-images/('.self::IMAGE_ID_PATTERN.')\.png$~D', $src, $matches)) {
                return $resolveImage($matches[1]);
            }

            return $src;
        });
    }
}
