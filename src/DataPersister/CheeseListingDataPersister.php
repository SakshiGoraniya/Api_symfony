<?php

namespace App\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\CheeseListing;
use App\Entity\CheeseNotification;

class CheeseListingDataPersister implements DataPersisterInterface
{
    private $entityManager;
    private $decoratedDataPersister;

    public function __construct(DataPersisterInterface $decoratedDataPersister, EntityManagerInterface $entityManager)
    {
        $this->decoratedDataPersister = $decoratedDataPersister;
        $this->entityManager = $entityManager;
    }

    public function supports($data): bool
    {
        return $data instanceof CheeseListing;
    }

    public function persist($data)
    {
        $originalData = $this->entityManager->getUnitOfWork()->getOriginalEntityData($data);
        $wasAlreadyPublished = ($originalData['isPublished'] ?? false);
        if ($data->getIsPublished() &&  !$wasAlreadyPublished) {
            $notification = new CheeseNotification($data, 'Cheese listing was created!');
            $this->entityManager->persist($notification);
            $this->entityManager->flush();
        }
        return $this->decoratedDataPersister->persist($data);
    }

    public function remove($data)
    {
        return $this->decoratedDataPersister->remove($data);
    }
}
