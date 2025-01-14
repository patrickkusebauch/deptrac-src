<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Dependency;

use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeReference;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Qossmic\Deptrac\Contract\Ast\AstMap\FileReference;
use Qossmic\Deptrac\Contract\Ast\AstMap\FileToken;
use Qossmic\Deptrac\Contract\Ast\AstMap\FunctionReference;
use Qossmic\Deptrac\Contract\Ast\AstMap\FunctionToken;
use Qossmic\Deptrac\Contract\Ast\AstMap\SuperGlobalToken;
use Qossmic\Deptrac\Contract\Ast\AstMap\TokenInterface;
use Qossmic\Deptrac\Contract\Ast\AstMap\TokenReferenceInterface;
use Qossmic\Deptrac\Contract\Ast\AstMap\VariableReference;
use Qossmic\Deptrac\Core\Ast\AstMap\AstMap;

class TokenResolver
{
    /**
     * @throws UnrecognizedTokenException
     */
    public function resolve(TokenInterface $token, AstMap $astMap): TokenReferenceInterface
    {
        return match (true) {
            $token instanceof ClassLikeToken => $astMap->getClassReferenceForToken($token) ?? new ClassLikeReference($token),
            $token instanceof FunctionToken => $astMap->getFunctionReferenceForToken($token) ?? new FunctionReference($token),
            $token instanceof SuperGlobalToken => new VariableReference($token),
            $token instanceof FileToken => $astMap->getFileReferenceForToken($token) ?? new FileReference($token->path, [], [], []),
            default => throw UnrecognizedTokenException::cannotCreateReference($token),
        };
    }
}
