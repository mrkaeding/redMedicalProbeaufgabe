# Bewerberaufgabe

Herzlichen Glückwunsch, Du erhältst heute unsere Coding Challenge! Weiter unten findest Du die Aufgaben-Stellung.

## Kriterien
1. Es werden nur Lösungen gewertet, die mit PHP/Laravel programmiert wurden. Wenn Du über die Aufgabe hinaus Hilfstools 
entwickeln möchtest, kannst Du dafür auch eine andere Sprache / ein anderes Framework verwenden.
2. Für die Aufgabe dürfen nur Libraries verwendet werden, die sich bereits in der `composer.json` befinden.
3. Zeitansatz max. 4 Stunden
4. Abgabe der Lösung erfolgt als Repository (github oder bitbucket). Du kannst einfach ein Repository anlegen, deine 
Lösung pushen und uns den Link zukommen lassen. Bitte achte darauf, dass das Repository alle zur Ausführung benötigten Dateien und Anweisungen enthält.

> Im Repository bitte darauf achten, dass die Commit Struktur nachvollziehbar und die Lösung klar von der Aufgabenstellung getrennt ist.
> Am besten machst Du vor Beginn einen initialen Commit.

## Aufgabe 
Es soll ein API-Service (JSON) erstellt werden, der Bestellungen entgegennehmen kann. Für den API-Service muss keine
Authentifizierung implementiert werden.

Die Bestellungen müssen an einen Drittanbieter Service `RedProviderPortal` weiter gegeben werden. Dieser Service wird
kompiliert zur Verfügung gestellt (siehe "RedProviderPortal Dokumentation" weiter unten). Konfiguration, wie URL,
Secrets, usw., sollen per Environment Variablen gesetzt werden können.

Außerdem soll möglich sein, per Environment Variable zu entscheiden, ob das RedProviderPortal angesprochen wird, oder ein
interner Mock Service. Der Mock Service soll innerhalb der Applikation gehandelt werden und keine Kommunikation nach
außen durchführen.

Die aufgegebenen Bestellungen sollen in der Datenbank persistiert werden und eine UUID als `id` haben. Der
RedProviderPortal nimmt Bestellungen entgegen und verarbeitet diese asynchron. Achtung! Die Verarbeitung der Bestellung 
kann längere Zeit in Anspruch nehmen.
Wird eine Bestellung durchgeführt, hat diese den Status `ordered`. Anschließend verarbeitet der RedProviderPortal die 
Bestellung im Status `processing`. Ist die Bestellung abgeschlossen und bereit, hat sie den Status `completed`.

Die Applikation soll den Status in der Datenbank aktualisieren, sobald der RedProviderPortal die Verarbeitung
abgeschlossen hat.

> Bitte achte drauf, dass Dein Service selbst die Daten speichern und ausgeben soll. Der `RedProviderPortal` Service kann
> die Daten jederzeit löschen. Die Daten sollen aber nicht verloren gehen.

## API-Service Beschreibung

Es werden vier Endpunkte gefordert:

- Auflisten aller Bestellungen
- Erstellen einer Bestellung
- Ausgeben einer bestimmten Bestellung
- Löschen einer Bestellung

Bestellungen, die ausgegeben werden, sollen die Werte `id`, `name`, `type` und `status` liefern.

Beim Auflisten von Bestellungen soll nach dem Namen gefiltert und nach `name` und dem Erstellungsdatum der Bestellung
sortiert werden können.

Für die Erstellung werden die Attribute `name` und `type` benötigt. Es gibt die Bestelltypen (`type`) `connector` und
`vpn_connection`.

Gelöscht werden kann eine Bestellung nur, wenn der Status `completed` ist. Die bestellung soll dann sowohl aus der 
Datenbank als auch im RedProviderPortal gelöscht werden.

# RedProviderPortal Dokumentation

Der RedProviderPortal-Service nimmt über HTTPS-REST Requests auf Port 3000 Bestellungen entgegen und verarbeitet diese.
Das TLS Zertifikat ist in der Datei `ssl_cert.pem`.
Dabei dauert das Verarbeiten der Bestellungen bis zu 30 Minuten.
Über den _Bestellungen ausgeben_-Endpunkt kann man sich den aktuellen Status der Bestellung abholen.

Für den Zugriff muss ein OAuth-ähnliches Access Token abgeholt und bei allen anderen Requests als Bearer-Authorization Header mitgegeben werden.
Zu Testzwecken hat RED den User mit der Client Id `Fun` mit dem Secret `=work@red` angelegt.
Dieser kann zum Testen und Entwickeln benutzt werden.

## Starten des RedProviderPortals

Die kompilierte JavaScript Node Datei ist im Ordner `RedProviderPortal` zu finden.
Auszuführen ist sie mit mind. Node 22. Abhängigkeiten müssen keine installiert werden.

````bash
node ./RedProviderPortal/redproviderportal.js
````

## Verfügbare Endpunkte

### Token

Für jeden Request muss ein Token erstellt werden. Um ein Token zu erstellen, müssen `client_id` und `client_secret`
übergeben werden. Es wird das Token und die Gültigkeit in Sekunden zurückgegeben.

Method: `POST`
Path: `api/v1/token`
Body:

```json
{
  "client_id": "",
  "client_secret": ""
}
```

Response:

```json
{
  "access_token": "",
  "ttl": 60
}
```

### Bestellungen auflisten

Method: `GET`
Path: `api/v1/orders`

Response:

```json
[
  {
    "id": "",
    "type": "",
    "status": ""
  }
]
```

### Bestellung erstellen

Method: `POST`
Path: `api/v1/orders`
Body:

```json
{
  "type": ""
}
```

Response:

```json
{
  "id": "",
  "type": "",
  "status": ""
}
```

### Bestellung ausgeben

Method: `GET`
Path: `api/v1/order/some-id`

Response:

```json
{
  "id": "",
  "type": "",
  "status": ""
}
```

### Bestellung löschen

Method: `DELETE`
Path: `api/v1/order/some-id`
