<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Server-side validation. Rules are declared as
 *   'email' => 'required|email|max:190'
 * and every submitted value is validated here regardless of client-side checks.
 */
final class Validator
{
    private array $errors = [];
    private array $validated = [];

    public function __construct(private readonly array $data, private readonly array $rules, private readonly array $labels = [])
    {
        $this->run();
    }

    public static function make(array $data, array $rules, array $labels = []): self
    {
        return new self($data, $rules, $labels);
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        return $this->validated;
    }

    private function label(string $field): string
    {
        return $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            $value = is_string($value) ? trim($value) : $value;
            $rules = explode('|', $ruleString);

            $isRequired = in_array('required', $rules, true);
            $isEmpty    = $value === null || $value === '' || (is_array($value) && $value === []);

            if ($isRequired && $isEmpty) {
                $this->addError($field, $this->label($field) . ' is required.');
                continue;
            }

            if ($isEmpty) {
                $this->validated[$field] = $value;
                continue;
            }

            foreach ($rules as $rule) {
                [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);
                $this->apply($field, $value, $name, $parameter);
            }

            $this->validated[$field] = $value;
        }
    }

    private function apply(string $field, mixed $value, string $rule, ?string $parameter): void
    {
        $label = $this->label($field);

        switch ($rule) {
            case 'email':
                if (!filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, $label . ' must be a valid email address.');
                }
                break;

            case 'min':
                if (mb_strlen((string) $value) < (int) $parameter) {
                    $this->addError($field, $label . ' must be at least ' . $parameter . ' characters.');
                }
                break;

            case 'max':
                if (mb_strlen((string) $value) > (int) $parameter) {
                    $this->addError($field, $label . ' may not be longer than ' . $parameter . ' characters.');
                }
                break;

            case 'numeric':
                if (!is_numeric($value)) {
                    $this->addError($field, $label . ' must be a number.');
                }
                break;

            case 'integer':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, $label . ' must be a whole number.');
                }
                break;

            case 'gt':
                if (!is_numeric($value) || (float) $value <= (float) $parameter) {
                    $this->addError($field, $label . ' must be greater than ' . $parameter . '.');
                }
                break;

            case 'gte':
                if (!is_numeric($value) || (float) $value < (float) $parameter) {
                    $this->addError($field, $label . ' must be at least ' . $parameter . '.');
                }
                break;

            case 'lte':
                if (!is_numeric($value) || (float) $value > (float) $parameter) {
                    $this->addError($field, $label . ' may not be more than ' . $parameter . '.');
                }
                break;

            case 'in':
                if (!in_array((string) $value, explode(',', (string) $parameter), true)) {
                    $this->addError($field, $label . ' is not a valid choice.');
                }
                break;

            case 'date':
                if (strtotime((string) $value) === false) {
                    $this->addError($field, $label . ' must be a valid date.');
                }
                break;

            case 'phone':
                if (preg_match('/^[0-9+()\s-]{7,20}$/', (string) $value) !== 1) {
                    $this->addError($field, $label . ' must be a valid phone number.');
                }
                break;

            case 'slug':
                if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $value) !== 1) {
                    $this->addError($field, $label . ' may only contain lowercase letters, numbers and hyphens.');
                }
                break;

            case 'url':
                if (!filter_var((string) $value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, $label . ' must be a valid URL.');
                }
                break;

            case 'accepted':
                if (!in_array($value, ['1', 'on', 'yes', 'true', true, 1], true)) {
                    $this->addError($field, 'Please accept ' . strtolower($label) . '.');
                }
                break;

            case 'confirmed':
                if ((string) $value !== (string) ($this->data[$field . '_confirmation'] ?? '')) {
                    $this->addError($field, $label . ' confirmation does not match.');
                }
                break;

            case 'password':
                if (mb_strlen((string) $value) < 8
                    || preg_match('/[A-Za-z]/', (string) $value) !== 1
                    || preg_match('/[0-9]/', (string) $value) !== 1) {
                    $this->addError($field, 'Password must be at least 8 characters and include a letter and a number.');
                }
                break;

            case 'unique':
                // unique:table,column[,ignoreId]
                [$table, $column, $ignore] = array_pad(explode(',', (string) $parameter), 3, null);
                $sql    = sprintf('SELECT COUNT(*) FROM `%s` WHERE `%s` = ?', $table, $column);
                $params = [$value];

                if ($ignore !== null && $ignore !== '') {
                    $sql     .= ' AND id <> ?';
                    $params[] = $ignore;
                }

                if ((int) Database::scalar($sql, $params) > 0) {
                    $this->addError($field, 'That ' . strtolower($label) . ' is already registered.');
                }
                break;

            case 'exists':
                [$table, $column] = array_pad(explode(',', (string) $parameter), 2, 'id');
                if ((int) Database::scalar(sprintf('SELECT COUNT(*) FROM `%s` WHERE `%s` = ?', $table, $column), [$value]) === 0) {
                    $this->addError($field, 'That ' . strtolower($label) . ' could not be found.');
                }
                break;
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
