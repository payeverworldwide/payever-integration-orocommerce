<?php

namespace Payever\Bundle\PaymentBundle\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Payever\Bundle\PaymentBundle\Service\JobHandler\PaymentCommandJobHandler;
use Payever\Sdk\Payments\ThirdPartyPluginsApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PluginCommandController extends AbstractController
{
    /**
     * @Route("/execute_commands", name="payever_payment_execute_commands")
     */
    public function executeCommands(Request $request): JsonResponse
    {
        $result = [];
        try {
            $token = (string) $request->query->get('token', '');
            $businessUuid = $this->getConfigManager()->get('payever_payment.business_uuid');

            $isValidToken = $this->getThirdPartyPluginsApiClient()
                ->validateToken($businessUuid, $token);
            if ($isValidToken) {
                $this->getExecutePluginCommandsTask()->run();
                $result[] = [
                    'message' => 'The commands have been executed successfully',
                ];

                return $this->json($result);
            } else {
                throw new \Exception('Invalid token');
            }
        } catch (\Exception $e) {
            $result[] = [
                'message' => 'The commands haven\'t been executed successfully: ' . $e->getMessage(),
            ];
        }

        return $this->json($result);
    }

    private function getConfigManager(): ConfigManager
    {
        return $this->container->get(ConfigManager::class);
    }

    private function getThirdPartyPluginsApiClient(): ThirdPartyPluginsApiClient
    {
        return $this->container->get(ThirdPartyPluginsApiClient::class);
    }

    private function getExecutePluginCommandsTask(): PaymentCommandJobHandler
    {
        return $this->container->get(PaymentCommandJobHandler::class);
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ConfigManager::class,
                ThirdPartyPluginsApiClient::class,
                PaymentCommandJobHandler::class,
            ]
        );
    }
}
