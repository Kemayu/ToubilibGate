# Micro Toubilib — Résumé et fonctionnement

Ce document récapitule le fonctionnement du projet « Toubilib » découpé en microservices, ainsi que les exercices réalisés (TD 2.1, 2.2 et 2.3).

**But** : architecture microservices pour gestion des praticiens, rendez-vous et authentification, avec une API Gateway, communication via RabbitMQ pour notifications et un service mail qui utilise MailCatcher en développement.

**Structure principale**
- **Gateway** : point d'entrée HTTP (Slim + Guzzle) qui proxy/route les requêtes vers les microservices (app-praticiens, app-rdv, app-auth).
- **app-praticiens** : microservice REST pour la gestion des praticiens (sa propre BDD).
- **app-rdv** : microservice REST pour la gestion des rendez-vous (sa propre BDD). Contient la logique métier, vérifications et publication d'événements RDV.
- **app-auth** : microservice d'authentification (inscription, signin, refresh, validation de token).
- **app-mailer** : consommateur RabbitMQ qui transforme événements en e-mails (Symfony Mailer) et envoie vers MailCatcher.
- **RabbitMQ** : courtier AMQP pour router les événements queues `mail_praticiens`, `mail_patients`.
- **MailCatcher** : serveur SMTP de test utilisé en dev (capture des e-mails envoyés).

**Flux de notification (création / annulation de RDV)**
- Le service `app-rdv` publie des événements via RabbitMQ (ex: `rdv.created.praticien`, `rdv.created.patient`).
- Les messages contiennent : `rdv_id`, `praticien_id`, `patient_id`, `praticien_email`, `patient_email`, `event_type`, `date_heure_debut/fin`, etc.
- `app-mailer` consomme les messages des queues appropriées, construit l'email et l'envoie via Symfony Mailer vers MailCatcher.

**Problème détecté et correction appliquée**
- Observé : praticien et patient recevaient parfois le même destinataire (email du patient envoyé aussi au praticien).
- Cause : ordre de sélection de l'adresse `to` dans le consumer mail prenait `patient_email` avant `praticien_email`.
- Correction appliquée : la priorité de sélection dépend désormais de `recipient_type`. Le fichier modifié : [toubilib/app-mailer/mailer_consumer.php](toubilib/app-mailer/mailer_consumer.php).

**Composants techniques et points d'attention**
- Gateway : utilise `guzzle` ; le client Guzzle est injecté via le conteneur.
- Auth : la gateway possède un middleware d'authentification qui délègue la validation de token à `app-auth` (`/tokens/validate`).
- Autorisation : contrôles dans `app-rdv` (middleware interne) utilisent les token décodé.
- Event publisher : implémentation RabbitMQ (`toubilib/app-rdv/src/infrastructure/messaging/RabbitMQEventPublisher.php`).
- Mailer adapter : `SymfonyMailerAdapter` (port/implémentation) pour découpler l'envoi.

**Démarrage local / tests rapides**
- Lancer la stack Docker (compose principal) :

  `docker-compose up --build`


**Tester MailCatcher rapidement**
- Obtenez un token via signin :

  ```bash
  curl -X POST http://localhost:6081/auth/signin \
    -H "Content-Type: application/json" \
    -d '{"email":"Denis.Teixeira@hotmail.fr","password":"password123"}'
  ```

- Créez ensuite un RDV en remplaçant `YOUR_ACCESS_TOKEN` par l'access token obtenu :

  ```bash
  curl -X POST http://localhost:6081/rdvs \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
    -d '{
      "patient_id": "d975aca7-50c5-3d16-b211-cf7d302cba50",
      "praticien_id": "4305f5e9-be5a-4ccf-8792-7e07d7017363",
      "date_heure_debut": "2026-02-03 10:30:00",
      "duree": 30,
      "motif_visite": "IRM"
    }'
  ```

- Ouvrez l'interface MailCatcher à `http://localhost:1080` et vérifiez que deux e-mails distincts (praticien et patient) ont été capturés.

  



