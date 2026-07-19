<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReviewControllerTest extends WebTestCase
{
    public function testSixthSubmissionWithinTenMinutesIsRateLimited(): void
    {
        $client = static::createClient();
        static::getContainer()->get('limiter.review_submission')->create('127.0.0.1')->reset();

        for ($i = 1; $i <= 5; ++$i) {
            $client->request('GET', '/reviews/new');
            $client->submitForm('Vélemény beküldése', [
                'review[companyName]' => 'Rate Limit Teszt Kft.',
                'review[rating]' => '4',
                'review[reviewText]' => 'Ez egy automatizált teszt vélemény szövege.',
                'review[authorEmail]' => "ratelimit{$i}@example.com",
            ]);

            self::assertResponseRedirects();
        }

        $client->request('GET', '/reviews/new');
        $client->submitForm('Vélemény beküldése', [
            'review[companyName]' => 'Rate Limit Teszt Kft.',
            'review[rating]' => '4',
            'review[reviewText]' => 'Ez a hatodik próbálkozás, ennek már el kell buknia.',
            'review[authorEmail]' => 'ratelimit6@example.com',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-danger', 'Túl sok véleményt');
    }
}
