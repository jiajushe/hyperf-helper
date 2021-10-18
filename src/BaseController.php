<?php
declare(strict_types=1);

namespace Jiajushe\HyperfHelper;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerInterface;

abstract class BaseController
{
    /**
     * @Inject
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Inject
     * @var RequestInterface
     */
    protected $request;

    /**
     * @Inject
     * @var ResponseInterface
     */
    protected ResponseInterface $response;

    /**
     * @Inject()
     * @var Response
     */
    protected Response $responseHelper;

    /**
     * json格式返回
     * @param array $res
     * @return \Psr\Http\Message\ResponseInterface
     * @author yun 2021-10-12 14:26:06
     */
    protected function json(array $res = []): \Psr\Http\Message\ResponseInterface
    {
        $res = $this->responseHelper->normal($res);
        return $this->response->json($res);
    }

}