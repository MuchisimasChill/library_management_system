<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class DevUserController extends AbstractController
{
    #[Route('/dev/create-user', name: 'dev_create_user')]
    public function createUser(
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = new User();
        $user->setName('Admin');
        $user->setSurname('User');
        $user->setEmail('admin@example.com');
        $user->setType(UserType::LIBRARIAN);
        $user->setPassword($hasher->hashPassword($user, 'Qwer123!'));

        $em->persist($user);
        $em->flush();

        return new Response('User created!');
    }
}
