<?php

declare(strict_types=1);

namespace OCA\Lists\Controller;

use OCA\Lists\Exception\ForbiddenException;
use OCA\Lists\Exception\NotFoundException;
use OCA\Lists\Service\ListService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class ListController extends OCSController {
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly ListService $service,
        private readonly string $userId,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired]
    public function index(): DataResponse {
        $lists = $this->service->findAll($this->userId);
        return new DataResponse(array_map(fn($l) => $l->jsonSerialize(), $lists));
    }

    #[NoAdminRequired]
    public function create(string $name, ?string $description = null, ?string $icon = null, int $hasQuantities = 0): DataResponse {
        if (trim($name) === '') {
            return new DataResponse(['message' => 'Name is required'], Http::STATUS_BAD_REQUEST);
        }
        if (mb_strlen($name) > 255) {
            return new DataResponse(['message' => 'Name too long (max 255)'], Http::STATUS_BAD_REQUEST);
        }
        $entity = $this->service->create($this->userId, $name, $description, $icon, $hasQuantities);
        return new DataResponse($entity->jsonSerialize(), Http::STATUS_CREATED);
    }

    #[NoAdminRequired]
    public function show(int $id): DataResponse {
        try {
            $entity = $this->service->find($id, $this->userId);
            return new DataResponse($entity->jsonSerialize());
        } catch (NotFoundException) {
            return new DataResponse(['message' => 'Not found'], Http::STATUS_NOT_FOUND);
        }
    }

    #[NoAdminRequired]
    public function update(int $id, ?string $name = null, ?string $description = null, ?string $icon = null, mixed $hasQuantities = false): DataResponse {
        $resolvedHasQ = false;
        if ($hasQuantities !== false) {
            $resolvedHasQ = ($hasQuantities) ? 1 : 0;
        }
        try {
            $entity = $this->service->update($id, $this->userId, $name, $description, $icon, $resolvedHasQ);
            return new DataResponse($entity->jsonSerialize());
        } catch (NotFoundException) {
            return new DataResponse(['message' => 'Not found'], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException) {
            return new DataResponse(['message' => 'Forbidden'], Http::STATUS_FORBIDDEN);
        }
    }

    #[NoAdminRequired]
    public function destroy(int $id): DataResponse {
        try {
            $this->service->delete($id, $this->userId);
            return new DataResponse();
        } catch (NotFoundException) {
            return new DataResponse(['message' => 'Not found'], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException) {
            return new DataResponse(['message' => 'Forbidden'], Http::STATUS_FORBIDDEN);
        }
    }
}
