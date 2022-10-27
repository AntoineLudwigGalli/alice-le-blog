<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker;
use Symfony\Component\String\Slugger\SluggerInterface;

class AppFixtures extends Fixture {
    private $slugger;
    private $encoder;

    public function __construct(UserPasswordHasherInterface $encoder, SluggerInterface $slugger) {
        $this->encoder = $encoder;
        $this->slugger = $slugger;
    }

    public function load(ObjectManager $manager): void {
        $faker = Faker\Factory::create("fr_FR");

        $admin = new User();
        $admin->setEmail('admin@a.a')->setRegistrationDate($faker->dateTimeBetween("17/04/1915", "now"))->setPseudonym('Batman')->setRoles(['ROLE_ADMIN'])
            ->setPassword($this->encoder->hashPassword($admin, 'aA1!aaaa'));

        $manager->persist($admin);

        $listOfAllUsers[] = $admin;


        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setEmail($faker->email)->setRegistrationDate($faker->dateTimeBetween("-2 years", "now"))->setPseudonym($faker->userName)
                ->setPassword($this->encoder->hashPassword($user, 'aA1!aaaa'));

            $manager->persist($user);

            $listOfAllUsers[] = $user;
        }
        /*Création de 200 articles*/
        for ($i = 0; $i < 200; $i++) {
            $article = new Article();
            $article->setTitle($faker->sentence(10))->setContent($faker->paragraph(15))->setPublicationDate($faker->dateTimeBetween('-1 year', 'now'))->setAuthor($admin)
                ->setSlug($this->slugger->slug($article->getTitle())->lower());
            $manager->persist($article);


            $rand = rand(0, 10);

            for ($j = 0; $j < $rand; $j++) {
                $comment = new Comment();
                $comment
                    ->setContent(($faker->paragraph))
                    ->setPublicationDate($faker->dateTimeBetween('-1 year', 'now'))
                    ->setauthor($faker->randomElement($listOfAllUsers))
                    ->setArticle($article);

                $manager->persist($comment);
            }
        }
        $manager->flush();
    }
}

