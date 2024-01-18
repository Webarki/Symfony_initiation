<?php

namespace App\DataFixtures;

use App\Entity\Message;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for($i =1; $i < 50; $i ++){

            $message = new Message();
            $message->setEmail('user@user.com')
            ->setSujet('blablablba')
            ->setContent('blablablablblablabla');
            $manager->persist($message);
        }

        $manager->flush();
    }
}
