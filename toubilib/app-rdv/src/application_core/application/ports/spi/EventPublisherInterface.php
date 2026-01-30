<?php
declare(strict_types=1);

namespace toubilib\core\application\ports\spi;

/**
 * Interface pour la publication d'événements
 * Permet de découpler le code métier du système de messaging
 */
interface EventPublisherInterface
{
    /**
     * Publie un événement dans le système de messaging
     * 
     * @param string $eventName Nom de l'événement (routing key, ex: "rdv.created.patient")
     * @param array $data Données de l'événement à transmettre
     * @return void
     */
    public function publish(string $eventName, array $data): void;
}
