<?php

declare(strict_types=1);

namespace App;

class ExamScoring
{
    public const array PASSING_GRADES = ['TWK' => 65, 'TIU' => 80, 'TKP' => 16];

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<int|string, int|null>  $answers
     * @return array{score: int, max: int, correct: int, wrong: int, blank: int, weighted: int, sections: array<string, array<string, int|string>>}
     */
    public static function evaluate(array $definition, array $answers): array
    {
        $result = ['score' => 0, 'max' => 0, 'correct' => 0, 'wrong' => 0, 'blank' => 0, 'weighted' => 0, 'sections' => []];
        $points = (int) $definition['pointsPerCorrect'];
        $penalty = (int) $definition['penaltyWrong'];

        foreach ($definition['questions'] as $question) {
            $code = $question['section'] ?? 'Umum';
            $result['sections'][$code] ??= ['section' => $code, 'total' => 0, 'correct' => 0, 'wrong' => 0,
                'blank' => 0, 'weighted' => 0, 'weighted_total' => 0, 'score' => 0, 'max' => 0];
            $section = &$result['sections'][$code];
            $weighted = ($question['scoringType'] ?? 'binary') === 'weighted';
            $scores = $question['optionScores'] ?? [];
            $maximum = $weighted ? (int) max($scores ?: [5]) : $points;
            $chosen = $answers[(string) $question['id']] ?? null;
            $earned = 0;
            $section['total']++;
            $section['max'] += $maximum;
            $result['max'] += $maximum;

            if ($weighted) {
                $section['weighted_total']++;
            }
            if ($chosen === null) {
                $outcome = 'blank';
            } elseif ($weighted) {
                $outcome = 'weighted';
                $earned = (int) ($scores[(int) $chosen] ?? 0);
            } elseif ((int) $chosen === (int) $question['answer']) {
                $outcome = 'correct';
                $earned = $points;
            } else {
                $outcome = 'wrong';
                $earned = -$penalty;
            }

            $result[$outcome]++;
            $section[$outcome]++;
            $result['score'] += $earned;
            $section['score'] += $earned;
            unset($section);
        }

        $result['score'] = max(0, $result['score']);
        foreach ($result['sections'] as &$section) {
            $section['score'] = max(0, $section['score']);
        }

        return $result;
    }
}
