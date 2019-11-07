# Flatlogin - Template
Dies ist eine Vorlage mit einem Login-Bereich ohne Datenbank. Aufgebaut auf dem [Slim Framework](https://www.slimframework.com/).
Ich gehe davon aus das du ein Entwickler bist und dies als Vorlage für deine Webapp benutzen möchtest.

## Voraussetzungen
* Composer
* PHP 5.5.0 or newer
* Node

## Intstallation
* Lade dir Flatlogin herunter und entpacke dies in ein beliebiges Verzeichniss auf deinem Webserver.
* Öffne ein Terminal und führe `composer install` aus.

## Design / Theme
Im Ordner `themes/` kann ein eigenes Theme erstellt werden. 
Das `default` Theme kann als Vorlage kopiert werden und darauf aufgebaut werden.

## Configuration
### settings.yaml
```
system:
  title: 'Flatlogin'
  theme: 'default'
  register: true
```
**title** Dies ist der Seitentitel der im Browser angezeigt wird. \
**theme** Theme welches verwendet wird. \
**register** Hier wird festgelegt ob sich der Gast registrierne kann.

```
mail:
  from: 'mail@mail.ch'
  from_name: FIRMA
  host: ''
  pass: ''
  port: 465
  smtp:
    auth: 1
    secure: ssl
  type: sendmail
  user: ''
```
**from** Absender Adresse \
**from_name** Absender Name \
**host** Host des Anbieters \
**pass** Password des Accounts \
**port** SMTP Port \
**smtp.auth** Festlegen ob der Account eine Authentifizierung erfordert \
**smtp.secure** Festlegen welche Sicherheit erforderlich ist \
**type** Auswählen ob per SMTP oder per Sendmail versendet wird. \
**user** Username des Accounts

```
twig:
  cache:
    enabled: false
```
**enabled** Einschalten des caches.

```
secure:
  ip:
    enabled: false
    allowed:
      - '::1'
  ssl:
    force: false
  captcha:
    enabled: false
    version: '2'
    url: https://www.google.com/recaptcha/api/siteverify
    key:
      private: 'PRIVATEKEY'
      public: 'PUBLICKEY'
```
**ip.enabled** Einschalten der IP sperre
**ip.allowed** Definieren welche IPs auf die Seite zugreifen können
**ssl.force** https erzwingen
