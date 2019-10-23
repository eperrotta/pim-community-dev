<?php

declare(strict_types=1);

namespace Akeneo\Apps\Infrastructure\InternalApi\Controller;

use Akeneo\Apps\Application\Command\CreateAppCommand;
use Akeneo\Apps\Application\Command\CreateAppHandler;
use Akeneo\Apps\Application\Query\FetchAppsHandler;
use Akeneo\Apps\Domain\Exception\ConstraintViolationListException;
use Akeneo\Apps\Domain\Model\Read\App;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @author Romain Monceau <romain@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class AppController
{
    /** @var CreateAppHandler */
    private $createAppHandler;

    /** @var FetchAppsHandler */
    private $fetchAppsHandler;

    public function __construct(CreateAppHandler $createAppHandler, FetchAppsHandler $fetchAppsHandler)
    {
        $this->createAppHandler = $createAppHandler;
        $this->fetchAppsHandler = $fetchAppsHandler;
    }

    public function list()
    {
        $apps = $this->fetchAppsHandler->query();

        return new JsonResponse(
            array_map(function (App $app) {
                return $app->normalize();
            }, $apps)
        );
    }

    public function create(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        // TODO: Valid JSON format

        $command = new CreateAppCommand($data['code'], $data['label'], $data['flowType']);

        try {
            $this->createAppHandler->handle($command);
        } catch (ConstraintViolationListException $e) {
            $errorList = $this->buildViolationResponse($e->getConstraintViolationList());

            return new JsonResponse(
                json_encode(['errors' => $errorList, 'message' => $e->getMessage()]),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return new JsonResponse(json_encode(['message' => $e->getMessage()]), Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function buildViolationResponse(ConstraintViolationListInterface $constraintViolationList): array
    {
        $errors = [];
        foreach ($constraintViolationList as $constraintViolation) {
            $errors[] = [
                'name' => $constraintViolation->getPropertyPath(),
                'reason' => $constraintViolation->getMessage(),
            ];
        }

        return $errors;
    }
}
