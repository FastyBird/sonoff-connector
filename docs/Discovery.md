# Devices Discovery

The [Sonoff](https://sonoff.tech) connector includes a built-in feature for automatic device discovery. This feature can be triggered manually
through a console command or from the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface.

## Manual Console Command

To manually trigger device discovery, use the following command:

```shell
php bin/fb-console fb:sonoff-connector:discover
```

> [!NOTE]
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

The console will prompt for confirmation before proceeding with the discovery process.

```
Sonoff connector - discovery
============================

 ! [NOTE] This action will run connector devices discovery.

 Would you like to continue? (yes/no) [no]:
 > y
```

You will then be prompted to select the connector to use for the discovery process.

```
 Would you like to discover devices with "My Sonoff" connector (yes/no) [no]:
 > y
```

The connector will then begin searching for new [Sonoff](https://sonoff.tech) devices, which may take a few minutes to complete. Once finished,
a list of found devices will be displayed.

```
 [INFO] Starting Sonoff connector discovery...

[============================] 100% 1 min, 44 secs/1 min, 44 secs


 [INFO] Stopping Sonoff connector discovery...



 [INFO] Found 2 new devices


+---+--------------------------------------+----------------+---------------+--------------+
| # | ID                                   | Name           | Model         | IP address   |
+---+--------------------------------------+----------------+---------------+--------------+
| 1 | 89b1d985-0183-4c05-8d28-69f4acf4128e | MyDevice09889e | SNSW-001P16EU | N/A          |
| 2 | 8f377380-860f-4ac9-a4de-4be73e5ef59a | MyDevice04690b | SNSW-001P16EU | 10.10.10.126 |
+---+--------------------------------------+----------------+---------------+--------------+


 [OK] Devices discovery was successfully finished
```

Now that all newly discovered devices have been found, they are available in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) system and can be utilized.
