<?php

namespace App\Services;

class ValidationService
{
    public static function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleSet) {
            $value = $data[$field] ?? null;
            $list = explode('|', $ruleSet);

            foreach ($list as $rule) {
                if ($rule === 'required' && ($value === null || $value === '')) {
                    $errors[$field][] = 'الحقل مطلوب';
                }

                if ($rule === 'numeric' && $value !== null && $value !== '' && !is_numeric($value)) {
                    $errors[$field][] = 'الحقل يجب أن يكون رقمًا';
                }

                if ($rule === 'integer' && $value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $errors[$field][] = 'الحقل يجب أن يكون رقمًا صحيحًا';
                }

                if (str_starts_with($rule, 'min:') && is_numeric($value)) {
                    $min = (float) explode(':', $rule)[1];
                    if ((float) $value < $min) {
                        $errors[$field][] = 'القيمة أقل من الحد الأدنى';
                    }
                }
            }
        }

        return $errors;
    }
}
