<?php
declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Feed;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
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
