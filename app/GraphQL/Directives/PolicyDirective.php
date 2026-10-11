<?php

declare(strict_types=1);

namespace App\GraphQL\Directives;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Pagination\Paginator;
use Nuwave\Lighthouse\Auth\AuthServiceProvider;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;
use Nuwave\Lighthouse\Exceptions\AuthorizationException;
use Nuwave\Lighthouse\Exceptions\DefinitionException;
use Nuwave\Lighthouse\Execution\Resolved;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\RootType;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldManipulator;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Nuwave\Lighthouse\Support\Utils;

/**
 * Field middleware: Sanctum authentication plus Laravel policy checks.
 *
 * Replaces stacking @guard with @canModel / @canResolved on admin queries.
 */
class PolicyDirective extends BaseDirective implements FieldMiddleware, FieldManipulator
{
    public function __construct(
        protected AuthFactory $authFactory,
        protected Gate $gate,
    ) {}

    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'GRAPHQL'
"""
Authenticate the request and enforce a Laravel policy when configured.

Use `check: AUTHENTICATED` for auth-only fields (replaces `@guard`).
Use `check: MODEL_CLASS` with `model` for list queries (replaces `@canModel`).
Use `check: RESOLVED_VALUE` on single-record fields after `@find` (replaces `@canResolved`).
"""
directive @policy(
  """
  What to authorize against.
  """
  check: PolicyCheck! = AUTHENTICATED

  """
  Eloquent model class for MODEL_CLASS checks.
  """
  model: String

  """
  Policy ability. Defaults to `viewAny` for MODEL_CLASS and `view` for RESOLVED_VALUE.
  """
  ability: String

  """
  Guards from `config/auth.php`. Defaults to Lighthouse `guards` config.
  """
  with: [String!]
) repeatable on FIELD_DEFINITION

enum PolicyCheck {
  AUTHENTICATED
  MODEL_CLASS
  RESOLVED_VALUE
}
GRAPHQL;
    }

    public function handleField(FieldValue $fieldValue): void
    {
        $check = $this->directiveArgValue('check', 'AUTHENTICATED');

        $fieldValue->wrapResolver(fn (callable $resolver): \Closure => function (
            mixed $root,
            array $args,
            GraphQLContext $context,
            ResolveInfo $resolveInfo,
        ) use ($resolver, $check) {
            $user = $this->authenticate($this->directiveArgValue('with', AuthServiceProvider::guards()));
            $context->setUser($user);

            $gate = $this->gate->forUser($user);

            if ($check === 'AUTHENTICATED') {
                return $resolver($root, $args, $context, $resolveInfo);
            }

            if ($check === 'MODEL_CLASS') {
                $modelClass = $this->modelClass();
                $ability = $this->directiveArgValue('ability', 'viewAny');
                $this->authorize($gate, $ability, $modelClass);

                return $resolver($root, $args, $context, $resolveInfo);
            }

            $ability = $this->directiveArgValue('ability', 'view');

            return Resolved::handle(
                $resolver($root, $args, $context, $resolveInfo),
                static function (mixed $modelLike) use ($gate, $ability): mixed {
                    $modelOrModels = $modelLike instanceof Paginator
                        ? $modelLike->items()
                        : $modelLike;

                    Utils::applyEach(static function (mixed $model) use ($gate, $ability): void {
                        if ($model === null) {
                            return;
                        }

                        $response = $gate->inspect($ability, $model);
                        if ($response->denied()) {
                            throw new AuthorizationException($response->message(), $response->code());
                        }
                    }, $modelOrModels);

                    return $modelLike;
                },
            );
        });
    }

    public function manipulateFieldDefinition(
        DocumentAST &$documentAST,
        FieldDefinitionNode &$fieldDefinition,
        ObjectTypeDefinitionNode|InterfaceTypeDefinitionNode &$parentType,
    ): void {
        $check = $this->directiveArgValue('check', 'AUTHENTICATED');

        if ($check === 'RESOLVED_VALUE' && $parentType->name->value === RootType::MUTATION) {
            throw new DefinitionException(
                "Do not use @policy(check: RESOLVED_VALUE) on mutation {$fieldDefinition->name->value}.",
            );
        }

        if ($check === 'MODEL_CLASS' && ! $this->directiveHasArgument('model')) {
            throw new DefinitionException(
                "@policy(check: MODEL_CLASS) on {$fieldDefinition->name->value} requires a `model` argument.",
            );
        }
    }

    /**
     * @param  array<string|null>  $guards
     */
    protected function authenticate(array $guards): \Illuminate\Contracts\Auth\Authenticatable
    {
        foreach ($guards as $guard) {
            $user = $this->authFactory->guard($guard)->user();

            if ($user !== null) {
                // @phpstan-ignore-next-line passing null works fine here
                $this->authFactory->shouldUse($guard);

                return $user;
            }
        }

        throw new AuthenticationException(AuthenticationException::MESSAGE, $guards);
    }

    protected function authorize(Gate $gate, string $ability, string $modelClass): void
    {
        $response = $gate->inspect($ability, $modelClass);
        if ($response->denied()) {
            throw new AuthorizationException($response->message(), $response->code());
        }
    }

    protected function modelClass(): string
    {
        $model = $this->directiveArgValue('model');

        if ($model === null) {
            throw new DefinitionException('@policy(check: MODEL_CLASS) requires a `model` argument.');
        }

        return $this->namespaceModelClass($model);
    }
}
