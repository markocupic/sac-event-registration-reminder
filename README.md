![Alt text](docs/logo.png?raw=true "logo")


# SAC Event Registration Reminder

Folgende Idee:
Es wird ein Reminder-Tool benötigt, welches Tourenleitende und Bergführer/innen per E-Mail daran erinnert,
  dass diese noch unbearbeitete Registrierungen (nicht angenommen/abgelehnt und nicht auf Warteliste gesetzt) für Touren/Kurse haben,
  die sie seit mehr als 7 Tagen (einstellbar) nicht bearbeitet haben.

Ein paar Punkte dazu als Input:
- Reminder E-Mail wird nach einer definierten Zeitperiode (z.B. jede Woche) an Organisator geschickt
- E-Mail nur senden sobald min. 1 Teilnehmer/in nicht bearbeitet und folgende Bedingung:
- Aktuelle Zeit des zyklischen Check > Anmeldezeitpunkt + Zeitperiode
- Check an einem definierten Zeitpunkt (z.B. mittwochs um 6:00 Uhr)
- Somit E-Mail zwischen min. 1 und max. 2 Wochen nach TN Anmeldezeitpunkt
- Nur eine E-Mail pro Zeitperiode
- Konfiguration von Admins:
- Globales Ein-/Ausschalten (Auf Ebene Symfony Friendly Configuration)
- Zeitperiode einstellbar (Auf Ebene Kalender)
- Zeitpunkt definierbar (Cronjob auf Webhosting und Annotation in der Cron-Klasse)
- Bemerkung: Benutzer kann es nicht individuell ausschalten!
- Realisierbar z.B. mit Cron Job


# E-Mail-Beispiel "Reminder für unbearbeitete Event-Anmeldungen":

Hallo Martin

Du hast für einen oder mehrere Events den Status
  der folgenden Teilnehmenden seit über 7 Tagen noch nicht bearbeitet:

**Tour XYZ**:

Teilnehmer/in Heidi Muster (seit 8 Tagen)

Diese neuen Anmeldungen in der vergangenen Woche sind ebenfalls hängig:

Teilnehmer/in Fritz Huber (seit 3 Tagen)

Teilnehmer/in Nadja Meier (seit 2 Tagen)

-------------------------------------------------------------

**Kurs XYZ**:

Teilnehmer/in Carla Muster (seit 8 Tagen)

Diese neuen Anmeldungen in der vergangenen Woche sind ebenfalls hängig:

Teilnehmer/in Benno Huber (seit 3 Tagen)

Teilnehmer/in Lisi Meier (seit 2 Tagen)


Bitte nimm die Teilnehmende an, lehne sie ab oder setze sie auf die Warteliste.

## Konfiguration

```yaml
# config/config.yml
sac_evt_reg_reminder:
  disable: false
  sid: 'dasrwuefhsd567ewdsf3265667zte'
  allow_web_scope: false
  notification_limit_per_request: 100
  default_locale: 'de'
  cron_scheduel: '15 23 * * *' # every day at 11:15 pm
```
