<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Agent;

class AgentFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $osName_list = ['Ubuntu', 'CentOS', 'Debian', 'Fedora', 'Windows', 'Windows Server', 'Red Hat Enterprise Linux', 'SUSE Linux Enterprise Server', 'Arch Linux', 'Alpine Linux', 'FreeBSD'];
        $osVersion_list = ['18.04', '20.04', '7', '8', '10', '2016', '2019', '8.3', '2021.03', '3.14', '12.2', '13.0'];
        for ($i = 0; $i < 1000; $i++) {
            $agent = new Agent();

            $agent->setHostname('MOCK-'.strtoupper(bin2hex(random_bytes(4))));
            $agent->setOsName($osName_list[array_rand($osName_list)]);
            $agent->setOsVersion($osVersion_list[array_rand($osVersion_list)]);
            $manager->persist($agent);
        }

        $manager->flush();
    }
}
