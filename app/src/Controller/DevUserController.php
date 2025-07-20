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
        $librarian = new User();
        $librarian->setName('Admin');
        $librarian->setSurname('User');
        $librarian->setEmail('admin@example.com');
        $librarian->setType(UserType::LIBRARIAN);
        $librarian->setPassword($hasher->hashPassword($librarian, 'Qwer123!'));

        $em->persist($librarian);
        $em->flush();


        $user = new User();
        $user->setName('Test');
        $user->setSurname('tested');
        $user->setEmail('test@example.com');
        $user->setType(UserType::MEMBER);
        $user->setPassword($hasher->hashPassword($user, 'Qwer123!'));

        $em->persist($user);
        $em->flush();

        return new Response('Users created!');
    }
}
