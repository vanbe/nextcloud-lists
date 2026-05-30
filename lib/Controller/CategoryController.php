<?php

declare(strict_types=1);

namespace OCA\Lists\Controller;

use OCA\Lists\Exception\ForbiddenException;
use OCA\Lists\Exception\NotFoundException;
use OCA\Lists\Service\CategoryService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class CategoryController extends OCSController {
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly CategoryService $service,
        private readonly string $userId,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired]
    public function index(int $listId): DataResponse {
        try {
            $categories = $this->service->findAll($listId, $this->userId);
            return new DataResponse(array_map(fn($c) => $c->jsonSerialize(), $categories));
        } catch (NotFoundException) {
            return new DataResponse(['message' => 'List not found'], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException) {
            return new DataResponse(['message' => 'Forbidden'], Http::STATUS_FORBIDDEN);
        }
    }

    #[NoAdminRequired]
    public function create(int $listId, string $name, string $icon = '', ?int $position = null): DataResponse {
        if (trim($name) === '') {
            return new DataResponse(['message' => 'Name is required'], Http::STATUS_BAD_REQUEST);
        }
        if (mb_strlen($name) > 255) {
            return new DataResponse(['message' => 'Name too long (max 255)'], Http::STATUS_BAD_REQUEST);
        }
        if (mb_strlen($icon) > 32) {
            return new DataResponse(['message' => 'Icon too long (max 32)'], Http::STATUS_BAD_REQUEST);
        }
        try {
            $entity = $this->service->create($listId, $this->userId, trim($name), $icon, $position);
            return new DataResponse($entity->jsonSerialize(), Http::STATUS_CREATED);
        } catch (NotFoundException) {
            return new DataResponse(['message' => 'List not found'], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException) {
            return new DataResponse(['message' => 'Forbidden'], Http::STATUS_FORBIDDEN);
        }
    }

    #[NoAdminRequired]
    public function update(int $listId, int $id, ?string $name = null, ?string $icon = null): DataResponse {
        if ($name !== null) {
            if (trim($name) === '') {
                return new DataResponse(['message' => 'Name is required'], Http::STATUS_BAD_REQUEST);
            }
            if (mb_strlen($name) > 255) {
                return new DataResponse(['message' => 'Name too long (max 255)'], Http::STATUS_BAD_REQUEST);
            }
            $name = trim($name);
        }
        if ($icon !== null && mb_strlen($icon) > 32) {
            return new DataResponse(['message' => 'Icon too long (max 32)'], Http::STATUS_BAD_REQUEST);
        }
        try {
            $entity = $this->service->update($id, $listId, $this->userId, $name, $icon);
            return new DataResponse($entity->jsonSerialize());
        } catch (NotFoundException) {
            return new DataResponse(['message' => 'Not found'], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException) {
            return new DataResponse(['message' => 'Forbidden'], Http::STATUS_FORBIDDEN);
        }
    }

    #[NoAdminRequired]
    public function reorder(int $listId, array $order = []): DataResponse {
        try {
            $this->service->reorder($listId, $this->userId, $order);
            return new DataResponse();
        } catch (NotFoundException) {
            return new DataResponse(['message' => 'List not found'], Http::STATUS_NOT_FOUND);
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
