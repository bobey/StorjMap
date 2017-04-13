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
        $client = new Client();

        $hasAnotherPage = true;
        $page = 1;
        $contacts = [];
        while ($hasAnotherPage && $page < 500) {
            $output->writeln(sprintf('Retrieve contact data from API (page: %d)', $page));
            $response = $client->get(sprintf('https://api.storj.io/contacts?connected=true&page=%d', $page++));

            $contacts = array_merge($contacts, json_decode($response->getBody(), true));
        }

        $contacts = array_map(function ($contact) use ($output, $client) {

            $ipAddress = $contact['address'];
            $output->writeln(sprintf('Retrieve geolocation data for address: %s', $ipAddress));

            $response = $client->get('http://ip-api.com/json/'.$ipAddress);

            $geolocation = json_decode($response->getBody(), true);

            $contact['lat'] = $geolocation['lat'];
            $contact['lng'] = $geolocation['lon'];

            usleep(500*1000); // to avoid API ban

            return $contact;

        }, $contacts);

        file_put_contents(__DIR__.'/../var/contacts/contacts.json', json_encode($contacts));
    })
;

return $console;
