<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProgramContent
{
    public static function validate(array $input): array
    {
        $validator = Validator::make($input, [
            'title' => ['required', 'string', 'max:160'],
            'summary' => ['required', 'string', 'max:2000'],
            'goals' => ['nullable', 'string', 'max:6000'],
            'estimated_minutes' => ['required', 'integer', 'min:1', 'max:10000'],
            'lessons' => ['required', 'array', 'min:1', 'max:60'],
            'lessons.*' => ['required', 'array:title,type,body,module,minutes,question,options,correct_option,explanation,items'],
            'lessons.*.title' => ['required', 'string', 'max:160'],
            'lessons.*.type' => ['required', Rule::in(['text', 'reflection', 'quiz', 'checklist', 'action'])],
            'lessons.*.body' => ['required', 'string', 'max:10000'],
            'lessons.*.module' => ['nullable', 'string', 'max:120'],
            'lessons.*.minutes' => ['nullable', 'integer', 'min:1', 'max:120'],
            'lessons.*.question' => ['nullable', 'string', 'max:1000'],
            'lessons.*.options' => ['sometimes', 'array', 'max:6'],
            'lessons.*.options.*' => ['required', 'string', 'max:500'],
            'lessons.*.correct_option' => ['nullable', 'integer', 'min:0', 'max:5'],
            'lessons.*.explanation' => ['nullable', 'string', 'max:2000'],
            'lessons.*.items' => ['sometimes', 'array', 'max:15'],
            'lessons.*.items.*' => ['required', 'string', 'max:500'],
        ]);
        $validator->after(function ($v) use ($input) {
            if (! is_array($input['lessons'] ?? null)) return;
            foreach ($input['lessons'] as $i => $lesson) {
                if (! is_array($lesson)) continue;
                if (($lesson['type'] ?? '') === 'quiz') {
                    $options = $lesson['options'] ?? [];
                    if (! is_array($options) || count($options) < 2 || ! array_is_list($options)) {
                        $v->errors()->add("lessons.$i.options", 'Voeg 2 tot 6 antwoordopties toe.');
                    }
                    if (! is_string($lesson['question'] ?? null) || trim($lesson['question']) === '') $v->errors()->add("lessons.$i.question", 'Voeg de kennisvraag toe.');
                    $correct = $lesson['correct_option'] ?? null;
                    if (! is_numeric($correct) || ! is_array($options) || ! array_key_exists((int) $correct, $options)) $v->errors()->add("lessons.$i.correct_option", 'Kies een bestaand juist antwoord.');
                    if (! is_string($lesson['explanation'] ?? null) || trim($lesson['explanation']) === '') $v->errors()->add("lessons.$i.explanation", 'Geef uitleg bij de vraag.');
                }
                if (($lesson['type'] ?? '') === 'checklist' && (! is_array($lesson['items'] ?? null) || count($lesson['items']) < 1 || ! array_is_list($lesson['items']))) {
                    $v->errors()->add("lessons.$i.items", 'Voeg minimaal één keuze toe.');
                }
            }
            if (! array_is_list($input['lessons'])) $v->errors()->add('lessons', 'Gebruik een geordende lessenlijst.');
        });
        $data = $validator->validate();
        $data['lessons'] = array_map(function ($lesson) {
            $clean = array_intersect_key($lesson, array_flip(['title', 'type', 'body', 'module', 'minutes']));
            if ($lesson['type'] === 'quiz') $clean += array_intersect_key($lesson, array_flip(['question', 'options', 'correct_option', 'explanation']));
            if ($lesson['type'] === 'checklist') $clean['items'] = $lesson['items'];
            return $clean;
        }, $data['lessons']);
        if (count(array_filter($data['lessons'], fn ($l) => isset($l['minutes']))) === count($data['lessons'])) {
            $data['estimated_minutes'] = array_sum(array_column($data['lessons'], 'minutes'));
        }
        return $data;
    }
}
