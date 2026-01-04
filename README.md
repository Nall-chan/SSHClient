[![SDK](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Module Version](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FNall-chan%2FSSHClient%2Frefs%2Fheads%2Fmaster%2Flibrary.json&query=%24.version&label=Modul%20Version&color=blue)](https://community.symcon.de/t/modul-ssh-client/130090)
[![Symcon Version](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FNall-chan%2FSSHClient%2Frefs%2Fheads%2Fmaster%2Flibrary.json&query=%24.compatibility.version&suffix=%3E&label=Symcon%20Version&color=green)](https://www.symcon.de/de/service/dokumentation/installation/migrationen/v80-v81-q3-2025/)  
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Check Style](https://github.com/Nall-chan/SSHClient/workflows/Check%20Style/badge.svg)](https://github.com/Nall-chan/SSHClient/actions)
[![Run Tests](https://github.com/Nall-chan/SSHClient/workflows/Run%20Tests/badge.svg)](https://github.com/Nall-chan/SSHClient/actions)  
[![PayPal.Me](https://img.shields.io/badge/PayPal-Me-lightblue.svg)](#2-spenden) [![Wunschliste](https://img.shields.io/badge/Wunschliste-Amazon-ff69fb.svg)](#2-spenden)

# SSH Client <!-- omit in toc -->

Modul welches PHP-Funktionen für einen SSH Zugriff anbietet.

## Inhaltsverzeichnis <!-- omit in toc -->

- [1. Funktionsumfang](#1-funktionsumfang)
- [2. Voraussetzungen](#2-voraussetzungen)
- [3. Software-Installation](#3-software-installation)
- [4. Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
- [5. Statusvariablen](#5-statusvariablen)
- [6. Visualisierung](#6-visualisierung)
- [7. PHP-Funktionsreferenz](#7-php-funktionsreferenz)
- [8. Aktionen](#8-aktionen)
- [9. Anhang](#9-anhang)
	- [1. Changelog](#1-changelog)
	- [2. Spenden](#2-spenden)
- [10. Lizenz](#10-lizenz)

## 1. Funktionsumfang

- Stellt einen SSH-Client als Instanz  bereit  
- Unterstützt Anmeldung per Passwort als auch per privaten Schlüssel  
- Optionale Validierung des Server möglich

## 2. Voraussetzungen

- Symcon ab Version 8.1

## 3. Software-Installation

Über den 'Module-Store' in IPS das Modul 'SSH-Client' hinzufügen.  
**Bei kommerzieller Nutzung (z.B. als Errichter oder Integrator) wenden Sie sich bitte an den Autor.**  
![Module-Store](imgs/install.png)  

## 4. Einrichten der Instanzen in IP-Symcon

Unter 'Instanz hinzufügen' kann das 'SSH Client'-Modul mithilfe des Schnellfilters gefunden werden.  
[Module](imgs/Module.png)  

- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)  

### Konfigurationsseite  <!-- omit in toc -->

![Config](imgs/Config1.png)  

| Name      | Text               | Beschreibung                                                                                               |
| --------- | ------------------ | ---------------------------------------------------------------------------------------------------------- |
| Address   | Host               | Adresse des SSH Servers                                                                                    |
| Username  | Benutzername       | Benutzername für die Anmeldung                                                                             |
| CheckHost | Validiere Host     | Benutzername für die Anmeldung                                                                             |
| Password  | Passwort           | Passwort für die Anmeldung. Bei Verwendung eines privaten Schlüssel, wird hier das Passphrase eingetragen. |
| KeyFile   | privater Schlüssel | Datei mit privatem Schlüssel                                                                               |

Der Button `Anmeldung prüfen` kann zum überprüfen der Einstellungen verwendet werden.  

### Option: Validiere Host  <!-- omit in toc -->

![Config](imgs/Config2.png)  

Über den Button 'Speichere Schlüssel des Host' kann der öffentliche Schlüssel des Host abgerufen und anschließend in der Instanz abgespeichert werden.  
Anschließend steht als neue konfigurierbare Option die Auswahl 'Validiere Host' zur Verfügung.  
Wird diese Einstellung aktiviert, wird bei jeder Anfrage der Schlüssel überprüft und im Fehlerfall die Verbindung abgebrochen.  

### Option: privater Schlüssel  <!-- omit in toc -->

Mittels eines Datei-Uploads über die Schaltfläche 'privater Schlüssel' kann der Schlüssel für die Anmeldung in Symcon hinterlegt werden.  
Ist der der Schlüssel mit einem Passwort (Passphrase) gesichert, so ist Dieses im Passwort Feld einzutragen.  

## 5. Statusvariablen

Dieses Modul erzeugt keine Statusvariablen.  

## 6. Visualisierung

Dieses Modul ist nicht für die Darstellung in einer Visualisierung geeignet.  

## 7. PHP-Funktionsreferenz

``` php
string|boolean SSHC_Execute(integer $InstanzID, string $Command);
```  

Sendet die in `$Command` übergeben Befehlszeile an den Host und liefert das Ergebnis von stdOut als Rückgabewert.  
Der Rückgabewert kann auch bei erfolgreichen Ausführen eine leere Zeichenkette sein.  
Im Fehlerfall enthält der Rückgabewert `false`.  
Somit ist bei der Prüfung des Rückgabewert eine typensichere Prüfung mit drei Gleichzeichen zu verwenden:  

**Beispiel:**  

```php
$Result = SSHC_Execute(12345, 'ls');
if ( $Result=== false){
    exit ("Fehler bei der Verbindung");
}
echo $Result;
```

---

``` php
string|boolean SSHC_ExecuteEX(integer $InstanzID, array $Command);
```

Sendet die in `$Command` übergeben Befehlszeilen an den Host und liefert **ein** Ergebnis als Rückgabewert.  
Ansonsten gelten die gleichen Hinweise zum Rückgabewert wie bei `SSHC_Execute`.

**Beispiel:**  

```php
$Result = SSHC_ExecuteEx(12345, ['cd /', 'ls -la']);
if ( $Result=== false){
    exit ("Fehler bei der Verbindung");
}
echo $Result;
```

---

``` php
string SSHC_GetLastError(integer $InstanzID);
```

Liefer den Inhalt von stdErr vom letzten Execute Befehl.

**Beispiel:**  

``` php
echo SSHC_GetLastError(12345);
```

## 8. Aktionen

Wenn eine 'SSH Client' Instanz als Ziel einer [`Aktion`](https://www.symcon.de/service/dokumentation/konzepte/automationen/ablaufplaene/aktionen/) ausgewählt wurde, steht folgende Aktion zur Verfügung:  

![Aktionen](imgs/Actions.png)  

- Befehl über SSH an Host senden.  

## 9. Anhang

### 1. Changelog

**Version 1.30:**  

- Release Version für Symcon 8.1  
- Umstellung auf phpseclib3, somit werden auch weitere Schlüssel außer RSA unterstützt.  
  
**Version 1.10:**  

- Release Version für Symcon 7.0  

**Version 1.00:**  

- Release Version für Symcon 6.1  

### 2. Spenden

Die Library ist für die nicht kommerzielle Nutzung kostenlos, Schenkungen als Unterstützung für den Autor werden hier akzeptiert:  

 PayPal:  
[![PayPal.Me](https://img.shields.io/badge/PayPal-Me-lightblue.svg)](https://paypal.me/Nall4chan)  

 Wunschliste:  
[![Wunschliste](https://img.shields.io/badge/Wunschliste-Amazon-ff69fb.svg)](https://www.amazon.de/hz/wishlist/ls/YU4AI9AQT9F?ref_=wl_share)  

## 10. Lizenz

IPS-Modul:  
[CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/)  
