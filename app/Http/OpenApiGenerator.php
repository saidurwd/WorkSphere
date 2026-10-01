<?php

namespace App\Http;

use App\Http\Requests\Api\StoreTokenRequest;
use App\Http\Requests\ApiIndexRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\MeetingResource;
use App\Http\Resources\ObligationResource;
use App\Http\Resources\PersonResource;
use App\Http\Resources\TagResource;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TodoResource;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum as EnumRule;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\ValidationRuleParser;
use Modules\Meetings\Http\Requests\IndexMeetingRequest;
use Modules\Obligations\Http\Requests\IndexObligationRequest;
use Modules\Tasks\Http\Requests\IndexTaskRequest;
use Modules\Todos\Http\Requests\AssignTodoRequest;
use Modules\Todos\Http\Requests\CompleteTodoRequest;
use Modules\Todos\Http\Requests\IndexTodoRequest;
use Modules\Todos\Http\Requests\StoreTodoCommentRequest;
use Modules\Todos\Http\Requests\StoreTodoRequest;
use Modules\Todos\Http\Requests\UpdateTodoRequest;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

/**
 * Generates the OpenAPI 3.1 document from the routes, Form Requests and
 * Resources that actually exist.
 *
 * The alternative — a hand-written `openapi.yaml` — is a document that is wrong
 * the moment somebody adds a field, and nothing in CI notices, because a YAML file
 * has no compiler. Here the document is derived:
 *
 * - **Paths** come from the route table, so a route that is not in the spec cannot
 *   be registered, and a spec entry cannot outlive its route.
 * - **Request bodies** come from the Form Request's `rules()`, converted rule by
 *   rule. The API uses the same requests as the web layer, so the published
 *   validation IS the enforced validation — there is no second schema to keep in
 *   step.
 * - **Responses** come from each Resource's `schema()`, the same declaration that
 *   `ApiSchemaTest` pins against `toArray()`.
 *
 * What it deliberately does NOT do: guess at business meaning. Summaries and tags
 * are declared per route in {@see self::describe()} rather than inferred, because
 * a wrong description is worse than none — it is believed.
 */
class OpenApiGenerator
{
    /** The document version. 3.1 because the resources use JSON Schema 2020-12 unions. */
    private const OPENAPI_VERSION = '3.1.0';

    private const API_PREFIX = 'api/v1/';

    /**
     * Every resource that can appear in a response, so the generator can emit a
     * component schema for each.
     *
     * @var list<class-string<ApiResource>>
     */
    private const RESOURCES = [
        TodoResource::class,
        TaskResource::class,
        MeetingResource::class,
        ObligationResource::class,
        CommentResource::class,
        PersonResource::class,
        TagResource::class,
    ];

    /**
     * Every Form Request the API accepts.
     *
     * @var list<class-string<FormRequest>>
     */
    private const REQUESTS = [
        StoreTokenRequest::class,
        StoreTodoRequest::class,
        UpdateTodoRequest::class,
        AssignTodoRequest::class,
        CompleteTodoRequest::class,
        StoreTodoCommentRequest::class,
        IndexTodoRequest::class,
        IndexTaskRequest::class,
        IndexMeetingRequest::class,
        IndexObligationRequest::class,
    ];

