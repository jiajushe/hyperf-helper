<?php

namespace Jiajushe\HyperfHelper\Middleware;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Context;
use Jiajushe\HyperfHelper\Exception\CustomError;
use Jiajushe\HyperfHelper\Exception\CustomNormal;
use Jiajushe\HyperfHelper\JsonRPCInterface\AdminTokenJsonRPCInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 公用 Admin token 验证中间件.
 */
class AdminTokenJsonRPC implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Inject
     */
    protected AdminTokenJsonRPCInterface $adminTokenJsonRPC;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @throws CustomError
     * @throws CustomNormal
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $request->getHeaderLine('Authorization');
        if (!$token) {
            throw new CustomNormal('请先登录', config('res_code.token'));
        }
        $res = $this->adminTokenJsonRPC->verify($token);
        $middlewareHandler = new Handler();
        $request = $middlewareHandler->checkTokenJsonRPC($res, $request);
        $response = $handler->handle($request);
        return $middlewareHandler->newTokenInHeader($res['response']['new_token'], $response);
    }
}
