<?php
/*
 * Copyright 2005 - 2019 Centreon (https://www.centreon.com/)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * For more information : contact@centreon.com
 *
 */
declare(strict_types=1);

namespace Centreon\Application\Controller\Configuration;

use Centreon\Domain\Entity\EntityValidator;
use Centreon\Domain\Proxy\Interfaces\ProxyServiceInterface;
use Centreon\Domain\Proxy\Proxy;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\Exception\ValidationFailedException;
use JMS\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * This class is design to manage all API REST requests concerning the proxy configuration.
 *
 * @package Centreon\Application\Controller\Configuration
 */
class ProxyController extends AbstractFOSRestController
{
    /**
     * @var ProxyServiceInterface
     */
    private $proxyService;

    /**
     * ProxyController constructor.
     *
     * @param ProxyServiceInterface $proxyService
     */
    public function __construct(ProxyServiceInterface $proxyService)
    {
        $this->proxyService = $proxyService;
    }

    /**
     * @IsGranted("ROLE_API_CONFIGURATION", message="You are not authorized to access this resource")
     * @Rest\Get(
     *     "/configuration/proxy",
     *     condition="request.attributes.get('version.is_beta') == true",
     *     name="configuration.proxy.getProxy")
     * @return View
     * @throws \Exception
     */
    public function getProxy(): View
    {
        if (!$this->getUser()->isAdmin() && !$this->isGranted('ROLE_ADMINISTRATION_PARAMETERS_CENTREON_UI')) {
            return $this->view(null, Response::HTTP_FORBIDDEN);
        }
        return $this->view($this->proxyService->getProxy());
    }

    /**
     * @IsGranted("ROLE_API_CONFIGURATION", message="You are not authorized to access this resource")
     * @Rest\Post(
     *     "/configuration/proxy",
     *     condition="request.attributes.get('version.is_beta') == true",
     *     name="configuration.proxy.updateProxy")
     * @param Request $request
     * @param EntityValidator $entityValidator
     * @param SerializerInterface $serializer
     * @return View
     * @throws \Exception
     */
    public function updateProxy(
        Request $request,
        EntityValidator $entityValidator,
        SerializerInterface $serializer
    ): View {
        if (!$this->getUser()->isAdmin() && !$this->isGranted('ROLE_ADMINISTRATION_PARAMETERS_CENTREON_UI')) {
            return $this->view(null, Response::HTTP_FORBIDDEN);
        }
        $data = json_decode((string) $request->getContent(), true);
        if ($data === null) {
            throw new HttpException(json_last_error(), 'Invalid json message received');
        }
        $errors = $entityValidator->validateEntity(
            Proxy::class,
            json_decode((string) $request->getContent(), true),
            ['Default'],
            false // We don't allow extra fields
        );
        if ($errors->count() > 0) {
            throw new ValidationFailedException($errors);
        }
        /**
         * @var Proxy $proxy
         */
        $proxy = $serializer->deserialize(
            (string)$request->getContent(),
            Proxy::class,
            'json'
        );

        $this->proxyService->updateProxy($proxy);
        return $this->view();
    }
}