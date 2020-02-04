<?php
declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Feed;
use App\Repository\RssUserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    /**
     * @var RssUserRepository
     */
    private RssUserRepository $userRepository;

    public function __construct(RssUserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function load(ObjectManager $manager)
    {
        $adminUser = $this->userRepository->makeUser('admin@admin.com', 'admin', true);
        $user      = $this->userRepository->makeUser('non_admin@admin.com', 'non_admin', false);

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

        foreach ([$adminUser, $user] as $user) {
            foreach ($feeds as $feed) {
                $manager->persist((new Feed())
                    ->setFeedUrl($feed)
                    ->setRssUser($user)
                );
            }
        }

        $manager->flush();
    }
}