    /**
     * Per-operation prose, keyed `METHOD uri`.
     *
     * Only the operations a reader cannot infer from the path are described. The
     * rest fall back to a generated summary.
     *
     * @var array<string, array{summary: string, description: string, tags: list<string>}>
     */
    private const DESCRIPTIONS = [
        'GET api/v1/meta' => [
            'summary' => 'API vocabularies and the caller\'s abilities',
            'description' => 'Every enum the API accepts, plus the abilities on the presented token. '
                .'A client that reads its vocabulary from here cannot go stale when a status is added.',
            'tags' => ['meta'],
        ],
        'GET api/v1/openapi' => [
            'summary' => 'This document',
            'description' => 'Generated from the live route table, the Form Requests and the API Resources.',
            'tags' => ['meta'],
        ],
        'POST api/v1/tokens' => [
            'summary' => 'Issue an API token',
            'description' => 'Super-admin only. The plaintext token is returned once and never again. '
                .'Abilities are restricted to a whitelist and the lifetime is capped by `sanctum.expiration`.',
            'tags' => ['tokens'],
        ],
        'GET api/v1/tokens' => [
            'summary' => 'List your own tokens',
            'description' => 'Never returns a token value — only metadata. A token belonging to another account is a 404.',
            'tags' => ['tokens'],
        ],
        'DELETE api/v1/tokens/{tokenId}' => [
            'summary' => 'Revoke one of your own tokens',
            'description' => 'This is the "sign this device out" action. Revoking the token making the request '
                .'ends the session it authenticated.',
            'tags' => ['tokens'],
        ],
        'GET api/v1/todos' => [
            'summary' => 'List To-Dos visible to the caller',
            'description' => 'Filtered by `TodoScope`, the same predicate `TodoPolicy::view` uses, so the API '
                .'cannot return a record the web list would hide.',
            'tags' => ['todos'],
        ],
        'POST api/v1/todos' => [
            'summary' => 'Create a To-Do',
            'description' => 'Assigning to another user additionally requires `todos.create_for_others`. '
                .'Status changes go through the transition graph, never a field write.',
            'tags' => ['todos'],
        ],
        'GET api/v1/todos/{todo}' => [
            'summary' => 'Fetch one To-Do',
            'description' => '`recurrence_rule` is not exposed; `is_recurring` answers "is there more coming?" '
                .'without pinning the client to the storage shape.',
            'tags' => ['todos'],
        ],
        'PATCH api/v1/todos/{todo}' => [
            'summary' => 'Update a To-Do',
            'description' => '`status` is rejected: a status change is a transition, enforced by `TodoService`.',
            'tags' => ['todos'],
        ],
        'DELETE api/v1/todos/{todo}' => [
            'summary' => 'Soft-delete a To-Do',
            'description' => 'Requires `todos.delete` and creator or `todos.update_any` — the asymmetry the spec calls for.',
            'tags' => ['todos'],
        ],
        'POST api/v1/todos/{todo}/complete' => [
            'summary' => 'Complete a To-Do',
            'description' => 'Sets `completed_at` and `completed_by`, and materialises the next occurrence for a '
                .'recurring To-Do. An illegal transition is a 422, never a silent no-op.',
            'tags' => ['todos'],
        ],
        'POST api/v1/todos/{todo}/reopen' => [
            'summary' => 'Reopen a completed To-Do',
            'description' => 'Clears `completed_at` and `completed_by` together.',
            'tags' => ['todos'],
        ],
        'POST api/v1/todos/{todo}/archive' => [
            'summary' => 'Archive a To-Do',
            'description' => 'Stores the prior status in `archived_from`, which is what makes restore a true reversal.',
            'tags' => ['todos'],
        ],
        'POST api/v1/todos/{todo}/restore' => [
            'summary' => 'Unarchive a To-Do',
            'description' => 'Returns it to the status it held before archiving.',
            'tags' => ['todos'],
        ],
        'POST api/v1/todos/{todo}/assign' => [
            'summary' => 'Reassign a To-Do',
            'description' => 'A null `assignee_id` returns it to the unassigned inbox.',
            'tags' => ['todos'],
        ],
        'GET api/v1/todos/{todo}/comments' => [
            'summary' => 'List a To-Do\'s comments',
            'description' => 'Top-level comments with their replies nested, so a client need not rebuild the thread.',
            'tags' => ['comments'],
        ],
        'POST api/v1/todos/{todo}/comments' => [
            'summary' => 'Comment on a To-Do',
            'description' => 'Mentions are parsed from `@handle` in the body rather than accepted as a list, so a '
                .'mention cannot be silenced by omitting it.',
            'tags' => ['comments'],
        ],
        'GET api/v1/todos/{todo}/activity' => [
            'summary' => 'A To-Do\'s activity trail',
            'description' => 'The last 50 writes with old and new values, from the shared `activity_logs` timeline.',
            'tags' => ['todos'],
        ],
    ];

