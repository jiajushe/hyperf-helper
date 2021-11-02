<?php
declare(strict_types=1);

namespace Jiajushe\HyperfHelper\Helper;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Jiajushe\HyperfHelper\Helper\ResponseHelper;
use Psr\Container\ContainerInterface;

/**
 * 自定义控制器抽象类
 * @author yun 2021-10-18 23:40:26
 */
abstract class BaseController
{
    /**
     * @Inject
     */
    protected ContainerInterface $container;

    /**
     * @Inject
     */
    protected RequestInterface $request;

    /**
     * @Inject
     */
    protected ResponseInterface $response;

    /**
     * @Inject()
     */
    protected ResponseHelper $responseHelper;

    /**
     * json格式返回
     * @param mixed $res
     * @return \Psr\Http\Message\ResponseInterface
     * @author yun 2021-10-12 14:26:06
     */
    protected function json($res = ''): \Psr\Http\Message\ResponseInterface
    {
        $res = $this->responseHelper->normal($res);
        return $this->response->json($res);
    }

}