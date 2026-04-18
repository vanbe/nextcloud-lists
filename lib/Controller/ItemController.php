<?php

declare(strict_types=1);

namespace OCA\Lists\Controller;

use OCA\Lists\Exception\ForbiddenException;
use OCA\Lists\Exception\NotFoundException;
use OCA\Lists\Service\ItemService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class ItemController extends OCSController {
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly ItemService $service,
        private readonly string $userId,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired]
    public function index(int $listId): DataResponse {
        try {
            $items = $this->service->findAll($listId, $this->userId);
            return new DataResponse(array_map(fn($i) => $i->jsonSerialize(), $items));
        } catch (NotFoundException) {
            return new DataResponse(['message' => 'List not found'], Http::STATUS_NOT_FOUND);
        }
    }

    #[NoAdminRequired]
    public function suggest(int $listId, string $q = ''): DataResponse {
        if (mb_strlen($q) < 2) {
            return new DataResponse([]);
        }
        try {
            $items = $this->service->suggest($listId, $this->userId, $q);
            return new DataResponse(array_map(fn($i) => $i->jsonSerialize(), $items));
        } catch (NotFoundException) {
            return new DataResponse(['message' => 'List not found'], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException) {
            return new DataResponse(['message' => 'Forbidden'], Http::STATUS_FORBIDDEN);
        }
    }

    #[NoAdminRequired]
    public function create(int $listId, string $title, ?string $description = null): DataResponse {
        if (trim($title) === '') {
            return new DataResponse(['message' => 'Title is required'], Http::STATUS_BAD_REQUEST);
        }
        if (mb_strlen($title) > 255) {
            return new DataResponse(['message' => 'Title too long (max 255)'], Http::STATUS_BAD_REQUEST);
        }
        try {
            $entity = $this->service->create($listId, $this->userId, $title, $description);
            return new DataResponse($entity->jsonSerialize(), Http::STATUS_CREATED);
        } catch (NotFoundException) {
            return new DataResponse(['message' => 'List not found'], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException) {
            return new DataResponse(['message' => 'Forbidden'], Http::STATUS_FORBIDDEN);
        }
    }

    #[NoAdminRequired]
    public function update(int $listId, int $id, ?string $title = null, ?string $description = null): DataResponse {
        try {
            $entity = $this->service->update($id, $listId, $this->userId, $title, $description);
            return new DataResponse($entity->jsonSerialize());
        } catch (NotFoundException) {
            return new DataResponse(['message' => 'Not found'], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException) {
            return new DataResponse(['message' => 'Forbidden'], Http::STATUS_FORBIDDEN);
        }
    }

    #[NoAdminRequired]
    public function toggle(int $listId, int $id): DataResponse {
        try {
            $entity = $this->service->toggle($id, $listId, $this->userId);
            return new DataResponse($entity->jsonSerialize());
        } catch (NotFoundException) {
            return new DataResponse(['message' => 'Not found'], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException) {
            return new DataResponse(['message' => 'Forbidden'], Http::STATUS_FORBIDDEN);
        }
    }

    #[NoAdminRequired]
    public function destroy(int $listId, int $id): DataResponse {
        try {
            $this->service->delete($id, $listId, $this->userId);
            return new DataResponse();
        } catch (NotFoundException) {
            return new DataResponse(['message' => 'Not found'], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException) {
            return new DataResponse(['message' => 'Forbidden'], Http::STATUS_FORBIDDEN);
        }
    }
}
