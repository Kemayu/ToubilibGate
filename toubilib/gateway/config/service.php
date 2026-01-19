<?php

use Psr\Container\ContainerInterface;
use GuzzleHttp\Client;
use Psr\Http\Client\ClientInterface;

return [

    // Client pour le service RDV
    'client.rdv' => function (ContainerInterface $c) {
        $settings = $c->get("settings");
        return new Client([
            'base_uri' => $settings["api-rdv"]["base_uri"],
            'timeout'  => $settings["api-rdv"]["timeout"],
        ]);
    },

    // Client pour le service praticiens
    'client.praticiens' => function (ContainerInterface $c) {
        $settings = $c->get("settings");
        return new Client([
            'base_uri' => $settings["api-praticiens"]["base_uri"],
            'timeout'  => $settings["api-praticiens"]["timeout"],
        ]);
    },

    // Client pour le service authentification
    'client.auth' => function (ContainerInterface $c) {
        $settings = $c->get("settings");
        return new Client([
            'base_uri' => $settings["api-auth"]["base_uri"],
            'timeout'  => $settings["api-auth"]["timeout"],
        ]);
    },

    // Client par défaut (fallback)
    ClientInterface::class => function (ContainerInterface $c) {
        // Retourne le client auth comme client par défaut
        return $c->get('client.auth');
    },

];
