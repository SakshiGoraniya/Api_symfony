<?php

namespace App\DataPersister;

use Psr\Log\LoggerInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;

class UserDataPersister implements ContextAwareDataPersisterInterface
{
    private $decoratedDataPersister;
    private $userPasswordHasher;
    private $logger;
    public function __construct(DataPersisterInterface $decoratedDataPersister,UserPasswordHasherInterface $userPasswordHasher,LoggerInterface $logger)
    {
      
        $this->decoratedDataPersister = $decoratedDataPersister;;
        $this->userPasswordHasher= $userPasswordHasher;
        $this->logger = $logger;
    }

    public function supports($data, array $context = []): bool
    {
        return $data instanceof User;
    }
    /**
    * @param User $data
    */
    public function persist($data, array $context = [])
    {
        if (($context['item_operation_name'] ?? null) === 'put') {
            $this->logger->info(sprintf('User "%s" is being updated!', $data->getId()));
        }
        if (!$data->getId()) {
            // take any actions needed for a new user
            // send registration email
            // integrate into some CRM or payment system
            $this->logger->info(sprintf('User %s just registered! Eureka!', $data->getEmail()));
        }
        if ($data->getPlainPassword()) {
            $data->setPassword(
                $this->userPasswordHasher->hashPassword($data, $data->getPlainPassword())
            );
            $data->eraseCredentials();
        }
        return $this->decoratedDataPersister->persist($data);
    }

    public function remove($data, array $context = [])
    {
        $this->decoratedDataPersister->remove($data);
    }
}
