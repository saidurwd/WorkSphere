<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base class for every API resource.
 *
 * Two things it guarantees that a bare `JsonResource` does not:
 *
 * 1. **A declared schema.** {@see schema()} returns the JSON Schema fragment for
 *    the resource's fields, which is what the OpenAPI generator publishes. A
 *    resource whose `toArray()` grows a key that `schema()` does not declare
 *    fails `ApiSchemaTest`, so the published contract cannot quietly drift from
 *    what the endpoint actually returns.
 * 2. **No hidden fields.** Nothing here reaches for `$this->resource` internals
 *    that the API contract does not name. Soft-delete columns, audit columns and
 *    internal rule blobs are omitted by construction rather than by remembering
 *    to hide them.
 */
abstract class ApiResource extends JsonResource
{
    /**
     * The JSON Schema for this resource's own fields, as a property map.
     *
     * `array<string, array<string, mixed>>` — field name => JSON Schema fragment.
     * `$ref` to another resource uses `['type' => 'object', 'ref' => Foo::class]`,
     * which the generator resolves into a component reference.
     *
     * @return array<string, array<string, mixed>>
     */
    abstract public static function schema(): array;

    /**
     * `enum` values for a backed enum, for use inside a schema fragment.
     *
     * @param  class-string<\BackedEnum>  $enum
     * @return list<string>
     */
    protected static function enumValues(string $enum): array
    {
        return array_column($enum::cases(), 'value');
    }

    /**
     * A `date` / `date-time` fragment.
     *
     * @return array<string, string>
     */
    protected static function date(): array
    {
        return ['type' => 'string', 'format' => 'date'];
    }

    /**
     * A `date-time` fragment.
     *
     * @return array<string, string>
     */
    protected static function dateTime(): array
    {
        return ['type' => 'string', 'format' => 'date-time'];
    }

    /**
     * A nullable integer.
     *
     * @return array<string, mixed>
     */
    protected static function nullableInt(): array
    {
        return ['type' => ['integer', 'null']];
    }

    /**
     * A nullable string.
     *
     * @return array<string, mixed>
     */
    protected static function nullableString(): array
    {
        return ['type' => ['string', 'null']];
    }
}