    /**
     * The response resource for each route, where it is not derivable from the
     * controller's return type.
     *
     * A single resource class per route, or an empty list where the endpoint has
     * no resource body (`meta`, a bare 204, the token list).
     *
     * @var array<string, class-string<ApiResource>|list<class-string<ApiResource>>>
     */
    private const RESPONSES = [
        'GET api/v1/meta' => [],
        'GET api/v1/openapi' => [],
        'GET api/v1/tokens' => [],
        'POST api/v1/tokens' => [],
        'DELETE api/v1/tokens/{tokenId}' => [],
        'GET api/v1/todos' => TodoResource::class,
        'POST api/v1/todos' => TodoResource::class,
        'GET api/v1/todos/{todo}' => TodoResource::class,
        'PATCH api/v1/todos/{todo}' => TodoResource::class,
        'DELETE api/v1/todos/{todo}' => [],
        'POST api/v1/todos/{todo}/complete' => TodoResource::class,
        'POST api/v1/todos/{todo}/reopen' => TodoResource::class,
        'POST api/v1/todos/{todo}/archive' => TodoResource::class,
        'POST api/v1/todos/{todo}/restore' => TodoResource::class,
        'POST api/v1/todos/{todo}/assign' => TodoResource::class,
        'GET api/v1/todos/{todo}/comments' => CommentResource::class,
        'POST api/v1/todos/{todo}/comments' => CommentResource::class,
        'GET api/v1/todos/{todo}/activity' => [],
        'GET api/v1/tasks' => TaskResource::class,
        'GET api/v1/tasks/{task}' => TaskResource::class,
        'GET api/v1/meetings' => MeetingResource::class,
        'GET api/v1/meetings/{meeting}' => MeetingResource::class,
        'GET api/v1/obligations' => ObligationResource::class,
        'GET api/v1/obligations/{obligation}' => ObligationResource::class,
    ];

