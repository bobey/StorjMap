<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client;

$console = new Application('StorjMap console', 'n/a');
$console->setDispatcher($app['dispatcher']);
$console
    ->register('storjmap:cache-contacts')
    ->setDefinition([])
    ->setDescription('Store every storj contact along with IP geolocation in a cache file')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {

        $contactsCachePath = __DIR__.'/../var/contacts/contacts.json';
        $existingContacts = [];
        if (file_exists($contactsCachePath)) {
            foreach (json_decode(file_get_contents($contactsCachePath), true) as $existingContact) {
                $existingContact['lastUpdate'] = new DateTime();
                $existingContacts[$existingContact['nodeID']] = $existingContact;
            }
        }

        $client = new Client();

        $hasAnotherPage = true;
        $page = 1;
        $contacts = [];
        while ($hasAnotherPage && $page < 600) {
            $output->writeln(sprintf('Retrieve contact data from API (page: %d)', $page));
            $response = $client->get(sprintf('https://api.storj.io/contacts?connected=true&page=%d', $page++));

            $contacts = array_merge($contacts, json_decode($response->getBody(), true));
        }

        $cacheTime = new DateTime();
        $cacheTime->modify('-1 day');

        $contacts = array_map(function ($contact) use ($output, $client, $existingContacts, $cacheTime) {

            if (isset($existingContacts[$contact['nodeID']])
                && isset($existingContacts[$contact['nodeID']]['lastUpdate'])
                && $existingContacts[$contact['nodeID']]['lastUpdate'] > $cacheTime) {

                $output->writeln('Retrieve geolocation from cache');

                return $existingContacts[$contact['nodeID']];
            }

            $ipAddress = $contact['address'];
            $output->writeln(sprintf('Retrieve geolocation data for address: %s', $ipAddress));

            $response = $client->get('http://ip-api.com/json/'.$ipAddress);

            $geolocation = json_decode($response->getBody(), true);

            $contact['lat'] = $geolocation['lat'];
            $contact['lng'] = $geolocation['lon'];
            $contact['lastUpdate'] = new DateTime();

            usleep(500*1000); // to avoid API ban

            return $contact;

        }, $contacts);

        $contacts = array_filter($contacts, function($contact) {
            return isset($contact['lat']) && isset($contact['lng']);
        });

        file_put_contents($contactsCachePath, json_encode(array_values($contacts)));
    })
;

return $console;
