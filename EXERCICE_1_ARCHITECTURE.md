# Exercice 1 : Architecture

## 1. Schéma des services

```
┌─────────────────────────────────┐
│  PRODUCTEUR (app.rdv)          │
│  - CreateRdvAction              │
│  - ServiceRendezVous            │
│  - EventPublisher (interface)   │
│  - RabbitMQEventPublisher       │
└────────────┬────────────────────┘
             │ AMQP
             ↓
┌─────────────────────────────────┐
│  COURTIER (RabbitMQ)           │
│                                 │
│  Exchange: rdv_events (TOPIC)   │
│    - rdv.created.praticien      │
│    - rdv.created.patient        │
│    - rdv.cancelled.praticien    │
│    - rdv.cancelled.patient      │
│                                 │
│  Queues:                        │
│    - mail_praticiens            │
│    - mail_patients              │
└────────────┬────────────────────┘
             │ AMQP
             ↓
┌─────────────────────────────────┐
│  CONSOMMATEURS                  │
│  - mail-worker → MailCatcher    │
│  (Futur: sms-worker, push...)   │
└─────────────────────────────────┘
```

## 2. Configuration RabbitMQ

### Exchange
- **Type** : TOPIC
- **Nom** : `rdv_events`
- **Raison** : Permet le pattern matching (`rdv.*.patient`) pour l'extensibilité

### Queues
- `mail_praticiens` (binding: `rdv.*.praticien`)
- `mail_patients` (binding: `rdv.*.patient`)

### Routing Keys
- `rdv.created.praticien` / `rdv.created.patient`
- `rdv.cancelled.praticien` / `rdv.cancelled.patient`

**Évolution future** : Ajout facile de `sms_*`, `push_*` avec même pattern

## 3. Producteur d'événements

### Composant responsable
**`ServiceRendezVous`** (Use Case) publie les événements après :
- Création RDV réussie → 2 messages (praticien + patient)
- Annulation RDV → 2 messages (praticien + patient)

### Architecture découplée

**Interface (garantit l'évolutivité)** :
```php
interface EventPublisherInterface {
    public function publish(string $eventName, array $data): void;
}
```

**Implémentation RabbitMQ** :
```php
class RabbitMQEventPublisher implements EventPublisherInterface {
    public function publish(string $eventName, array $data): void {
        // Connexion AMQP + publication
    }
}
```

**Usage** :
```php
class ServiceRendezVous {
    public function __construct(
        RdvRepositoryInterface $rdvRepository,
        EventPublisherInterface $eventPublisher // ← Interface
    ) {}
    
    public function creerRendezVous($dto): array {
        $rdvId = $this->rdvRepository->save($dto);
        
        $this->eventPublisher->publish('rdv.created.praticien', [...]);
        $this->eventPublisher->publish('rdv.created.patient', [...]);
    }
}
```

### Changer de protocole/serveur

1. Créer la classe du nouveau protocole qui implémente EventPublisherInterface
2. Modifier `config/service.php` :
```php
EventPublisherInterface::class => fn() => new NewClasseProtocol(...)
```