    /**
     * The whole document.
     *
     * @return array<string, mixed>
     */
    public function generate(): array
    {
        $paths = [];

        foreach ($this->apiRoutes() as $route) {
            $key = $this->key($route);

            foreach (['GET', 'POST', 'PATCH', 'PUT', 'DELETE'] as $method) {
                if (! in_array($method, $route->methods(), true)) {
                    continue;
                }

                $paths[$route->uri()][strtolower($method)] = $this->operation($route, $method, $key);
            }
        }

        return [
            'openapi' => self::OPENAPI_VERSION,
            'info' => [
                'title' => 'WorkSphere API',
                'version' => '1.0.0',
                'description' => implode("\n\n", [
                    'Generated from the live route table, the Form Requests and the API Resources. '
                        .'It cannot describe an endpoint the application does not have.',
                    '**Authentication.** `Authorization: Bearer <token>`. Tokens expire — see `GET /api/v1/tokens`. '
                        .'An expired or revoked token is a 401.',
                    '**Authorization.** The API shares the web layer\'s policies exactly. There is no API-only '
                        .'and no web-only shortcut: a record the web UI hides is a 403 (or a 404 where its '
                        .'existence is itself the secret) here too.',
                    '**Envelope.** Collections are `{data, meta:{current_page,last_page,per_page,total}, links}`; '
                        .'single records are `{data}`. Errors are `{error:{code,message,errors?}}` where `code` '
                        .'is one of: '.implode(', ', ApiErrorCode::all()).'.',
                    '**Sorting.** `sort` is a whitelist. An unknown column is a 422, not a silently ignored '
                        .'parameter.',
                ]),
            ],
            'servers' => [
                // A RELATIVE server url, deliberately. An absolute one bakes this
                // machine's host into a generated artefact, which makes the output
                // differ per developer and defeats `api:openapi --check`.
                ['url' => '/api/v1', 'description' => 'This deployment'],
            ],
            'tags' => [
                ['name' => 'todos', 'description' => 'To-Dos: the full lifecycle.'],
                ['name' => 'tasks', 'description' => 'Tasks, read only in v1.'],
                ['name' => 'meetings', 'description' => 'Meetings, read only in v1.'],
                ['name' => 'obligations', 'description' => 'Obligations, read only in v1.'],
                ['name' => 'comments', 'description' => 'The shared comment table.'],
                ['name' => 'tokens', 'description' => 'Token lifecycle.'],
                ['name' => 'meta', 'description' => 'Vocabularies and this document.'],
            ],
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'bearer' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'description' => 'A Sanctum personal access token. Tokens expire; an expired token is a 401.',
                    ],
                ],
                'schemas' => $this->schemas(),
            ],
            'security' => [['bearer' => []]],
        ];
    }

    /**
     * Every registered route under the v1 prefix.
     *
     * @return list<RoutingRoute>
     */
    protected function apiRoutes(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RoutingRoute $route): bool => str_starts_with($route->uri(), self::API_PREFIX))
            ->filter(fn (RoutingRoute $route): bool => $route->getName() !== null)
            ->values()
            ->all();
    }

    protected function key(RoutingRoute $route): string
    {
        $methods = array_values(array_filter(
            ['GET', 'POST', 'PATCH', 'PUT', 'DELETE'],
            fn (string $method): bool => in_array($method, $route->methods(), true),
        ));

        return ($methods[0] ?? 'GET').' '.$route->uri();
    }

    /**
     * @return array<string, mixed>
     */
    protected function operation(RoutingRoute $route, string $method, string $key): array
    {
        $described = self::DESCRIPTIONS[$key] ?? [
            'summary' => $this->fallbackSummary($route, $method),
            'description' => 'Generated from the route table; no hand-written description is declared for this '
                .'endpoint. See the repository for the behaviour it delegates to.',
            'tags' => [Str::before($route->uri(), '/')],
        ];

        $operation = [
            'summary' => $described['summary'],
            'description' => $described['description'],
            'tags' => $described['tags'],
            'operationId' => (string) $route->getName(),
            'parameters' => $this->parameters($route, $method),
            'responses' => $this->responses($key, $this->isCollection($route, $method)),
        ];

        $body = $this->requestBody($route, $method);

        if ($body !== null) {
            $operation['requestBody'] = $body;
        }

        return $operation;
    }

    /**
     * Whether this operation returns a collection rather than a single record.
     *
     * Inferred from the shape of the URI: a trailing `/` on the resource root is the
     * index. Anything with a path parameter after the resource is a single record.
     * That is derivable rather than declared, so a new route cannot forget to say.
     */
    protected function isCollection(RoutingRoute $route, string $method): bool
    {
        if ($method !== 'GET') {
            return false;
        }

        // An index has no path parameter; a `show` always has one.
        return ! str_contains($route->uri(), '{');
    }

    protected function fallbackSummary(RoutingRoute $route, string $method): string
    {
        return Str::of($method)->lower()->append(' ')
            .Str::of(Str::after($route->uri(), self::API_PREFIX))->replace(['{', '}', '/', '-', '_'], ' ')
                ->replace('-', ' ')
                ->squish()
                ->toString();
    }

    /**
     * Path parameters plus, for an index endpoint, every query parameter the
     * request validates.
     *
     * @return list<array<string, mixed>>
     */
    protected function parameters(RoutingRoute $route, string $method): array
    {
        $parameters = [];

        foreach ($route->parameterNames() as $name) {
            $parameters[] = [
                'name' => $name,
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => 'integer'],
            ];
        }

        $request = $this->formRequestFor($route, $method);

        if ($request === null) {
            return $parameters;
        }

        // `$request` is a CLASS NAME at this point, so the subclass check and the
        // `rules()` call both need an instance.
        if (! is_subclass_of($request, ApiIndexRequest::class)) {
            return $parameters;
        }

        // An index has no path parameter; a `show` always has one. Derived from the
        // URI rather than declared, so a new route cannot forget to say.
        if (str_contains($route->uri(), '{')) {
            return $parameters;
        }

        /** @var ApiIndexRequest $validated */
        $validated = new $request;

        $properties = $this->rulesToSchema($request);

        foreach ($validated->rules() as $field => $rules) {
            if (str_ends_with((string) $field, '.*')) {
                continue;
            }

            $parameters[] = [
                'name' => (string) $field,
                'in' => 'query',
                'required' => false,
                'schema' => $properties[(string) $field] ?? ['type' => 'string'],
            ];
        }

        return $parameters;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function requestBody(RoutingRoute $route, string $method): ?array
    {
        $request = $this->formRequestFor($route, $method);

        if ($request === null) {
            return null;
        }

        // An index request's rules describe the query string, which is already
        // published as parameters. Its "body" is not a body.
        if ($request instanceof ApiIndexRequest) {
            return null;
        }

        $schema = $this->rulesToSchema($request);

        return [
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => array_merge(
                        ['type' => 'object', 'properties' => $schema],
                        ['required' => $this->requiredFields($request)],
                    ),
                ],
            ],
        ];
    }

    /**
     * The Form Request a route's action declares, if any.
     *
     * Read by reflection rather than by an attribute: the request class is already
     * the method's type hint, so reading it from the signature means the published
     * schema and the enforced validation are the same object by construction.
     *
     * @return class-string<FormRequest>|null
     */
    protected function formRequestFor(RoutingRoute $route, string $method): ?string
    {
        $action = $route->getActionName();

        if (! is_string($action) || ! str_contains($action, '@')) {
            return null;
        }

        [$controller, $method] = explode('@', $action, 2);

        if (! class_exists($controller) || ! method_exists($controller, $method)) {
            return null;
        }

        foreach ((new ReflectionMethod($controller, $method))->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (! $type instanceof ReflectionNamedType) {
                continue;
            }

            $class = $type->getName();

            if (is_subclass_of($class, FormRequest::class)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * The fields a Form Request marks `required`.
     *
     * Declared rather than inferred from "has no `nullable`", because a field can
     * be optional in the payload and mandatory in the domain: `priority` defaults
     * to Medium, and publishing it as required would reject a perfectly valid
     * minimal body.
     *
     * @param  class-string<FormRequest>  $class
     * @return list<string>
     */
    protected function requiredFields(string $class): array
    {
        try {
            /** @var FormRequest $request */
            $request = new $class;
            $rules = $request->rules();
        } catch (Throwable) {
            return [];
        }

        $required = [];

        foreach ($rules as $field => $ruleSet) {
            $name = (string) $field;

            // `ids.*` describes items; the array itself is what is required.
            if (str_ends_with($name, '.*')) {
                continue;
            }

            // `parse()` studly-cases the name, so it is lowercased rather than
            // studly-casing every comparison below.
            $names = array_map(
                fn (mixed $rule): string => is_string($rule)
                    ? strtolower((string) ValidationRuleParser::parse($rule)[0])
                    : '',
                is_array($ruleSet) ? $ruleSet : [$ruleSet],
            );

            if (in_array('required', $names, true)) {
                $required[] = $name;
            }
        }

        return $required;
    }

    /**
     * Convert a Form Request's rules into a JSON Schema property map.
     *
     * Instantiating the request is safe: `rules()` is pure and the constructor
     * takes no collaborators. Only rules that can be resolved without a live
     * request are honoured — anything else falls through to a permissive string,
     * which is the safe direction for a *published* schema (a client may send more
     * than documented; the server still validates).
     *
     * @param  class-string<FormRequest>  $class
     * @return array<string, array<string, mixed>>
     */
    protected function rulesToSchema(string $class): array
    {
        try {
            /** @var FormRequest $request */
            $request = new $class;

            $rules = $request->rules();
        } catch (Throwable) {
            return [];
        }

        $properties = [];

        foreach ($rules as $field => $ruleSet) {
            $name = (string) $field;
            $schema = $this->ruleSetToSchema(is_array($ruleSet) ? $ruleSet : [$ruleSet]);

            if (str_ends_with($name, '.*')) {
                // `ids.*` describes the ITEMS of `ids`, not a field of its own.
                $name = substr($name, 0, -2);
                $schema = ['type' => 'array', 'items' => $schema];
            }

            $properties[$name] = $schema;
        }

        return $properties;
    }

    /**
     * @param  array<int, mixed>  $rules
     * @return array<string, mixed>
     */
    protected function ruleSetToSchema(array $rules): array
    {
        $schema = ['type' => 'string'];
        $nullable = false;

        foreach ($rules as $rule) {
            // Rule objects: `Rule::enum()`, `Rule::in()`. Both stringify to the
            // same `in:"a","b"` form the validator parses, so reading the string
            // back is parsing once rather than reflecting into protected state.
            if ($rule instanceof EnumRule || $rule instanceof In) {
                // `ValidationRuleParser::parse()` studly-cases the rule name, so the
                // comparison below is case-insensitive for the same reason.
                [$name, $values] = ValidationRuleParser::parse((string) $rule);

                if (strtolower((string) $name) === 'in') {
                    $schema['type'] = 'string';
                    $schema['enum'] = array_values(array_map(strval(...), $values));
                }

                continue;
            }

            if ($rule instanceof Rule) {
                // `exists`, `unique`, `password` — portable constraints the schema
                // cannot express and must not pretend to.
                continue;
            }

            if (! is_string($rule)) {
                continue;
            }

            [$parsed, $parameters] = ValidationRuleParser::parse($rule);

            // `parse()` studly-cases the name (`max:255` -> `Max`), so it is
            // lowercased here rather than studly-casing every match arm.
            $name = strtolower((string) $parsed);

            $schema = match ($name) {
                'integer', 'numeric' => ['type' => $name],
                'boolean' => ['type' => 'boolean'],
                'array' => ['type' => 'array'],
                'date' => ['type' => 'string', 'format' => 'date'],
                'date_format' => ['type' => 'string', 'format' => ($parameters[0] ?? null)],
                'in' => ['type' => 'string', 'enum' => array_map(strval(...), $parameters)],
                default => $schema,
            };

            if ($name === 'nullable') {
                $nullable = true;
            }

            if ($name === 'max' && is_numeric($parameters[0] ?? null) && ($schema['type'] ?? null) === 'string') {
                $schema['maxLength'] = (int) $parameters[0];
            }
        }

        if ($nullable && ($schema['type'] ?? null) === 'string') {
            $schema['type'] = ['string', 'null'];
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    protected function responses(string $key, bool $collection): array
    {
        $resource = self::RESPONSES[$key] ?? null;

        $responses = [
            '200' => $resource === null || $resource === []
                ? ['description' => 'Success.']
                : [
                    'description' => 'Success.',
                    'content' => [
                        'application/json' => [
                            'schema' => $collection ? $this->collectionOf($resource) : $this->singleOf($resource),
                        ],
                    ],
                ],
            '401' => $this->errorResponse('No credential, or one that has expired or been revoked.', [ApiErrorCode::Unauthenticated]),
            '403' => $this->errorResponse('Authenticated, but the policy denies this action.', [ApiErrorCode::Forbidden]),
            '404' => $this->errorResponse('No such record, or none the caller may see.', [ApiErrorCode::NotFound]),
            '422' => $this->errorResponse('Validation, or a transition the state machine refuses.', [ApiErrorCode::ValidationFailed]),
            '429' => $this->errorResponse('Rate limit exceeded.', [ApiErrorCode::RateLimited]),
        ];

        return $responses;
    }

    /**
     * A paginated collection envelope: `data` plus the documented `meta` block.
     *
     * @param  class-string<ApiResource>  $resource
     * @return array<string, mixed>
     */
    protected function collectionOf(string $resource): array
    {
        return [
            'type' => 'object',
            'properties' => ['data' => ['type' => 'array', 'items' => $this->ref($resource)]],
            'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
            'links' => ['type' => 'object'],
            'required' => ['data', 'meta'],
        ];
    }

    /**
     * A single-record envelope.
     *
     * @param  class-string<ApiResource>  $resource
     * @return array<string, mixed>
     */
    protected function singleOf(string $resource): array
    {
        return [
            'type' => 'object',
            'properties' => ['data' => $this->ref($resource)],
            'required' => ['data'],
        ];
    }

    /**
     * @param  class-string<ApiResource>  $resource
     * @return array<string, string>
     */
    protected function ref(string $resource): array
    {
        return ['$ref' => '#/components/schemas/'.class_basename($resource)];
    }

    /**
     * @param  list<string>  $codes
     * @return array<string, mixed>
     */
    protected function errorResponse(string $description, array $codes): array
    {
        return [
            'description' => $description,
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'error' => [
                                'type' => 'object',
                                'properties' => [
                                    'code' => ['type' => 'string', 'enum' => $codes],
                                    'message' => ['type' => 'string'],
                                    'errors' => ['type' => 'object', 'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']]],
                                ],
                                'required' => ['code', 'message'],
                            ],
                        ],
                        'required' => ['error'],
                    ],
                ],
            ],
        ];
    }

    /**
     * A component schema per resource, with `$ref` placeholders resolved.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function schemas(): array
    {
        $schemas = [];

        foreach (self::RESOURCES as $class) {
            $properties = [];

            foreach ($class::schema() as $field => $fragment) {
                $properties[$field] = $this->resolveFragment($fragment);
            }

            $schemas[class_basename($class)] = [
                'type' => 'object',
                'properties' => $properties,
            ];
        }

        $schemas['Error'] = [
            'type' => 'object',
            'properties' => [
                'error' => [
                    'type' => 'object',
                    'properties' => [
                        'code' => ['type' => 'string', 'enum' => ApiErrorCode::all()],
                        'message' => ['type' => 'string'],
                        'errors' => ['type' => 'object', 'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']]],
                    ],
                    'required' => ['code', 'message'],
                ],
            ],
            'required' => ['error'],
        ];

        $schemas['PaginationMeta'] = [
            'type' => 'object',
            'properties' => [
                'current_page' => ['type' => 'integer'],
                'last_page' => ['type' => 'integer'],
                'per_page' => ['type' => 'integer'],
                'total' => ['type' => 'integer'],
                'from' => ['type' => ['integer', 'null']],
                'to' => ['type' => ['integer', 'null']],
            ],
            'required' => ['current_page', 'last_page', 'per_page', 'total'],
        ];

        return $schemas;
    }

    /**
     * Turn a declared fragment into a real schema, following a `$ref` to another
     * resource when one is named and dropping the internal marker.
     *
     * @param  array<string, mixed>  $fragment
     * @return array<string, mixed>
     */
    protected function resolveFragment(array $fragment): array
    {
        $ref = $fragment['ref'] ?? null;

        unset($fragment['ref']);

        if (is_string($ref) && is_subclass_of($ref, ApiResource::class)) {
            return ['$ref' => '#/components/schemas/'.class_basename($ref)];
        }

        // A `$ref` may sit under `items`, as in the tags array.
        if (isset($fragment['items']) && is_array($fragment['items']) && isset($fragment['items']['ref'])) {
            $itemRef = $fragment['items']['ref'];
            unset($fragment['items']['ref']);

            $fragment['items'] = is_string($itemRef) && is_subclass_of($itemRef, ApiResource::class)
                ? ['$ref' => '#/components/schemas/'.class_basename($itemRef)]
                : $fragment['items'];
        }

        return $fragment;
    }

    /**
     * The documented Form Requests, so the generator can be checked against them.
     *
     * @return list<class-string<FormRequest>>
     */
    public static function documentedRequests(): array
    {
        return self::REQUESTS;
    }
}
