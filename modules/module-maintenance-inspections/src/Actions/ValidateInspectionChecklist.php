<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inspections\Models\InspectionTemplate;

final class ValidateInspectionChecklist
{
    /** @param array<string, mixed>|null $readings */
    public function handle(int $teamId, ?string $templateKey, ?array $readings, bool $requireRequired = false): void
    {
        if ($templateKey === null || trim($templateKey) === '') {
            return;
        }

        $template = InspectionTemplate::query()
            ->where('team_id', $teamId)
            ->where('key', trim($templateKey))
            ->where('is_active', true)
            ->first();
        if ($template === null) {
            throw ValidationException::withMessages(['template_key' => 'The inspection template is not available in this team.']);
        }

        $readings ??= [];
        foreach ($template->checklist as $field => $definition) {
            if (! is_array($definition)) {
                throw ValidationException::withMessages(['readings' => 'The inspection template contains an invalid checklist field.']);
            }
            if (! $this->isApplicable($definition, $readings)) {
                continue;
            }

            $hasValue = array_key_exists($field, $readings) && $readings[$field] !== null && $readings[$field] !== '';
            if (! $hasValue) {
                if ($requireRequired && ($definition['required'] ?? false) === true) {
                    throw ValidationException::withMessages(["readings.{$field}" => 'This inspection reading is required.']);
                }

                continue;
            }
            $this->validateValue($field, $readings[$field], $definition);
        }
    }

    /** @param array<string, mixed> $definition */
    private function isApplicable(array $definition, array $readings): bool
    {
        $condition = $definition['when'] ?? $definition['condition'] ?? null;
        if (! is_array($condition) || ! array_key_exists('field', $condition)) {
            return true;
        }
        $matches = ($readings[$condition['field']] ?? null) === ($condition['value'] ?? null);

        return ($condition['operator'] ?? 'equals') === 'not_equals' ? ! $matches : $matches;
    }

    /** @param array<string, mixed> $definition */
    private function validateValue(string $field, mixed $value, array $definition): void
    {
        $valid = match ($definition['type'] ?? 'string') {
            'numeric', 'number' => is_int($value) || is_float($value) || (is_string($value) && is_numeric($value)),
            'boolean', 'bool' => is_bool($value),
            'choice', 'select' => in_array($value, $definition['options'] ?? [], true),
            'string', 'text' => is_string($value),
            default => false,
        };
        if (! $valid) {
            throw ValidationException::withMessages(["readings.{$field}" => "The {$field} reading has an invalid value."]);
        }
    }
}
