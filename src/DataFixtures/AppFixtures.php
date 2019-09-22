<?php
declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Feed;
use App\Entity\RssUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $user = new RssUser();
        $user
            ->setEmail('admin@admin.com')
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword($this->passwordEncoder->encodePassword($user, 'admin'));

        $manager->persist($user);

        $feeds = [
            'http://feeds.arstechnica.com/arstechnica/index',
            'http://rss.slashdot.org/Slashdot/slashdotMainatom',
            'http://feeds.bbci.co.uk/news/rss.xml',
            'https://www.sciencedaily.com/rss/top.xml',
            'https://www.sciencedaily.com/rss/space_time.xml',
            'https://www.sciencedaily.com/rss/matter_energy.xml',
            'https://www.sciencedaily.com/rss/computers_math.xml',
            'https://www.universetoday.com/feed/',
            'https://www.phoronix.com/rss.php',
            'http://www.bungie.net/News/NewsRss.ashx',
        ];

        foreach ($feeds as $feed) {
            $manager->persist((new Feed())->setFeedUrl($feed));
        }

        $manager->flush();
    }
}
