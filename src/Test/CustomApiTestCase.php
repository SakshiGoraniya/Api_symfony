<?php

namespace App\Test;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;
use App\Entity\User;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Proxy;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CustomApiTestCase extends ApiTestCase
{
    use Factories;
    use ResetDatabase;
    protected function createUser(string $email, string $password): User
    {
        $factory = new PasswordHasherFactory(['auto' => ['algorithm' => 'bcrypt'], 'memory-hard' => ['algorithm' => 'sodium'],]);
        $passwordHasher = $factory->getPasswordHasher('auto');
        $hashedPassword = $passwordHasher->hash($password);
        $container = static::getContainer();
        $user = new User();
        $user->setEmail($email);
        $user->setUsername(substr($email, 0, strpos($email, '@')));
        $user->setPassword($hashedPassword);

        $em = $container->get('doctrine')->getManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    protected function logIn(Client $client, $userOrEmail, string $password = UserFactory::DEFAULT_PASSWORD)
    {
        if ($userOrEmail instanceof User || $userOrEmail instanceof Proxy) {
            $email = $userOrEmail->getEmail();
        } elseif (is_string($userOrEmail)) {
            $email = $userOrEmail;
        } else {
            throw new \InvalidArgumentException(sprintf(
                'Argument 2 to "%s" should be a User, Foundry Proxy or string email, "%s" given',
                __METHOD__,
                is_object($userOrEmail) ? get_class($userOrEmail) : gettype($userOrEmail)
            ));
        }

        $client->request('POST', '/login', [
            'json' => [
                'email' => $email,
                'password' => $password
            ],
        ]);
        $this->assertResponseStatusCodeSame(204);
    }
    protected function createUserAndLogIn(Client $client, string $email, string $password): User
    {
        $user = $this->createUser($email, $password);
        $this->logIn($client, $email, $password);
        return $user;
    }
    protected function getEntityManager(): EntityManagerInterface
    {
        $container = static::getContainer();
        return $container->get('doctrine')->getManager();
    }
}
