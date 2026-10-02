<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A runtime-editable application setting.
 *
 * The `value` is cast to an ARRAY rather than left as the raw JSON string, because
 * a settings table whose values are strings forces every reader to know the type.
 * With the cast, `Setting::cast('300')` is `300` and `cast(true)` is `true`, so
 * the type travels with the value.
 *
 * `cast()` exists for that reason. A settings screen renders a checkbox for a
 * boolean and a number input for an integer; without the declared type it would
 * have to guess from the value, and `"false"` — the string — is truthy.
 *
 * @property int $id
 * @property string $key
 * @property array<mixed>|null $value
 * @property string $type
 * @property string $group
 * @property string $label
 * @property string|null $description
 * @property bool $is_encrypted
 * @property int|null $updated_by
 */
class Setting extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_encrypted',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'is_encrypted' => 'boolean',
        ];
    }

    /**
     * The value, coerced to the declared type.
     *
     * The declared type wins over whatever the stored value happens to be, because
     * a hand-edited row with `"300"` in an integer setting must still read as an
     * integer rather than as a string that breaks an arithmetic comparison.
     */
    public function typedValue(): mixed
    {
        $value = $this->value;

        if ($value === null) {
            return null;
        }

        return match ($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            'integer' => is_numeric($value) ? (int) $value : null,
            'float' => is_numeric($value) ? (float) $value : null,
            'json', 'array' => $value,
            default => is_array($value) ? json_encode($value) : (string) $value,
        };
    }

    /**
     * Settings that must never be rendered back to a browser.
     *
     * An `is_encrypted` row is stored encrypted and its value is redacted on the
     * settings screen — otherwise the screen that manages secrets becomes the
     * place secrets are displayed to every administrator.
     */
    public function isSecret(): bool
    {
        return (bool) $this->is_encrypted;
    }

    public function scopeInGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}
